<?php
$teacherRequestedPage = isset($_GET['c']) ? trim((string) $_GET['c']) : 'dashboard';
$teacherRequestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function teacherPanelRedirect(string $page = 'dashboard'): void
{
    header('Location: ./?c=' . $page);
    exit;
}

function teacherPanelRootRedirect(string $page = 'login'): void
{
    header('Location: ../?c=' . $page);
    exit;
}

function teacherPanelRequireUserRepository($userRepository): UserRepository
{
    if (!$userRepository instanceof UserRepository) {
        throw new RuntimeException('User repository is not available.');
    }

    return $userRepository;
}

function teacherPanelCsrfToken(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function teacherPanelCsrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="'
        . htmlspecialchars(teacherPanelCsrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function teacherPanelRefreshSession(array $user): void
{
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role_id'] = (int) $user['role_id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['email'] = (string) $user['email'];

    $firstname = trim((string) ($user['firstname'] ?? ''));
    $lastname = trim((string) ($user['lastname'] ?? ''));
    $avatarUrl = trim((string) ($user['avatar_url'] ?? ''));

    if ($firstname !== '') {
        $_SESSION['firstname'] = $firstname;
    }

    if ($lastname !== '') {
        $_SESSION['lastname'] = $lastname;
    }

    if ($firstname !== '' || $lastname !== '') {
        $_SESSION['avatar_initials'] = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
    }

    if ($avatarUrl !== '') {
        $_SESSION['avatar_url'] = $avatarUrl;
    }
}
