<?php
if ($requestMethod === 'POST' && $requestedPage === 'settings') {
    if ((string) ($_POST['settings_action'] ?? '') === 'password_reset') {
        return;
    }

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('settings');
    }

    $users = pixelwarRequireUserRepository($userRepository);
    $accounts = pixelwarRequireUserAccountService($userAccountService);
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $username = trim((string) ($_POST['username'] ?? ''));
    $profileImageFile = $_FILES['profile_image'] ?? [];
    $errors = [];

    if ($userId <= 0) {
        pixelwarRedirect('login');
    }

    $currentUser = $users->findUserForSettings($userId);

    if (!$currentUser) {
        pixelwarRedirect('login');
    }

    $firstname = trim((string) ($currentUser['firstname'] ?? ''));
    $lastname = trim((string) ($currentUser['lastname'] ?? ''));
    $email = trim((string) ($currentUser['email'] ?? ''));
    $currentUsername = trim((string) ($currentUser['username'] ?? ''));
    $usernameChanged = strcmp($currentUsername, $username) !== 0;

    if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
        $errors[] = 'Username must contain 3-32 letters, numbers, or underscores.';
    } elseif ($users->usernameExistsForOtherUser($username, $userId)) {
        $errors[] = 'Username is already taken.';
    }

    if ($usernameChanged) {
        $usernameAvailableAt = $users->accountChangeAvailableAt($userId, 'username');
        if ($usernameAvailableAt > time()) {
            $errors[] = 'Username can only be changed every 15 days. Try again on ' . date('M j, Y g:i A', $usernameAvailableAt) . '.';
        }
    }

    $uploadError = (int) ($profileImageFile['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Profile image upload failed. Please choose another file.';
    }

    if ($errors !== []) {
        $_SESSION['alert'] = [
            'error' => true,
            'content' => implode(' ', $errors)
        ];
        pixelwarRedirect('settings');
    }

    $existingDetails = $users->findUserDetailsAvatar($userId);
    $imageId = (int) ($existingDetails['image_id'] ?? 0);
    $avatarUrl = trim((string) ($existingDetails['avatar_url'] ?? ''));
    $previousAvatarUrl = $avatarUrl;
    $newAvatarUrl = null;
    $supabaseStorage = null;

    try {
        if ($uploadError === UPLOAD_ERR_OK) {
            $supabaseStorage = new SupabaseStorage(
                SUPABASE_URL,
                SUPABASE_SERVICE_ROLE_KEY,
                SUPABASE_STORAGE_BUCKET,
                SUPABASE_STORAGE_AVATAR_FOLDER
            );
            $avatarUrl = $supabaseStorage->uploadProfileImage($profileImageFile, $userId);
            $newAvatarUrl = $avatarUrl;
        }

        if ($imageId <= 0 && $avatarUrl === '') {
            throw new RuntimeException('Profile image is required before updating settings.');
        }

        $isVerified = (int) ($currentUser['is_verified'] ?? 1);
        $imageId = $accounts->saveSettingsProfile(
            $userId,
            $firstname,
            $lastname,
            $email,
            $isVerified,
            $imageId,
            $newAvatarUrl,
            false,
            $usernameChanged ? $username : null
        );
    } catch (Throwable $err) {
        error_log('Pixelwar settings update error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Settings update failed. Please try again.'
        ];
        pixelwarRedirect('settings');
    }

    if (
        $uploadError === UPLOAD_ERR_OK
        && $supabaseStorage instanceof SupabaseStorage
        && $previousAvatarUrl !== ''
        && $previousAvatarUrl !== $avatarUrl
    ) {
        try {
            $supabaseStorage->deletePublicObject($previousAvatarUrl);
        } catch (Throwable $err) {
            error_log('Pixelwar previous avatar delete error: ' . $err->getMessage());
        }
    }

    $_SESSION['email'] = $email;
    $_SESSION['username'] = $username;
    $_SESSION['firstname'] = $firstname;
    $_SESSION['lastname'] = $lastname;
    $_SESSION['avatar_initials'] = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
    $_SESSION['avatar_url'] = function_exists('pixelwarAvatarUrl') ? pixelwarAvatarUrl($avatarUrl, 128) : $avatarUrl;

    pixelwarLogActivity($activityLogRepository ?? null, $userId, 'settings', 'Updated account settings.');

    $_SESSION['alert'] = [
        'error' => false,
        'content' => 'Settings saved.'
    ];

    header('Location: ./?c=settings&updated=1');
    exit;
}
