<?php
if ($requestMethod === 'POST' && $requestedPage === 'settings') {
    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('settings');
    }

    $users = pixelwarRequireUserRepository($userRepository);
    $accounts = pixelwarRequireUserAccountService($userAccountService);
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $firstname = trim((string) ($_POST['firstname'] ?? ''));
    $lastname = trim((string) ($_POST['lastname'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $profileImageFile = $_FILES['profile_image'] ?? [];
    $errors = [];

    if ($userId <= 0) {
        pixelwarRedirect('login');
    }

    if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $firstname)) {
        $errors[] = 'Enter a valid first name.';
    }

    if (!preg_match('/^[A-Za-z][A-Za-z .\'-]{1,79}$/', $lastname)) {
        $errors[] = 'Enter a valid last name.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($email !== '' && $users->emailExistsForOtherUser($email, $userId)) {
        $errors[] = 'Email is already registered.';
    }

    $uploadError = (int) ($profileImageFile['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Profile image upload failed. Please choose another file.';
    }

    $currentUser = $users->findUserForSettings($userId);

    if (!$currentUser) {
        pixelwarRedirect('login');
    }

    $currentEmail = trim((string) ($currentUser['email'] ?? ''));
    $emailChanged = strcasecmp($currentEmail, $email) !== 0;

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

        $isVerified = $emailChanged ? 0 : (int) ($currentUser['is_verified'] ?? 1);
        $imageId = $accounts->saveSettingsProfile(
            $userId,
            $firstname,
            $lastname,
            $email,
            $isVerified,
            $imageId,
            $newAvatarUrl,
            $emailChanged
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
    $_SESSION['firstname'] = $firstname;
    $_SESSION['lastname'] = $lastname;
    $_SESSION['avatar_initials'] = strtoupper(substr($firstname, 0, 1) . substr($lastname, 0, 1));
    $_SESSION['avatar_url'] = $avatarUrl;

    if ($emailChanged) {
        pixelwarPrepareAccountVerification(
            $auth,
            $tools,
            $userId,
            $email,
            (string) ($currentUser['username'] ?? ($_SESSION['username'] ?? 'Player'))
        );
        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Email updated. Verify your new email address to continue.'
        ];
        pixelwarRedirect('email-verification');
    }

    $_SESSION['alert'] = [
        'error' => false,
        'content' => 'Settings saved.'
    ];

    header('Location: ./?c=settings&updated=1');
    exit;
}
