<?php
if ($requestMethod === 'POST' && $requestedPage === 'email-verification') {
    try {
        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf('email-verification');
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $verifications = pixelwarRequireVerificationRepository($verificationRepository);
        $accounts = pixelwarRequireUserAccountService($userAccountService);
        $userId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);

        if (isset($_POST['change_verification_email'])) {
            $newEmail = trim((string) ($_POST['new_email'] ?? ''));

            if ($userId <= 0) {
                $_SESSION['verification_errors'] = ['Start by creating your player account first.'];
                pixelwarRedirect('signup');
            }

            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['verification_errors'] = ['Enter a valid email address.'];
                pixelwarRedirect('email-verification');
            }

            $verificationUser = $users->findVerificationUser($userId);

            if (!$verificationUser) {
                $_SESSION['verification_errors'] = ['Account was not found. Please create your player account again.'];
                pixelwarRedirect('signup');
            }

            if ((int) $verificationUser['is_verified'] === 1) {
                $_SESSION['verification_errors'] = ['This account is already verified.'];
                pixelwarRedirect('login');
            }

            if ($users->emailExistsForOtherUser($newEmail, $userId)) {
                $_SESSION['verification_errors'] = ['This email is already linked to another account.'];
                pixelwarRedirect('email-verification');
            }

            $accounts->changeVerificationEmail($userId, $newEmail);
            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'account', 'Updated verification email address.');

            $_SESSION['email'] = $newEmail;
            pixelwarPrepareAccountVerification(
                $verifications,
                $tools,
                $userId,
                $newEmail,
                (string) $verificationUser['username']
            );
            if (!empty($_SESSION['pending_verification_mail_sent'])) {
                $_SESSION['verification_notices'] = ['Email updated. A new verification code was sent.'];
            } else {
                $_SESSION['verification_errors'] = ['Email updated, but we could not send the verification email. Please request another code.'];
            }
            pixelwarRedirect('email-verification');
        }

        if (isset($_POST['resend_verification'])) {
            if ($userId <= 0) {
                $_SESSION['verification_errors'] = ['Start by creating your player account first.'];
                pixelwarRedirect('signup');
            }

            $verificationUser = $users->findVerificationUser($userId);

            if (!$verificationUser) {
                $_SESSION['verification_errors'] = ['Account was not found. Please create your player account again.'];
                pixelwarRedirect('signup');
            }

            if ((int) $verificationUser['is_verified'] === 1) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = (string) $verificationUser['username'];
                $_SESSION['email'] = (string) $verificationUser['email'];
                $verifiedSessionUser = $users->findSessionUser($userId);
                if ($verifiedSessionUser !== null) {
                    pixelwarRedirectAfterAuthState($users, $verifiedSessionUser);
                }

                pixelwarRedirect('login');
            }

            $resendAvailableAt = pixelwarVerificationResendAvailableAt();
            if ($resendAvailableAt > time()) {
                $secondsLeft = max(1, $resendAvailableAt - time());
                $_SESSION['verification_errors'] = ['Please wait ' . $secondsLeft . ' seconds before requesting another code.'];
                pixelwarRedirect('email-verification');
            }

            pixelwarPrepareAccountVerification(
                $verifications,
                $tools,
                $userId,
                (string) $verificationUser['email'],
                (string) $verificationUser['username']
            );
            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'account', 'Requested a new verification code.');
            if (!empty($_SESSION['pending_verification_mail_sent'])) {
                $_SESSION['verification_notices'] = ['A new verification code was sent.'];
            } else {
                $_SESSION['verification_errors'] = ['We could not send the verification email. Please check your email address or try again.'];
            }
            pixelwarRedirect('email-verification');
        }

        $token = preg_replace('/\D+/', '', (string) ($_POST['token'] ?? ''));

        if ($userId <= 0 || $token === '') {
            $_SESSION['verification_errors'] = ['Enter the verification code sent to your email.'];
            pixelwarRedirect('email-verification');
        }

        $verificationType = 'account verification';
        $verification = $verifications->findLatest($userId, $verificationType);

        if (!$verification) {
            $_SESSION['verification_errors'] = ['Verification code is incorrect.'];
            pixelwarRedirect('email-verification');
        }

        $verificationStatus = (int) $verification['status'];
        $requestTimestamp = strtotime((string) $verification['request_timestamp']);
        $expiresAt = $requestTimestamp === false ? 0 : $requestTimestamp + (20 * 60);
        $verificationId = (int) $verification['ev_id'];

        if ($verificationStatus === 1) {
            $_SESSION['verification_errors'] = ['Verification code was already used.'];
            pixelwarRedirect('email-verification');
        }

        if ($verificationStatus === -1 || $expiresAt < time()) {
            if ($verificationStatus === 0) {
                $verifications->updateStatus($verificationId, -1);
            }

            $_SESSION['verification_errors'] = ['Verification code expired. Please request a new code.'];
            pixelwarRedirect('email-verification');
        }

        if (!pixelwarVerificationTokenMatches((string) $verification['token'], $token)) {
            $attempts = pixelwarRegisterVerificationAttempt($userId);

            if ($attempts >= 5) {
                $verifications->updateStatus($verificationId, -1);
                $_SESSION['verification_errors'] = ['Too many incorrect attempts. Please request a new code.'];
            } else {
                $remainingAttempts = 5 - $attempts;
                $_SESSION['verification_errors'] = ['Verification code is incorrect. ' . $remainingAttempts . ' attempts left.'];
            }

            pixelwarRedirect('email-verification');
        }

        $user = $accounts->verifyUserEmail($userId, $verificationId);
        pixelwarLogActivity($activityLogRepository ?? null, $userId, 'account', 'Verified email address.');
        pixelwarClearVerificationResendCooldown();

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = (string) ($user['username'] ?? 'Player');
        $_SESSION['email'] = (string) ($user['email'] ?? ($_SESSION['pending_verification_email'] ?? ''));
        $_SESSION['role_id'] = (int) ($user['role_id'] ?? ($_SESSION['role_id'] ?? 0));

        unset(
            $_SESSION['pending_verification_user_id'],
            $_SESSION['pending_verification_email'],
            $_SESSION['pending_verification_mail_sent'],
            $_SESSION['verification_resend_available_at'],
            $_SESSION['verification_errors'],
            $_SESSION['verification_notices']
        );
        pixelwarResetVerificationAttempts($userId);
        $verifiedSessionUser = $users->findSessionUser($userId);
        if ($verifiedSessionUser) {
            pixelwarRefreshSessionUser($verifiedSessionUser);
            $_SESSION['alert'] = [
                'error' => false,
                'content' => (int) ($verifiedSessionUser['role_id'] ?? 0) === pixelwarTeacherRoleId()
                    ? 'Email verified. You can continue to your teacher panel.'
                    : ((int) ($verifiedSessionUser['role_id'] ?? 0) === pixelwarAdminRoleId()
                        ? 'Email verified. You can continue to your admin panel.'
                        : 'Email verified. Set up your player profile next.'),
            ];
            pixelwarRedirectAfterAuthState($users, $verifiedSessionUser);
        }

        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Email verified.',
        ];
        pixelwarRedirect('login');
    } catch (Throwable $err) {
        error_log('Pixelwar verification error: ' . $err->getMessage());
        $_SESSION['verification_errors'] = ['Verification failed. Please try again.'];
        pixelwarRedirect('email-verification');
    }
}
