<?php
if (
    $requestMethod === 'GET'
    && $requestedPage === 'player-analytics'
    && isset($_GET['export'])
    && DataExporter::isSupported((string) $_GET['export'])
) {
    try {
        $exportFormat = strtolower((string) $_GET['export']);
        $currentStudentId = (int) ($_SESSION['user_id'] ?? 0);

        if ($currentStudentId <= 0) {
            throw new RuntimeException('Login is required before exporting records.');
        }

        $repository = $userChallengeRepository instanceof UserChallengeRepository ? $userChallengeRepository : null;

        if (!$repository instanceof UserChallengeRepository) {
            throw new RuntimeException('Challenge history repository is not available.');
        }

        $exportStartInput = trim((string) ($_GET['export_start_date'] ?? ''));
        $exportEndInput = trim((string) ($_GET['export_end_date'] ?? ''));
        $exportMode = strtolower(trim((string) ($_GET['export_mode'] ?? 'all')));
        $allowedExportModes = ['all', 'solo', 'pvp', 'room'];

        if (!in_array($exportMode, $allowedExportModes, true)) {
            throw new RuntimeException('Select a valid game mode for the export.');
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

        $exportRows = $repository->listAttemptHistoryByDateRange($currentStudentId, $exportStartDate, $exportEndDate, 1000);
        $exportRows = array_values(array_filter($exportRows, static function (array $row) use ($exportMode): bool {
            if ($exportMode === 'all') {
                return true;
            }

            $isRoomAttempt = (int) ($row['room_id'] ?? 0) > 0;
            $isPvpAttempt = (int) ($row['pvp_id'] ?? 0) > 0;

            return match ($exportMode) {
                'solo' => !$isRoomAttempt && !$isPvpAttempt,
                'pvp' => $isPvpAttempt,
                'room' => $isRoomAttempt,
                default => false,
            };
        }));
        $exportModeLabels = [
            'all' => 'All Game Modes',
            'solo' => 'Solo Practice',
            'pvp' => '1v1',
            'room' => 'Room',
        ];
        $studentUsername = trim((string) ($_SESSION['username'] ?? 'player')) ?: 'player';
        $studentFilePart = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($studentUsername)), '-') ?: 'player';
        $exportFileName = sprintf(
            'player-%d-%s-%s-solving-history-%s-to-%s.csv',
            $currentStudentId,
            $studentFilePart,
            $exportMode,
            $exportStartDate->format('Y-m-d'),
            $exportEndDate->format('Y-m-d')
        );

        if (ob_get_level() > 0) {
            ob_clean();
        }

        $output = DataExporter::open($exportFormat, $exportFileName);

        fputcsv($output, ['Challenge ID', 'Challenge', 'Game Type', 'Status', 'Difficulty', 'Started At', 'Completed At', 'Duration', 'Game Points', 'Activity Points'], ',', '"', '\\');

        foreach ($exportRows as $exportRow) {
            $startedAt = new DateTimeImmutable((string) $exportRow['started_at']);
            $completedAt = !empty($exportRow['completed_at'])
                ? new DateTimeImmutable((string) $exportRow['completed_at'])
                : null;
            $attemptStatus = (string) ($exportRow['attempt_status'] ?? ($completedAt instanceof DateTimeImmutable ? 'completed' : 'ongoing'));
            $isRoomAttempt = (int) ($exportRow['room_id'] ?? 0) > 0;
            $isPvpAttempt = (int) ($exportRow['pvp_id'] ?? 0) > 0;
            $gameType = $isPvpAttempt ? '1v1' : ($isRoomAttempt ? 'Room' : 'Solo');
            $isStrictRoomAttempt = $isRoomAttempt && (int) ($exportRow['room_mode'] ?? 0) === 1;
            $isHardCodeRoomAttempt = $isRoomAttempt && (int) ($exportRow['room_mode'] ?? 0) === 3;
            $strictModeScore = max(0, min(100, (int) ($exportRow['strict_mode_score'] ?? 0)));
            $duration = $completedAt instanceof DateTimeImmutable
                ? $formatDurationLabel(max(0, $completedAt->getTimestamp() - $startedAt->getTimestamp()))
                : ($attemptStatus === 'pvp_win' ? 'Win' : ($attemptStatus === 'pvp_loss' || $attemptStatus === 'gave_up' ? 'Failed' : 'Ongoing'));
            $statusLabel = match ($attemptStatus) {
                'pvp_win' => 'Win',
                'pvp_loss' => 'Loss',
                'gave_up' => 'Failed',
                default => ($completedAt instanceof DateTimeImmutable ? 'Completed' : 'Ongoing'),
            };

            if ($isStrictRoomAttempt && $attemptStatus !== 'ongoing') {
                $statusLabel = $strictModeScore . '%';
            }
            if ($isHardCodeRoomAttempt && $completedAt instanceof DateTimeImmutable) {
                $statusLabel = 'Submitted';
            }

            $activityPoints = '-';
            if ($isHardCodeRoomAttempt) {
                $roomPoints = max(0, (int) ($exportRow['room_points'] ?? 0));
                $hardCodeGrade = isset($exportRow['hard_code_grade'])
                    ? max(0, (int) $exportRow['hard_code_grade'])
                    : null;
                $activityPoints = $hardCodeGrade === null
                    ? 'Pending / ' . $roomPoints
                    : $hardCodeGrade . ' / ' . $roomPoints;
            }

            fputcsv($output, [
                (int) ($exportRow['challenge_id'] ?? 0),
                (string) $exportRow['name'],
                $gameType,
                $statusLabel,
                ucfirst(strtolower((string) ($exportRow['difficulty_name'] ?? 'Beginner'))),
                $startedAt->format('Y-m-d H:i:s'),
                $completedAt instanceof DateTimeImmutable ? $completedAt->format('Y-m-d H:i:s') : '',
                $duration,
                (int) ($exportRow['awarded_points'] ?? 0),
                $activityPoints,
            ], ',', '"', '\\');
        }

        DataExporter::finish($output, $exportFormat, $exportFileName, 'Player Challenge History', [
            'Player' => $studentUsername,
            'User ID' => $currentStudentId,
            'Game Mode' => $exportModeLabels[$exportMode],
            'Period' => $exportStartDate->format('M j, Y') . ' - ' . $exportEndDate->format('M j, Y'),
        ]);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar player analytics export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export the solving records right now.';
        exit;
    }
}
