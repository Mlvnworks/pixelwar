<?php
if ($requestMethod === 'GET' && $requestedPage === 'profile-setup' && isset($_GET['check_student_number'])) {
    try {
        $users = pixelwarRequireUserRepository($userRepository);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $roleId = (int) ($_SESSION['role_id'] ?? 0);
        $studentNumber = strtoupper(trim((string) ($_GET['student_number'] ?? '')));

        if ($userId <= 0 || $roleId !== pixelwarStudentRoleId()) {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => 'Unauthorized student number check.',
            ], 403);
        }

        if ($studentNumber === '') {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => '',
            ]);
        }

        if (preg_match('/^TAL\d{4}-\d{5}$/', $studentNumber) !== 1) {
            pixelwarJsonResponse([
                'valid' => false,
                'available' => false,
                'message' => 'Use the exact format TAL2024-00287.',
            ]);
        }

        // Profile setup only accepts a student number that has never been assigned.
        $exists = $users->studentNumberExistsForOtherUser($studentNumber);
        pixelwarJsonResponse([
            'valid' => true,
            'available' => !$exists,
            'message' => $exists ? 'This student number is already registered.' : 'Student number is available.',
        ]);
    } catch (Throwable $err) {
        error_log('Pixelwar profile setup student number availability error: ' . $err->getMessage());
        pixelwarJsonResponse([
            'valid' => false,
            'available' => false,
            'message' => 'Unable to check the student number right now.',
        ], 500);
    }
}
