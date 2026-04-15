<?php
if ($requestMethod === 'GET' && $currentPage === 'email-verification' && isset($_GET['check_email'])) {
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $users = pixelwarRequireUserRepository($userRepository);
        $userId = (int) ($_SESSION['pending_verification_user_id'] ?? $_SESSION['user_id'] ?? 0);
        $email = trim((string) ($_GET['email'] ?? ''));

        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode([
                'available' => false,
                'message' => 'Start by creating your player account first.',
            ]);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'available' => false,
                'message' => $email === '' ? '' : 'Enter a valid email address.',
            ]);
            exit;
        }

        $exists = $users->emailExistsForOtherUser($email, $userId);

        echo json_encode([
            'available' => !$exists,
            'message' => $exists ? 'This email is already linked to another account.' : 'Email is available.',
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar verification email check error: ' . $err->getMessage());
        http_response_code(500);
        echo json_encode([
            'available' => false,
            'message' => 'Unable to check email right now.',
        ]);
    }

    exit;
}
