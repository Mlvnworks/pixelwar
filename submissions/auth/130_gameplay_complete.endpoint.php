<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'complete') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('challenges', $wantsJson);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $userChallengeId = (int) ($_POST['user_challenge_id'] ?? 0);

    try {
        if (!$gameplayCompletionService instanceof GameplayCompletionService) {
            throw new RuntimeException('Gameplay completion service is not available.');
        }

        $completion = $gameplayCompletionService->complete($userId, $userChallengeId, $challengeId);

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

    pixelwarRedirect('challenge&id=' . $challengeId);
}
