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
        $profileImageFile = $_FILES['profile_image'] ?? [];
        $idPictureFile = $_FILES['id_picture'] ?? [];
        $errors = [];

        $_SESSION['profile_setup_old'] = [
            'username' => $username,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'student_number' => $studentNumber,
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

        if ($isStaffSetup) {
            if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
                $errors[] = 'Username must be 3-32 characters and only use letters, numbers, or underscores.';
            }

            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters.';
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

        if (!$isStaffSetup) {
            if (!preg_match('/^[A-Za-z0-9-]{4,40}$/', $studentNumber)) {
                $errors[] = 'Enter a valid student number.';
            }

            if ((int) ($idPictureFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload your ID picture before continuing.';
            }
        }

        if ((int) ($profileImageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
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
        $profileImage = $supabaseStorage->uploadProfileImage($profileImageFile, $userId);
        $idPictureImage = null;

        if (!$isStaffSetup) {
            $idPictureStorage = new SupabaseStorage(
                SUPABASE_URL,
                SUPABASE_SERVICE_ROLE_KEY,
                SUPABASE_STORAGE_BUCKET,
                SUPABASE_STORAGE_ID_PICTURE_FOLDER
            );
            $idPictureImage = $idPictureStorage->uploadProfileImage($idPictureFile, $userId);
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
            $successMessage = 'Admin setup saved. Verify your email to continue to the admin panel.';
            $redirect = './?c=email-verification';
        } else {
            $accounts->createProfileDetails($userId, $profileImage, $firstname, $lastname, $idPictureImage, $studentNumber);
            pixelwarLogActivity($activityLogRepository ?? null, $userId, 'profile', 'Completed player profile setup.');
            $sessionUser['is_active'] = 0;
            $successMessage = 'Profile submitted. We are reviewing your details before unlocking the rest of Pixelwar.';
            $redirect = './?c=review-pending';
        }

        unset($_SESSION['profile_setup_old'], $_SESSION['profile_setup_errors']);
        $sessionUser['firstname'] = $firstname;
        $sessionUser['lastname'] = $lastname;
        $sessionUser['avatar_url'] = $profileImage;
        pixelwarRefreshSessionUser($sessionUser);
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
