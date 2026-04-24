<?php
$teacherRequestedPage = isset($_GET['c']) ? trim((string) $_GET['c']) : 'dashboard';
$teacherRequestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function teacherPanelRedirect(string $page = 'dashboard'): void
{
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/teacher/index.php');
    $teacherPath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    header('Location: ' . ($teacherPath !== '' ? $teacherPath : '/teacher') . '/?c=' . $page);
    exit;
}

function teacherPanelRootRedirect(string $page = 'login'): void
{
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/teacher/index.php');
    $teacherPath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $rootPath = rtrim(str_replace('\\', '/', dirname($teacherPath)), '/');
    header('Location: ' . ($rootPath !== '' ? $rootPath : '') . '/?c=' . $page);
    exit;
}

function teacherPanelRequireUserRepository($userRepository): UserRepository
{
    if (!$userRepository instanceof UserRepository) {
        throw new RuntimeException('User repository is not available.');
    }

    return $userRepository;
}

function teacherPanelRequireVerificationRepository($verificationRepository): VerificationRepository
{
    if (!$verificationRepository instanceof VerificationRepository) {
        throw new RuntimeException('Verification repository is not available.');
    }

    return $verificationRepository;
}

function teacherPanelRequireUserAccountService($userAccountService): UserAccountService
{
    if (!$userAccountService instanceof UserAccountService) {
        throw new RuntimeException('User account service is not available.');
    }

    return $userAccountService;
}

function teacherPanelRequireChallengeCreationService($challengeCreationService): ChallengeCreationService
{
    if (!$challengeCreationService instanceof ChallengeCreationService) {
        throw new RuntimeException('Challenge creation service is not available.');
    }

    return $challengeCreationService;
}

function teacherPanelRequireChallengeRepository($challengeRepository): ChallengeRepository
{
    if (!$challengeRepository instanceof ChallengeRepository) {
        throw new RuntimeException('Challenge repository is not available.');
    }

    return $challengeRepository;
}

function teacherPanelRequireActivityLogRepository($activityLogRepository): ActivityLogRepository
{
    if (!$activityLogRepository instanceof ActivityLogRepository) {
        throw new RuntimeException('Activity log repository is not available.');
    }

    return $activityLogRepository;
}

function teacherPanelValidateCsrf(): bool
{
    $token = (string) ($_POST['_csrf_token'] ?? '');

    return isset($_SESSION['_csrf_token'])
        && is_string($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $token);
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

function teacherPanelLogActivity($activityLogRepository, int $userId, string $category, string $logText): void
{
    if ($userId <= 0 || !$activityLogRepository instanceof ActivityLogRepository) {
        return;
    }

    try {
        $activityLogRepository->create($userId, $category, $logText);
    } catch (Throwable $err) {
        error_log('Teacher activity log error: ' . $err->getMessage());
    }
}

function teacherPanelVerificationAttemptKey(int $userId): string
{
    return 'verification_attempts_' . $userId;
}

function teacherPanelResetVerificationAttempts(int $userId): void
{
    unset($_SESSION[teacherPanelVerificationAttemptKey($userId)]);
}

function teacherPanelSetVerificationResendCooldown(int $seconds = 60): void
{
    $_SESSION['verification_resend_available_at'] = time() + max(1, $seconds);
}

function teacherPanelHashVerificationToken(string $token): string
{
    return password_hash($token, PASSWORD_DEFAULT);
}

function teacherPanelVerificationTokenIsHashed(string $storedToken): bool
{
    return (int) (password_get_info($storedToken)['algo'] ?? 0) !== 0;
}

function teacherPanelSendVerificationToken(Tools $tools, string $email, string $username, string $token): bool
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
        . '<p style="margin:0 0 14px;font-size:16px;line-height:1.65;color:#26190f;">Hello <strong>' . $safeUsername . '</strong>, use this code to confirm your updated Pixelwar email address.</p>'
        . '<div style="margin:22px 0;padding:18px 14px;text-align:center;background:#ffd166;border:3px solid #26190f;border-radius:20px;box-shadow:5px 5px 0 rgba(38,25,15,0.25);">'
        . '<p style="margin:0 0 8px;font-size:11px;font-weight:900;letter-spacing:3px;text-transform:uppercase;color:#8a4a12;">Verification Code</p>'
        . '<p style="margin:0;font-size:34px;line-height:1;font-weight:900;letter-spacing:9px;color:#26190f;">' . $safeToken . '</p>'
        . '</div>'
        . '<div style="margin:18px 0;padding:14px 16px;background:#4cc9f0;border:3px solid #26190f;border-radius:18px;">'
        . '<p style="margin:0;font-size:14px;line-height:1.55;font-weight:800;color:#26190f;">This code expires in 20 minutes. After that, Pixelwar will mark it as invalid and you will need a new code.</p>'
        . '</div>'
        . '<p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:rgba(38,25,15,0.7);">If you did not request this change, contact your administrator right away.</p>'
        . '</td></tr>'
        . '<tr><td style="padding:16px 24px 22px;background:#fff7e8;border-top:2px solid rgba(38,25,15,0.12);">'
        . '<p style="margin:0;font-size:12px;line-height:1.6;font-weight:700;color:rgba(38,25,15,0.55);">Pixelwar keeps your account protected. Keep this code private.</p>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr>'
        . '</table>'
        . '</body></html>';

    $result = $tools->sendEmail($content, $email, 'Your Pixelwar verification code');

    if (empty($result['success'])) {
        $error = $result['err'] ?? null;
        error_log('Teacher verification email failed: ' . ($error instanceof Throwable ? $error->getMessage() : 'Unknown mail error'));

        return false;
    }

    return true;
}

function teacherPanelPrepareAccountVerification(VerificationRepository $verificationRepository, Tools $tools, int $userId, string $email, string $username): void
{
    $verificationType = 'account verification';
    $verification = $verificationRepository->findLatest($userId, $verificationType);
    $token = null;

    if ($verification) {
        $verificationStatus = (int) $verification['status'];
        $requestTimestamp = strtotime((string) $verification['request_timestamp']);
        $expiresAt = $requestTimestamp === false ? 0 : $requestTimestamp + (20 * 60);
        $storedToken = (string) $verification['token'];
        if ($verificationStatus === 0 && $expiresAt >= time()) {
            if (!teacherPanelVerificationTokenIsHashed($storedToken)) {
                $token = $storedToken;
            }
        } elseif ($verificationStatus === 0) {
            $verificationRepository->updateStatus((int) $verification['ev_id'], -1);
        }
    }

    if ($token === null) {
        $token = (string) random_int(100000, 999999);
        $verificationRepository->create($userId, $verificationType, teacherPanelHashVerificationToken($token), 0);
    }

    $_SESSION['pending_verification_user_id'] = $userId;
    $_SESSION['pending_verification_email'] = $email;
    $_SESSION['pending_verification_mail_sent'] = teacherPanelSendVerificationToken($tools, $email, $username, $token);
    if (!empty($_SESSION['pending_verification_mail_sent'])) {
        teacherPanelSetVerificationResendCooldown(60);
    }
    teacherPanelResetVerificationAttempts($userId);
}
