<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'room-session' && (string) ($_POST['room_action'] ?? '') === 'cleanup_presence') {
    if (!teacherPanelValidateCsrf()) {
        http_response_code(403);
        exit;
    }

    $teacherId = (int) ($_SESSION['user_id'] ?? 0);
    $roomId = max(0, (int) ($_POST['room_id'] ?? 0));

    if (
        $teacherId <= 0
        || $roomId <= 0
        || !$roomRepository instanceof RoomRepository
        || !$roomPlayerRepository instanceof RoomPlayerRepository
    ) {
        http_response_code(204);
        exit;
    }

    try {
        $room = $roomRepository->findByIdForOwner($roomId, $teacherId);
        if ($room === null || trim((string) ($room['started_at'] ?? '')) !== '') {
            http_response_code(204);
            exit;
        }

        $removedUserIds = $roomPlayerRepository->deleteInactiveWaitingForRoom($roomId, 8);
        if ($removedUserIds !== [] && isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
            foreach ($removedUserIds as $removedUserId) {
                try {
                    $pusherService->trigger(
                        'room-' . $roomId,
                        'player-left',
                        [
                            'room_id' => $roomId,
                            'user_id' => $removedUserId,
                        ]
                    );
                } catch (Throwable $pusherError) {
                    error_log('Pixelwar teacher cleanup pusher error: ' . $pusherError->getMessage());
                }
            }
        }
    } catch (Throwable $err) {
        error_log('Pixelwar teacher room presence cleanup error: ' . $err->getMessage());
    }

    http_response_code(204);
    exit;
}
