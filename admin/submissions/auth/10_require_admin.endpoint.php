<?php

try {
    if (!isset($_SESSION['user_id'])) {
        adminPanelRootRedirect('login');
    }

    $users = adminPanelRequireUserRepository($userRepository);
    $adminSessionUser = $users->findSessionUser((int) $_SESSION['user_id']);

    if ($adminSessionUser === null) {
        adminPanelRootRedirect('login');
    }

    if ((int) ($adminSessionUser['role_id'] ?? 0) !== 1) {
        $roleId = (int) ($adminSessionUser['role_id'] ?? 0);

        if ($roleId === 2) {
            header('Location: ../teacher/?c=dashboard');
            exit;
        }

        adminPanelRootRedirect('home');
    }

    if (adminPanelAdminNeedsSetup($users, $adminSessionUser)) {
        adminPanelRootRedirect('profile-setup');
    }

    if ((int) ($adminSessionUser['is_verified'] ?? 0) !== 1) {
        adminPanelRootRedirect('email-verification');
    }

    adminPanelRefreshSession($adminSessionUser);
} catch (Throwable $err) {
    error_log('Pixelwar admin auth error: ' . $err->getMessage());
    adminPanelRootRedirect('login');
}
