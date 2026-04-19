<?php

try {
    if (!isset($_SESSION['user_id'])) {
        teacherPanelRootRedirect('login');
    }

    $users = teacherPanelRequireUserRepository($userRepository);
    $teacherSessionUser = $users->findSessionUser((int) $_SESSION['user_id']);


    if ($teacherSessionUser === null) {
        teacherPanelRootRedirect('login');
    }

    $roleId = (int) ($teacherSessionUser['role_id'] ?? 0);

    if ($roleId !== 2) {
        if ($roleId === 1) {
            header('Location: ../admin/?c=dashboard');
            exit;
        }

        teacherPanelRootRedirect('home');
    }

    teacherPanelRefreshSession($teacherSessionUser);
} catch (Throwable $err) {
    error_log('Pixelwar teacher auth error: ' . $err->getMessage());
    teacherPanelRootRedirect('login');
}
