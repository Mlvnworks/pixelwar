<?php
if ($adminRequestMethod === 'GET' && $adminRequestedPage === 'teachers' && isset($_GET['check_teacher_field'])) {
    try {
        $users = adminPanelRequireUserRepository($userRepository);
        $field = (string) ($_GET['field'] ?? '');
        $value = trim((string) ($_GET['value'] ?? ''));

        if (!in_array($field, ['username', 'email'], true)) {
            adminPanelJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => 'Unsupported field.',
            ], 400);
        }

        if ($value === '') {
            adminPanelJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => '',
            ]);
        }

        if ($field === 'username') {
            $isValid = preg_match('/^[A-Za-z0-9_]{3,32}$/', $value) === 1;
            $message = $isValid ? '' : 'Username must be 3-32 characters and only use letters, numbers, or underscores.';

            if (!$isValid) {
                adminPanelJsonResponse([
                    'valid' => false,
                    'available' => false,
                    'message' => $message,
                ]);
            }

            $exists = $users->usernameExists($value);
            adminPanelJsonResponse([
                'valid' => true,
                'available' => !$exists,
                'message' => $exists ? 'Username is already taken.' : 'Username is available.',
            ]);
        }

        $isValid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        $message = $isValid ? '' : 'Enter a valid teacher email address.';

        if (!$isValid) {
            adminPanelJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => $message,
            ]);
        }

        $exists = $users->emailExists($value);
        adminPanelJsonResponse([
            'valid' => true,
            'available' => !$exists,
            'message' => $exists ? 'Email is already registered.' : 'Email is available.',
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar admin teacher availability error: ' . $err->getMessage());
        adminPanelJsonResponse([
            'valid' => false,
            'available' => false,
            'message' => 'Unable to check availability right now.',
        ], 500);
    }
}
