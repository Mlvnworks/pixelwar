<?php

if ($requestMethod === 'POST' && $requestedPage === 'versus' && (string) ($_POST['versus_action'] ?? '') === 'snapshot') {
    if (!pixelwarValidateCsrf()) {
        pixelwarJsonResponse(['success' => false], 403);
    }

    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

    if (!$userRepository instanceof UserRepository) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Online student list is unavailable.'], 422);
    }

    $rankForPoints = static function (int $points) use ($rankRepository): string {
        if ($rankRepository instanceof RankRepository) {
            $rankProgress = $rankRepository->progressForPoints($points);

            return (string) ($rankProgress['current_name'] ?? 'Beginner');
        }

        return 'Beginner';
    };

    $players = array_map(static function (array $player) use ($rankForPoints): array {
        $displayName = trim((string) ($player['username'] ?? 'Student')) ?: 'Student';
        $accentOptions = ['yellow', 'cyan', 'orange', 'mint'];
        $accent = $accentOptions[((int) ($player['user_id'] ?? 0)) % count($accentOptions)];
        $points = (int) ($player['points'] ?? 0);
        $solves = (int) ($player['solves'] ?? 0);

        return [
            'user_id' => (int) ($player['user_id'] ?? 0),
            'name' => $displayName,
            'username' => (string) ($player['username'] ?? ''),
            'initials' => strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2)),
            'avatar_url' => (string) ($player['avatar_url'] ?? ''),
            'rank' => $rankForPoints($points),
            'status' => 'Online',
            'solves' => $solves,
            'streak' => 1 + ($solves % 14),
            'points' => $points,
            'accent' => $accent,
        ];
    }, $userRepository->listOnlineStudentsForVersus($currentUserId, 120));

    pixelwarJsonResponse([
        'success' => true,
        'players' => $players,
    ]);
}
