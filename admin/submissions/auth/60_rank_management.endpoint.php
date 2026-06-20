<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'rank-management' && isset($_POST['rank_action'])) {
    try {
        if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) ($_POST['_csrf_token'] ?? ''))) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('rank-management');
        }

        $action = trim((string) ($_POST['rank_action'] ?? ''));
        $rankId = (int) ($_POST['rank_id'] ?? 0);
        $name = preg_replace('/\s+/', ' ', trim((string) ($_POST['name'] ?? ''))) ?? '';
        $pointsInput = trim((string) ($_POST['points_requirements'] ?? ''));
        $pointsRequirements = ctype_digit($pointsInput) ? (int) $pointsInput : -1;
        $ranks = adminPanelRequireRankRepository($rankRepository ?? null);
        $adminUserId = (int) ($_SESSION['user_id'] ?? 0);

        if (!in_array($action, ['create', 'update', 'delete'], true)) {
            throw new RuntimeException('Rank action is not supported.');
        }

        if ($action === 'create' || $action === 'update') {
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._-]{1,99}$/', $name)) {
                throw new RuntimeException('Rank name must be 2-100 characters and start with a letter or number.');
            }

            if ($pointsRequirements < 0 || $pointsRequirements > 999999999) {
                throw new RuntimeException('Points requirement must be a whole number from 0 to 999,999,999.');
            }

            if ($ranks->nameExists($name, $rankId)) {
                throw new RuntimeException('A rank with that name already exists.');
            }

            if ($ranks->pointsRequirementExists($pointsRequirements, $rankId)) {
                throw new RuntimeException('A rank with that points requirement already exists.');
            }
        }

        if ($action === 'create') {
            $createdRankId = $ranks->create($name, $pointsRequirements);
            if ($createdRankId <= 0) {
                throw new RuntimeException('Rank record could not be created.');
            }
            adminPanelLogActivitySafely($activityLogRepository ?? null, $adminUserId, 'rank', 'Created rank "' . $name . '".');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Rank created successfully.',
            ];
        } elseif ($action === 'update') {
            if ($rankId <= 0) {
                throw new RuntimeException('Rank record is missing.');
            }

            if (!$ranks->exists($rankId)) {
                throw new RuntimeException('Rank record was not found.');
            }

            if (!$ranks->update($rankId, $name, $pointsRequirements)) {
                throw new RuntimeException('Rank record could not be updated.');
            }
            adminPanelLogActivitySafely($activityLogRepository ?? null, $adminUserId, 'rank', 'Updated rank "' . $name . '".');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Rank updated successfully.',
            ];
        } elseif ($action === 'delete') {
            if ($rankId <= 0) {
                throw new RuntimeException('Rank record is missing.');
            }

            if (!$ranks->exists($rankId)) {
                throw new RuntimeException('Rank record was not found.');
            }

            if (!$ranks->delete($rankId)) {
                throw new RuntimeException('Rank record could not be deleted.');
            }
            adminPanelLogActivitySafely($activityLogRepository ?? null, $adminUserId, 'rank', 'Deleted rank #' . $rankId . '.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Rank deleted successfully.',
            ];
        }
    } catch (mysqli_sql_exception $err) {
        error_log('Pixelwar admin rank management database error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => str_contains(strtolower($err->getMessage()), 'duplicate')
                ? 'Rank names and points requirements must be unique.'
                : (APP_DEBUG ? $err->getMessage() : 'Unable to update ranks right now.'),
        ];
    } catch (Throwable $err) {
        error_log('Pixelwar admin rank management error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update ranks right now.',
        ];
    }

    adminPanelRedirect('rank-management');
}
