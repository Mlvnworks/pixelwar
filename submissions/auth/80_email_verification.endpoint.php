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

            $_SESSION['email'] = $newEmail;
            pixelwarPrepareAccountVerification(
                $auth,
                $tools,
                $userId,
                $newEmail,
                (string) $verificationUser['username']
            );
            $_SESSION['verification_notices'] = [
                !empty($_SESSION['pending_verification_mail_sent'])
                    ? 'Email updated. A new verification code was sent.'
                    : 'Email updated. A new verification code was prepared, but email delivery is not available right now.'
            ];
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
                pixelwarRedirectAfterAuthState($users, [
                    'user_id' => $userId,
                    'username' => (string) $verificationUser['username'],
                    'email' => (string) $verificationUser['email'],
                    'is_verified' => 1,
                ]);
            }

            pixelwarPrepareAccountVerification(
                $auth,
                $tools,
                $userId,
                (string) $verificationUser['email'],
                (string) $verificationUser['username']
            );
            $_SESSION['verification_notices'] = [
                !empty($_SESSION['pending_verification_mail_sent'])
                    ? 'A new verification code was sent.'
                    : 'A new verification code was prepared. Email delivery is not available right now.'
            ];
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

        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = (string) ($user['username'] ?? 'Player');
        $_SESSION['email'] = (string) ($user['email'] ?? ($_SESSION['pending_verification_email'] ?? ''));
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Email verified. Set up your player profile next.'
        ];

        unset(
            $_SESSION['pending_verification_user_id'],
            $_SESSION['pending_verification_email'],
            $_SESSION['pending_verification_token'],
            $_SESSION['pending_verification_mail_sent'],
            $_SESSION['verification_errors'],
            $_SESSION['verification_notices']
        );
        pixelwarResetVerificationAttempts($userId);

        pixelwarRedirect('profile-setup');
    } catch (Throwable $err) {
        error_log('Pixelwar verification error: ' . $err->getMessage());
        $_SESSION['verification_errors'] = ['Verification failed. Please try again.'];
        pixelwarRedirect('email-verification');
    }
}
