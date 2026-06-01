<?php

if ($requestMethod === 'POST' && $requestedPage === 'versus' && (string) ($_POST['versus_action'] ?? '') === 'invite') {
    if (!pixelwarValidateCsrf()) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Your session has expired.'], 403);
    }

    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $targetUserId = max(0, (int) ($_POST['target_user_id'] ?? 0));

    if ($currentUserId <= 0) {
        pixelwarJsonResponse(['success' => false, 'message' => 'You must be logged in to send an invite.'], 401);
    }

    if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Select a valid player to invite.'], 422);
    }

    if (!$userRepository instanceof UserRepository) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Player records are unavailable right now.'], 422);
    }

    if (!$pusherService instanceof PusherService || !$pusherService->isConfigured()) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Realtime invites are unavailable right now.'], 503);
    }

    $inviter = $userRepository->findSessionUser($currentUserId);
    if (!$inviter) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to load your player profile.'], 422);
    }

    $target = $userRepository->findOnlineStudentForVersus($targetUserId, $currentUserId);
    if (!$target) {
        pixelwarJsonResponse(['success' => false, 'message' => 'That player is no longer online.'], 404);
    }

    $inviterName = trim((string) ($inviter['firstname'] ?? '') . ' ' . (string) ($inviter['lastname'] ?? ''));
    $inviterName = $inviterName !== '' ? $inviterName : trim((string) ($inviter['username'] ?? 'Student'));
    $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $inviterName) ?: 'ST', 0, 2));

    try {
        $pusherService->trigger(
            'user-' . $targetUserId,
            'versus-invite',
            [
                'inviter_user_id' => $currentUserId,
                'inviter_name' => $inviterName,
                'inviter_username' => (string) ($inviter['username'] ?? ''),
                'inviter_avatar_url' => trim((string) ($inviter['avatar_url'] ?? '')),
                'inviter_initials' => $initials,
                'sent_at' => date(DATE_ATOM),
                'versus_url' => './?c=versus',
            ]
        );
    } catch (Throwable $throwable) {
        error_log('Pixelwar versus invite error: ' . $throwable->getMessage());
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to send the invite right now.'], 502);
    }

    pixelwarJsonResponse([
        'success' => true,
        'message' => 'Invite sent.',
    ]);
}

if ($requestMethod === 'POST' && $requestedPage === 'versus' && (string) ($_POST['versus_action'] ?? '') === 'decline_invite') {
    if (!pixelwarValidateCsrf()) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Your session has expired.'], 403);
    }

    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $inviterUserId = max(0, (int) ($_POST['inviter_user_id'] ?? 0));

    if ($currentUserId <= 0) {
        pixelwarJsonResponse(['success' => false, 'message' => 'You must be logged in to respond to an invite.'], 401);
    }

    if ($inviterUserId <= 0 || $inviterUserId === $currentUserId) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Invalid inviter.'], 422);
    }

    if (!$userRepository instanceof UserRepository) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Player records are unavailable right now.'], 422);
    }

    if (!$pusherService instanceof PusherService || !$pusherService->isConfigured()) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Realtime invites are unavailable right now.'], 503);
    }

    $decliner = $userRepository->findSessionUser($currentUserId);
    if (!$decliner) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to load your player profile.'], 422);
    }

    $declinerName = trim((string) ($decliner['firstname'] ?? '') . ' ' . (string) ($decliner['lastname'] ?? ''));
    $declinerName = $declinerName !== '' ? $declinerName : trim((string) ($decliner['username'] ?? 'Student'));

    try {
        $pusherService->trigger(
            'user-' . $inviterUserId,
            'versus-invite-declined',
            [
                'decliner_user_id' => $currentUserId,
                'decliner_name' => $declinerName,
                'decliner_username' => (string) ($decliner['username'] ?? ''),
                'sent_at' => date(DATE_ATOM),
            ]
        );
    } catch (Throwable $throwable) {
        error_log('Pixelwar versus decline error: ' . $throwable->getMessage());
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to send the decline right now.'], 502);
    }

    pixelwarJsonResponse([
        'success' => true,
        'message' => 'Invite declined.',
    ]);
}

if ($requestMethod === 'POST' && $requestedPage === 'versus' && (string) ($_POST['versus_action'] ?? '') === 'accept_invite') {
    if (!pixelwarValidateCsrf()) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Your session has expired.'], 403);
    }

    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $inviterUserId = max(0, (int) ($_POST['inviter_user_id'] ?? 0));

    if ($currentUserId <= 0) {
        pixelwarJsonResponse(['success' => false, 'message' => 'You must be logged in to accept an invite.'], 401);
    }

    if ($inviterUserId <= 0 || $inviterUserId === $currentUserId) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Invalid inviter.'], 422);
    }

    if (!$userRepository instanceof UserRepository) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Player records are unavailable right now.'], 422);
    }

    if (
        !$challengeRepository instanceof ChallengeRepository
        || !$pvpMatchRepository instanceof PvpMatchRepository
        || !$pvpPlayerRepository instanceof PvpPlayerRepository
        || !$connection instanceof mysqli
    ) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Versus matchmaking is unavailable right now.'], 422);
    }

    if (!$pusherService instanceof PusherService || !$pusherService->isConfigured()) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Realtime invites are unavailable right now.'], 503);
    }

    $acceptor = $userRepository->findSessionUser($currentUserId);
    $inviter = $userRepository->findSessionUser($inviterUserId);
    if (!$acceptor || !$inviter) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to load player profiles.'], 422);
    }

    $acceptorName = trim((string) ($acceptor['firstname'] ?? '') . ' ' . (string) ($acceptor['lastname'] ?? ''));
    $acceptorName = $acceptorName !== '' ? $acceptorName : trim((string) ($acceptor['username'] ?? 'Student'));
    $inviterName = trim((string) ($inviter['firstname'] ?? '') . ' ' . (string) ($inviter['lastname'] ?? ''));
    $inviterName = $inviterName !== '' ? $inviterName : trim((string) ($inviter['username'] ?? 'Student'));
    $publicChallenges = array_values(array_filter(
        $challengeRepository->listLatestPublicCreated(60),
        static fn (array $challenge): bool => (int) ($challenge['challenge_id'] ?? 0) > 0
    ));

    if ($publicChallenges === []) {
        pixelwarJsonResponse(['success' => false, 'message' => 'No public challenges are available for versus right now.'], 404);
    }

    $selectedChallenge = $publicChallenges[random_int(0, count($publicChallenges) - 1)];
    $selectedChallengeId = (int) ($selectedChallenge['challenge_id'] ?? 0);
    if ($selectedChallengeId <= 0) {
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to prepare the versus challenge right now.'], 422);
    }
    $gameStartAtMs = ((int) floor(microtime(true) * 1000)) + 8200;
    $rankForPoints = static function (int $points) use ($rankRepository): string {
        if ($rankRepository instanceof RankRepository) {
            $rankProgress = $rankRepository->progressForPoints($points);

            return (string) ($rankProgress['current_name'] ?? 'Beginner');
        }

        return 'Beginner';
    };
    $inviterRank = $rankForPoints($userRepository instanceof UserRepository ? $userRepository->totalPlayerProgressPointsForUser($inviterUserId) : 0);
    $acceptorRank = $rankForPoints($userRepository instanceof UserRepository ? $userRepository->totalPlayerProgressPointsForUser($currentUserId) : 0);

    try {
        $connection->begin_transaction();
        $pvpId = $pvpMatchRepository->create($inviterUserId, $selectedChallengeId);
        $pvpPlayerRepository->create($pvpId, $inviterUserId, 0);
        $pvpPlayerRepository->create($pvpId, $currentUserId, 0);
        $connection->commit();
    } catch (Throwable $throwable) {
        $connection->rollback();
        error_log('Pixelwar versus match creation error: ' . $throwable->getMessage());
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to create the versus match right now.'], 502);
    }

    $matchingUrlForInviter = './?c=matching'
        . '&pvp_id=' . $pvpId
        . '&challenge_id=' . $selectedChallengeId
        . '&game_start_at_ms=' . $gameStartAtMs
        . '&opponent=' . rawurlencode($acceptorName)
        . '&opponent_username=' . rawurlencode((string) ($acceptor['username'] ?? 'opponent'))
        . '&opponent_avatar_url=' . rawurlencode(trim((string) ($acceptor['avatar_url'] ?? '')))
        . '&opponent_rank=' . rawurlencode($acceptorRank);
    $matchingUrlForAcceptor = './?c=matching'
        . '&pvp_id=' . $pvpId
        . '&challenge_id=' . $selectedChallengeId
        . '&game_start_at_ms=' . $gameStartAtMs
        . '&opponent=' . rawurlencode($inviterName)
        . '&opponent_username=' . rawurlencode((string) ($inviter['username'] ?? 'opponent'))
        . '&opponent_avatar_url=' . rawurlencode(trim((string) ($inviter['avatar_url'] ?? '')))
        . '&opponent_rank=' . rawurlencode($inviterRank);

    try {
        $pusherService->trigger(
            'user-' . $inviterUserId,
            'versus-invite-accepted',
            [
                'acceptor_user_id' => $currentUserId,
                'acceptor_name' => $acceptorName,
                'acceptor_username' => (string) ($acceptor['username'] ?? ''),
                'redirect_url' => $matchingUrlForInviter,
            ]
        );
    } catch (Throwable $throwable) {
        error_log('Pixelwar versus accept error: ' . $throwable->getMessage());
        pixelwarJsonResponse(['success' => false, 'message' => 'Unable to accept the invite right now.'], 502);
    }

    pixelwarJsonResponse([
        'success' => true,
        'message' => 'Invite accepted.',
        'redirect_url' => $matchingUrlForAcceptor,
    ]);
}
