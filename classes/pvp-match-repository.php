<?php

final class PvpMatchRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function create(int $userId, int $challengeId): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO pvp_matches (user_id, challenge_id)
             VALUES (?, ?)'
        );
        $statement->bind_param('ii', $userId, $challengeId);
        $statement->execute();
        $pvpId = (int) $statement->insert_id;
        $statement->close();

        return $pvpId;
    }

    public function findById(int $pvpId): ?array
    {
        if ($pvpId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT pvp_id, user_id, challenge_id
             FROM pvp_matches
             WHERE pvp_id = ?
             LIMIT 1'
        );
        $statement->bind_param('i', $pvpId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }
}
