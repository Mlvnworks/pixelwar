<?php
$adminRequestedPage = isset($_GET['c']) ? trim((string) $_GET['c']) : 'dashboard';
$adminRequestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function adminPanelRedirect(string $page = 'dashboard'): void
{
    header('Location: ./?c=' . $page);
    exit;
}

function adminPanelRootRedirect(string $page = 'login'): void
{
    header('Location: ../?c=' . $page);
    exit;
}

function adminPanelRequireUserRepository($userRepository): UserRepository
{
    if (!$userRepository instanceof UserRepository) {
        throw new RuntimeException('User repository is not available.');
    }

    return $userRepository;
}

function adminPanelRequireTeacherAccountService($teacherAccountService): TeacherAccountService
{
    if (!$teacherAccountService instanceof TeacherAccountService) {
        throw new RuntimeException('Teacher account service is not available.');
    }

    return $teacherAccountService;
}

function adminPanelRequireActivityLogRepository($activityLogRepository): ActivityLogRepository
{
    if (!$activityLogRepository instanceof ActivityLogRepository) {
        throw new RuntimeException('Activity log repository is not available.');
    }

    return $activityLogRepository;
}

function adminPanelFindSignupConflict(UserRepository $userRepository, string $username, string $email): ?string
{
    $existingUser = $userRepository->findSignupConflict($username, $email);

    if (!$existingUser) {
        return null;
    }

    return strcasecmp((string) $existingUser['username'], $username) === 0
        ? 'Username is already taken.'
        : 'Email is already registered.';
}

function adminPanelCsrfToken(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function adminPanelCsrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="'
        . htmlspecialchars(adminPanelCsrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function adminPanelRefreshSession(array $user): void
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

/**
 * @param array<string, mixed> $payload
 */
function adminPanelJsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}
