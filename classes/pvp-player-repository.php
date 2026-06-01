<?php

final class PvpPlayerRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function create(int $pvpId, int $userId, int $status = 0): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO pvp_players (pvp_id, user_id, status)
             VALUES (?, ?, ?)'
        );
        $statement->bind_param('iii', $pvpId, $userId, $status);
        $statement->execute();
        $playerId = (int) $statement->insert_id;
        $statement->close();

        return $playerId;
    }

    /**
     * @return array<int, int>
     */
    public function listUserIdsForMatch(int $pvpId): array
    {
        if ($pvpId <= 0) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT user_id
             FROM pvp_players
             WHERE pvp_id = ?
             ORDER BY p_pvp_id ASC'
        );
        $statement->bind_param('i', $pvpId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return array_map(static fn (array $row): int => (int) ($row['user_id'] ?? 0), $rows);
    }

    public function updateStatusForUser(int $pvpId, int $userId, int $status): void
    {
        if ($pvpId <= 0 || $userId <= 0) {
            return;
        }

        $statement = $this->connection->prepare(
            'UPDATE pvp_players
             SET status = ?
             WHERE pvp_id = ?
                AND user_id = ?'
        );
        $statement->bind_param('iii', $status, $pvpId, $userId);
        $statement->execute();
        $statement->close();
    }

    public function findByMatchAndUser(int $pvpId, int $userId): ?array
    {
        if ($pvpId <= 0 || $userId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT p_pvp_id, pvp_id, user_id, status, created_at
             FROM pvp_players
             WHERE pvp_id = ?
                AND user_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $pvpId, $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }
}
