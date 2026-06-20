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

function adminPanelUserDetailsExist(UserRepository $userRepository, int $userId): bool
{
    return $userRepository->userDetailsExist($userId);
}

function adminPanelAdminNeedsSetup(UserRepository $userRepository, array $user): bool
{
    return (int) ($user['role_id'] ?? 0) === 1
        && !adminPanelUserDetailsExist($userRepository, (int) ($user['user_id'] ?? 0));
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

function adminPanelLogActivitySafely($activityLogRepository, int $userId, string $category, string $description): void
{
    if (!$activityLogRepository instanceof ActivityLogRepository || $userId <= 0) {
        error_log('Pixelwar admin activity log skipped: repository or user is unavailable.');
        return;
    }

    try {
        $activityLogRepository->create($userId, $category, $description);
    } catch (Throwable $err) {
        error_log('Pixelwar admin activity log failed: ' . $err->getMessage());
    }
}

function adminPanelRequireChallengeRepository($challengeRepository): ChallengeRepository
{
    if (!$challengeRepository instanceof ChallengeRepository) {
        throw new RuntimeException('Challenge repository is not available.');
    }

    return $challengeRepository;
}

function adminPanelRequireRankRepository($rankRepository): RankRepository
{
    if (!$rankRepository instanceof RankRepository) {
        throw new RuntimeException('Rank repository is not available.');
    }

    return $rankRepository;
}

function adminPanelRequireVerificationRepository($verificationRepository): VerificationRepository
{
    if (!$verificationRepository instanceof VerificationRepository) {
        throw new RuntimeException('Verification repository is not available.');
    }

    return $verificationRepository;
}

function adminPanelRequireSeasonRepository($seasonRepository): SeasonRepository
{
    if (!$seasonRepository instanceof SeasonRepository) {
        throw new RuntimeException('Season repository is not available.');
    }

    return $seasonRepository;
}

function adminPanelRequireTools($tools): Tools
{
    if (!$tools instanceof Tools) {
        throw new RuntimeException('Tools service is not available.');
    }

    return $tools;
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

function adminPanelValidateCsrf(): bool
{
    return hash_equals(
        (string) ($_SESSION['_csrf_token'] ?? ''),
        (string) ($_POST['_csrf_token'] ?? '')
    );
}

function adminPanelAppUrl(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptPath = str_replace('\\', '/', dirname(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'))));
    $basePath = rtrim($scriptPath, '/');
    $path = ltrim($path, '/');

    return $scheme . '://' . $host . ($basePath !== '' ? $basePath : '') . '/' . $path;
}

function adminPanelSendPasswordResetLink(Tools $tools, string $email, string $username, string $resetLink): bool
{
    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
    $content = '<!doctype html>'
        . '<html lang="en">'
        . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pixelwar Password Reset</title></head>'
        . '<body style="margin:0;padding:0;background:#fff7e8;color:#26190f;font-family:Arial,Helvetica,sans-serif;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#fff7e8;padding:28px 14px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fffdf6;border:4px solid #26190f;border-radius:28px;box-shadow:8px 8px 0 #26190f;overflow:hidden;">'
        . '<tr><td style="padding:24px 24px 18px;background:linear-gradient(135deg,#4cc9f0 0%,#ffd166 52%,#ff8c42 100%);border-bottom:4px solid #26190f;">'
        . '<p style="margin:0 0 12px;font-size:11px;font-weight:900;letter-spacing:4px;text-transform:uppercase;color:#26190f;">Pixelwar Admin</p>'
        . '<h1 style="margin:0;font-size:28px;line-height:1.12;color:#26190f;">Reset your password</h1>'
        . '</td></tr>'
        . '<tr><td style="padding:26px 24px 24px;">'
        . '<p style="margin:0 0 14px;font-size:16px;line-height:1.65;color:#26190f;">Hello <strong>' . $safeUsername . '</strong>, we received a request to change your Pixelwar admin password.</p>'
        . '<p style="margin:0 0 18px;font-size:14px;line-height:1.65;color:#26190f;">Use the secure link below to open the password reset page. The link expires in 20 minutes and becomes invalid after one successful use.</p>'
        . '<p style="margin:0 0 22px;text-align:center;">'
        . '<a href="' . $safeLink . '" style="display:inline-block;background:#ffd166;color:#26190f;text-decoration:none;font-size:14px;font-weight:900;padding:14px 22px;border:3px solid #26190f;border-radius:18px;box-shadow:5px 5px 0 rgba(38,25,15,0.25);">Open Reset Page</a>'
        . '</p>'
        . '<div style="margin:18px 0;padding:14px 16px;background:#4cc9f0;border:3px solid #26190f;border-radius:18px;">'
        . '<p style="margin:0;font-size:14px;line-height:1.55;font-weight:800;color:#26190f;">If you did not request this change, ignore this email. Your current password will stay active until the link is used.</p>'
        . '</div>'
        . '<p style="margin:18px 0 0;font-size:12px;line-height:1.65;color:rgba(38,25,15,0.7);word-break:break-all;">If the button does not work, copy this link into your browser:<br>' . $safeLink . '</p>'
        . '</td></tr>'
        . '<tr><td style="padding:16px 24px 22px;background:#fff7e8;border-top:2px solid rgba(38,25,15,0.12);">'
        . '<p style="margin:0;font-size:12px;line-height:1.6;font-weight:700;color:rgba(38,25,15,0.55);">Pixelwar account security notice.</p>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '</table>'
        . '</body></html>';

    $result = $tools->sendEmail($content, $email, 'Reset your Pixelwar admin password');

    if (empty($result['success'])) {
        $error = $result['err'] ?? null;
        error_log('Pixelwar admin password reset email failed: ' . ($error instanceof Throwable ? $error->getMessage() : 'Unknown mail error'));

        return false;
    }

    return true;
}

function adminPanelRefreshSession(array $user): void
{
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['role_id'] = (int) $user['role_id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['email'] = (string) $user['email'];
    $_SESSION['is_verified'] = (int) ($user['is_verified'] ?? 0);

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

function adminPanelExtractRoomActivityName(string $logText): string
{
    $normalized = trim($logText);

    if ($normalized === '') {
        return 'Room creation';
    }

    if (preg_match('/Created room "([^"]+)"/i', $normalized, $matches) === 1) {
        return trim((string) ($matches[1] ?? '')) !== ''
            ? trim((string) $matches[1])
            : 'Room creation';
    }

    if (preg_match('/Created room:?\\s*(.+)$/i', $normalized, $matches) === 1) {
        return trim((string) ($matches[1] ?? '')) !== ''
            ? rtrim(trim((string) $matches[1]), '.')
            : 'Room creation';
    }

    return 'Room creation';
}

/**
 * @param array<string, mixed> $player
 */
function adminPanelRoomPlayerStatusLabel(array $player, bool $roomEnded = false): string
{
    $completedAt = trim((string) ($player['completed_at'] ?? ''));
    $startedAt = trim((string) ($player['started_at'] ?? ''));
    $status = (int) ($player['status'] ?? 0);

    if ($roomEnded && $completedAt === '' && $status !== 2) {
        return 'Failed';
    }

    if ($status === 3) {
        return 'Failed';
    }

    if ($completedAt !== '' || $status === 2) {
        return 'Completed';
    }

    if ($startedAt !== '' || $status === 1) {
        return 'Solving';
    }

    return 'Waiting';
}

function adminPanelSendStudentReviewEmail(Tools $tools, string $email, string $studentName, string $status): bool
{
    $safeName = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
    $isApproved = $status === 'approved';
    $subject = $isApproved
        ? 'Your Pixelwar account is approved'
        : 'Update on your Pixelwar account review';
    $headline = $isApproved ? 'Your access is now open' : 'We could not approve the account yet';
    $accent = $isApproved ? '#8bd3c7' : '#f97373';
    $message = $isApproved
        ? 'Your submitted student details have been reviewed and approved. You can now sign in and access your Pixelwar dashboard and learning resources.'
        : 'We reviewed the submitted student details, but we could not approve the account yet. Please contact your instructor or admin so they can guide you on the next step.';

    $content = '<!doctype html>'
        . '<html lang="en">'
        . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Pixelwar Account Review</title></head>'
        . '<body style="margin:0;padding:0;background:#fff7e8;color:#26190f;font-family:Arial,Helvetica,sans-serif;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background:#fff7e8;padding:28px 14px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fffdf6;border:4px solid #26190f;border-radius:28px;box-shadow:8px 8px 0 #26190f;overflow:hidden;">'
        . '<tr><td style="padding:22px 24px 18px;background:' . $accent . ';border-bottom:4px solid #26190f;">'
        . '<p style="margin:0 0 10px;font-size:11px;font-weight:900;letter-spacing:4px;text-transform:uppercase;color:#26190f;">Pixelwar</p>'
        . '<h1 style="margin:0;font-size:28px;line-height:1.15;color:#26190f;">' . $headline . '</h1>'
        . '</td></tr>'
        . '<tr><td style="padding:24px;">'
        . '<p style="margin:0 0 14px;font-size:16px;line-height:1.65;color:#26190f;">Hello <strong>' . $safeName . '</strong>,</p>'
        . '<p style="margin:0;font-size:15px;line-height:1.7;color:#374151;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<div style="margin:18px 0 0;padding:14px 16px;background:#ffffff;border:2px solid rgba(38,25,15,0.12);border-radius:18px;">'
        . '<p style="margin:0;font-size:13px;line-height:1.65;color:#6b7280;">If you need help, reply to your school administrator or the team handling your registration.</p>'
        . '</div>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '</table>'
        . '</body></html>';

    $result = $tools->sendEmail($content, $email, $subject);

    if (empty($result['success'])) {
        $error = $result['err'] ?? null;
        error_log('Pixelwar student review email failed: ' . ($error instanceof Throwable ? $error->getMessage() : 'Unknown mail error'));

        return false;
    }

    return true;
}
