<?php
if ($requestMethod === 'POST' && $requestedPage === 'challenge' && (string) ($_POST['comment_action'] ?? '') === 'create') {
    $challengeId = (int) ($_POST['challenge_id'] ?? ($_GET['id'] ?? 0));
    $redirectUrl = './?c=challenge&id=' . max(0, $challengeId) . '&comments=1';

    $redirectToChallenge = static function () use ($redirectUrl): void {
        header('Location: ' . $redirectUrl);
        exit;
    };

    if (!pixelwarValidateCsrf()) {
        $_SESSION['alert'] = [
            'error' => true,
            'content' => 'Security check failed. Refresh the page and try again.',
        ];
        $redirectToChallenge();
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $comment = trim((string) ($_POST['comment'] ?? ''));
    $comment = preg_replace('/[ \t]+/', ' ', $comment) ?? $comment;

    try {
        if (!$connection instanceof mysqli) {
            throw new RuntimeException('Database connection is not available.');
        }

        if ($userId <= 0) {
            throw new RuntimeException('Log in before posting a comment.');
        }

        if ($challengeId <= 0) {
            throw new RuntimeException('Challenge is missing.');
        }

        if ($comment === '') {
            throw new RuntimeException('Enter a comment before posting.');
        }

        $commentLength = function_exists('mb_strlen') ? mb_strlen($comment) : strlen($comment);
        if ($commentLength > 1000) {
            throw new RuntimeException('Comment must be 1000 characters or fewer.');
        }

        $challengeStatement = $connection->prepare(
            'SELECT challenge_id
             FROM challenges
             WHERE challenge_id = ?
                AND status = 1
                AND date_deleted IS NULL
             LIMIT 1'
        );
        $challengeStatement->bind_param('i', $challengeId);
        $challengeStatement->execute();
        $challenge = $challengeStatement->get_result()->fetch_assoc();
        $challengeStatement->close();

        if (!$challenge) {
            throw new RuntimeException('This challenge is not available for comments.');
        }

        $statement = $connection->prepare(
            'INSERT INTO comments (user_id, challenge_id, `comment`)
             VALUES (?, ?, ?)'
        );
        $statement->bind_param('iis', $userId, $challengeId, $comment);
        $statement->execute();
        $statement->close();

        $_SESSION['alert'] = [
            'error' => false,
            'content' => 'Comment posted.',
        ];
    } catch (Throwable $err) {
        error_log('Pixelwar challenge comment error: ' . $err->getMessage());
        $_SESSION['alert'] = [
            'error' => true,
            'content' => APP_DEBUG ? $err->getMessage() : 'Unable to post comment right now.',
        ];
    }

    $redirectToChallenge();
}
