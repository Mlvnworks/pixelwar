<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'complete') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('challenges', $wantsJson);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $roomId = (int) ($_POST['room_id'] ?? 0);
    $pvpId = (int) ($_POST['pvp_id'] ?? 0);
    $userChallengeId = (int) ($_POST['user_challenge_id'] ?? 0);

    try {
        if (!$gameplayCompletionService instanceof GameplayCompletionService) {
            throw new RuntimeException('Gameplay completion service is not available.');
        }

        if (!$userChallengeRepository instanceof UserChallengeRepository) {
            throw new RuntimeException('Challenge progress is not available.');
        }

        $currentRun = $userChallengeRepository->findById($userChallengeId);
        if (
            $currentRun === null
            || (int) ($currentRun['user_id'] ?? 0) !== $userId
            || (int) ($currentRun['challenge_id'] ?? 0) !== $challengeId
            || trim((string) ($currentRun['completed_at'] ?? '')) !== ''
        ) {
            throw new RuntimeException('No active challenge run was found to complete.');
        }

        $actualRoomId = (int) ($currentRun['room_id'] ?? 0);
        $actualPvpId = (int) ($currentRun['pvp_id'] ?? 0);

        if ($roomId > 0 && $actualRoomId !== $roomId) {
            throw new RuntimeException('This room does not match the active challenge run.');
        }

        if ($pvpId > 0 && $actualPvpId !== $pvpId) {
            throw new RuntimeException('This 1v1 match does not match the active challenge run.');
        }

        if ($roomId <= 0 && $actualRoomId > 0) {
            throw new RuntimeException('Room challenge context is missing.');
        }

        if ($pvpId <= 0 && $actualPvpId > 0) {
            throw new RuntimeException('1v1 challenge context is missing.');
        }

        $completion = $gameplayCompletionService->complete($userId, $userChallengeId, $challengeId);

        if ($roomId > 0 && isset($roomPlayerRepository) && $roomPlayerRepository instanceof RoomPlayerRepository) {
            $roomPlayerRepository->markCompleted($userId, $roomId);

            if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
                try {
                    $pusherService->trigger(
                        'room-' . $roomId,
                        'player-status',
                        [
                            'user_id' => $userId,
                            'status_label' => 'completed',
                            'started_at' => (string) ($completion['started_at'] ?? ''),
                            'completed_at' => (string) ($completion['completed_at'] ?? ''),
                        ]
                    );
                } catch (Throwable $pusherError) {
                    error_log('Pixelwar room completion pusher error: ' . $pusherError->getMessage());
                }
            }
        }

        if ($pvpId > 0) {
            if (!$pvpPlayerRepository instanceof PvpPlayerRepository) {
                throw new RuntimeException('1v1 player records are not available.');
            }

            $pvpUsers = array_values(array_filter(
                $pvpPlayerRepository->listUserIdsForMatch($pvpId),
                static fn (int $matchUserId): bool => $matchUserId > 0
            ));
            if ($pvpUsers === []) {
                throw new RuntimeException('This 1v1 match is no longer available.');
            }

            $winnerUserId = $userId;
            $loserUserId = 0;
            foreach ($pvpUsers as $matchUserId) {
                if ($matchUserId === $userId) {
                    $pvpPlayerRepository->updateStatusForUser($pvpId, $matchUserId, 2);
                    continue;
                }

                $loserUserId = $matchUserId;
                $pvpPlayerRepository->updateStatusForUser($pvpId, $matchUserId, 3);
            }

            $redirectAtMs = ((int) floor(microtime(true) * 1000)) + 900;
            $pvpPayload = [
                'winner_user_id' => $winnerUserId,
                'loser_user_id' => $loserUserId,
                'duration_seconds' => max(0, (int) ($completion['duration_seconds'] ?? 0)),
                'redirect_at_ms' => $redirectAtMs,
            ];

            if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
                try {
                    $pusherService->trigger(
                        'pvp-' . $pvpId,
                        'pvp-ended',
                        $pvpPayload
                    );
                } catch (Throwable $pusherError) {
                    error_log('Pixelwar pvp completion pusher error: ' . $pusherError->getMessage());
                }
            }

            if ($wantsJson) {
                pixelwarJsonResponse([
                    'success' => true,
                    'message' => '1v1 match completed.',
                    'pvp_ended' => true,
                    'data' => $pvpPayload,
                ]);
            }

            $_SESSION['alert'] = [
                'error' => false,
                'content' => '1v1 match completed.',
            ];

            pixelwarRedirect('home&pvp_notice=win&pvp_duration=' . max(0, (int) ($completion['duration_seconds'] ?? 0)));
        }

        if ($wantsJson) {
            pixelwarJsonResponse([
                'success' => true,
                'message' => 'Challenge completed.',
                'data' => $completion,
            ]);
        }

        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Challenge completed.',
        ];
    } catch (Throwable $err) {
        error_log('Pixelwar gameplay completion error: ' . $err->getMessage());

        if ($wantsJson) {
            pixelwarJsonResponse([
                'success' => false,
                'message' => APP_DEBUG ? $err->getMessage() : 'Unable to complete this challenge right now.',
            ], 422);
        }

        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to complete this challenge right now.',
        ];
    }

    if ($roomId > 0) {
        pixelwarRedirect('room&id=' . $roomId);
    }

    pixelwarRedirect('challenge&id=' . $challengeId);
}
