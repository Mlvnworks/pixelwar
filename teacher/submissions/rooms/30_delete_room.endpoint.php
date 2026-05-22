<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'rooms' && (string) ($_POST['room_action'] ?? '') === 'delete') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Session expired. Please try deleting the room again.',
            ];
            teacherPanelRedirect('rooms');
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

        $deleted = $roomRepo->softDeleteForOwner($roomId, $teacherId);
        if (!$deleted) {
            throw new RuntimeException('Room could not be deleted right now.');
        }

        teacherPanelLogActivity(
            $activityLogs,
            $teacherId,
            'room',
            'Deleted room "' . (string) ($room['room_name'] ?? 'Untitled Room') . '".'
        );

        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Room deleted successfully.',
        ];
        teacherPanelRedirect('rooms');
    } catch (InvalidArgumentException $err) {
        $_SESSION['alert'] = [
            'error' => true,
            'content' => $err->getMessage(),
        ];
        teacherPanelRedirect('rooms');
    } catch (Throwable $err) {
        error_log('Pixelwar teacher room delete error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Room delete failed. Please try again.',
        ];
        teacherPanelRedirect('rooms');
    }
}
