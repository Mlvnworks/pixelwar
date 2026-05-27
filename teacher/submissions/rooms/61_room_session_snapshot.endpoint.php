<?php

if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'room-session' && (string) ($_POST['room_action'] ?? '') === 'sync_presence') {
    if (!teacherPanelValidateCsrf()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false]);
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
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => true, 'players' => []]);
        exit;
    }

    try {
        $room = $roomRepository->findByIdForOwner($roomId, $teacherId);
        if ($room === null) {
            http_response_code(200);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => true, 'players' => []]);
            exit;
        }

        $roomEnded = trim((string) ($room['ended_at'] ?? '')) !== '';
        $strictModeEnabled = (int) ($room['strict_mode'] ?? 0) === 1;
        $players = [];
        foreach ($roomPlayerRepository->listJoinedForRoom($roomId) as $player) {
            $displayName = trim((string) ($player['firstname'] ?? '') . ' ' . (string) ($player['lastname'] ?? ''))
                ?: trim((string) ($player['username'] ?? 'Student'))
                ?: 'Student';
            $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2));
            $completedAt = trim((string) ($player['completed_at'] ?? ''));
            $startedAt = trim((string) ($player['started_at'] ?? ''));
            $status = (int) ($player['status'] ?? 0);

            if ($roomEnded && $completedAt === '' && $status !== 2) {
                $statusLabel = 'failed';
            } elseif ($status === 3) {
                $statusLabel = 'failed';
            } elseif ($completedAt !== '' || $status === 2) {
                $statusLabel = 'completed';
            } elseif ($startedAt !== '' || $status === 1) {
                $statusLabel = 'solving';
            } else {
                $statusLabel = 'waiting';
            }

            $strictModeScore = max(0, min(100, (int) ($player['strict_mode_score'] ?? 0)));

            if ($startedAt !== '' && $completedAt !== '') {
                $startedTs = strtotime($startedAt);
                $completedTs = strtotime($completedAt);
                if ($startedTs !== false && $completedTs !== false && $completedTs >= $startedTs) {
                    $remaining = $completedTs - $startedTs;
                    $hours = (int) floor($remaining / 3600);
                    $remaining -= $hours * 3600;
                    $minutes = (int) floor($remaining / 60);
                    $seconds = $remaining % 60;

                    if ($hours > 0) {
                        $durationLabel = sprintf('%dh %02dm', $hours, $minutes);
                    } elseif ($minutes > 0) {
                        $durationLabel = sprintf('%dm %ds', $minutes, $seconds);
                    } else {
                        $durationLabel = sprintf('%ds', $seconds);
                    }
                } else {
                    $durationLabel = $statusLabel;
                }
            } else {
                $durationLabel = $statusLabel;
            }

            if ($strictModeEnabled && in_array($status, [2, 3], true)) {
                $statusLabel = $strictModeScore . '%';
                $durationLabel = $completedAt !== '' && $startedAt !== ''
                    ? $durationLabel
                    : ($strictModeScore . '%');
            }

            $players[] = [
                'rp_id' => (int) ($player['rp_id'] ?? 0),
                'user_id' => (int) ($player['user_id'] ?? 0),
                'name' => $displayName,
                'username' => (string) ($player['username'] ?? ''),
                'email' => (string) ($player['email'] ?? ''),
                'student_number' => (string) ($player['student_number'] ?? ''),
                'avatar_url' => (string) ($player['avatar_url'] ?? ''),
                'initials' => $initials,
                'status_label' => $statusLabel,
                'duration_label' => $durationLabel,
                'started_at' => (string) ($player['started_at'] ?? ''),
                'completed_at' => (string) ($player['completed_at'] ?? ''),
                'strict_mode_score' => $strictModeScore,
            ];
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'room_started' => trim((string) ($room['started_at'] ?? '')) !== '',
            'room_ended' => $roomEnded,
            'ended_at' => (string) ($room['ended_at'] ?? ''),
            'players' => $players,
        ]);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar teacher room session snapshot error: ' . $err->getMessage());
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'players' => []]);
        exit;
    }
}
