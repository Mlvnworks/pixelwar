<?php
if ($adminRequestMethod === 'POST' && $adminRequestedPage === 'settings' && (string) ($_POST['settings_action'] ?? '') === 'profile_update') {
    try {
        if (!adminPanelValidateCsrf()) {
            $_SESSION['alert'] = [
                'error' => true,
                'content' => 'Security check failed. Refresh the page and try again.',
            ];
            adminPanelRedirect('settings');
        }

        $users = adminPanelRequireUserRepository($userRepository ?? null);
        $logs = adminPanelRequireActivityLogRepository($activityLogRepository ?? null);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $firstname = trim((string) ($_POST['firstname'] ?? ''));
        $lastname = trim((string) ($_POST['lastname'] ?? ''));
        $admin = $users->findSessionUser($userId);

        if ($admin === null || (int) ($admin['role_id'] ?? 0) !== 1) {
            throw new RuntimeException('Admin account was not found.');
        }

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $firstname)) {
            throw new RuntimeException('Enter a valid first name.');
        }

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $lastname)) {
            throw new RuntimeException('Enter a valid last name.');
        }

        $users->updateUserNameDetails($userId, $firstname, $lastname);
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        $_SESSION['avatar_initials'] = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Admin settings saved.',
        ];
        $logs->create($userId, 'settings', 'Updated admin account settings.');
    } catch (Throwable $err) {
        error_log('Pixelwar admin settings update error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to update admin settings right now.',
        ];
    }

    header('Location: ./?c=settings&updated=1');
    exit;
}
