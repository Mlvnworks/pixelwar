<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'settings' && (string) ($_POST['settings_action'] ?? '') === 'password_reset') {
    try {
        if (!adminPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('settings');
        }

        $cooldownAvailableAt = (int) ($_SESSION['admin_password_reset_available_at'] ?? 0);
        if ($cooldownAvailableAt > time()) {
            $secondsLeft = max(1, $cooldownAvailableAt - time());
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Please wait ' . $secondsLeft . ' seconds before requesting another reset link.',
            ];
            adminPanelRedirect('settings');
        }

        $users = adminPanelRequireUserRepository($userRepository ?? null);
        $verifications = adminPanelRequireVerificationRepository($verificationRepository ?? null);
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository ?? null);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $admin = $users->findSessionUser($userId);

        if ($admin === null || (int) ($admin['role_id'] ?? 0) !== 1) {
            throw new RuntimeException('Admin account was not found.');
        }

        $email = trim((string) ($admin['email'] ?? ''));
        $username = trim((string) ($admin['username'] ?? 'Admin')) ?: 'Admin';

        if ($userId <= 0 || $email === '') {
            throw new RuntimeException('Admin account recovery is not available right now.');
        }

        $verifications->expirePending($userId, 'password change');
        $rawToken = bin2hex(random_bytes(24));
        $verifications->create($userId, 'password change', password_hash($rawToken, PASSWORD_DEFAULT), 0);

        $resetLink = adminPanelAppUrl('?c=update-pass&uid=' . $userId . '&token=' . urlencode($rawToken));
        $mailSent = adminPanelSendPasswordResetLink($tools, $email, $username, $resetLink);

        if (!$mailSent) {
            throw new RuntimeException('We could not send the password reset email right now. Please try again later.');
        }

        $_SESSION['admin_password_reset_available_at'] = time() + 180;
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'A password reset link has been sent to your registered email.',
        ];
        $logs->create($userId, 'auth', 'Requested an admin password reset link.');
    } catch (Throwable $err) {
        error_log('Pixelwar admin password reset error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to send a password reset link right now.',
        ];
    }

    adminPanelRedirect('settings');
}
