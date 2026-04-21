<?php
if ($requestMethod === 'GET' && $requestedPage === 'profile-setup' && isset($_GET['check_username'])) {
    try {
        $users = pixelwarRequireUserRepository($userRepository);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $roleId = (int) ($_SESSION['role_id'] ?? 0);
        $username = trim((string) ($_GET['username'] ?? ''));

        if ($userId <= 0 || $roleId !== pixelwarTeacherRoleId()) {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => 'Unauthorized username check.',
            ], 403);
        }

        if ($username === '') {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => '',
            ]);
        }

        if (preg_match('/^[A-Za-z0-9_]{3,32}$/', $username) !== 1) {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => 'Username must be 3-32 characters and only use letters, numbers, or underscores.',
            ]);
        }

        $exists = $users->usernameExistsForOtherUser($username, $userId);
        pixelwarJsonResponse([
            'valid' => true,
            'available' => !$exists,
            'message' => $exists ? 'Username is already taken.' : 'Username is available.',
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar profile setup username availability error: ' . $err->getMessage());
        pixelwarJsonResponse([
            'valid' => false,
            'available' => false,
            'message' => 'Unable to check username right now.',
        ], 500);
    }
}
