<?php

if ($adminRequestMethod === 'POST' && (string) ($_POST['presence_action'] ?? '') === 'heartbeat') {
    if (!adminPanelValidateCsrf()) {
        adminPanelJsonResponse(['success' => false], 403);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId > 0 && $userRepository instanceof UserRepository) {
        $userRepository->touchLastSeen($userId);
    }

    adminPanelJsonResponse(['success' => true]);
}
