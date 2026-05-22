<?php
if (
    $adminRequestMethod === 'GET'
    && $adminRequestedPage === 'logs'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository ?? null);
        $exportStartInput = trim((string) ($_GET['export_start_date'] ?? ''));
        $exportEndInput = trim((string) ($_GET['export_end_date'] ?? ''));
        $exportCategory = strtolower(trim((string) ($_GET['category'] ?? 'all')));

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

        $roleLabels = [
            1 => 'Admin',
            2 => 'Teacher',
            3 => 'Student',
        ];

        $exportRows = $logs->listOverallByDateRange($exportStartDate, $exportEndDate, $exportCategory, 5000);
        $fileName = sprintf(
            'activity-logs-%s-%s-to-%s.csv',
            $exportCategory !== '' ? $exportCategory : 'all',
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

        fputcsv($output, ['Date', 'User', 'Email', 'Role', 'Category', 'Log']);

        foreach ($exportRows as $exportRow) {
            $fullname = trim(((string) ($exportRow['firstname'] ?? '')) . ' ' . ((string) ($exportRow['lastname'] ?? '')));
            $displayUser = $fullname !== ''
                ? $fullname
                : (trim((string) ($exportRow['username'] ?? '')) ?: ('User #' . (int) ($exportRow['user_id'] ?? 0)));
            $roleText = $roleLabels[(int) ($exportRow['role_id'] ?? 0)] ?? 'Unknown';

            fputcsv($output, [
                (string) ($exportRow['date_created'] ?? ''),
                $displayUser,
                (string) ($exportRow['email'] ?? ''),
                $roleText,
                ucfirst((string) ($exportRow['category'] ?? 'general')),
                (string) ($exportRow['log_text'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar admin logs export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export activity logs right now.';
        exit;
    }
}
