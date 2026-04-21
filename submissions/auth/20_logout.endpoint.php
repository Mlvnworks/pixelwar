<?php
if ($requestedPage === 'login' && isset($_GET['logout'])) {
    pixelwarRedirect('home');
}

if ($requestMethod === 'POST' && $requestedPage === 'logout') {
    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('home');
    }

    $logoutUserId = (int) ($_SESSION['user_id'] ?? 0);
    pixelwarLogActivity($activityLogRepository ?? null, $logoutUserId, 'auth', 'Logged out.');
    pixelwarLogout();
    pixelwarRedirect('landing');
}
