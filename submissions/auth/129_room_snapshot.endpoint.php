<?php

if ($requestMethod === 'POST' && $requestedPage === 'room' && (string) ($_POST['room_action'] ?? '') === 'sync_room_presence') {
    if (!pixelwarValidateCsrf()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'players' => []]);
        exit;
    }

    $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

    if (
        $roomId <= 0
        || !$roomRepository instanceof RoomRepository
        || !$roomPlayerRepository instanceof RoomPlayerRepository
    ) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => true, 'players' => [], 'room_started' => false, 'redirect_url' => '']);
        exit;
    }

    try {
        $room = $roomRepository->findById($roomId);
        if ($room === null) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => true, 'players' => [], 'room_started' => false, 'redirect_url' => '']);
            exit;
        }

        $players = [];
        foreach ($roomPlayerRepository->listJoinedForRoom($roomId) as $player) {
            $displayName = trim((string) ($player['firstname'] ?? '') . ' ' . (string) ($player['lastname'] ?? ''))
                ?: trim((string) ($player['username'] ?? 'Player'))
                ?: 'Player';
            $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'PR', 0, 2));
            $statusValue = (int) ($player['status'] ?? 0);
            $startedAt = trim((string) ($player['started_at'] ?? ''));
            $completedAt = trim((string) ($player['completed_at'] ?? ''));

            if ($statusValue === 3) {
                $status = 'Gave Up';
            } elseif ($completedAt !== '' || $statusValue === 2) {
                $status = 'Completed';
            } elseif ($startedAt !== '' || $statusValue === 1) {
                $status = 'Solving';
            } else {
                $status = 'Waiting';
            }

            $playerUserId = (int) ($player['user_id'] ?? 0);
            $players[] = [
                'rp_id' => (int) ($player['rp_id'] ?? 0),
                'user_id' => $playerUserId,
                'name' => $displayName,
                'role' => $playerUserId === $currentUserId ? 'You' : 'Player',
                'rank' => 'Student',
                'status' => $status,
                'initials' => $initials,
                'avatar_url' => (string) ($player['avatar_url'] ?? ''),
                'accent' => $playerUserId === $currentUserId ? 'yellow' : 'mint',
            ];
        }

        $roomStarted = trim((string) ($room['started_at'] ?? '')) !== '';
        $redirectUrl = $roomStarted
            ? './?c=pixelwar&challenge_id=' . (int) ($room['challenge_id'] ?? 0) . '&room_id=' . $roomId
            : '';

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'players' => $players,
            'room_started' => $roomStarted,
            'redirect_url' => $redirectUrl,
        ]);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar room snapshot error: ' . $err->getMessage());
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'players' => [], 'room_started' => false, 'redirect_url' => '']);
        exit;
    }
}
