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

    if ((int) ($teacherSessionUser['is_verified'] ?? 0) !== 1) {
        if (
            empty($_SESSION['pending_verification_user_id'])
            || (int) $_SESSION['pending_verification_user_id'] !== (int) $teacherSessionUser['user_id']
        ) {
            teacherPanelPrepareAccountVerification(
                teacherPanelRequireVerificationRepository($verificationRepository ?? null),
                $tools,
                (int) $teacherSessionUser['user_id'],
                (string) $teacherSessionUser['email'],
                (string) $teacherSessionUser['username']
            );
        }

        teacherPanelRootRedirect('email-verification');
    }

    if (!$users->userDetailsExist((int) $teacherSessionUser['user_id'])) {
        teacherPanelRootRedirect('profile-setup');
    }

    teacherPanelRefreshSession($teacherSessionUser);
} catch (Throwable $err) {
    error_log('Pixelwar teacher auth error: ' . $err->getMessage());
    teacherPanelRootRedirect('login');
}
