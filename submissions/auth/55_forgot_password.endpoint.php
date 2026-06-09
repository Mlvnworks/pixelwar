<?php
if (
    $requestMethod === 'POST'
    && (
        $requestedPage === 'forgot-password'
        || ($requestedPage === 'settings' && (string) ($_POST['settings_action'] ?? '') === 'password_reset')
    )
) {
    try {
        $isSettingsPasswordReset = $requestedPage === 'settings';
        $redirectPage = $isSettingsPasswordReset ? 'settings' : 'forgot-password';

        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf($redirectPage);
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $verifications = pixelwarRequireVerificationRepository($verificationRepository);
        $identity = $isSettingsPasswordReset
            ? trim((string) ($_SESSION['email'] ?? ''))
            : trim((string) ($_POST['identity'] ?? ''));

        if (!$isSettingsPasswordReset) {
            $_SESSION['forgot_password_old'] = ['identity' => $identity];
        }

        if ($identity === '') {
            if ($isSettingsPasswordReset) {
                $_SESSION['alert'] = [
                    'error' => true,
                    'content' => 'Your email address is missing. Update your profile email first.',
                ];
            } else {
                $_SESSION['forgot_password_errors'] = ['Enter your username or email.'];
            }
            pixelwarRedirect($redirectPage);
        }

        $cooldownAvailableAt = pixelwarForgotPasswordCooldownAvailableAt();
        if ($cooldownAvailableAt > time()) {
            $secondsLeft = max(1, $cooldownAvailableAt - time());
            if ($isSettingsPasswordReset) {
                $_SESSION['alert'] = [
                    'error' => true,
                    'content' => 'Please wait ' . $secondsLeft . ' seconds before requesting another reset link.',
                ];
            } else {
                $_SESSION['forgot_password_errors'] = ['Please wait ' . $secondsLeft . ' seconds before requesting another reset link.'];
            }
            pixelwarRedirect($redirectPage);
        }

        $user = $users->findUserByIdentity($identity);
        $deletedUser = $user === null ? $users->findDeletedLoginUser($identity) : null;

        if (!$user) {
            $message = $deletedUser !== null
                ? 'You no longer have access to this account. If you think this is a mistake, please contact your admin or instructor.'
                : 'No account was found for that username or email.';

            if ($isSettingsPasswordReset) {
                $_SESSION['alert'] = [
                    'error' => true,
                    'content' => $message,
                ];
            } else {
                $_SESSION['forgot_password_errors'] = [$message];
            }
            pixelwarRedirect($redirectPage);
        }

        $userId = (int) ($user['user_id'] ?? 0);
        $email = (string) ($user['email'] ?? '');
        $username = (string) ($user['username'] ?? 'Player');

        if ($isSettingsPasswordReset && $userId !== (int) ($_SESSION['user_id'] ?? 0)) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Unable to send a reset link for this account.',
            ];
            pixelwarRedirect('settings');
        }

        if ($userId <= 0 || $email === '') {
            if ($isSettingsPasswordReset) {
                $_SESSION['alert'] = [
                    'error' => true,
                    'content' => 'Account recovery is not available for this account yet.',
                ];
            } else {
                $_SESSION['forgot_password_errors'] = ['Account recovery is not available for this account yet.'];
            }
            pixelwarRedirect($redirectPage);
        }

        $verifications->expirePending($userId, 'password change');
        $rawToken = bin2hex(random_bytes(24));
        $verifications->create($userId, 'password change', pixelwarHashVerificationToken($rawToken), 0);

        $resetLink = pixelwarAppUrl('./?c=update-pass&uid=' . $userId . '&token=' . urlencode($rawToken));
        $mailSent = pixelwarSendPasswordResetLink($tools, $email, $username, $resetLink);

        if (!$mailSent) {
            if ($isSettingsPasswordReset) {
                $_SESSION['alert'] = [
                    'error' => true,
                    'content' => 'We could not send the password reset email right now. Please try again later.',
                ];
            } else {
                $_SESSION['forgot_password_errors'] = ['We could not send the password reset email right now. Please try again later.'];
            }
            pixelwarRedirect($redirectPage);
        }

        unset($_SESSION['forgot_password_old']);
        if ($isSettingsPasswordReset) {
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'A password reset link has been sent to your registered email.',
            ];
        } else {
            $_SESSION['forgot_password_notices'] = ['A password reset link has been sent to the registered email.'];
        }
        pixelwarSetForgotPasswordCooldown(180);
        pixelwarLogActivity($activityLogRepository ?? null, $userId, 'auth', 'Requested a password reset link.');
        pixelwarRedirect($redirectPage);
    } catch (Throwable $err) {
        error_log('Pixelwar forgot password error: ' . $err->getMessage());
        if (($requestedPage ?? '') === 'settings') {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Account recovery could not be started right now.',
            ];
            pixelwarRedirect('settings');
        }

        $_SESSION['forgot_password_errors'] = ['Account recovery could not be started right now.'];
        pixelwarRedirect('forgot-password');
    }
}
