<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'teachers' && (string) ($_POST['admin_action'] ?? '') === 'create_teacher') {
    try {
        if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token']) || !hash_equals($_SESSION['_csrf_token'], (string) ($_POST['_csrf_token'] ?? ''))) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('teachers');
        }

        $users = adminPanelRequireUserRepository($userRepository);
        $teachers = adminPanelRequireTeacherAccountService($teacherAccountService);
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $errors = [];

        $_SESSION['admin_teacher_create_old'] = [
            'username' => $username,
            'email' => $email,
        ];

        if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
            $errors[] = 'Username must be 3-32 characters and only use letters, numbers, or underscores.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid teacher email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($errors === [] && ($conflict = adminPanelFindSignupConflict($users, $username, $email)) !== null) {
            $errors[] = $conflict;
        }

        if ($errors !== []) {
            $_SESSION['admin_teacher_create_errors'] = $errors;
            adminPanelRedirect('teachers');
        }

        $teachers->createPendingTeacher($username, $email, $password);
        $adminUserId = (int) ($_SESSION['user_id'] ?? 0);
        $activityLogs = adminPanelRequireActivityLogRepository($activityLogRepository ?? null);
        $activityLogs->create($adminUserId, 'admin', 'Created teacher account "' . $username . '".');

        unset($_SESSION['admin_teacher_create_old'], $_SESSION['admin_teacher_create_errors']);
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Teacher account created. Temporary login credentials were sent by email.',
        ];
        adminPanelRedirect('teachers');
    } catch (Throwable $err) {
        error_log('Pixelwar admin teacher create error: ' . $err->getMessage());
        $_SESSION['admin_teacher_create_errors'] = [APP_DEBUG ? $err->getMessage() : 'Teacher account creation failed. Please try again.'];
        adminPanelRedirect('teachers');
    }
}
