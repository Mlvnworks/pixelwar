<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'room-session' && (string) ($_POST['room_action'] ?? '') === 'grade_hard_code') {
    header('Content-Type: application/json; charset=UTF-8');

    try {
        if (!teacherPanelValidateCsrf()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Session expired. Refresh the page and try again.']);
            exit;
        }

        $teacherId = (int) ($_SESSION['user_id'] ?? 0);
        $roomId = max(0, (int) ($_POST['room_id'] ?? 0));
        $roomPlayerId = max(0, (int) ($_POST['rp_id'] ?? 0));
        $gradeInput = trim((string) ($_POST['grade'] ?? ''));

        if ($teacherId <= 0 || $roomId <= 0 || $roomPlayerId <= 0) {
            throw new InvalidArgumentException('The submitted design could not be identified.');
        }

        if ($gradeInput === '' || filter_var($gradeInput, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('Enter a non-negative whole-number grade.');
        }

        $grade = (int) $gradeInput;
        if ($grade < 0) {
            throw new InvalidArgumentException('Grade cannot be negative.');
        }

        if ($grade > 2147483647) {
            throw new InvalidArgumentException('The grade is too large.');
        }

        $rooms = teacherPanelRequireRoomRepository($roomRepository ?? null);
        if (!$roomPlayerRepository instanceof RoomPlayerRepository) {
            throw new RuntimeException('Room player records are unavailable.');
        }

        $room = $rooms->findByIdForOwner($roomId, $teacherId);
        if ($room === null) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'You cannot grade submissions in this room.']);
            exit;
        }

        if ((int) ($room['mode'] ?? 0) !== 3) {
            throw new InvalidArgumentException('Grades can only be added to Hard Code submissions.');
        }

        if (!$roomPlayerRepository->updateHardCodeGrade($roomPlayerId, $roomId, $grade)) {
            throw new InvalidArgumentException('The Hard Code submission is unavailable.');
        }

        echo json_encode(['ok' => true, 'rp_id' => $roomPlayerId, 'grade' => $grade]);
        exit;
    } catch (InvalidArgumentException $error) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
        exit;
    } catch (Throwable $error) {
        error_log('Pixelwar teacher grade hard code error: ' . $error->getMessage());
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'message' => APP_DEBUG ? $error->getMessage() : 'The grade could not be saved right now.',
        ]);
        exit;
    }
}
