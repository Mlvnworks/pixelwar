<?php
if ($requestMethod === 'GET' && $requestedPage === 'signup' && isset($_GET['check_signup'])) {
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $users = pixelwarRequireUserRepository($userRepository);
        $field = (string) ($_GET['field'] ?? '');
        $value = trim((string) ($_GET['value'] ?? ''));
        $allowedFields = ['username', 'email'];

        if (!in_array($field, $allowedFields, true) || $value === '') {
            echo json_encode(['available' => true]);
            exit;
        }

        $exists = $field === 'username'
            ? $users->usernameExists($value)
            : $users->emailExists($value);

        echo json_encode([
            'available' => !$exists,
            'message' => $exists
                ? ($field === 'username' ? 'Username is already taken.' : 'Email is already registered.')
                : '',
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar signup availability error: ' . $err->getMessage());
        http_response_code(500);
        echo json_encode([
            'available' => false,
            'message' => 'Unable to check availability right now.',
        ]);
    }

    exit;
}
