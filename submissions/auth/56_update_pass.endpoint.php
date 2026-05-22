<?php
if ($requestMethod === 'POST' && $requestedPage === 'update-pass') {
    try {
        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf('update-pass');
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $verifications = pixelwarRequireVerificationRepository($verificationRepository);
        $userId = max(0, (int) ($_POST['uid'] ?? 0));
        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $_SESSION['reset_password_old'] = ['uid' => $userId, 'token' => $token];

        $errors = [];

        if ($userId <= 0 || $token === '') {
            $errors[] = 'Password reset link is invalid. Request a new one.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        $user = $errors === [] ? $users->findAuthUserById($userId) : null;
        if ($errors === [] && !$user) {
            $errors[] = 'Password reset link is invalid. Request a new one.';
        }

        $verification = $errors === [] ? $verifications->findLatest($userId, 'password change') : null;
        if ($errors === [] && !$verification) {
            $errors[] = 'Password reset link is invalid. Request a new one.';
        }

        if ($errors === [] && $verification) {
            $verificationStatus = (int) ($verification['status'] ?? 0);
            $requestedAt = strtotime((string) ($verification['request_timestamp'] ?? ''));
            $expiresAt = $requestedAt === false ? 0 : $requestedAt + (20 * 60);

            if ($verificationStatus === 1) {
                $errors[] = 'This password reset link was already used.';
            } elseif ($verificationStatus === -1 || $expiresAt < time()) {
                if ($verificationStatus === 0) {
                    $verifications->updateStatus((int) $verification['ev_id'], -1);
                }
                $errors[] = 'This password reset link has expired. Request a new one.';
            } elseif (!pixelwarVerificationTokenMatches((string) ($verification['token'] ?? ''), $token)) {
                $errors[] = 'Password reset link is invalid. Request a new one.';
            }
        }

        if ($errors !== []) {
            $_SESSION['reset_password_errors'] = $errors;
            pixelwarRedirect('update-pass');
        }

        $users->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        $verifications->updateStatus((int) $verification['ev_id'], 1);
        unset($_SESSION['reset_password_old']);
        $_SESSION['login_notices'] = ['Password updated. You can now log in.'];
        pixelwarLogActivity($activityLogRepository ?? null, $userId, 'auth', 'Reset account password.');
        pixelwarRedirect('login');
    } catch (Throwable $err) {
        error_log('Pixelwar update password error: ' . $err->getMessage());
        $_SESSION['reset_password_errors'] = ['Password could not be updated right now.'];
        pixelwarRedirect('update-pass');
    }
}
