<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'announcement' && isset($_POST['announcement_action'])) {
    $allowedAnnouncementTypes = ['announcement_all', 'announcement_student', 'announcement_teacher'];
    $action = (string) ($_POST['announcement_action'] ?? '');

    try {
        if (!adminPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('announcement');
        }

        if (!$connection instanceof mysqli) {
            throw new RuntimeException('Database connection is not available.');
        }

        $adminUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($adminUserId <= 0) {
            throw new RuntimeException('Admin session is missing.');
        }

        if (!in_array($action, ['create', 'update', 'delete'], true)) {
            throw new RuntimeException('Announcement action is invalid.');
        }

        if ($action === 'delete') {
            $notificationId = (int) ($_POST['notif_id'] ?? 0);
            if ($notificationId <= 0) {
                throw new RuntimeException('Announcement record is missing.');
            }

            $statement = $connection->prepare(
                'DELETE FROM notifications
                 WHERE notif_id = ?
                    AND type IN (?, ?, ?)'
            );
            $typeAll = 'announcement_all';
            $typeStudent = 'announcement_student';
            $typeTeacher = 'announcement_teacher';
            $statement->bind_param('isss', $notificationId, $typeAll, $typeStudent, $typeTeacher);
            $statement->execute();
            $statement->close();

            if ($activityLogRepository instanceof ActivityLogRepository) {
                $activityLogRepository->create($adminUserId, 'announcement', 'Deleted an announcement.');
            }

            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Announcement deleted.',
            ];

            adminPanelRedirect('announcement');
        }

        $text = trim((string) ($_POST['text'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'announcement_all'));
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        if ($text === '') {
            throw new RuntimeException('Enter an announcement before saving.');
        }

        $textLength = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        if ($textLength > 2000) {
            throw new RuntimeException('Announcement must be 2000 characters or fewer.');
        }

        if (!in_array($type, $allowedAnnouncementTypes, true)) {
            throw new RuntimeException('Announcement audience is invalid.');
        }

        if ($action === 'create') {
            $statement = $connection->prepare(
                'INSERT INTO notifications (user_id, `text`, `type`)
                 VALUES (?, ?, ?)'
            );
            $statement->bind_param('iss', $adminUserId, $text, $type);
            $statement->execute();
            $statement->close();

            if ($activityLogRepository instanceof ActivityLogRepository) {
                $activityLogRepository->create($adminUserId, 'announcement', 'Posted an announcement.');
            }

            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Announcement posted.',
            ];

            adminPanelRedirect('announcement');
        }

        $notificationId = (int) ($_POST['notif_id'] ?? 0);
        if ($notificationId <= 0) {
            throw new RuntimeException('Announcement record is missing.');
        }

        $statement = $connection->prepare(
            'UPDATE notifications
             SET `text` = ?, `type` = ?
             WHERE notif_id = ?
                AND type IN (?, ?, ?)'
        );
        $typeAll = 'announcement_all';
        $typeStudent = 'announcement_student';
        $typeTeacher = 'announcement_teacher';
        $statement->bind_param('ssisss', $text, $type, $notificationId, $typeAll, $typeStudent, $typeTeacher);
        $statement->execute();
        $statement->close();

        if ($activityLogRepository instanceof ActivityLogRepository) {
            $activityLogRepository->create($adminUserId, 'announcement', 'Updated an announcement.');
        }

        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Announcement updated.',
        ];
    } catch (Throwable $err) {
        error_log('Pixelwar admin announcement error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update announcement right now.',
        ];
    }

    adminPanelRedirect('announcement');
}
