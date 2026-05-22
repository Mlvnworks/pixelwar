<?php

if ($requestMethod === 'POST' && $requestedPage === 'room' && (string) ($_POST['room_action'] ?? '') === 'heartbeat') {
    if (!pixelwarValidateCsrf()) {
        http_response_code(403);
        exit;
    }

    $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $roleId = (int) ($_SESSION['role_id'] ?? 0);

    if (
        $roomId <= 0
        || $userId <= 0
        || $roleId !== pixelwarStudentRoleId()
        || !$roomRepository instanceof RoomRepository
        || !$roomPlayerRepository instanceof RoomPlayerRepository
    ) {
        http_response_code(204);
        exit;
    }

    try {
        $room = $roomRepository->findById($roomId);
        if ($room !== null && trim((string) ($room['started_at'] ?? '')) === '') {
            $roomPlayerRepository->touchPresence($userId, $roomId);
        }
    } catch (Throwable $err) {
        error_log('Pixelwar room heartbeat error: ' . $err->getMessage());
    }

    http_response_code(204);
    exit;
}
