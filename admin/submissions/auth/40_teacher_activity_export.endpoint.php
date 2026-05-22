<?php
if (
    $adminRequestMethod === 'GET'
    && $adminRequestedPage === 'teacher-activity'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $teacherId = max(0, (int) ($_GET['id'] ?? 0));
        if ($teacherId <= 0) {
            throw new RuntimeException('A valid teacher is required before exporting records.');
        }

        $users = adminPanelRequireUserRepository($userRepository ?? null);
        $teacherProfile = $users->findSessionUser($teacherId);

        if ($teacherProfile === null || (int) ($teacherProfile['role_id'] ?? 0) !== 2) {
            throw new RuntimeException('The selected teacher account could not be found.');
        }

        $challengeRepo = $challengeRepository instanceof ChallengeRepository ? $challengeRepository : null;
        $activityLogs = adminPanelRequireActivityLogRepository($activityLogRepository ?? null);

        if (!$challengeRepo instanceof ChallengeRepository) {
            throw new RuntimeException('Challenge repository is not available.');
        }

        $exportStartInput = trim((string) ($_GET['export_start_date'] ?? ''));
        $exportEndInput = trim((string) ($_GET['export_end_date'] ?? ''));
        $exportType = strtolower(trim((string) ($_GET['export_type'] ?? 'all')));

        if (!in_array($exportType, ['all', 'challenge', 'room'], true)) {
            $exportType = 'all';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $exportStartInput) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $exportEndInput)) {
            throw new RuntimeException('Select a valid export date range.');
        }

        try {
            $exportStartDate = new DateTimeImmutable($exportStartInput);
            $exportEndDate = new DateTimeImmutable($exportEndInput);
        } catch (Throwable) {
            throw new RuntimeException('Select a valid export date range.');
        }

        $todayDate = new DateTimeImmutable('today');

        if ($exportStartDate > $exportEndDate) {
            throw new RuntimeException('The export start date cannot be later than the end date.');
        }

        if ($exportStartDate > $todayDate) {
            throw new RuntimeException('The export date range cannot start in the future.');
        }

        if ($exportEndDate > $todayDate) {
            $exportEndDate = $todayDate;
        }

        $exportRows = [];

        if ($exportType === 'all' || $exportType === 'challenge') {
            foreach ($challengeRepo->listCreatedChallengesForUserByDateRange($teacherId, $exportStartDate, $exportEndDate, 2000) as $challengeRow) {
                $exportRows[] = [
                    'type' => 'Challenge',
                    'name' => (string) ($challengeRow['name'] ?? 'Untitled Challenge'),
                    'details' => trim(sprintf(
                        '%s · %d points',
                        ucfirst(strtolower((string) ($challengeRow['difficulty_name'] ?? 'Unknown'))),
                        (int) ($challengeRow['points'] ?? 0)
                    )),
                    'created_at' => (string) ($challengeRow['date_created'] ?? ''),
                ];
            }
        }

        if ($exportType === 'all' || $exportType === 'room') {
            foreach ($activityLogs->listRoomCreationLogsForUserByDateRange($teacherId, $exportStartDate, $exportEndDate, 2000) as $roomRow) {
                $exportRows[] = [
                    'type' => 'Room',
                    'name' => adminPanelExtractRoomActivityName((string) ($roomRow['log_text'] ?? 'Created room')),
                    'details' => (string) ($roomRow['log_text'] ?? ''),
                    'created_at' => (string) ($roomRow['date_created'] ?? ''),
                ];
            }
        }

        usort(
            $exportRows,
            static fn(array $left, array $right): int => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''))
        );

        $fileName = sprintf(
            'teacher-activity-%s-%s-to-%s.csv',
            $exportType,
            $exportStartDate->format('Y-m-d'),
            $exportEndDate->format('Y-m-d')
        );

        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $output = fopen('php://output', 'wb');

        if (!is_resource($output)) {
            throw new RuntimeException('Could not create the export file.');
        }

        fputcsv($output, ['Type', 'Name', 'Details', 'Created At']);

        foreach ($exportRows as $exportRow) {
            fputcsv($output, [
                (string) ($exportRow['type'] ?? ''),
                (string) ($exportRow['name'] ?? ''),
                (string) ($exportRow['details'] ?? ''),
                (string) ($exportRow['created_at'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar admin teacher activity export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export teacher activity right now.';
        exit;
    }
}
