<?php

class VerificationRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function findLatest(int $userId, string $type): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT ev_id, token, status, request_timestamp FROM verifications WHERE user_id = ? AND `type` = ? ORDER BY request_timestamp DESC LIMIT 1'
        );
        $statement->bind_param('is', $userId, $type);
        $statement->execute();
        $verification = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $verification ?: null;
    }

    public function create(int $userId, string $type, string $tokenHash, int $status): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO verifications (user_id, `type`, token, status) VALUES (?, ?, ?, ?)'
        );
        $statement->bind_param('issi', $userId, $type, $tokenHash, $status);
        $statement->execute();
        $statement->close();
    }

    public function updateStatus(int $verificationId, int $status): void
    {
        $statement = $this->connection->prepare('UPDATE verifications SET status = ? WHERE ev_id = ?');
        $statement->bind_param('ii', $status, $verificationId);
        $statement->execute();
        $statement->close();
    }

    public function expirePending(int $userId, string $type): void
    {
        $expiredStatus = -1;
        $pendingStatus = 0;
        $statement = $this->connection->prepare('UPDATE verifications SET status = ? WHERE user_id = ? AND `type` = ? AND status = ?');
        $statement->bind_param('iisi', $expiredStatus, $userId, $type, $pendingStatus);
        $statement->execute();
        $statement->close();
    }
}
