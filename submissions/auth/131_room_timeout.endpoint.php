<?php

if ($requestMethod === 'POST' && $requestedPage === 'pixelwar' && (string) ($_POST['gameplay_action'] ?? '') === 'end_room_timeout') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('home', $wantsJson);
    }

    $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    try {
        if (
            $roomId <= 0
            || $userId <= 0
            || !$roomRepository instanceof RoomRepository
            || !$roomPlayerRepository instanceof RoomPlayerRepository
        ) {
            throw new InvalidArgumentException('Room timeout request is invalid.');
        }

        $room = $roomRepository->findById($roomId);
        if ($room === null) {
            throw new RuntimeException('Room not found.');
        }

        $joined = $roomPlayerRepository->findByUserAndRoom($userId, $roomId);
        if ($joined === null && (int) ($room['user_id'] ?? 0) !== $userId) {
            throw new RuntimeException('You do not have access to this room.');
        }

        if (trim((string) ($room['started_at'] ?? '')) === '') {
            throw new RuntimeException('This room has not started yet.');
        }

        if (trim((string) ($room['ended_at'] ?? '')) !== '') {
            if ($wantsJson) {
                pixelwarJsonResponse([
                    'success' => true,
                    'message' => 'Room already ended.',
                    'ended' => true,
                ]);
            }
            exit;
        }

        if ((int) ($room['timer_limit'] ?? 0) <= 0) {
            throw new RuntimeException('This room does not use a timer.');
        }

        $ended = $roomRepository->markEndedIfExpired($roomId);
        if ($ended) {
            $roomPlayerRepository->markUnfinishedAsGaveUpForRoom($roomId);
        }
        $refreshedRoom = $roomRepository->findById($roomId) ?? $room;

        if ($ended && isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
            try {
                $pusherService->trigger(
                    'room-' . $roomId,
                    'session-ended',
                    [
                        'room_id' => $roomId,
                        'ended_at' => (string) ($refreshedRoom['ended_at'] ?? ''),
                        'redirect_url' => APP_URL . '/?c=home&room_notice=ended_incomplete',
                        'message' => 'The room was ended. Your challenge run was not completed.',
                    ]
                );
            } catch (Throwable $pusherError) {
                error_log('Pixelwar room timeout pusher error: ' . $pusherError->getMessage());
            }
        }

        if ($wantsJson) {
            pixelwarJsonResponse([
                'success' => true,
                'message' => $ended
                    ? 'The room was ended. Your challenge run was not completed.'
                    : 'Room is still active.',
                'ended' => $ended,
            ]);
        }

        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar room timeout error: ' . $err->getMessage());

        if ($wantsJson) {
            pixelwarJsonResponse([
                'success' => false,
                'message' => APP_DEBUG ? $err->getMessage() : 'Unable to end this room right now.',
            ], 422);
        }

        exit;
    }
}
