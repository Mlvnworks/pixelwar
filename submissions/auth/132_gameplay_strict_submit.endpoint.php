<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'strict_mode_submit') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('challenges', $wantsJson);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $roomId = (int) ($_POST['room_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $userChallengeId = (int) ($_POST['user_challenge_id'] ?? 0);
    $strictModeScore = max(0, min(100, (int) ($_POST['strict_mode_score'] ?? 0)));

    try {
        if (
            !$roomRepository instanceof RoomRepository
            || !$roomPlayerRepository instanceof RoomPlayerRepository
            || !$userChallengeRepository instanceof UserChallengeRepository
            || !$gameplayCompletionService instanceof GameplayCompletionService
        ) {
            throw new RuntimeException('Strict mode progress is not available.');
        }

        if ($userId <= 0 || $roomId <= 0 || $challengeId <= 0 || $userChallengeId <= 0) {
            throw new InvalidArgumentException('Invalid strict mode progress request.');
        }

        $room = $roomRepository->findById($roomId);
        if ($room === null) {
            throw new RuntimeException('Room not found.');
        }

        if ((int) ($room['challenge_id'] ?? 0) !== $challengeId) {
            throw new RuntimeException('This room is not linked to the selected challenge.');
        }

        if ((int) ($room['strict_mode'] ?? 0) !== 1) {
            throw new RuntimeException('This room is not in strict mode.');
        }

        if (trim((string) ($room['started_at'] ?? '')) === '' || trim((string) ($room['ended_at'] ?? '')) !== '') {
            throw new RuntimeException('This room is no longer accepting strict mode submissions.');
        }

        $roomPlayer = $roomPlayerRepository->findByUserAndRoom($userId, $roomId);
        if ($roomPlayer === null) {
            throw new RuntimeException('You have not joined this room.');
        }

        $currentRun = $userChallengeRepository->findById($userChallengeId);
        if (
            $currentRun === null
            || (int) ($currentRun['user_id'] ?? 0) !== $userId
            || (int) ($currentRun['challenge_id'] ?? 0) !== $challengeId
            || (int) ($currentRun['room_id'] ?? 0) !== $roomId
            || trim((string) ($currentRun['completed_at'] ?? '')) !== ''
        ) {
            throw new RuntimeException('No active strict mode room run was found.');
        }

        $roomPlayerRepository->updateStrictModeScore($userId, $roomId, $strictModeScore);

        $redirectUrl = './?c=room&id=' . $roomId;
        $resultMessage = 'Strict mode result recorded: ' . $strictModeScore . '%.';

        if ($strictModeScore >= 100) {
            $completion = $gameplayCompletionService->complete($userId, $userChallengeId, $challengeId);
            $roomPlayerRepository->markCompleted($userId, $roomId);
            $resultMessage = 'Strict mode result recorded: 100%.';
            $updatedRoomPlayer = $roomPlayerRepository->findByUserAndRoom($userId, $roomId);

            if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
                try {
                    $pusherService->trigger(
                        'room-' . $roomId,
                        'player-status',
                        [
                            'user_id' => $userId,
                            'status_label' => '100%',
                            'started_at' => (string) ($updatedRoomPlayer['started_at'] ?? ($completion['started_at'] ?? '')),
                            'completed_at' => (string) ($updatedRoomPlayer['completed_at'] ?? ($completion['completed_at'] ?? '')),
                            'strict_mode_score' => 100,
                        ]
                    );
                } catch (Throwable $pusherError) {
                    error_log('Pixelwar strict mode completion pusher error: ' . $pusherError->getMessage());
                }
            }
        } else {
            $roomPlayerRepository->markStrictSubmittedFailed($userId, $roomId);
            $updatedRoomPlayer = $roomPlayerRepository->findByUserAndRoom($userId, $roomId);

            if ($challengeRepository instanceof ChallengeRepository) {
                $challenge = $challengeRepository->findCreatedChallenge($challengeId);
                $challengeName = $challenge !== null
                    ? (string) ($challenge['name'] ?? 'Challenge')
                    : 'Challenge';
                pixelwarLogActivity(
                    $activityLogRepository ?? null,
                    $userId,
                    'challenge',
                    'Submitted strict mode challenge "' . $challengeName . '" with ' . $strictModeScore . '% match.'
                );
            }

            if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
                try {
                    $pusherService->trigger(
                        'room-' . $roomId,
                        'player-status',
                        [
                            'user_id' => $userId,
                            'status_label' => $strictModeScore . '%',
                            'started_at' => (string) ($updatedRoomPlayer['started_at'] ?? ''),
                            'completed_at' => (string) ($updatedRoomPlayer['completed_at'] ?? ''),
                            'strict_mode_score' => $strictModeScore,
                        ]
                    );
                } catch (Throwable $pusherError) {
                    error_log('Pixelwar strict mode failed pusher error: ' . $pusherError->getMessage());
                }
            }
        }

        $_SESSION['alert'] = [
            'error' => false,
            'content' => $resultMessage,
        ];

        pixelwarJsonResponse([
            'success' => true,
            'score' => $strictModeScore,
            'message' => $resultMessage,
            'redirect_url' => $redirectUrl,
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar strict mode score submit error: ' . $err->getMessage());
        pixelwarJsonResponse([
            'success' => false,
            'message' => APP_DEBUG ? $err->getMessage() : 'Unable to submit strict mode progress right now.',
        ], 422);
    }
}
