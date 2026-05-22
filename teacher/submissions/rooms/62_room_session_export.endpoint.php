<?php

if (
    $teacherRequestMethod === 'GET'
    && $teacherRequestedPage === 'room-session'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        $roomId = max(0, (int) ($_GET['id'] ?? 0));

        if ($teacherId <= 0 || $roomId <= 0) {
            throw new RuntimeException('Room session record is missing.');
        }

        $rooms = teacherPanelRequireRoomRepository($roomRepository ?? null);
        if (!$roomPlayerRepository instanceof RoomPlayerRepository) {
            throw new RuntimeException('Room player records are not available.');
        }

        $room = $rooms->findByIdForOwner($roomId, $teacherId);
        if ($room === null) {
            throw new RuntimeException('Room session not found.');
        }

        $rows = $roomPlayerRepository->listJoinedForRoom($roomId);
        $fileName = sprintf(
            'room-session-%d-%s.csv',
            $roomId,
            date('Y-m-d')
        );

        $statusLabel = static function (array $row): string {
            $status = (int) ($row['status'] ?? 0);
            $startedAt = trim((string) ($row['started_at'] ?? ''));
            $completedAt = trim((string) ($row['completed_at'] ?? ''));

            if ($status === 3) {
                return 'Failed';
            }
            if ($completedAt !== '' || $status === 2) {
                return 'Completed';
            }
            if ($startedAt !== '' || $status === 1) {
                return 'Solving';
            }

            return 'Waiting';
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

        fputcsv($output, [
            'Room Code',
            'Room Name',
            'Challenge',
            'Player',
            'Username',
            'Email',
            'Student ID',
            'Status',
            'Started At',
            'Completed At',
        ]);

        foreach ($rows as $row) {
            $firstname = trim((string) ($row['firstname'] ?? ''));
            $lastname = trim((string) ($row['lastname'] ?? ''));
            $displayName = trim($firstname . ' ' . $lastname) ?: (string) ($row['username'] ?? 'Student');

            fputcsv($output, [
                (string) ($room['room_code'] ?? ''),
                (string) ($room['room_name'] ?? ''),
                (string) ($room['challenge_name'] ?? ''),
                $displayName,
                (string) ($row['username'] ?? ''),
                (string) ($row['email'] ?? ''),
                (string) ($row['student_number'] ?? ''),
                $statusLabel($row),
                (string) ($row['started_at'] ?? ''),
                (string) ($row['completed_at'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher room session export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export room session records right now.';
        exit;
    }
}
