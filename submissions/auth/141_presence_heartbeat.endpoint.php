<?php

if ($requestMethod === 'POST' && (string) ($_POST['presence_action'] ?? '') === 'heartbeat') {
    $wantsJson = pixelwarWantsJson();

    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('login', $wantsJson);
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId > 0 && $userRepository instanceof UserRepository) {
        $userRepository->touchLastSeen($userId);
    }

    pixelwarJsonResponse(['success' => true]);
}
