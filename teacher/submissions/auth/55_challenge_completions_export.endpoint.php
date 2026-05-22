<?php
if (
    $teacherRequestMethod === 'GET'
    && $teacherRequestedPage === 'challenge-completions'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        $challengeId = (int) ($_GET['id'] ?? 0);

        if ($teacherId <= 0 || $challengeId <= 0) {
            throw new RuntimeException('Challenge record is missing.');
        }

        $challenges = teacherPanelRequireChallengeRepository($challengeRepository ?? null);
        $completions = teacherPanelRequireUserChallengeRepository($userChallengeRepository ?? null);
        $challenge = $challenges->findCreatedChallengeForOwner($challengeId, $teacherId);

        if ($challenge === null) {
            throw new RuntimeException('Challenge not found.');
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

        $rows = $completions->listCompletedByChallengeAndDateRange($challengeId, $exportStartDate, $exportEndDate, 2000);
        $fileName = sprintf(
            'challenge-completions-%d-%s-to-%s.csv',
            $challengeId,
            $exportStartDate->format('Y-m-d'),
            $exportEndDate->format('Y-m-d')
        );

        $formatDuration = static function (?string $startedAt, ?string $completedAt): string {
            if (!$startedAt || !$completedAt) {
                return 'Unavailable';
            }

            try {
                $start = new DateTimeImmutable($startedAt);
                $end = new DateTimeImmutable($completedAt);
                $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());
            } catch (Throwable) {
                return 'Unavailable';
            }

            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);
            $remainingSeconds = $seconds % 60;
            $parts = [];

            if ($hours > 0) {
                $parts[] = $hours . 'h';
            }
            if ($minutes > 0) {
                $parts[] = $minutes . 'm';
            }
            if ($remainingSeconds > 0 || $parts === []) {
                $parts[] = $remainingSeconds . 's';
            }

            return implode(' ', $parts);
        };

        if (ob_get_level() > 0) {
            ob_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $output = fopen('php://output', 'wb');

        if (!is_resource($output)) {
            throw new RuntimeException('Could not create the export file.');
        }

        fputcsv($output, ['Player', 'Username', 'Email', 'Started At', 'Completed At', 'Duration']);

        foreach ($rows as $row) {
            $firstname = trim((string) ($row['firstname'] ?? ''));
            $lastname = trim((string) ($row['lastname'] ?? ''));
            $displayName = trim($firstname . ' ' . $lastname) ?: (string) ($row['username'] ?? 'Player');

            fputcsv($output, [
                $displayName,
                (string) ($row['username'] ?? ''),
                (string) ($row['email'] ?? ''),
                (string) ($row['started_at'] ?? ''),
                (string) ($row['completed_at'] ?? ''),
                $formatDuration((string) ($row['started_at'] ?? ''), (string) ($row['completed_at'] ?? '')),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher challenge completions export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export challenge completion records right now.';
        exit;
    }
}
