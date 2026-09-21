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
            'INSERT INTO room_players (user_id, room_id, status, strict_mode_score, last_seen_at, started_at, completed_at)
             VALUES (?, ?, ?, 0, CURRENT_TIMESTAMP, NULL, NULL)'
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
            'strict_mode_score' => 0,
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
            'SELECT rp_id, user_id, room_id, status, strict_mode_score, started_at, completed_at
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
                room_players.strict_mode_score,
                room_players.started_at,
                room_players.completed_at,
                code_solution.css_code AS code_solution_url,
                code_solution.grade AS code_solution_grade,
                users.username,
                users.email,
                user_details.firstname,
                user_details.lastname,
                user_details.student_number,
                user_details.section,
                avatar_images.source AS avatar_url,
                COALESCE(player_points.points, 0) AS points
             FROM room_players
             INNER JOIN users ON users.user_id = room_players.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
             LEFT JOIN code_solution ON code_solution.rp_id = room_players.rp_id
             LEFT JOIN (
                SELECT user_id, SUM(points) AS points
                FROM player_progress
                GROUP BY user_id
             ) AS player_points ON player_points.user_id = users.user_id
             WHERE room_players.room_id = ?
                AND users.date_deleted IS NULL
             ORDER BY room_players.rp_id ASC'
        );
        $statement->bind_param('i', $roomId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $this->withOptimizedAvatarRows($rows);
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

        $roomPlayer = $this->findByUserAndRoom($userId, $roomId);
        if ($roomPlayer === null) {
            return false;
        }

        $roomPlayerId = (int) ($roomPlayer['rp_id'] ?? 0);
        $this->connection->begin_transaction();

        try {
            $deleteSolution = $this->connection->prepare('DELETE FROM code_solution WHERE rp_id = ?');
            $deleteSolution->bind_param('i', $roomPlayerId);
            $deleteSolution->execute();
            $deleteSolution->close();

            $statement = $this->connection->prepare(
                'DELETE FROM room_players
                 WHERE rp_id = ?
                    AND user_id = ?
                    AND room_id = ?
                 LIMIT 1'
            );
            $statement->bind_param('iii', $roomPlayerId, $userId, $roomId);
            $statement->execute();
            $deleted = $statement->affected_rows > 0;
            $statement->close();

            $this->connection->commit();
            return $deleted;
        } catch (Throwable $error) {
            $this->connection->rollback();
            throw $error;
        }
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
                 strict_mode_score = 0,
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
                 strict_mode_score = 100,
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

    public function saveHardCodeSolution(int $roomPlayerId, string $solutionUrl): void
    {
        if ($roomPlayerId <= 0 || trim($solutionUrl) === '') {
            throw new InvalidArgumentException('A valid code solution is required.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO code_solution (rp_id, css_code)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE css_code = VALUES(css_code)'
        );
        $statement->bind_param('is', $roomPlayerId, $solutionUrl);
        $statement->execute();
        $statement->close();
    }

    public function updateHardCodeGrade(int $roomPlayerId, int $roomId, int $grade): bool
    {
        if ($roomPlayerId <= 0 || $roomId <= 0 || $grade < 0 || $grade > 2147483647) {
            return false;
        }

        $check = $this->connection->prepare(
            'SELECT 1
             FROM code_solution
             INNER JOIN room_players ON room_players.rp_id = code_solution.rp_id
             WHERE code_solution.rp_id = ?
                AND room_players.room_id = ?
             LIMIT 1'
        );
        $check->bind_param('ii', $roomPlayerId, $roomId);
        $check->execute();
        $exists = $check->get_result()->fetch_row() !== null;
        $check->close();

        if (!$exists) {
            return false;
        }

        $statement = $this->connection->prepare('UPDATE code_solution SET grade = ? WHERE rp_id = ? LIMIT 1');
        $statement->bind_param('ii', $grade, $roomPlayerId);
        $statement->execute();
        $statement->close();

        return true;
    }

    public function markHardCodeSubmitted(int $userId, int $roomId): bool
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

    public function markStrictSubmittedFailed(int $userId, int $roomId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET status = 3,
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

    public function updateStrictModeScore(int $userId, int $roomId, int $score): bool
    {
        if ($userId <= 0 || $roomId <= 0) {
            return false;
        }

        $normalizedScore = max(0, min(100, $score));
        $statement = $this->connection->prepare(
            'UPDATE room_players
             SET strict_mode_score = ?,
                 last_seen_at = CURRENT_TIMESTAMP
             WHERE user_id = ?
                AND room_id = ?
             LIMIT 1'
        );
        $statement->bind_param('iii', $normalizedScore, $userId, $roomId);
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

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function withOptimizedAvatarRows(array $rows): array
    {
        foreach ($rows as $index => $row) {
            if (trim((string) ($row['avatar_url'] ?? '')) !== '' && function_exists('pixelwarAvatarUrl')) {
                $row['avatar_url'] = pixelwarAvatarUrl((string) $row['avatar_url'], 128);
            }

            $rows[$index] = $row;
        }

        return $rows;
    }
}
