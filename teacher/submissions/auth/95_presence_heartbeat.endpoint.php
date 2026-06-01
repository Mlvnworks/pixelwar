<?php

if ($teacherRequestMethod === 'POST' && (string) ($_POST['presence_action'] ?? '') === 'heartbeat') {
    if (!teacherPanelValidateCsrf()) {
        teacherPanelJsonResponse(['success' => false], 403);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId > 0 && $userRepository instanceof UserRepository) {
        $userRepository->touchLastSeen($userId);
    }

    teacherPanelJsonResponse(['success' => true]);
}
