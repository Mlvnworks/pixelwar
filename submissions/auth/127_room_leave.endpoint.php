<?php

if ($requestMethod === 'POST' && $requestedPage === 'room' && (string) ($_POST['room_action'] ?? '') === 'leave_room') {
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
        if ($room === null || trim((string) ($room['started_at'] ?? '')) !== '') {
            http_response_code(204);
            exit;
        }

        $deleted = $roomPlayerRepository->deleteByUserAndRoom($userId, $roomId);
        http_response_code(204);

        if ($deleted && isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
            try {
                $pusherService->trigger(
                    'room-' . $roomId,
                    'player-left',
                    [
                        'room_id' => $roomId,
                        'user_id' => $userId,
                    ]
                );
            } catch (Throwable $pusherError) {
                error_log('Pixelwar room leave pusher error: ' . $pusherError->getMessage());
            }
        }
    } catch (Throwable $err) {
        error_log('Pixelwar room leave error: ' . $err->getMessage());
    }

    exit;
}
