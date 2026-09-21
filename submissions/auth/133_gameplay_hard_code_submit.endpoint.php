<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'hard_code_submit') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('challenges', $wantsJson);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $roomId = (int) ($_POST['room_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $userChallengeId = (int) ($_POST['user_challenge_id'] ?? 0);
    $cssCode = trim((string) ($_POST['css_code'] ?? ''));

    try {
        if (
            !$roomRepository instanceof RoomRepository
            || !$roomPlayerRepository instanceof RoomPlayerRepository
            || !$userChallengeRepository instanceof UserChallengeRepository
        ) {
            throw new RuntimeException('Hard code submission is unavailable.');
        }

        if ($userId <= 0 || $roomId <= 0 || $challengeId <= 0 || $userChallengeId <= 0) {
            throw new InvalidArgumentException('Invalid hard code submission.');
        }
        if ($cssCode === '') {
            throw new InvalidArgumentException('Write CSS before submitting your design.');
        }
        if (strlen($cssCode) > 100000) {
            throw new InvalidArgumentException('CSS solution must be 100 KB or smaller.');
        }

        $room = $roomRepository->findById($roomId);
        if (
            $room === null
            || (int) ($room['challenge_id'] ?? 0) !== $challengeId
            || (int) ($room['mode'] ?? 0) !== 3
            || trim((string) ($room['started_at'] ?? '')) === ''
            || trim((string) ($room['ended_at'] ?? '')) !== ''
        ) {
            throw new RuntimeException('This hard code room is no longer accepting submissions.');
        }

        $roomPlayer = $roomPlayerRepository->findByUserAndRoom($userId, $roomId);
        $currentRun = $userChallengeRepository->findById($userChallengeId);
        if (
            $roomPlayer === null
            || $currentRun === null
            || (int) ($currentRun['user_id'] ?? 0) !== $userId
            || (int) ($currentRun['challenge_id'] ?? 0) !== $challengeId
            || (int) ($currentRun['room_id'] ?? 0) !== $roomId
            || trim((string) ($currentRun['completed_at'] ?? '')) !== ''
        ) {
            throw new RuntimeException('No active hard code room run was found.');
        }

        $storage = new SupabaseStorage(
            SUPABASE_URL,
            SUPABASE_SERVICE_ROLE_KEY,
            SUPABASE_STORAGE_BUCKET,
            SUPABASE_STORAGE_CODE_SOLUTION_FOLDER
        );
        $solutionUrl = $storage->uploadTextObject(
            $cssCode,
            'room-' . $roomId . '-player-' . $userId,
            'css',
            'text/css'
        );

        $roomPlayerRepository->saveHardCodeSolution((int) ($roomPlayer['rp_id'] ?? 0), $solutionUrl);
        $roomPlayerRepository->markHardCodeSubmitted($userId, $roomId);
        $userChallengeRepository->assignActiveSeason($userChallengeId, $userId);
        $completion = $userChallengeRepository->markCompleted($userChallengeId, $userId, $challengeId);

        if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
            try {
                $pusherService->trigger('room-' . $roomId, 'player-status', [
                    'user_id' => $userId,
                    'status_label' => 'submitted',
                    'started_at' => (string) ($completion['started_at'] ?? ''),
                    'completed_at' => (string) ($completion['completed_at'] ?? ''),
                    'code_solution_url' => $solutionUrl,
                ]);
            } catch (Throwable $pusherError) {
                error_log('Pixelwar hard code submission pusher error: ' . $pusherError->getMessage());
            }
        }

        pixelwarLogActivity($activityLogRepository ?? null, $userId, 'challenge', 'Submitted a hard code room solution.');
        pixelwarJsonResponse([
            'success' => true,
            'message' => 'Your coded design was submitted for teacher review.',
            'redirect_url' => './?c=home&room_notice=hard_code_submitted',
        ]);
    } catch (Throwable $error) {
        error_log('Pixelwar hard code submission error: ' . $error->getMessage());
        pixelwarJsonResponse([
            'success' => false,
            'message' => APP_DEBUG ? $error->getMessage() : 'Unable to submit your coded design right now.',
        ], 422);
    }
}
