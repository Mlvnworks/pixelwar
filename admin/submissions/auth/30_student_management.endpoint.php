<?php

if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'students') {
    try {
        if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), (string) ($_POST['_csrf_token'] ?? ''))) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('students');
        }

        $action = trim((string) ($_POST['student_action'] ?? ''));
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $adminPassword = (string) ($_POST['admin_password'] ?? '');
        $users = adminPanelRequireUserRepository($userRepository);
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository);

        if ($studentId <= 0) {
            throw new RuntimeException('Student record is missing.');
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

        $student = $users->findSessionUser($studentId);

        if ($student === null || (int) ($student['role_id'] ?? 0) !== 3) {
            throw new RuntimeException('Student account was not found.');
        }

        $studentLabel = trim((string) ($student['firstname'] ?? '') . ' ' . (string) ($student['lastname'] ?? ''));
        $studentLabel = $studentLabel !== '' ? $studentLabel : ((string) ($student['username'] ?? ('Student #' . $studentId)));

        if ($action === 'verify') {
            $users->updateActiveState($studentId, 1);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'student', 'Verified student account "' . $studentLabel . '" from admin students.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Student account verified successfully.',
            ];
        } elseif ($action === 'unverify') {
            $users->updateActiveState($studentId, 0);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'student', 'Marked student account "' . $studentLabel . '" as unverified from admin students.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Student account moved back to pending review.',
            ];
        } elseif ($action === 'delete') {
            $users->softDeleteUser($studentId);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'student', 'Deleted student account "' . $studentLabel . '" from admin students.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Student account deleted successfully.',
            ];
        } elseif ($action === 'edit') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $firstname = trim((string) ($_POST['firstname'] ?? ''));
            $lastname = trim((string) ($_POST['lastname'] ?? ''));
            $studentNumber = trim((string) ($_POST['student_number'] ?? ''));
            $errors = [];

            if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
                $errors[] = 'Username must be 3-32 characters and only use letters, numbers, or underscores.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid student email address.';
            }

            if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $firstname)) {
                $errors[] = 'Enter a valid first name.';
            }

            if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $lastname)) {
                $errors[] = 'Enter a valid last name.';
            }

            if ($studentNumber !== '' && !preg_match('/^[A-Za-z0-9-]{4,40}$/', $studentNumber)) {
                $errors[] = 'Enter a valid student number.';
            }

            if ($users->usernameExistsForOtherUser($username, $studentId)) {
                $errors[] = 'Username is already taken.';
            }

            if ($users->emailExistsForOtherUser($email, $studentId)) {
                $errors[] = 'Email is already registered.';
            }

            if ($errors !== []) {
                throw new RuntimeException(implode(' ', $errors));
            }

            $users->updateStudentAccount($studentId, $username, $email, $firstname, $lastname, $studentNumber !== '' ? $studentNumber : null);
            $logs->create((int) ($_SESSION['user_id'] ?? 0), 'student', 'Updated student account "' . $studentLabel . '" from admin students.');
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Student account updated successfully.',
            ];
        } else {
            throw new RuntimeException('Student action is not supported.');
        }
    } catch (Throwable $err) {
        error_log('Pixelwar admin student management error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update the student account right now.',
        ];
    }

    adminPanelRedirect('students');
}
