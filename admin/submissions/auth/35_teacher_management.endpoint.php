<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'teachers' && (string) ($_POST['admin_action'] ?? '') !== 'create_teacher') {
    try {
        if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) ($_POST['_csrf_token'] ?? ''))) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('teachers');
        }

        $action = trim((string) ($_POST['teacher_action'] ?? ''));
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $adminPassword = (string) ($_POST['admin_password'] ?? '');
        $users = adminPanelRequireUserRepository($userRepository);
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository);

        if ($teacherId <= 0) {
            throw new RuntimeException('Teacher record is missing.');
        }

        if ($adminPassword === '') {
            throw new RuntimeException('Enter your admin password to confirm this action.');
        }

        $adminUser = $users->findAuthUserById((int) ($_SESSION['user_id'] ?? 0));

        if ($adminUser === null || (int) ($adminUser['role_id'] ?? 0) !== 1) {
            throw new RuntimeException('Admin session could not be confirmed.');
        }

        if (!password_verify($adminPassword, (string) ($adminUser['password'] ?? ''))) {
            throw new RuntimeException('Admin password is incorrect.');
        }

        $teacher = $users->findSessionUser($teacherId);

        if ($teacher === null || (int) ($teacher['role_id'] ?? 0) !== 2) {
            throw new RuntimeException('Teacher account was not found.');
        }

        $teacherLabel = trim((string) ($teacher['firstname'] ?? '') . ' ' . (string) ($teacher['lastname'] ?? ''));
        $teacherLabel = $teacherLabel !== '' ? $teacherLabel : ((string) ($teacher['username'] ?? ('Teacher #' . $teacherId)));

        if ($action === 'verify') {
            $users->updateUserVerifiedState($teacherId, 1);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'teacher', 'Verified teacher account "' . $teacherLabel . '" from admin teachers.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Teacher account verified successfully.',
            ];
        } elseif ($action === 'unverify') {
            $users->updateUserVerifiedState($teacherId, 0);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'teacher', 'Marked teacher account "' . $teacherLabel . '" as pending from admin teachers.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Teacher account moved back to pending setup.',
            ];
        } elseif ($action === 'delete') {
            $users->softDeleteUser($teacherId);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'teacher', 'Deleted teacher account "' . $teacherLabel . '" from admin teachers.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Teacher account deleted successfully.',
            ];
        } elseif ($action === 'edit') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $firstname = trim((string) ($_POST['firstname'] ?? ''));
            $lastname = trim((string) ($_POST['lastname'] ?? ''));
            $errors = [];

            if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
                $errors[] = 'Username must be 3-32 characters and only use letters, numbers, or underscores.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid teacher email address.';
            }

            if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $firstname)) {
                $errors[] = 'Enter a valid first name.';
            }

            if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $lastname)) {
                $errors[] = 'Enter a valid last name.';
            }

            if ($users->usernameExistsForOtherUser($username, $teacherId)) {
                $errors[] = 'Username is already taken.';
            }

            if ($users->emailExistsForOtherUser($email, $teacherId)) {
                $errors[] = 'Email is already registered.';
            }

            if ($errors !== []) {
                throw new RuntimeException(implode(' ', $errors));
            }

            $users->updateTeacherAccount($teacherId, $username, $email, $firstname, $lastname);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'teacher', 'Updated teacher account "' . $teacherLabel . '" from admin teachers.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Teacher account updated successfully.',
            ];
        } else {
            throw new RuntimeException('Teacher action is not supported.');
        }
    } catch (Throwable $err) {
        error_log('Pixelwar admin teacher management error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update the teacher account right now.',
        ];
    }

    adminPanelRedirect('teachers');
}
