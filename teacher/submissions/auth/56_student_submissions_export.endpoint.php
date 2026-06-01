<?php
if (
    $teacherRequestMethod === 'GET'
    && $teacherRequestedPage === 'student-submissions'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $studentId = (int) ($_GET['id'] ?? 0);

        if ($studentId <= 0) {
            throw new RuntimeException('Student record is missing.');
        }

        $users = teacherPanelRequireUserRepository($userRepository ?? null);
        $history = teacherPanelRequireUserChallengeRepository($userChallengeRepository ?? null);
        $student = $users->findSessionUser($studentId);

        if ($student === null) {
            throw new RuntimeException('Student not found.');
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

        $rows = $history->listAttemptHistoryByDateRange($studentId, $exportStartDate, $exportEndDate, 1000);
        $studentName = trim((string) (($student['firstname'] ?? '') . ' ' . ($student['lastname'] ?? '')))
            ?: trim((string) ($student['username'] ?? 'student'));
        $safeStudentName = preg_replace('/[^a-z0-9]+/i', '-', strtolower($studentName)) ?: 'student';
        $fileName = sprintf(
            'student-submissions-%s-%s-to-%s.csv',
            trim($safeStudentName, '-'),
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

        fputcsv($output, ['Challenge', 'Type', 'Outcome', 'Difficulty', 'Started At', 'Completed At', 'Duration', 'Awarded Points']);

        foreach ($rows as $row) {
            $startedAt = new DateTimeImmutable((string) $row['started_at']);
            $completedAt = !empty($row['completed_at'])
                ? new DateTimeImmutable((string) $row['completed_at'])
                : null;
            $isRoomAttempt = (int) ($row['room_id'] ?? 0) > 0;
            $isPvpAttempt = (int) ($row['pvp_id'] ?? 0) > 0;
            $isStrictRoomAttempt = $isRoomAttempt && (int) ($row['room_strict_mode'] ?? 0) === 1;
            $strictModeScore = max(0, min(100, (int) ($row['strict_mode_score'] ?? 0)));
            $attemptStatus = (string) ($row['attempt_status'] ?? ($completedAt instanceof DateTimeImmutable ? 'completed' : 'ongoing'));
            $typeLabel = $isPvpAttempt ? '1v1' : ($isRoomAttempt ? 'Room' : 'Solo');
            $duration = $completedAt instanceof DateTimeImmutable
                ? $formatDuration($startedAt->format('Y-m-d H:i:s'), $completedAt->format('Y-m-d H:i:s'))
                : ($attemptStatus === 'pvp_win' ? 'Win' : ($attemptStatus === 'pvp_loss' || $attemptStatus === 'gave_up' ? 'Failed' : 'Ongoing'));
            $outcomeLabel = match ($attemptStatus) {
                'pvp_win' => 'Win',
                'pvp_loss' => 'Loss',
                'gave_up' => 'Failed',
                default => ($completedAt instanceof DateTimeImmutable ? 'Completed' : 'Ongoing'),
            };

            if ($isStrictRoomAttempt && $attemptStatus !== 'ongoing') {
                $outcomeLabel = $strictModeScore . '%';
            }

            fputcsv($output, [
                (string) $row['name'],
                $typeLabel,
                $outcomeLabel,
                ucfirst(strtolower((string) ($row['difficulty_name'] ?? 'Beginner'))),
                $startedAt->format('Y-m-d H:i:s'),
                $completedAt instanceof DateTimeImmutable ? $completedAt->format('Y-m-d H:i:s') : '',
                $duration,
                (int) ($row['awarded_points'] ?? 0),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher student submissions export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export student submission records right now.';
        exit;
    }
}
