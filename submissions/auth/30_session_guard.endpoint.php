<?php
$currentPage = $requestedPage !== '' ? $requestedPage : 'landing';
$publicPages = ['landing', 'login', 'signup', 'forgot-password'];
$guestOnlyPages = ['login', 'signup', 'forgot-password'];
$onboardingPages = ['email-verification', 'profile-setup'];
$isPublicPage = in_array($currentPage, $publicPages, true);
$isOnboardingPage = in_array($currentPage, $onboardingPages, true);
$sessionUser = null;

if (isset($_SESSION['user_id'])) {
    try {
        $sessionUser = pixelwarFindSessionUser(pixelwarRequireUserRepository($userRepository));

        if ($sessionUser === null) {
            pixelwarLogout();
            pixelwarRedirect('login');
        }

        pixelwarRefreshSessionUser($sessionUser);
    } catch (Throwable $err) {
        error_log('Pixelwar session lookup error: ' . $err->getMessage());
        pixelwarLogout();
        pixelwarRedirect('login');
    }
}

if (
    $sessionUser !== null
    && (int) $sessionUser['is_verified'] !== 1
    && (
        empty($_SESSION['pending_verification_user_id'])
        || (int) $_SESSION['pending_verification_user_id'] !== (int) $sessionUser['user_id']
    )
) {
    pixelwarPrepareAccountVerification(
        pixelwarRequireVerificationRepository($verificationRepository),
        $tools,
        (int) $sessionUser['user_id'],
        (string) $sessionUser['email'],
        (string) $sessionUser['username']
    );
}

if ($requestMethod === 'GET' && $sessionUser !== null && in_array($currentPage, $guestOnlyPages, true)) {
    pixelwarRedirectAfterAuthState(pixelwarRequireUserRepository($userRepository), $sessionUser);
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) !== pixelwarStudentRoleId()
    && !$isPublicPage
    && !$isOnboardingPage
) {
    pixelwarRedirectToRoleHome($sessionUser);
}

if ($requestMethod === 'GET' && $currentPage === 'email-verification' && $sessionUser === null && empty($_SESSION['pending_verification_user_id'])) {
    pixelwarRedirect('signup');
}

if ($sessionUser === null && $currentPage === 'profile-setup') {
    if ($requestMethod === 'POST' && pixelwarWantsJson()) {
        pixelwarJsonResponse([
            'success' => false,
            'message' => 'Finish signup and email verification first.',
            'redirect' => './?c=signup',
        ], 401);
    }

    pixelwarRedirect('signup');
}

if ($sessionUser === null && !$isPublicPage && !$isOnboardingPage) {
    pixelwarRedirect('login');
}

if (
    $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarStudentRoleId()
    && (int) $sessionUser['is_verified'] !== 1
    && $currentPage !== 'email-verification'
) {
    pixelwarRedirect('email-verification');
}

if (
    $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarStudentRoleId()
    && (int) $sessionUser['is_verified'] === 1
    && !pixelwarUserDetailsExist(pixelwarRequireUserRepository($userRepository), (int) $sessionUser['user_id'])
    && $currentPage !== 'profile-setup'
) {
    pixelwarRedirect('profile-setup');
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarStudentRoleId()
    && (int) $sessionUser['is_verified'] === 1
    && $currentPage === 'email-verification'
) {
    pixelwarRedirectAfterAuthState(pixelwarRequireUserRepository($userRepository), $sessionUser);
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarStudentRoleId()
    && (int) $sessionUser['is_verified'] === 1
    && $currentPage === 'profile-setup'
    && pixelwarUserDetailsExist(pixelwarRequireUserRepository($userRepository), (int) $sessionUser['user_id'])
) {
    pixelwarRedirect('home');
}
