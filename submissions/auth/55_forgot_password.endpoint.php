<?php
if ($requestMethod === 'POST' && $requestedPage === 'forgot-password') {
    try {
        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf('forgot-password');
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $verifications = pixelwarRequireVerificationRepository($verificationRepository);
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $_SESSION['forgot_password_old'] = ['identity' => $identity];

        if ($identity === '') {
            $_SESSION['forgot_password_errors'] = ['Enter your username or email.'];
            pixelwarRedirect('forgot-password');
        }

        $cooldownAvailableAt = pixelwarForgotPasswordCooldownAvailableAt();
        if ($cooldownAvailableAt > time()) {
            $secondsLeft = max(1, $cooldownAvailableAt - time());
            $_SESSION['forgot_password_errors'] = ['Please wait ' . $secondsLeft . ' seconds before requesting another reset link.'];
            pixelwarRedirect('forgot-password');
        }

        $user = $users->findUserByIdentity($identity);
        $deletedUser = $user === null ? $users->findDeletedLoginUser($identity) : null;

        if (!$user) {
            $_SESSION['forgot_password_errors'] = $deletedUser !== null
                ? ['You no longer have access to this account. If you think this is a mistake, please contact your admin or instructor.']
                : ['No account was found for that username or email.'];
            pixelwarRedirect('forgot-password');
        }

        $userId = (int) ($user['user_id'] ?? 0);
        $email = (string) ($user['email'] ?? '');
        $username = (string) ($user['username'] ?? 'Player');

        if ($userId <= 0 || $email === '') {
            $_SESSION['forgot_password_errors'] = ['Account recovery is not available for this account yet.'];
            pixelwarRedirect('forgot-password');
        }

        $verifications->expirePending($userId, 'password change');
        $rawToken = bin2hex(random_bytes(24));
        $verifications->create($userId, 'password change', pixelwarHashVerificationToken($rawToken), 0);

        $resetLink = pixelwarAppUrl('./?c=update-pass&uid=' . $userId . '&token=' . urlencode($rawToken));
        $mailSent = pixelwarSendPasswordResetLink($tools, $email, $username, $resetLink);

        if (!$mailSent) {
            $_SESSION['forgot_password_errors'] = ['We could not send the password reset email right now. Please try again later.'];
            pixelwarRedirect('forgot-password');
        }

        unset($_SESSION['forgot_password_old']);
        $_SESSION['forgot_password_notices'] = ['A password reset link has been sent to the registered email.'];
        pixelwarSetForgotPasswordCooldown(180);
        pixelwarLogActivity($activityLogRepository ?? null, $userId, 'auth', 'Requested a password reset link.');
        pixelwarRedirect('forgot-password');
    } catch (Throwable $err) {
        error_log('Pixelwar forgot password error: ' . $err->getMessage());
        $_SESSION['forgot_password_errors'] = ['Account recovery could not be started right now.'];
        pixelwarRedirect('forgot-password');
    }
}
