<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'room-session' && (string) ($_POST['room_action'] ?? '') === 'remove_player') {
    header('Content-Type: application/json; charset=UTF-8');

    try {
        if (!teacherPanelValidateCsrf()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Session expired. Refresh the page and try again.']);
            exit;
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
        $playerUserId = max(0, (int) ($_POST['player_user_id'] ?? 0));

        if ($teacherId <= 0 || $roomId <= 0 || $playerUserId <= 0) {
            throw new InvalidArgumentException('The room player could not be identified.');
        }

        $rooms = teacherPanelRequireRoomRepository($roomRepository ?? null);
        if (!$roomPlayerRepository instanceof RoomPlayerRepository) {
            throw new RuntimeException('Room player records are unavailable.');
        }

        $room = $rooms->findByIdForOwner($roomId, $teacherId);
        if ($room === null) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'You cannot manage this room.']);
            exit;
        }

        if (trim((string) ($room['ended_at'] ?? '')) !== '') {
            throw new InvalidArgumentException('Players cannot be removed after the room has ended.');
        }

        if (!$roomPlayerRepository->deleteByUserAndRoom($playerUserId, $roomId)) {
            throw new InvalidArgumentException('The player is no longer in this room.');
        }

        $redirectUrl = teacherPanelAppUrl('?c=home&room_notice=removed');
        if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
            try {
                $pusherService->trigger(
                    'room-' . $roomId,
                    'player-removed',
                    [
                        'room_id' => $roomId,
                        'user_id' => $playerUserId,
                        'redirect_url' => $redirectUrl,
                        'message' => 'You were removed from the room by the teacher.',
                    ]
                );
            } catch (Throwable $pusherError) {
                error_log('Pixelwar teacher remove room player pusher error: ' . $pusherError->getMessage());
            }
        }

        echo json_encode(['ok' => true, 'user_id' => $playerUserId]);
        exit;
    } catch (InvalidArgumentException $error) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
        exit;
    } catch (Throwable $error) {
        error_log('Pixelwar teacher remove room player error: ' . $error->getMessage());
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'message' => APP_DEBUG ? $error->getMessage() : 'The player could not be removed right now.',
        ]);
        exit;
    }
}
