<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'edit-room') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Session expired. Please try updating the room again.',
            ];
            teacherPanelRedirect('rooms');
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        if ($teacherId <= 0) {
            teacherPanelRootRedirect('login');
        }

        $roomRepo = teacherPanelRequireRoomRepository($roomRepository ?? null);
        $challengeRepo = teacherPanelRequireChallengeRepository($challengeRepository ?? null);
        $activityLogs = teacherPanelRequireActivityLogRepository($activityLogRepository ?? null);

        $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
        $challengeId = max(0, (int) ($_POST['challenge_id'] ?? 0));
        $roomName = trim((string) ($_POST['room_name'] ?? ''));
        $roomDescription = trim((string) ($_POST['room_description'] ?? ''));
        $timerLimit = max(0, (int) ($_POST['timer_limit'] ?? 0));
        $strictMode = (int) ($_POST['strict_mode'] ?? 0) === 1 ? 1 : 0;

        $_SESSION['teacher_rooms_old'] = [
            'room_id' => $roomId,
            'challenge_id' => $challengeId,
            'room_name' => $roomName,
            'room_description' => $roomDescription,
            'timer_limit' => $timerLimit,
            'strict_mode' => $strictMode,
        ];

        if ($roomId <= 0) {
            throw new InvalidArgumentException('Room could not be identified.');
        }

        $existingRoom = $roomRepo->findByIdForOwner($roomId, $teacherId);
        if ($existingRoom === null) {
            throw new InvalidArgumentException('The selected room is unavailable.');
        }

        if ($challengeId <= 0) {
            throw new InvalidArgumentException('Select a challenge for the room.');
        }

        $selectedChallenge = $challengeRepo->findCreatedChallengeForOwner($challengeId, $teacherId);
        if ($selectedChallenge === null) {
            throw new InvalidArgumentException('The selected challenge is unavailable.');
        }

        if ($roomName === '') {
            throw new InvalidArgumentException('Enter a room name.');
        }

        if (strlen($roomName) > 150) {
            throw new InvalidArgumentException('Room name must be 150 characters or fewer.');
        }

        if ($roomDescription === '') {
            throw new InvalidArgumentException('Enter a room description.');
        }

        if (strlen($roomDescription) > 255) {
            throw new InvalidArgumentException('Room description must be 255 characters or fewer.');
        }

        if ($timerLimit < 0) {
            throw new InvalidArgumentException('Timer limit must be zero or greater.');
        }

        $roomRepo->updateForOwner(
            $roomId,
            $teacherId,
            $challengeId,
            $roomName,
            $roomDescription,
            $timerLimit,
            $strictMode
        );

        teacherPanelLogActivity(
            $activityLogs,
            $teacherId,
            'room',
            'Updated room "' . $roomName . '".'
        );

        unset($_SESSION['teacher_rooms_old']);
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Room updated successfully.',
        ];

        header('Location: ./?c=room-view&id=' . $roomId);
        exit;
    } catch (InvalidArgumentException $err) {
        $_SESSION['alert'] = [
            'error' => true,
            'content' => $err->getMessage(),
        ];
        $targetRoomId = max(0, (int) ($_POST['room_id'] ?? 0));
        header('Location: ./?c=edit-room&id=' . $targetRoomId);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher room update error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Room update failed. Please try again.',
        ];
        $targetRoomId = max(0, (int) ($_POST['room_id'] ?? 0));
        header('Location: ./?c=edit-room&id=' . $targetRoomId);
        exit;
    }
}
