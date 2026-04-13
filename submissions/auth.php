<?php
$requestedPage = isset($_GET['c']) ? trim((string) $_GET['c']) : '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestedPage === 'login' && isset($_GET['logout'])) {
    unset($_SESSION['username']);
}

if ($requestMethod === 'POST' && $requestedPage === 'home') {
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

if ($requestMethod === 'POST' && $requestedPage === 'settings') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $avatarInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_POST['avatar_initials'] ?? '')) ?: $username, 0, 2));
    $avatarColor = trim((string) ($_POST['avatar_color'] ?? 'yellow'));
    $avatarUrl = trim((string) ($_POST['avatar_url'] ?? ''));
    $allowedAvatarColors = ['yellow', 'cyan', 'orange', 'mint'];

    if ($username !== '') {
        $_SESSION['username'] = $username;
    }

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['email'] = $email;
    }

    $_SESSION['avatar_initials'] = $avatarInitials !== '' ? $avatarInitials : 'PR';
    $_SESSION['avatar_color'] = in_array($avatarColor, $allowedAvatarColors, true) ? $avatarColor : 'yellow';
    $_SESSION['avatar_url'] = filter_var($avatarUrl, FILTER_VALIDATE_URL) ? $avatarUrl : '';

    header('Location: ./?c=settings&updated=1');
    exit;
}
