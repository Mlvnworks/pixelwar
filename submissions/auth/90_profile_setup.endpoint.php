<?php
if ($requestMethod === 'POST' && $requestedPage === 'profile-setup') {
    $profileSetupWantsJson = pixelwarWantsJson();

    try {
        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf('profile-setup', $profileSetupWantsJson);
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $accounts = pixelwarRequireUserAccountService($userAccountService);
        $teacherAccounts = pixelwarRequireTeacherAccountService($teacherAccountService);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $roleId = (int) ($_SESSION['role_id'] ?? 0);
        $isTeacherSetup = $roleId === pixelwarTeacherRoleId();
        $isAdminSetup = $roleId === pixelwarAdminRoleId();
        $isStaffSetup = $isTeacherSetup || $isAdminSetup;
        $username = trim((string) ($_POST['username'] ?? ($_SESSION['username'] ?? '')));
        $email = trim((string) ($_POST['email'] ?? ($_SESSION['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $firstname = trim((string) ($_POST['firstname'] ?? ''));
        $lastname = trim((string) ($_POST['lastname'] ?? ''));
        $studentNumber = trim((string) ($_POST['student_number'] ?? ''));
        $section = trim((string) ($_POST['section'] ?? ''));
        $profileImageFile = $_FILES['profile_image'] ?? [];
        $corUploadFile = $_FILES['cor_file'] ?? [];
        $errors = [];

        $_SESSION['profile_setup_old'] = [
            'username' => $username,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'student_number' => $studentNumber,
            'section' => $section,
        ];

        if ($userId <= 0) {
            if ($profileSetupWantsJson) {
                pixelwarJsonResponse([
                    'success' => false,
                    'message' => 'Finish signup and email verification first.',
                    'redirect' => './?c=signup',
                ], 401);
            }

            $_SESSION['profile_setup_errors'] = ['Finish signup and email verification first.'];
            pixelwarRedirect('signup');
        }

        $sessionUser = pixelwarFindSessionUser($users);
        $existingDetails = $users->findUserDetailsAvatar($userId);

        if ($sessionUser === null) {
            if ($profileSetupWantsJson) {
                pixelwarJsonResponse([
                    'success' => false,
                    'message' => 'Your session expired. Login again.',
                    'redirect' => './?c=login',
                ], 401);
            }

            $_SESSION['profile_setup_errors'] = ['Your session expired. Login again.'];
            pixelwarRedirect('login');
        }

        if (!$isStaffSetup && (int) $sessionUser['is_verified'] !== 1) {
            if ($profileSetupWantsJson) {
                pixelwarJsonResponse([
                    'success' => false,
                    'message' => 'Verify your email before setting up your profile.',
                    'redirect' => './?c=email-verification',
                ], 403);
            }

            $_SESSION['profile_setup_errors'] = ['Verify your email before setting up your profile.'];
            pixelwarRedirect('email-verification');
        }

        $isSectionOnlySetup = !$isStaffSetup && pixelwarStudentNeedsSectionOnlySetup($users, $sessionUser);

        if ($isSectionOnlySetup) {
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 _.-]{0,99}$/', $section)) {
                $errors[] = 'Section is required and must be 100 characters or fewer.';
            }

            if ($errors !== []) {
                if ($profileSetupWantsJson) {
                    pixelwarJsonResponse([
                        'success' => false,
                        'message' => implode(' ', $errors),
                        'errors' => $errors,
                    ], 422);
                }

                $_SESSION['profile_setup_errors'] = $errors;
                pixelwarRedirect('profile-setup');
            }

            $users->updateStudentSection($userId, $section);
            $users->updateActiveState($userId, 0);
            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'profile', 'Added student section and resubmitted profile for admin review.');

            unset($_SESSION['profile_setup_old'], $_SESSION['profile_setup_errors']);
            $savedSessionUser = $users->findSessionUser($userId) ?: $sessionUser;
            pixelwarRefreshSessionUser($savedSessionUser);
            $_SESSION['alert'] = [
                'error' => false,
                'content' => 'Section submitted. Your profile is ready for admin review.',
            ];

            if ($profileSetupWantsJson) {
                pixelwarJsonResponse([
                    'success' => true,
                    'message' => 'Section submitted. Your profile is ready for admin review.',
                    'redirect' => './?c=review-pending',
                ]);
            }

            pixelwarRedirect('review-pending');
        }

        if ($isStaffSetup) {
            if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
                $errors[] = 'Username must be 3-32 characters and only use letters, numbers, or underscores.';
            }

            if (!PasswordPolicy::isValid($password)) {
                $errors[] = PasswordPolicy::REQUIREMENTS_MESSAGE;
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Password confirmation does not match.';
            }

            if ($errors === [] && $users->usernameExistsForOtherUser($username, $userId)) {
                $errors[] = 'Username is already taken.';
            }
        }

        if ($isAdminSetup) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid email address.';
            }

            if ($errors === [] && $users->emailExistsForOtherUser($email, $userId)) {
                $errors[] = 'Email is already registered.';
            }
        }

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $firstname)) {
            $errors[] = 'Enter a valid first name.';
        }

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $lastname)) {
            $errors[] = 'Enter a valid last name.';
        }

        $hasExistingProfileImage = trim((string) ($existingDetails['avatar_url'] ?? ($sessionUser['avatar_url'] ?? ''))) !== '';
        $hasExistingCorFile = trim((string) ($existingDetails['cor_file_url'] ?? '')) !== '';

        if (!$isStaffSetup) {
            if (!preg_match('/^[A-Za-z0-9-]{4,40}$/', $studentNumber)) {
                $errors[] = 'Enter a valid student number.';
            }

            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9 _.-]{0,99}$/', $section)) {
                $errors[] = 'Section is required and must be 100 characters or fewer.';
            }

            if ((int) ($corUploadFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK && !$hasExistingCorFile) {
                $errors[] = 'Upload your Certificate of Registration before continuing.';
            }
        }

        if ((int) ($profileImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK && !$hasExistingProfileImage) {
            $errors[] = 'Upload a profile image before continuing.';
        }

        if ($errors !== []) {
            if ($profileSetupWantsJson) {
                pixelwarJsonResponse([
                    'success' => false,
                    'message' => implode(' ', $errors),
                    'errors' => $errors,
                ], 422);
            }

            $_SESSION['profile_setup_errors'] = $errors;
            pixelwarRedirect('profile-setup');
        }

        $supabaseStorage = new SupabaseStorage(
            SUPABASE_URL,
            SUPABASE_SERVICE_ROLE_KEY,
            SUPABASE_STORAGE_BUCKET,
            SUPABASE_STORAGE_AVATAR_FOLDER
        );
        $profileImage = $hasExistingProfileImage ? trim((string) ($existingDetails['avatar_url'] ?? ($sessionUser['avatar_url'] ?? ''))) : '';
        $corFileUrl = $hasExistingCorFile ? trim((string) ($existingDetails['cor_file_url'] ?? '')) : null;

        if ((int) ($profileImageFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $profileImage = $supabaseStorage->uploadProfileImage($profileImageFile, $userId);
        }

        if (!$isStaffSetup) {
            if ((int) ($corUploadFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $corFileStorage = new SupabaseStorage(
                    SUPABASE_URL,
                    SUPABASE_SERVICE_ROLE_KEY,
                    SUPABASE_STORAGE_BUCKET,
                    SUPABASE_STORAGE_COR_FILE_FOLDER
                );
                $corFileUrl = $corFileStorage->uploadRegistrationDocument($corUploadFile, $userId);
            }
        }

        if ($isTeacherSetup) {
            $teacherAccounts->completeTeacherSetup($userId, $username, $password, $profileImage, $firstname, $lastname);
            $sessionUser['username'] = $username;
            $sessionUser['is_verified'] = 1;
            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'account', 'Completed teacher account setup.');
            $successMessage = 'Teacher setup complete. Welcome to the teacher panel.';
            $redirect = './teacher/?c=dashboard';
        } elseif ($isAdminSetup) {
            $accounts->completeAdminSetup($userId, $username, $email, $password, $profileImage, $firstname, $lastname);
            $sessionUser['username'] = $username;
            $sessionUser['email'] = $email;
            $sessionUser['is_verified'] = 0;
            pixelwarPrepareAccountVerification(
                pixelwarRequireVerificationRepository($verificationRepository),
                $tools,
                $userId,
                $email,
                $username
            );
            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'account', 'Completed admin account setup and requested email verification.');
            if (!empty($_SESSION['pending_verification_mail_sent'])) {
                $_SESSION['verification_notices'] = ['Admin setup saved. A verification code was sent to your email.'];
                $successMessage = 'Admin setup saved. A verification code was sent to your email.';
            } else {
                $_SESSION['verification_errors'] = ['Admin setup saved, but we could not send the verification email. Please request another code.'];
                $successMessage = 'Admin setup saved. Request another verification code to continue.';
            }
            $redirect = './?c=email-verification';
        } else {
            $accounts->createProfileDetails($userId, $profileImage, $firstname, $lastname, $corFileUrl, $studentNumber, $section);
            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'profile', 'Completed player profile setup.');
            $sessionUser['is_active'] = 0;
            $successMessage = 'Profile submitted. We are reviewing your details before unlocking the rest of Pixelwar.';
            $redirect = './?c=review-pending';
        }

        unset($_SESSION['profile_setup_old'], $_SESSION['profile_setup_errors']);
        $savedSessionUser = $users->findSessionUser($userId) ?: array_merge($sessionUser, [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'avatar_url' => $profileImage,
        ]);
        pixelwarRefreshSessionUser($savedSessionUser);
        $_SESSION['alert'] = [
            'error' => false,
            'content' => $successMessage
        ];

        if ($profileSetupWantsJson) {
            pixelwarJsonResponse([
                'success' => true,
                'message' => $successMessage,
                'redirect' => $redirect,
            ]);
        }

        header('Location: ' . $redirect);
        exit;
    } catch (Throwable $err) {
        error_log('Pixelwar profile setup error: ' . $err->getMessage());
        if ($profileSetupWantsJson) {
            pixelwarJsonResponse([
                'success' => false,
                'message' => APP_DEBUG ? $err->getMessage() : 'Profile setup failed. Please check the form and try again.',
            ], 500);
        }

        $_SESSION['profile_setup_errors'] = [APP_DEBUG ? $err->getMessage() : 'Profile setup failed. Please check the form and try again.'];
        pixelwarRedirect('profile-setup');
    }
}
