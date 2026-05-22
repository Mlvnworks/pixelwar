<?php
if (
    $adminRequestMethod === 'GET'
    && $adminRequestedPage === 'student-submissions'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $studentId = max(0, (int) ($_GET['id'] ?? 0));

        if ($studentId <= 0) {
            throw new RuntimeException('A valid student is required before exporting records.');
        }

        $users = adminPanelRequireUserRepository($userRepository ?? null);
        $studentProfile = $users->findSessionUser($studentId);

        if ($studentProfile === null) {
            throw new RuntimeException('The selected student account could not be found.');
        }

        $repository = $userChallengeRepository instanceof UserChallengeRepository ? $userChallengeRepository : null;

        if (!$repository instanceof UserChallengeRepository) {
            throw new RuntimeException('Challenge history repository is not available.');
        }

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

        $formatDurationLabel = static function (int $totalSeconds): string {
            $safeSeconds = max(0, $totalSeconds);
            $hours = intdiv($safeSeconds, 3600);
            $minutes = intdiv($safeSeconds % 3600, 60);
            $seconds = $safeSeconds % 60;

            if ($hours > 0) {
                return sprintf('%dh %02dm', $hours, $minutes);
            }

            if ($minutes > 0) {
                return sprintf('%dm %02ds', $minutes, $seconds);
            }

            return sprintf('%ds', $seconds);
        };

        $exportRows = $repository->listAttemptHistoryByDateRange($studentId, $exportStartDate, $exportEndDate, 2000);
        $fileName = sprintf(
            'student-submissions-%d-%s-to-%s.csv',
            $studentId,
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

        fputcsv($output, ['Challenge', 'Status', 'Difficulty', 'Started At', 'Completed At', 'Duration', 'Awarded Points']);

        foreach ($exportRows as $exportRow) {
            $startedAt = new DateTimeImmutable((string) $exportRow['started_at']);
            $completedAt = !empty($exportRow['completed_at'])
                ? new DateTimeImmutable((string) $exportRow['completed_at'])
                : null;
            $attemptStatus = (string) ($exportRow['attempt_status'] ?? ($completedAt instanceof DateTimeImmutable ? 'completed' : 'ongoing'));
            $duration = $completedAt instanceof DateTimeImmutable
                ? $formatDurationLabel(max(0, $completedAt->getTimestamp() - $startedAt->getTimestamp()))
                : ($attemptStatus === 'gave_up' ? 'Gave Up' : 'Ongoing');
            $statusLabel = $completedAt instanceof DateTimeImmutable
                ? 'Completed'
                : ($attemptStatus === 'gave_up' ? 'Gave Up' : 'Ongoing');

            fputcsv($output, [
                (string) ($exportRow['name'] ?? ''),
                $statusLabel,
                ucfirst(strtolower((string) ($exportRow['difficulty_name'] ?? 'Unknown'))),
                $startedAt->format('Y-m-d H:i:s'),
                $completedAt instanceof DateTimeImmutable ? $completedAt->format('Y-m-d H:i:s') : '',
                $duration,
                (int) ($exportRow['awarded_points'] ?? 0),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar admin student submissions export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export student submissions right now.';
        exit;
    }
}
