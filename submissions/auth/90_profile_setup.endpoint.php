<?php
if ($requestMethod === 'POST' && $requestedPage === 'profile-setup') {
    $profileSetupWantsJson = pixelwarWantsJson();

    try {
        if (!pixelwarValidateCsrf()) {
            pixelwarFailCsrf('profile-setup', $profileSetupWantsJson);
        }

        $users = pixelwarRequireUserRepository($userRepository);
        $accounts = pixelwarRequireUserAccountService($userAccountService);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $firstname = trim((string) ($_POST['firstname'] ?? ''));
        $lastname = trim((string) ($_POST['lastname'] ?? ''));
        $profileImageFile = $_FILES['profile_image'] ?? [];
        $errors = [];

        $_SESSION['profile_setup_old'] = [
            'firstname' => $firstname,
            'lastname' => $lastname,
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

        if ($sessionUser === null || (int) $sessionUser['is_verified'] !== 1) {
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

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $firstname)) {
            $errors[] = 'Enter a valid first name.';
        }

        if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $lastname)) {
            $errors[] = 'Enter a valid last name.';
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

        $accounts->createProfileDetails($userId, $profileImage, $firstname, $lastname);

        unset($_SESSION['profile_setup_old'], $_SESSION['profile_setup_errors']);
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        $_SESSION['avatar_url'] = $profileImage;
        $_SESSION['avatar_initials'] = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Player profile saved. Welcome to Pixelwar.'
        ];

        if ($profileSetupWantsJson) {
            pixelwarJsonResponse([
                'success' => true,
                'message' => 'Player profile saved. Welcome to Pixelwar.',
                'redirect' => './?c=home',
            ]);
        }

        pixelwarRedirect('home');
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
