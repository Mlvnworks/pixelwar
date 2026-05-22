<?php
if (
    $adminRequestMethod === 'GET'
    && $adminRequestedPage === 'room-view'
    && isset($_GET['export'])
    && (string) $_GET['export'] === 'csv'
) {
    try {
        $roomId = max(0, (int) ($_GET['id'] ?? 0));
        $statusFilter = strtolower(trim((string) ($_GET['status'] ?? 'all')));
        $search = trim((string) ($_GET['search'] ?? ''));
        $searchNeedle = function_exists('mb_strtolower')
            ? mb_strtolower($search, 'UTF-8')
            : strtolower($search);

        if ($roomId <= 0) {
            throw new RuntimeException('A valid room is required before exporting.');
        }

        if (!in_array($statusFilter, ['all', 'waiting', 'solving', 'completed', 'failed'], true)) {
            $statusFilter = 'all';
        }

        if (
            !$roomRepository instanceof RoomRepository
            || !$roomPlayerRepository instanceof RoomPlayerRepository
        ) {
            throw new RuntimeException('Room records are not available.');
        }

        $room = $roomRepository->findById($roomId);
        if ($room === null) {
            throw new RuntimeException('The selected room could not be found.');
        }

        $roomEnded = trim((string) ($room['ended_at'] ?? '')) !== '';
        $rows = array_values(array_filter(
            $roomPlayerRepository->listJoinedForRoom($roomId),
            static function (array $row) use ($statusFilter, $searchNeedle, $roomEnded): bool {
                $statusLabel = strtolower(adminPanelRoomPlayerStatusLabel($row, $roomEnded));

                if ($statusFilter !== 'all' && $statusLabel !== $statusFilter) {
                    return false;
                }

                if ($searchNeedle === '') {
                    return true;
                }

                $fullName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? ''));
                $haystack = implode(' ', [
                    $fullName,
                    (string) ($row['username'] ?? ''),
                    (string) ($row['email'] ?? ''),
                    (string) ($row['student_number'] ?? ''),
                    $statusLabel,
                ]);
                $haystack = function_exists('mb_strtolower')
                    ? mb_strtolower($haystack, 'UTF-8')
                    : strtolower($haystack);

                return str_contains($haystack, $searchNeedle);
            }
        ));

        $fileName = sprintf(
            'room-players-%d-%s.csv',
            $roomId,
            date('Y-m-d')
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

        fputcsv($output, ['Record ID', 'Player', 'Username', 'Email', 'Student ID', 'Status', 'Started At', 'Completed At']);

        foreach ($rows as $row) {
            $fullName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? ''));
            $fullName = $fullName !== '' ? $fullName : (string) ($row['username'] ?? 'Student');

            fputcsv($output, [
                (int) ($row['rp_id'] ?? 0),
                $fullName,
                (string) ($row['username'] ?? ''),
                (string) ($row['email'] ?? ''),
                (string) ($row['student_number'] ?? ''),
                adminPanelRoomPlayerStatusLabel($row, $roomEnded),
                (string) ($row['started_at'] ?? ''),
                (string) ($row['completed_at'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar admin room view export error: ' . $err->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(422);
        header('Content-Type: text/plain; charset=UTF-8');
        echo APP_DEBUG ? $err->getMessage() : 'Unable to export room player records right now.';
        exit;
    }
}
