<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'room-view' && (string) ($_POST['room_action'] ?? '') === 'toggle_status') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Session expired. Please try changing the room state again.',
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
        $nextStatus = (int) ($_POST['next_status'] ?? 1) === 0 ? 0 : 1;
        if ($roomId <= 0) {
            throw new InvalidArgumentException('Room could not be identified.');
        }

        $room = $roomRepo->findByIdForOwner($roomId, $teacherId);
        if ($room === null) {
            throw new InvalidArgumentException('The selected room is unavailable.');
        }

        $roomRepo->updateStatusForOwner($roomId, $teacherId, $nextStatus);

        teacherPanelLogActivity(
            $activityLogs,
            $teacherId,
            'room',
            ($nextStatus === 1 ? 'Opened room "' : 'Closed room "')
            . (string) ($room['room_name'] ?? 'Untitled Room')
            . '".'
        );

        $_SESSION['alert'] = [
            'error' => false,
            'content' => $nextStatus === 1 ? 'Room opened successfully.' : 'Room closed successfully.',
        ];

        header('Location: ./?c=room-view&id=' . $roomId);
        exit;
    } catch (InvalidArgumentException $err) {
        $_SESSION['alert'] = [
            'error' => true,
            'content' => $err->getMessage(),
        ];
        $targetRoomId = max(0, (int) ($_POST['room_id'] ?? 0));
        header('Location: ./?c=room-view&id=' . $targetRoomId);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher room status toggle error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Room state could not be updated right now.',
        ];
        $targetRoomId = max(0, (int) ($_POST['room_id'] ?? 0));
        header('Location: ./?c=room-view&id=' . $targetRoomId);
        exit;
    }
}
