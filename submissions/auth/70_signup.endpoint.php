<?php
if ($requestMethod === 'POST' && $requestedPage === 'signup') {
    try {
        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf('signup');
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $accounts = pixelwarRequireUserAccountService($userAccountService);
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $errors = [];

        $_SESSION['signup_old'] = [
            'username' => $username,
            'email' => $email,
        ];

        if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
            $errors[] = 'Username must be 3-32 characters and only use letters, numbers, or underscores.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($errors === [] && ($conflict = pixelwarFindSignupConflict($users, $username, $email)) !== null) {
            $errors[] = $conflict;
        }

        if ($errors !== []) {
            $_SESSION['signup_errors'] = $errors;
            pixelwarRedirect('signup');
        }

        $token = (string) random_int(100000, 999999);
        $userId = $accounts->createStudentWithVerification(
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            pixelwarHashVerificationToken($token)
        );

        unset($_SESSION['signup_old'], $_SESSION['signup_errors']);
        $_SESSION['pending_verification_user_id'] = $userId;
        $_SESSION['pending_verification_email'] = $email;
        $_SESSION['pending_verification_token'] = $token;
        $_SESSION['pending_verification_mail_sent'] = pixelwarSendVerificationToken($tools, $email, $username, $token);
        pixelwarResetVerificationAttempts($userId);

        pixelwarRedirect('email-verification');
    } catch (Throwable $err) {
        error_log('Pixelwar signup error: ' . $err->getMessage());
        $_SESSION['signup_errors'] = ['Signup failed. Please check the form and try again.'];
        pixelwarRedirect('signup');
    }
}
