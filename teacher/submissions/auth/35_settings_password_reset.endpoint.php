<?php
if ($teacherRequestMethod === 'POST' && $teacherRequestedPage === 'settings' && (string) ($_POST['settings_action'] ?? '') === 'password_reset') {
    try {
        if (!teacherPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Session expired. Refresh the page and try again.',
            ];
            teacherPanelRedirect('settings');
        }

        $cooldownAvailableAt = (int) ($_SESSION['teacher_password_reset_available_at'] ?? 0);
        if ($cooldownAvailableAt > time()) {
            $secondsLeft = max(1, $cooldownAvailableAt - time());
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Please wait ' . $secondsLeft . ' seconds before requesting another reset link.',
            ];
            teacherPanelRedirect('settings');
        }

        $users = teacherPanelRequireUserRepository($userRepository ?? null);
        $verifications = teacherPanelRequireVerificationRepository($verificationRepository ?? null);
        $mailTools = teacherPanelRequireTools($tools ?? null);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $teacher = $users->findSessionUser($userId);

        if ($teacher === null || (int) ($teacher['role_id'] ?? 0) !== 2) {
            throw new RuntimeException('Teacher account was not found.');
        }

        $email = trim((string) ($teacher['email'] ?? ''));
        $username = trim((string) ($teacher['username'] ?? 'Teacher')) ?: 'Teacher';

        if ($userId <= 0 || $email === '') {
            throw new RuntimeException('Teacher account recovery is not available right now.');
        }

        $verifications->expirePending($userId, 'password change');
        $rawToken = bin2hex(random_bytes(24));
        $verifications->create($userId, 'password change', password_hash($rawToken, PASSWORD_DEFAULT), 0);

        $resetLink = teacherPanelAppUrl('?c=update-pass&uid=' . $userId . '&token=' . urlencode($rawToken));
        $mailSent = teacherPanelSendPasswordResetLink($mailTools, $email, $username, $resetLink);

        if (!$mailSent) {
            throw new RuntimeException('We could not send the password reset email right now. Please try again later.');
        }

        $_SESSION['teacher_password_reset_available_at'] = time() + 180;
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'A password reset link has been sent to your registered email.',
        ];
        teacherPanelLogActivity($activityLogRepository ?? null, $userId, 'auth', 'Requested a teacher password reset link.');
    } catch (Throwable $err) {
        error_log('Teacher password reset error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to send a password reset link right now.',
        ];
    }

    teacherPanelRedirect('settings');
}
