<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'give_up') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('challenges', $wantsJson);
    }

    $userChallengeId = (int) ($_POST['user_challenge_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $roomId = (int) ($_POST['room_id'] ?? 0);
    $pvpId = (int) ($_POST['pvp_id'] ?? 0);
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    try {
        if (!$userChallengeRepository instanceof UserChallengeRepository) {
            throw new RuntimeException('Challenge progress is not available.');
        }

        if ($userId <= 0 || $userChallengeId <= 0 || $challengeId <= 0) {
            throw new InvalidArgumentException('Invalid challenge progress request.');
        }

        $currentRun = $userChallengeRepository->findById($userChallengeId);
        if (
            $currentRun === null
            || (int) ($currentRun['user_id'] ?? 0) !== $userId
            || (int) ($currentRun['challenge_id'] ?? 0) !== $challengeId
            || trim((string) ($currentRun['completed_at'] ?? '')) !== ''
        ) {
            throw new RuntimeException('No active challenge run was found.');
        }

        $isRoomRun = $roomId > 0;
        $isPvpRun = $pvpId > 0;
        $preservedRoomRun = false;
        $deleted = false;
        $pvpRedirectAtMs = 0;
        $pvpWinnerUserId = 0;
        $pvpDurationSeconds = 0;
        $actualRoomId = (int) ($currentRun['room_id'] ?? 0);
        $actualPvpId = (int) ($currentRun['pvp_id'] ?? 0);

        if ($isRoomRun) {
            if ($actualRoomId !== $roomId) {
                throw new RuntimeException('This room does not match the active challenge run.');
            }

            $userChallengeRepository->assignActiveSeason($userChallengeId, $userId);
            $preservedRoomRun = $userChallengeRepository->hasOwnedOngoing($userChallengeId, $userId);
        } elseif ($isPvpRun) {
            if (!$pvpPlayerRepository instanceof PvpPlayerRepository) {
                throw new RuntimeException('1v1 player records are not available.');
            }

            if ($actualPvpId !== $pvpId) {
                throw new RuntimeException('This 1v1 match does not match the active challenge run.');
            }

            $userChallengeRepository->assignActiveSeason($userChallengeId, $userId);
            $pvpUsers = array_values(array_filter(
                $pvpPlayerRepository->listUserIdsForMatch($pvpId),
                static fn (int $matchUserId): bool => $matchUserId > 0
            ));
            if ($pvpUsers === []) {
                throw new RuntimeException('This 1v1 match is no longer available.');
            }

            $challengeDetails = $challengeRepository instanceof ChallengeRepository
                ? $challengeRepository->findCreatedChallenge($challengeId)
                : null;
            $pvpChallengePoints = max(0, (int) ($challengeDetails['points'] ?? 0));
            $pvpDurationSeconds = $userChallengeRepository->pvpDurationSeconds($pvpId);
            foreach ($pvpUsers as $matchUserId) {
                if ($matchUserId === $userId) {
                    $pvpPlayerRepository->updateStatusForUser($pvpId, $matchUserId, 3);
                    continue;
                }

                $pvpWinnerUserId = $matchUserId;
                $pvpPlayerRepository->updateStatusForUser($pvpId, $matchUserId, 2);

                $winnerRun = $userChallengeRepository->findOngoing($pvpWinnerUserId, $challengeId, 0, $pvpId);
                if ($winnerRun !== null) {
                    $winnerUserChallengeId = (int) ($winnerRun['uc_id'] ?? 0);
                    if ($winnerUserChallengeId > 0) {
                        $userChallengeRepository->markCompleted($winnerUserChallengeId, $pvpWinnerUserId, $challengeId);
                    }
                }

                if ($pvpChallengePoints > 0 && isset($userRepository) && $userRepository instanceof UserRepository) {
                    $userRepository->addPlayerProgressPoints($pvpWinnerUserId, $pvpChallengePoints);
                }
            }

            $deleted = true;
            $pvpRedirectAtMs = ((int) floor(microtime(true) * 1000)) + 900;
        } else {
            if ($actualRoomId > 0 || $actualPvpId > 0) {
                throw new RuntimeException('Challenge run context is missing.');
            }

            $deleted = $userChallengeRepository->deleteOngoingForUser($userChallengeId, $userId);
        }

        if ($deleted || $preservedRoomRun) {
            $challengeName = 'Challenge';

            if ($challengeRepository instanceof ChallengeRepository) {
                $challenge = $challengeRepository->findCreatedChallenge($challengeId);
                if ($challenge !== null) {
                    $challengeName = (string) ($challenge['name'] ?? 'Challenge');
                }
            }

            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'challenge', 'Gave up challenge "' . $challengeName . '".');

            if ($roomId > 0 && isset($roomPlayerRepository) && $roomPlayerRepository instanceof RoomPlayerRepository) {
                $roomPlayerRepository->markGaveUp($userId, $roomId);

                if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
                    try {
                        $pusherService->trigger(
                            'room-' . $roomId,
                            'player-status',
                            [
                                'user_id' => $userId,
                                'status_label' => 'gave_up',
                                'started_at' => date(DATE_ATOM),
                                'completed_at' => '',
                            ]
                        );
                    } catch (Throwable $pusherError) {
                        error_log('Pixelwar room give up pusher error: ' . $pusherError->getMessage());
                    }
                }
            }

            if ($isPvpRun && isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
                try {
                    $pusherService->trigger(
                        'pvp-' . $pvpId,
                        'pvp-ended',
                        [
                            'reason' => 'give_up',
                            'winner_user_id' => $pvpWinnerUserId,
                            'loser_user_id' => $userId,
                            'duration_seconds' => $pvpDurationSeconds,
                            'redirect_at_ms' => $pvpRedirectAtMs,
                        ]
                    );
                } catch (Throwable $pusherError) {
                    error_log('Pixelwar pvp give up pusher error: ' . $pusherError->getMessage());
                }
            }
        }

        if (!$isPvpRun) {
            $_SESSION['alert'] = [
                'error' => !($deleted || $preservedRoomRun),
                'content' => ($deleted || $preservedRoomRun)
                    ? ($isRoomRun
                        ? 'Challenge run marked as gave up for this room.'
                        : 'Challenge run removed. You can restart it anytime.')
                    : 'No ongoing challenge run was found.',
            ];
        }

        if ($isPvpRun && $wantsJson) {
            pixelwarJsonResponse([
                'success' => true,
                'message' => '1v1 match ended.',
                'data' => [
                    'winner_user_id' => $pvpWinnerUserId,
                    'loser_user_id' => $userId,
                    'duration_seconds' => $pvpDurationSeconds,
                    'redirect_at_ms' => $pvpRedirectAtMs,
                ],
            ]);
        }
    } catch (Throwable $err) {
        error_log('Pixelwar give up challenge error: ' . $err->getMessage());

        if ($wantsJson) {
            pixelwarJsonResponse([
                'success' => false,
                'message' => APP_DEBUG ? $err->getMessage() : 'Unable to give up this challenge right now.',
            ], 422);
        }

        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to give up this challenge right now.',
        ];
    }

    if ($pvpId > 0) {
        pixelwarRedirect('home&pvp_notice=loss&pvp_duration=' . max(0, (int) ($pvpDurationSeconds ?? 0)));
    }

    pixelwarRedirect('home');
}
