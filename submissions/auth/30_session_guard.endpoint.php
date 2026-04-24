<?php
$currentPage = $requestedPage !== '' ? $requestedPage : 'landing';
$publicPages = ['landing', 'login', 'signup', 'forgot-password'];
$guestOnlyPages = ['login', 'signup', 'forgot-password'];
$onboardingPages = ['email-verification', 'profile-setup'];
$reviewPage = 'review-pending';
$rejectedPage = 'review-rejected';
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
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarStudentRoleId()
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

if (
    $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarAdminRoleId()
    && !pixelwarAdminNeedsSetup(pixelwarRequireUserRepository($userRepository), $sessionUser)
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

if (
    $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarTeacherRoleId()
    && (int) ($sessionUser['is_verified'] ?? 0) !== 1
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
    $sessionUser !== null
    && pixelwarStudentRejected($sessionUser)
    && pixelwarUserDetailsExist(pixelwarRequireUserRepository($userRepository), (int) $sessionUser['user_id'])
    && $currentPage !== $rejectedPage
    && $currentPage !== 'logout'
) {
    pixelwarRedirect($rejectedPage);
}

if (
    $sessionUser !== null
    && pixelwarStudentUnderReview($sessionUser)
    && pixelwarUserDetailsExist(pixelwarRequireUserRepository($userRepository), (int) $sessionUser['user_id'])
    && $currentPage !== $reviewPage
    && $currentPage !== 'logout'
) {
    pixelwarRedirect($reviewPage);
}

if (
    $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarAdminRoleId()
    && pixelwarAdminNeedsSetup(pixelwarRequireUserRepository($userRepository), $sessionUser)
    && $currentPage !== 'profile-setup'
    && $currentPage !== 'logout'
) {
    pixelwarRedirect('profile-setup');
}

if (
    $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarAdminRoleId()
    && !pixelwarAdminNeedsSetup(pixelwarRequireUserRepository($userRepository), $sessionUser)
    && (int) $sessionUser['is_verified'] !== 1
    && $currentPage !== 'email-verification'
) {
    pixelwarRedirect('email-verification');
}

if (
    $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarTeacherRoleId()
    && (int) $sessionUser['is_verified'] !== 1
    && $currentPage !== 'email-verification'
) {
    pixelwarRedirect('email-verification');
}

if (
    $sessionUser !== null
    && pixelwarTeacherNeedsSetup(pixelwarRequireUserRepository($userRepository), $sessionUser)
    && $currentPage !== 'profile-setup'
    && $currentPage !== 'login'
    && $currentPage !== 'landing'
) {
    pixelwarRedirect('profile-setup');
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarAdminRoleId()
    && !pixelwarAdminNeedsSetup(pixelwarRequireUserRepository($userRepository), $sessionUser)
    && (int) $sessionUser['is_verified'] === 1
    && $currentPage === 'email-verification'
) {
    pixelwarRedirectAfterAuthState(pixelwarRequireUserRepository($userRepository), $sessionUser);
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarTeacherRoleId()
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
    if (pixelwarStudentRejected($sessionUser)) {
        pixelwarRedirect($rejectedPage);
    }

    if (pixelwarStudentUnderReview($sessionUser)) {
        pixelwarRedirect($reviewPage);
    }

    pixelwarRedirect('home');
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && !pixelwarStudentRejected($sessionUser)
    && $currentPage === $rejectedPage
) {
    pixelwarRedirectAfterAuthState(pixelwarRequireUserRepository($userRepository), $sessionUser);
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (
        !pixelwarStudentUnderReview($sessionUser)
        || !pixelwarUserDetailsExist(pixelwarRequireUserRepository($userRepository), (int) $sessionUser['user_id'])
    )
    && $currentPage === $reviewPage
) {
    pixelwarRedirectAfterAuthState(pixelwarRequireUserRepository($userRepository), $sessionUser);
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarAdminRoleId()
    && !pixelwarAdminNeedsSetup(pixelwarRequireUserRepository($userRepository), $sessionUser)
    && $currentPage === 'profile-setup'
) {
    pixelwarRedirectAfterAuthState(pixelwarRequireUserRepository($userRepository), $sessionUser);
}

if (
    $requestMethod === 'GET'
    && $sessionUser !== null
    && (int) ($sessionUser['role_id'] ?? 0) === pixelwarTeacherRoleId()
    && !pixelwarTeacherNeedsSetup(pixelwarRequireUserRepository($userRepository), $sessionUser)
    && $currentPage === 'profile-setup'
) {
    pixelwarRedirectToRoleHome($sessionUser);
}
