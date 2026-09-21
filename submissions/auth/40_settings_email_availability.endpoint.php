<?php
if ($requestMethod === 'GET' && $currentPage === 'settings' && (isset($_GET['check_email']) || isset($_GET['check_username']))) {
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $users = pixelwarRequireUserRepository($userRepository);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode([
                'available' => false,
                'message' => 'Login required.',
            ]);
            exit;
        }

        if (isset($_GET['check_username'])) {
            $username = trim((string) ($_GET['username'] ?? ''));

            if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
                echo json_encode([
                    'available' => false,
                    'message' => $username === '' ? '' : 'Use 3-32 letters, numbers, or underscores.',
                ]);
                exit;
            }

            $exists = $users->usernameExistsForOtherUser($username, $userId);
            echo json_encode([
                'available' => !$exists,
                'message' => $exists ? 'This username is already taken.' : 'Username is available.',
            ]);
            exit;
        }

        $email = trim((string) ($_GET['email'] ?? ''));
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
        error_log('Pixelwar settings email check error: ' . $err->getMessage());
        http_response_code(500);
        echo json_encode([
            'available' => false,
            'message' => 'Unable to check email right now.',
        ]);
    }

    exit;
}
