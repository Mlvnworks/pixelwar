<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'give_up') {
    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('challenges');
    }

    $userChallengeId = (int) ($_POST['user_challenge_id'] ?? 0);
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    try {
        if (!$userChallengeRepository instanceof UserChallengeRepository) {
            throw new RuntimeException('Challenge progress is not available.');
        }

        if ($userId <= 0 || $userChallengeId <= 0 || $challengeId <= 0) {
            throw new InvalidArgumentException('Invalid challenge progress request.');
        }

        $deleted = $userChallengeRepository->deleteOngoingForUser($userChallengeId, $userId);

        if ($deleted) {
            $challengeName = 'Challenge';

            if ($challengeRepository instanceof ChallengeRepository) {
                $challenge = $challengeRepository->findCreatedChallenge($challengeId);
                if ($challenge !== null) {
                    $challengeName = (string) ($challenge['name'] ?? 'Challenge');
                }
            }

            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'challenge', 'Gave up challenge "' . $challengeName . '".');
        }

        $_SESSION['alert'] = [
            'error' => !$deleted,
            'content' => $deleted
                ? 'Challenge run removed. You can restart it anytime.'
                : 'No ongoing challenge run was found.',
        ];
    } catch (Throwable $err) {
        error_log('Pixelwar give up challenge error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to give up this challenge right now.',
        ];
    }

    pixelwarRedirect('challenge&id=' . $challengeId);
}
