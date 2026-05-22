<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'room-session' && (string) ($_POST['room_action'] ?? '') === 'end_session') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Session expired. Please try ending the room again.',
            ];
            header('Location: ./?c=rooms');
            exit;
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        if ($teacherId <= 0) {
            teacherPanelRootRedirect('login');
        }

        $roomRepo = teacherPanelRequireRoomRepository($roomRepository ?? null);
        $roomPlayers = isset($roomPlayerRepository) && $roomPlayerRepository instanceof RoomPlayerRepository
            ? $roomPlayerRepository
            : null;
        $activityLogs = teacherPanelRequireActivityLogRepository($activityLogRepository ?? null);

        $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
        if ($roomId <= 0) {
            throw new InvalidArgumentException('Room could not be identified.');
        }

        $room = $roomRepo->findByIdForOwner($roomId, $teacherId);
        if ($room === null) {
            throw new InvalidArgumentException('The selected room is unavailable.');
        }

        if (trim((string) ($room['started_at'] ?? '')) === '') {
            throw new InvalidArgumentException('Start the room first before ending it.');
        }

        if (trim((string) ($room['ended_at'] ?? '')) !== '') {
            throw new InvalidArgumentException('This room is already ended.');
        }

        $roomRepo->markEndedForOwner($roomId, $teacherId);
        if ($roomPlayers instanceof RoomPlayerRepository) {
            $roomPlayers->markUnfinishedAsGaveUpForRoom($roomId);
        }
        $refreshedRoom = $roomRepo->findByIdForOwner($roomId, $teacherId) ?? $room;

        teacherPanelLogActivity(
            $activityLogs,
            $teacherId,
            'room',
            'Ended room session "' . (string) ($room['room_name'] ?? 'Untitled Room') . '".'
        );

        if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
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
                error_log('Pixelwar teacher room session end pusher error: ' . $pusherError->getMessage());
            }
        }

        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Room ended.',
        ];

        header('Location: ./?c=room-session&id=' . $roomId);
        exit;
    } catch (InvalidArgumentException $err) {
        $_SESSION['alert'] = [
            'error' => true,
            'content' => $err->getMessage(),
        ];
        $targetRoomId = max(0, (int) ($_POST['room_id'] ?? 0));
        header('Location: ./?c=room-session&id=' . $targetRoomId);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher room session end error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Room could not be ended right now.',
        ];
        $targetRoomId = max(0, (int) ($_POST['room_id'] ?? 0));
        header('Location: ./?c=room-session&id=' . $targetRoomId);
        exit;
    }
}
