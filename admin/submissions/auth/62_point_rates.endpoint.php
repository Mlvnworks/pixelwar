<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'point-rates') {
    try {
        if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) ($_POST['_csrf_token'] ?? ''))) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('point-rates');
        }

        $difficultyIds = $_POST['difficulty_id'] ?? [];
        $pointsValues = $_POST['points'] ?? [];
        $challenges = adminPanelRequireChallengeRepository($challengeRepository ?? null);
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository ?? null);
        $difficulties = $challenges->listDifficulties();
        $validDifficultyIds = array_fill_keys(array_map(
            static fn(array $difficulty): int => (int) ($difficulty['difficulty_id'] ?? 0),
            $difficulties
        ), true);
        $updatedCount = 0;

        if (!is_array($difficultyIds) || !is_array($pointsValues) || $difficultyIds === []) {
            throw new RuntimeException('No point rates were submitted.');
        }

        foreach ($difficultyIds as $index => $difficultyIdValue) {
            $difficultyId = (int) $difficultyIdValue;
            $pointsInput = trim((string) ($pointsValues[$index] ?? ''));
            $points = ctype_digit($pointsInput) ? (int) $pointsInput : -1;

            if ($difficultyId <= 0 || !isset($validDifficultyIds[$difficultyId])) {
                throw new RuntimeException('A difficulty record is invalid.');
            }

            if ($points < 0 || $points > 999999) {
                throw new RuntimeException('Point rewards must be whole numbers from 0 to 999,999.');
            }

            $challenges->updateDifficultyPoints($difficultyId, $points);
            $updatedCount++;
        }

        $logs->create((int) ($_SESSION['user_id'] ?? 0), 'difficulty', 'Updated ' . $updatedCount . ' point rate' . ($updatedCount === 1 ? '' : 's') . '.');
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Point rates updated successfully.',
        ];
    } catch (Throwable $err) {
        error_log('Pixelwar admin point rates error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update point rates right now.',
        ];
    }

    adminPanelRedirect('point-rates');
}
