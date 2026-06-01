<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'validate_availability') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('challenges', $wantsJson);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $userChallengeId = (int) ($_POST['user_challenge_id'] ?? 0);

    try {
        if (
            !$challengeRepository instanceof ChallengeRepository
            || !$userChallengeRepository instanceof UserChallengeRepository
        ) {
            throw new RuntimeException('Challenge progress is not available.');
        }

        if ($userId <= 0 || $challengeId <= 0 || $userChallengeId <= 0) {
            throw new InvalidArgumentException('Invalid challenge validation request.');
        }

        $challenge = $challengeRepository->findCreatedChallenge($challengeId);

        if ($challenge === null) {
            $userChallengeRepository->deleteOngoingForUser($userChallengeId, $userId);
            pixelwarJsonResponse([
                'success' => false,
                'available' => false,
                'message' => 'This challenge is no longer available. Your run was ended.',
            ], 409);
        }

        $ongoing = $userChallengeRepository->findOwnedOngoing($userChallengeId, $userId, $challengeId);
        $currentRun = $userChallengeRepository->findById($userChallengeId);

        if (
            $ongoing === null
            && $currentRun !== null
            && (int) ($currentRun['user_id'] ?? 0) === $userId
            && (int) ($currentRun['challenge_id'] ?? 0) === $challengeId
            && trim((string) ($currentRun['completed_at'] ?? '')) !== ''
        ) {
            pixelwarJsonResponse([
                'success' => true,
                'available' => true,
                'completed' => true,
            ]);
        }

        $activeRun = $ongoing ?? $currentRun;
        $isPvpLinkedRun = $activeRun !== null && (int) ($activeRun['pvp_id'] ?? 0) > 0;
        $isRoomLinkedRun = $activeRun !== null && (int) ($activeRun['room_id'] ?? 0) > 0;

        if ($isPvpLinkedRun && $pvpPlayerRepository instanceof PvpPlayerRepository) {
            $pvpId = (int) ($activeRun['pvp_id'] ?? 0);
            $pvpPlayer = $pvpPlayerRepository->findByMatchAndUser($pvpId, $userId);
            $pvpStatus = (int) ($pvpPlayer['status'] ?? 0);

            if ($pvpStatus === 2 || $pvpStatus === 3) {
                $winnerUserId = 0;
                $otherUsers = $pvpPlayerRepository->listUserIdsForMatch($pvpId);
                foreach ($otherUsers as $matchUserId) {
                    if ($matchUserId !== $userId) {
                        $winnerUserId = $pvpStatus === 2 ? $userId : $matchUserId;
                        break;
                    }
                }

                pixelwarJsonResponse([
                    'success' => false,
                    'available' => false,
                    'pvp_ended' => true,
                    'message' => $pvpStatus === 2 ? 'You won the duel.' : 'You lost the duel.',
                    'data' => [
                        'winner_user_id' => $winnerUserId,
                        'loser_user_id' => $pvpStatus === 3 ? $userId : 0,
                        'duration_seconds' => $userChallengeRepository->pvpDurationSeconds($pvpId),
                        'redirect_at_ms' => ((int) floor(microtime(true) * 1000)) + 900,
                    ],
                ], 409);
            }
        }

        if ($ongoing === null) {
            pixelwarJsonResponse([
                'success' => false,
                'available' => false,
                'message' => 'Your run is no longer active.',
            ], 409);
        }

        if ((int) ($challenge['status'] ?? 0) !== 1 && !$isRoomLinkedRun && !$isPvpLinkedRun) {
            $userChallengeRepository->deleteOngoingForUser($userChallengeId, $userId);
            pixelwarJsonResponse([
                'success' => false,
                'available' => false,
                'message' => 'This challenge is not available publicly right now. Your run was ended.',
            ], 409);
        }

        pixelwarJsonResponse([
            'success' => true,
            'available' => true,
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar gameplay availability validation error: ' . $err->getMessage());
        pixelwarJsonResponse([
            'success' => false,
            'available' => false,
            'message' => APP_DEBUG ? $err->getMessage() : 'Unable to validate this challenge right now.',
        ], 422);
    }
}
