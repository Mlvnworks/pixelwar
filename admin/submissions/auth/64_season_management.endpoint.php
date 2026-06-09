<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'season-management' && isset($_POST['season_action'])) {
    try {
        if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) ($_POST['_csrf_token'] ?? ''))) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('season-management');
        }

        $action = trim((string) ($_POST['season_action'] ?? ''));
        $seasonId = (int) ($_POST['season_id'] ?? 0);
        $name = preg_replace('/\s+/', ' ', trim((string) ($_POST['name'] ?? ''))) ?? '';
        $startInput = trim((string) ($_POST['start_date'] ?? ''));
        $endInput = trim((string) ($_POST['end_date'] ?? ''));
        $seasons = adminPanelRequireSeasonRepository($seasonRepository ?? null);
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository ?? null);

        if (!in_array($action, ['create', 'update', 'delete'], true)) {
            throw new RuntimeException('Season action is not supported.');
        }

        if ($action === 'create' || $action === 'update') {
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._-]{1,149}$/', $name)) {
                throw new RuntimeException('Season name must be 2-150 characters and start with a letter or number.');
            }

            $startDate = adminPanelParseSeasonDate($startInput, 'start date');
            $endDate = adminPanelParseSeasonDate($endInput, 'end date');

            if ($endDate <= $startDate) {
                throw new RuntimeException('Season end date must be after the start date.');
            }

            $startSql = $startDate->format('Y-m-d H:i:s');
            $endSql = $endDate->format('Y-m-d H:i:s');

            if ($seasons->nameExists($name, $seasonId)) {
                throw new RuntimeException('A season with that name already exists.');
            }

            if ($seasons->overlaps($startSql, $endSql, $seasonId)) {
                throw new RuntimeException('Season dates overlap an existing season.');
            }
        }

        if ($action === 'create') {
            $seasons->create($name, $startSql, $endSql);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'season', 'Created season "' . $name . '".');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Season created successfully.',
            ];
        } elseif ($action === 'update') {
            if ($seasonId <= 0) {
                throw new RuntimeException('Season record is missing.');
            }

            if (!$seasons->exists($seasonId)) {
                throw new RuntimeException('Season record was not found.');
            }

            $seasons->update($seasonId, $name, $startSql, $endSql);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'season', 'Updated season "' . $name . '".');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Season updated successfully.',
            ];
        } elseif ($action === 'delete') {
            if ($seasonId <= 0) {
                throw new RuntimeException('Season record is missing.');
            }

            if (!$seasons->exists($seasonId)) {
                throw new RuntimeException('Season record was not found.');
            }

            if ($seasons->hasRecords($seasonId)) {
                throw new RuntimeException('This season already has attempts or progress records and cannot be deleted.');
            }

            $seasons->delete($seasonId);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'season', 'Deleted season #' . $seasonId . '.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Season deleted successfully.',
            ];
        }
    } catch (mysqli_sql_exception $err) {
        error_log('Pixelwar admin season management database error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => str_contains(strtolower($err->getMessage()), 'duplicate')
                ? 'Season names must be unique.'
                : (APP_DEBUG ? $err->getMessage() : 'Unable to update seasons right now.'),
        ];
    } catch (Throwable $err) {
        error_log('Pixelwar admin season management error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update seasons right now.',
        ];
    }

    adminPanelRedirect('season-management');
}

function adminPanelParseSeasonDate(string $value, string $label): DateTimeImmutable
{
    if ($value === '') {
        throw new RuntimeException('Season ' . $label . ' is required.');
    }

    $timezone = new DateTimeZone(APP_TIMEZONE);
    $formats = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'];

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
    }

    throw new RuntimeException('Season ' . $label . ' is not a valid date/time.');
}
