<?php
if ($requestMethod === 'GET' && $requestedPage === 'profile-setup' && isset($_GET['check_email'])) {
    try {
        $users = pixelwarRequireUserRepository($userRepository);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $roleId = (int) ($_SESSION['role_id'] ?? 0);
        $email = trim((string) ($_GET['email'] ?? ''));

        if ($userId <= 0 || $roleId !== pixelwarAdminRoleId()) {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => 'Unauthorized email check.',
            ], 403);
        }

        if ($email === '') {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => '',
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => 'Enter a valid email address.',
            ]);
        }

        $exists = $users->emailExistsForOtherUser($email, $userId);
        pixelwarJsonResponse([
            'valid' => true,
            'available' => !$exists,
            'message' => $exists ? 'Email is already registered.' : 'Email is available.',
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar profile setup email availability error: ' . $err->getMessage());
        pixelwarJsonResponse([
            'valid' => false,
            'available' => false,
            'message' => 'Unable to check email right now.',
        ], 500);
    }
}
