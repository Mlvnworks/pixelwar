<?php

try {
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $users = teacherPanelRequireUserRepository($userRepository);
    $teacherSessionUser = $users->findSessionUser((int) $_SESSION['user_id']);

    if ($teacherSessionUser !== null) {
        teacherPanelRefreshSession($teacherSessionUser);
    }
} catch (Throwable $err) {
    error_log('Pixelwar teacher auth error: ' . $err->getMessage());
}
