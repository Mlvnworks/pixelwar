<?php
if (
    $requestMethod === 'GET'
    && $requestedPage === 'home'
    && isset($_SESSION['user_id'])
    && !pixelwarUserDetailsExist(pixelwarRequireUserRepository($userRepository), (int) $_SESSION['user_id'])
) {
    pixelwarRedirect('profile-setup');
}

if ($requestMethod === 'POST' && $requestedPage === 'home') {
    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('home');
    }

    $rawUsername = $_POST['username'] ?? $_POST['identity'] ?? '';
    $username = trim((string) $rawUsername);
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($username !== '') {
        if (str_contains($username, '@')) {
            if ($email === '') {
                $email = $username;
            }

            $username = strstr($username, '@', true) ?: $username;
        }

        $_SESSION['username'] = $username;
    }

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['email'] = $email;
    }
}
