<?php
$requestedPage = isset($_GET['c']) ? trim((string) $_GET['c']) : '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function pixelwarRedirect(string $page): void
{
    header('Location: ./?c=' . $page);
    exit;
}

function pixelwarWantsJson(): bool
{
    return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

/**
 * @param array<string, mixed> $payload
 */
function pixelwarJsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}

function pixelwarRequireUserRepository($userRepository): UserRepository
{
    if (!$userRepository instanceof UserRepository) {
        throw new RuntimeException('User repository is not available.');
    }

    return $userRepository;
}

function pixelwarRequireVerificationRepository($verificationRepository): VerificationRepository
{
    if (!$verificationRepository instanceof VerificationRepository) {
        throw new RuntimeException('Verification repository is not available.');
    }

    return $verificationRepository;
}

function pixelwarRequireUserAccountService($userAccountService): UserAccountService
{
    if (!$userAccountService instanceof UserAccountService) {
        throw new RuntimeException('User account service is not available.');
    }

    return $userAccountService;
}

function pixelwarFindSignupConflict(UserRepository $userRepository, string $username, string $email): ?string
{
    $existingUser = $userRepository->findSignupConflict($username, $email);

    if (!$existingUser) {
        return null;
    }

    return strcasecmp((string) $existingUser['username'], $username) === 0
        ? 'Username is already taken.'
        : 'Email is already registered.';
}

function pixelwarFindLoginUser(UserRepository $userRepository, string $identity): ?array
{
    return $userRepository->findLoginUser($identity);
}

function pixelwarStudentRoleId(): int
{
    return 3;
}

function pixelwarUserDetailsExist(UserRepository $userRepository, int $userId): bool
{
    return $userRepository->userDetailsExist($userId);
}

function pixelwarFindSessionUser(UserRepository $userRepository): ?array
{
    return $userRepository->findSessionUser((int) ($_SESSION['user_id'] ?? 0));
}

function pixelwarRefreshSessionUser(array $user): void
{
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role_id'] = (int) ($user['role_id'] ?? 0);
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

function pixelwarLogout(): void
{
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function pixelwarCsrfToken(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function pixelwarCsrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="'
        . htmlspecialchars(pixelwarCsrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function pixelwarValidateCsrf(): bool
{
    $sessionToken = (string) ($_SESSION['_csrf_token'] ?? '');
    $requestToken = (string) ($_POST['_csrf_token'] ?? '');

    return $sessionToken !== '' && $requestToken !== '' && hash_equals($sessionToken, $requestToken);
}

function pixelwarRedirectAfterAuthState(UserRepository $userRepository, array $user): void
{
    pixelwarRefreshSessionUser($user);

    if ((int) $user['is_verified'] !== 1) {
        pixelwarRedirect('email-verification');
    }

    if (!pixelwarUserDetailsExist($userRepository, (int) $user['user_id'])) {
        pixelwarRedirect('profile-setup');
    }

    pixelwarRedirect('home');
}

function pixelwarFailCsrf(string $redirectPage, bool $wantsJson = false): void
{
    if ($wantsJson) {
        pixelwarJsonResponse([
            'success' => false,
            'message' => 'Security check failed. Refresh the page and try again.',
        ], 419);
    }

    $_SESSION['alert'] = [
        'error' => true,
        'content' => 'Security check failed. Refresh the page and try again.'
    ];
    pixelwarRedirect($redirectPage);
}

function pixelwarVerificationAttemptKey(int $userId): string
{
    return 'verification_attempts_' . $userId;
}

function pixelwarResetVerificationAttempts(int $userId): void
{
    unset($_SESSION[pixelwarVerificationAttemptKey($userId)]);
}

function pixelwarRegisterVerificationAttempt(int $userId): int
{
    $key = pixelwarVerificationAttemptKey($userId);
    $_SESSION[$key] = (int) ($_SESSION[$key] ?? 0) + 1;

    return (int) $_SESSION[$key];
}

function pixelwarHashVerificationToken(string $token): string
{
    return password_hash($token, PASSWORD_DEFAULT);
}

function pixelwarVerificationTokenIsHashed(string $storedToken): bool
{
    return (int) (password_get_info($storedToken)['algo'] ?? 0) !== 0;
}

function pixelwarVerificationTokenMatches(string $storedToken, string $token): bool
{
    if (pixelwarVerificationTokenIsHashed($storedToken)) {
        return password_verify($token, $storedToken);
    }

    return hash_equals($storedToken, $token);
}

function pixelwarSendVerificationToken(Tools $tools, string $email, string $username, string $token): bool
{
    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    $content = '<!doctype html>'
        . '<html lang="en">'
        . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pixelwar Verification</title></head>'
        . '<body style="margin:0;padding:0;background:#fff7e8;color:#26190f;font-family:Arial,Helvetica,sans-serif;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#fff7e8;padding:28px 14px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fffdf6;border:4px solid #26190f;border-radius:28px;box-shadow:8px 8px 0 #26190f;overflow:hidden;">'
        . '<tr><td style="padding:24px 24px 18px;background:linear-gradient(135deg,#ffd166 0%,#ff8c42 55%,#4cc9f0 100%);border-bottom:4px solid #26190f;">'
        . '<p style="margin:0 0 12px;font-size:11px;font-weight:900;letter-spacing:4px;text-transform:uppercase;color:#26190f;">Pixelwar</p>'
        . '<h1 style="margin:0;font-size:28px;line-height:1.12;color:#26190f;">Confirm your email</h1>'
        . '</td></tr>'
        . '<tr><td style="padding:26px 24px 24px;">'
        . '<p style="margin:0 0 14px;font-size:16px;line-height:1.65;color:#26190f;">Hello <strong>' . $safeUsername . '</strong>, use this code to finish creating your Pixelwar account.</p>'
        . '<div style="margin:22px 0;padding:18px 14px;text-align:center;background:#ffd166;border:3px solid #26190f;border-radius:20px;box-shadow:5px 5px 0 rgba(38,25,15,0.25);">'
        . '<p style="margin:0 0 8px;font-size:11px;font-weight:900;letter-spacing:3px;text-transform:uppercase;color:#8a4a12;">Verification Code</p>'
        . '<p style="margin:0;font-size:34px;line-height:1;font-weight:900;letter-spacing:9px;color:#26190f;">' . $safeToken . '</p>'
        . '</div>'
        . '<div style="margin:18px 0;padding:14px 16px;background:#4cc9f0;border:3px solid #26190f;border-radius:18px;">'
        . '<p style="margin:0;font-size:14px;line-height:1.55;font-weight:800;color:#26190f;">This code expires in 20 minutes. After that, Pixelwar will mark it as invalid and you will need a new code.</p>'
        . '</div>'
        . '<p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:rgba(38,25,15,0.7);">If you did not create this account, you can ignore this email.</p>'
        . '</td></tr>'
        . '<tr><td style="padding:16px 24px 22px;background:#fff7e8;border-top:2px solid rgba(38,25,15,0.12);">'
        . '<p style="margin:0;font-size:12px;line-height:1.6;font-weight:700;color:rgba(38,25,15,0.55);">Pixelwar turns CSS design matching into a game. Keep this code private.</p>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '</table>'
        . '</body></html>';

    $result = $tools->sendEmail($content, $email, 'Your Pixelwar verification code');

    return !empty($result['success']);
}

function pixelwarPrepareAccountVerification(VerificationRepository $verificationRepository, Tools $tools, int $userId, string $email, string $username): void
{
    $verificationType = 'account verification';
    $verification = $verificationRepository->findLatest($userId, $verificationType);
    $token = null;

    if ($verification) {
        $verificationStatus = (int) $verification['status'];
        $requestTimestamp = strtotime((string) $verification['request_timestamp']);
        $expiresAt = $requestTimestamp === false ? 0 : $requestTimestamp + (20 * 60);
        $storedToken = (string) $verification['token'];
        $pendingToken = (string) ($_SESSION['pending_verification_token'] ?? '');
        $pendingUserId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);

        if ($verificationStatus === 0 && $expiresAt >= time()) {
            if (
                $pendingUserId === $userId
                && $pendingToken !== ''
                && pixelwarVerificationTokenMatches($storedToken, $pendingToken)
            ) {
                $token = $pendingToken;
            } elseif (!pixelwarVerificationTokenIsHashed($storedToken)) {
                $token = $storedToken;
            }
        } elseif ($verificationStatus === 0) {
            $verificationRepository->updateStatus((int) $verification['ev_id'], -1);
        }
    }

    if ($token === null) {
        $token = (string) random_int(100000, 999999);
        $verificationRepository->create($userId, $verificationType, pixelwarHashVerificationToken($token), 0);
    }

    $_SESSION['pending_verification_user_id'] = $userId;
    $_SESSION['pending_verification_email'] = $email;
    $_SESSION['pending_verification_token'] = $token;
    $_SESSION['pending_verification_mail_sent'] = pixelwarSendVerificationToken($tools, $email, $username, $token);
    pixelwarResetVerificationAttempts($userId);
}

$authEndpointFiles = glob(__DIR__ . '/auth/*.endpoint.php') ?: [];
sort($authEndpointFiles, SORT_NATURAL);

foreach ($authEndpointFiles as $authEndpointFile) {
    require $authEndpointFile;
}
