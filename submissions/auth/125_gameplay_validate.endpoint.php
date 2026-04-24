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
        if (!$challengeRepository instanceof ChallengeRepository || !$userChallengeRepository instanceof UserChallengeRepository) {
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

        if ((int) ($challenge['status'] ?? 0) !== 1) {
            $userChallengeRepository->deleteOngoingForUser($userChallengeId, $userId);
            pixelwarJsonResponse([
                'success' => false,
                'available' => false,
                'message' => 'This challenge is not available publicly right now. Your run was ended.',
            ], 409);
        }

        $ongoing = $userChallengeRepository->findOwnedOngoing($userChallengeId, $userId, $challengeId);

        if ($ongoing === null) {
            pixelwarJsonResponse([
                'success' => false,
                'available' => false,
                'message' => 'Your run is no longer active.',
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
