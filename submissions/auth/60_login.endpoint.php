<?php
if ($requestMethod === 'POST' && $requestedPage === 'login') {
    try {
        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf('login');
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];

        $_SESSION['login_old'] = [
            'identity' => $identity,
        ];

        if ($identity === '') {
            $errors[] = 'Enter your username or email.';
        }

        if ($password === '') {
            $errors[] = 'Enter your password.';
        }

        $user = $errors === [] ? pixelwarFindLoginUser($users, $identity) : null;
        $deletedUser = $errors === [] && $user === null ? $users->findDeletedLoginUser($identity) : null;

        if ($errors === [] && $deletedUser !== null) {
            $errors[] = 'You no longer have access to this account. If you think this is a mistake, please contact your admin or instructor.';
        }

        if ($errors === [] && (!$user || !password_verify($password, (string) $user['password']))) {
            $errors[] = 'Login details do not match our records.';
        }

        if ($errors !== []) {
            $_SESSION['login_errors'] = $errors;
            pixelwarRedirect('login');
        }

        unset($_SESSION['login_old'], $_SESSION['login_errors']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['role_id'] = (int) ($user['role_id'] ?? 0);
        $_SESSION['username'] = (string) $user['username'];
        $_SESSION['email'] = (string) $user['email'];
        $_SESSION['is_verified'] = (int) ($user['is_verified'] ?? 0);
        pixelwarLogActivity($activityLogRepository ?? null, (int) $user['user_id'], 'auth', 'Logged in.');

        if ((int) ($user['role_id'] ?? 0) === pixelwarAdminRoleId()) {
            if (pixelwarAdminNeedsSetup($users, $user)) {
                pixelwarRedirect('profile-setup');
            }

            if ((int) ($user['is_verified'] ?? 0) !== 1) {
                pixelwarPrepareAccountVerification(
                    pixelwarRequireVerificationRepository($verificationRepository),
                    $tools,
                    (int) $user['user_id'],
                    (string) $user['email'],
                    (string) $user['username']
                );
                pixelwarRedirect('email-verification');
            }

            pixelwarRedirectToRoleHome($user);
        }

        if ((int) ($user['role_id'] ?? 0) === pixelwarTeacherRoleId()) {
            if ((int) ($user['is_verified'] ?? 0) !== 1) {
                pixelwarPrepareAccountVerification(
                    pixelwarRequireVerificationRepository($verificationRepository),
                    $tools,
                    (int) $user['user_id'],
                    (string) $user['email'],
                    (string) $user['username']
                );
                pixelwarRedirect('email-verification');
            }

            if (pixelwarTeacherNeedsSetup($users, $user)) {
                pixelwarRedirect('profile-setup');
            }

            pixelwarRedirectToRoleHome($user);
        }

        if ((int) ($user['role_id'] ?? 0) !== pixelwarStudentRoleId()) {
            pixelwarRedirectToRoleHome($user);
        }

        if ((int) $user['is_verified'] !== 1) {
            pixelwarPrepareAccountVerification(pixelwarRequireVerificationRepository($verificationRepository), $tools, (int) $user['user_id'], (string) $user['email'], (string) $user['username']);
            pixelwarRedirect('email-verification');
        }

        if (!pixelwarUserDetailsExist($users, (int) $user['user_id'])) {
            pixelwarRedirect('profile-setup');
        }

        pixelwarRedirectToRoleHome($user);
    } catch (Throwable $err) {
        error_log('Pixelwar login error: ' . $err->getMessage());
        $_SESSION['login_errors'] = ['Login failed. Please try again.'];
        pixelwarRedirect('login');
    }
}
