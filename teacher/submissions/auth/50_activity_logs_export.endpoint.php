<?php
if (
    $teacherRequestMethod === 'GET'
    && $teacherRequestedPage === 'activity-logs'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $teacherId = (int) ($_SESSION['user_id'] ?? 0);

        if ($teacherId <= 0) {
            throw new RuntimeException('Login is required before exporting records.');
        }

        $logs = teacherPanelRequireActivityLogRepository($activityLogRepository ?? null);
        $exportStartInput = trim((string) ($_GET['export_start_date'] ?? ''));
        $exportEndInput = trim((string) ($_GET['export_end_date'] ?? ''));

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

        $exportRows = $logs->listCreationLogsForUserByDateRange($teacherId, $exportStartDate, $exportEndDate, 2000);
        $fileName = sprintf(
            'teacher-activity-creations-%s-to-%s.csv',
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

        fputcsv($output, ['Category', 'Activity', 'Created At']);

        foreach ($exportRows as $exportRow) {
            fputcsv($output, [
                ucfirst(strtolower((string) ($exportRow['category'] ?? 'general'))),
                (string) ($exportRow['log_text'] ?? ''),
                (string) ($exportRow['date_created'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher activity export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export activity logs right now.';
        exit;
    }
}
