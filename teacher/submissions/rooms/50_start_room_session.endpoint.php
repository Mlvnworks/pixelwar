<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'room-session' && (string) ($_POST['room_action'] ?? '') === 'start_session') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Session expired. Please try starting the room again.',
            ];
            header('Location: ./?c=rooms');
            exit;
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        if ($teacherId <= 0) {
            teacherPanelRootRedirect('login');
        }

        $roomRepo = teacherPanelRequireRoomRepository($roomRepository ?? null);
        $activityLogs = teacherPanelRequireActivityLogRepository($activityLogRepository ?? null);

        $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
        if ($roomId <= 0) {
            throw new InvalidArgumentException('Room could not be identified.');
        }

        $room = $roomRepo->findByIdForOwner($roomId, $teacherId);
        if ($room === null) {
            throw new InvalidArgumentException('The selected room is unavailable.');
        }

        if ((int) ($room['status'] ?? 1) !== 1) {
            throw new InvalidArgumentException('Open the room first before starting the session.');
        }

        $roomRepo->markStartedForOwner($roomId, $teacherId);
        $refreshedRoom = $roomRepo->findByIdForOwner($roomId, $teacherId) ?? $room;

        teacherPanelLogActivity(
            $activityLogs,
            $teacherId,
            'room',
            'Started room session "' . (string) ($room['room_name'] ?? 'Untitled Room') . '".'
        );

        if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
            try {
                $pusherService->trigger(
                    'room-' . $roomId,
                    'session-started',
                    [
                        'room_id' => $roomId,
                        'challenge_id' => (int) ($refreshedRoom['challenge_id'] ?? 0),
                        'started_at' => (string) ($refreshedRoom['started_at'] ?? ''),
                        'timer_limit' => (int) ($refreshedRoom['timer_limit'] ?? 0),
                        'redirect_url' => APP_URL . '/?c=pixelwar&challenge_id=' . (int) ($refreshedRoom['challenge_id'] ?? 0) . '&room_id=' . $roomId,
                    ]
                );
            } catch (Throwable $pusherError) {
                error_log('Pixelwar teacher room session start pusher error: ' . $pusherError->getMessage());
            }
        }

        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Room session started.',
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
        error_log('Pixelwar teacher room session start error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Room session could not be started right now.',
        ];
        $targetRoomId = max(0, (int) ($_POST['room_id'] ?? 0));
        header('Location: ./?c=room-session&id=' . $targetRoomId);
        exit;
    }
}
