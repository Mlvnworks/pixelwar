<?php

final class RoomPlayerRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function ensureJoined(int $userId, int $roomId): array
    {
        $existing = $this->findByUserAndRoom($userId, $roomId);
        if ($existing !== null) {
            $existing['was_created'] = false;
            return $existing;
        }

        $status = 0;
        $statement = $this->connection->prepare(
            'INSERT INTO room_players (user_id, room_id, status, last_seen_at, started_at, completed_at)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP, NULL, NULL)'
        );
        $statement->bind_param('iii', $userId, $roomId, $status);
        $statement->execute();
        $roomPlayerId = (int) $statement->insert_id;
        $statement->close();

        return [
            'rp_id' => $roomPlayerId,
            'user_id' => $userId,
            'room_id' => $roomId,
            'status' => 0,
            'started_at' => null,
            'completed_at' => null,
            'was_created' => true,
        ];
    }

    public function findByUserAndRoom(int $userId, int $roomId): ?array
    {
        if ($userId <= 0 || $roomId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT rp_id, user_id, room_id, status, started_at, completed_at
             FROM room_players
             WHERE user_id = ?
                AND room_id = ?
             ORDER BY rp_id DESC
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $roomId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listJoinedForRoom(int $roomId): array
    {
        if ($roomId <= 0) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT
                room_players.rp_id,
                room_players.user_id,
                room_players.room_id,
                room_players.status,
                room_players.started_at,
                room_players.completed_at,
                users.username,
                users.email,
                user_details.firstname,
                user_details.lastname,
                user_details.student_number,
                avatar_images.source AS avatar_url
             FROM room_players
             INNER JOIN users ON users.user_id = room_players.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
             WHERE room_players.room_id = ?
                AND users.date_deleted IS NULL
             ORDER BY room_players.rp_id ASC'
        );
        $statement->bind_param('i', $roomId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    public function countJoinedForRoom(int $roomId): int
    {
        if ($roomId <= 0) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM room_players
             INNER JOIN users ON users.user_id = room_players.user_id
             WHERE room_players.room_id = ?
                AND users.date_deleted IS NULL'
        );
        $statement->bind_param('i', $roomId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function deleteByUserAndRoom(int $userId, int $roomId): bool
    {
        if ($userId <= 0 || $roomId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'DELETE FROM room_players
             WHERE user_id = ?
                AND room_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $roomId);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();

        return $deleted;
    }

    public function touchPresence(int $userId, int $roomId): bool
    {
        if ($userId <= 0 || $roomId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET last_seen_at = CURRENT_TIMESTAMP
             WHERE user_id = ?
                AND room_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $roomId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    /**
     * @return int[]
     */
    public function deleteInactiveWaitingForRoom(int $roomId, int $olderThanSeconds = 10): array
    {
        if ($roomId <= 0) {
            return [];
        }

        $olderThanSeconds = max(3, $olderThanSeconds);
        $cutoff = date('Y-m-d H:i:s', time() - $olderThanSeconds);

        $select = $this->connection->prepare(
            'SELECT user_id
             FROM room_players
             WHERE room_id = ?
                AND status = 0
                AND completed_at IS NULL
                AND (last_seen_at IS NULL OR last_seen_at < ?)'
        );
        $select->bind_param('is', $roomId, $cutoff);
        $select->execute();
        $rows = $select->get_result()->fetch_all(MYSQLI_ASSOC);
        $select->close();

        if ($rows === []) {
            return [];
        }

        $delete = $this->connection->prepare(
            'DELETE FROM room_players
             WHERE room_id = ?
                AND status = 0
                AND completed_at IS NULL
                AND (last_seen_at IS NULL OR last_seen_at < ?)'
        );
        $delete->bind_param('is', $roomId, $cutoff);
        $delete->execute();
        $delete->close();

        return array_values(array_map(static fn (array $row): int => (int) ($row['user_id'] ?? 0), $rows));
    }

    public function markWaiting(int $userId, int $roomId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET status = 0,
                 last_seen_at = CURRENT_TIMESTAMP,
                 started_at = NULL,
                 completed_at = NULL
             WHERE user_id = ?
                AND room_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $roomId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function markSolving(int $userId, int $roomId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET status = 1,
                 last_seen_at = CURRENT_TIMESTAMP,
                 started_at = COALESCE(started_at, CURRENT_TIMESTAMP),
                 completed_at = NULL
             WHERE user_id = ?
                AND room_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $roomId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function markCompleted(int $userId, int $roomId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET status = 2,
                 last_seen_at = CURRENT_TIMESTAMP,
                 started_at = COALESCE(started_at, CURRENT_TIMESTAMP),
                 completed_at = CURRENT_TIMESTAMP
             WHERE user_id = ?
                AND room_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $roomId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function markGaveUp(int $userId, int $roomId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET status = 3,
                 last_seen_at = CURRENT_TIMESTAMP,
                 completed_at = NULL
             WHERE user_id = ?
                AND room_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $roomId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function markUnfinishedAsGaveUpForRoom(int $roomId): bool
    {
        if ($roomId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET status = 3,
                 last_seen_at = CURRENT_TIMESTAMP,
                 completed_at = NULL
             WHERE room_id = ?
                AND completed_at IS NULL
                AND status <> 3'
        );
        $statement->bind_param('i', $roomId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }
}
