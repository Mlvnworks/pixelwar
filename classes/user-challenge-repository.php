<?php

final class UserChallengeRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function findOngoing(int $userId, int $challengeId, int $roomId = 0, int $pvpId = 0): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT user_challenge.uc_id, user_challenge.challenge_id, user_challenge.user_id, user_challenge.room_id, user_challenge.pvp_id, user_challenge.started_at, user_challenge.completed_at
             FROM user_challenge
             LEFT JOIN rooms ON rooms.room_id = user_challenge.room_id
             WHERE user_challenge.user_id = ?
                AND user_challenge.challenge_id = ?
                AND ((? > 0 AND user_challenge.room_id = ?) OR (? <= 0 AND user_challenge.room_id IS NULL))
                AND ((? > 0 AND user_challenge.pvp_id = ?) OR (? <= 0 AND user_challenge.pvp_id IS NULL))
                AND user_challenge.completed_at IS NULL
                AND (user_challenge.room_id IS NULL OR rooms.ended_at IS NULL)
             ORDER BY user_challenge.started_at DESC
             LIMIT 1'
        );
        $statement->bind_param('iiiiiiii', $userId, $challengeId, $roomId, $roomId, $roomId, $pvpId, $pvpId, $pvpId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function findActiveRoomRunLock(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.user_id,
                user_challenge.room_id,
                user_challenge.started_at,
                user_challenge.completed_at
             FROM user_challenge
             INNER JOIN rooms ON rooms.room_id = user_challenge.room_id
             LEFT JOIN room_players
                ON room_players.room_id = user_challenge.room_id
                AND room_players.user_id = user_challenge.user_id
             WHERE user_challenge.user_id = ?
                AND user_challenge.room_id IS NOT NULL
                AND user_challenge.completed_at IS NULL
                AND rooms.started_at IS NOT NULL
                AND rooms.ended_at IS NULL
                AND (room_players.status IS NULL OR room_players.status <> 3)
             ORDER BY user_challenge.started_at DESC
             LIMIT 1'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function findActivePvpRunLock(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.user_id,
                user_challenge.room_id,
                user_challenge.pvp_id,
                user_challenge.started_at,
                user_challenge.completed_at
             FROM user_challenge
             INNER JOIN pvp_players
                ON pvp_players.pvp_id = user_challenge.pvp_id
                AND pvp_players.user_id = user_challenge.user_id
             WHERE user_challenge.user_id = ?
                AND user_challenge.room_id IS NULL
                AND user_challenge.pvp_id IS NOT NULL
                AND user_challenge.completed_at IS NULL
                AND pvp_players.status NOT IN (2, 3)
             ORDER BY user_challenge.started_at DESC
             LIMIT 1'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function startOrFindOngoing(int $userId, int $challengeId, int $roomId = 0, int $pvpId = 0): array
    {
        $ongoing = $this->findOngoing($userId, $challengeId, $roomId, $pvpId);

        if ($ongoing !== null) {
            if ($roomId > 0 && (int) ($ongoing['room_id'] ?? 0) <= 0) {
                $statement = $this->connection->prepare(
                    'UPDATE user_challenge
                     SET room_id = ?
                     WHERE uc_id = ?
                        AND room_id IS NULL
                     LIMIT 1'
                );
                $ongoingId = (int) ($ongoing['uc_id'] ?? 0);
                $statement->bind_param('ii', $roomId, $ongoingId);
                $statement->execute();
                $statement->close();
                $ongoing['room_id'] = $roomId;
            }

            if ($pvpId > 0 && (int) ($ongoing['pvp_id'] ?? 0) <= 0) {
                $statement = $this->connection->prepare(
                    'UPDATE user_challenge
                     SET pvp_id = ?
                     WHERE uc_id = ?
                        AND pvp_id IS NULL
                     LIMIT 1'
                );
                $ongoingId = (int) ($ongoing['uc_id'] ?? 0);
                $statement->bind_param('ii', $pvpId, $ongoingId);
                $statement->execute();
                $statement->close();
                $ongoing['pvp_id'] = $pvpId;
            }

            $ongoing['was_created'] = false;
            return $ongoing;
        }

        if ($roomId > 0) {
            $statement = $this->connection->prepare(
                'INSERT INTO user_challenge (challenge_id, user_id, room_id, pvp_id) VALUES (?, ?, ?, NULL)'
            );
            $statement->bind_param('iii', $challengeId, $userId, $roomId);
        } elseif ($pvpId > 0) {
            $statement = $this->connection->prepare(
                'INSERT INTO user_challenge (challenge_id, user_id, room_id, pvp_id) VALUES (?, ?, NULL, ?)'
            );
            $statement->bind_param('iii', $challengeId, $userId, $pvpId);
        } else {
            $statement = $this->connection->prepare(
                'INSERT INTO user_challenge (challenge_id, user_id, room_id, pvp_id) VALUES (?, ?, NULL, NULL)'
            );
            $statement->bind_param('ii', $challengeId, $userId);
        }
        $statement->execute();
        $userChallengeId = (int) $statement->insert_id;
        $statement->close();

        $started = $this->findById($userChallengeId);

        if ($started === null) {
            throw new RuntimeException('Unable to start challenge progress.');
        }

        $started['was_created'] = true;
        return $started;
    }

    /**
     * @return array<int, bool>
     */
    public function ongoingChallengeIdLookup(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT DISTINCT user_challenge.challenge_id
             FROM user_challenge
             LEFT JOIN rooms ON rooms.room_id = user_challenge.room_id
             WHERE user_challenge.user_id = ?
                AND user_challenge.completed_at IS NULL
                AND (user_challenge.room_id IS NULL OR rooms.ended_at IS NULL)'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $lookup = [];

        foreach ($rows as $row) {
            $lookup[(int) $row['challenge_id']] = true;
        }

        return $lookup;
    }

    /**
     * @return array<int, bool>
     */
    public function completedChallengeIdLookup(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $statement = $this->connection->prepare(
            'SELECT DISTINCT challenge_id
             FROM user_challenge
             WHERE user_id = ?
                AND completed_at IS NOT NULL'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $lookup = [];

        foreach ($rows as $row) {
            $lookup[(int) $row['challenge_id']] = true;
        }

        return $lookup;
    }

    public function hasAnyRecordForChallenge(int $userId, int $challengeId): bool
    {
        if ($userId <= 0 || $challengeId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT uc_id
             FROM user_challenge
             WHERE user_id = ?
                AND challenge_id = ?
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $challengeId);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function hasCompletedRecordForChallenge(int $userId, int $challengeId, int $excludeUserChallengeId = 0): bool
    {
        if ($userId <= 0 || $challengeId <= 0) {
            return false;
        }

        $sql = 'SELECT uc_id
             FROM user_challenge
             WHERE user_id = ?
                AND challenge_id = ?
                AND completed_at IS NOT NULL';

        if ($excludeUserChallengeId > 0) {
            $sql .= ' AND uc_id <> ?';
        }

        $sql .= ' LIMIT 1';

        $statement = $this->connection->prepare($sql);

        if ($excludeUserChallengeId > 0) {
            $statement->bind_param('iii', $userId, $challengeId, $excludeUserChallengeId);
        } else {
            $statement->bind_param('ii', $userId, $challengeId);
        }

        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    /**
     * @return array<string, int>
     */
    public function completedCountsByDate(int $userId, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        if ($userId <= 0) {
            return [];
        }

        $start = $startDate->format('Y-m-d');
        $end = $endDate->format('Y-m-d');
        $statement = $this->connection->prepare(
            'SELECT DATE(completed_at) AS completed_date, COUNT(*) AS total
             FROM user_challenge
             WHERE user_id = ?
                AND completed_at IS NOT NULL
                AND DATE(completed_at) BETWEEN ? AND ?
             GROUP BY DATE(completed_at)'
        );
        $statement->bind_param('iss', $userId, $start, $end);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['completed_date']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public function attemptCountsByDate(int $userId, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        if ($userId <= 0) {
            return [];
        }

        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT DATE(COALESCE(completed_at, started_at)) AS activity_date, COUNT(*) AS total
             FROM user_challenge
             WHERE user_id = ?
                AND COALESCE(completed_at, started_at) >= ?
                AND COALESCE(completed_at, started_at) <= ?
             GROUP BY DATE(COALESCE(completed_at, started_at))'
        );
        $statement->bind_param('iss', $userId, $start, $end);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['activity_date']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAttemptHistory(int $userId, int $limit = 200): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min(500, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.room_id,
                user_challenge.pvp_id,
                user_challenge.started_at,
                user_challenge.completed_at,
                room_players.strict_mode_score,
                rooms.strict_mode AS room_strict_mode,
                pvp_players.status AS pvp_player_status,
                challenges.name,
                challenges.instruction,
                difficulties.name AS difficulty_name,
                difficulties.points,
                CASE
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 2 THEN "pvp_win"
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 3 THEN "pvp_loss"
                    WHEN user_challenge.completed_at IS NOT NULL THEN "completed"
                    WHEN user_challenge.room_id IS NOT NULL AND (room_players.status = 3 OR rooms.ended_at IS NOT NULL) THEN "gave_up"
                    ELSE "ongoing"
                END AS attempt_status,
                CASE
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 2 THEN difficulties.points
                    WHEN user_challenge.room_id IS NOT NULL AND user_challenge.completed_at IS NOT NULL THEN difficulties.points
                    WHEN user_challenge.room_id IS NULL
                        AND user_challenge.completed_at IS NOT NULL
                        AND NOT EXISTS (
                            SELECT 1
                            FROM user_challenge AS previous_completion
                            WHERE previous_completion.user_id = user_challenge.user_id
                                AND previous_completion.challenge_id = user_challenge.challenge_id
                                AND previous_completion.completed_at IS NOT NULL
                                AND previous_completion.uc_id <> user_challenge.uc_id
                                AND previous_completion.completed_at <= user_challenge.completed_at
                        )
                    THEN difficulties.points
                    ELSE 0
                END AS awarded_points
             FROM user_challenge
             INNER JOIN challenges ON challenges.challenge_id = user_challenge.challenge_id
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             LEFT JOIN rooms ON rooms.room_id = user_challenge.room_id
             LEFT JOIN room_players ON room_players.room_id = user_challenge.room_id AND room_players.user_id = user_challenge.user_id
             LEFT JOIN pvp_players ON pvp_players.pvp_id = user_challenge.pvp_id AND pvp_players.user_id = user_challenge.user_id
             WHERE user_challenge.user_id = ?
             ORDER BY COALESCE(user_challenge.completed_at, user_challenge.started_at) DESC
             LIMIT ?'
        );
        $statement->bind_param('ii', $userId, $limit);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAttemptHistoryByDateRange(
        int $userId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 500
    ): array {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min(1000, $limit));
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.room_id,
                user_challenge.pvp_id,
                user_challenge.started_at,
                user_challenge.completed_at,
                room_players.strict_mode_score,
                rooms.strict_mode AS room_strict_mode,
                pvp_players.status AS pvp_player_status,
                challenges.name,
                challenges.instruction,
                difficulties.name AS difficulty_name,
                difficulties.points,
                CASE
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 2 THEN "pvp_win"
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 3 THEN "pvp_loss"
                    WHEN user_challenge.completed_at IS NOT NULL THEN "completed"
                    WHEN user_challenge.room_id IS NOT NULL AND (room_players.status = 3 OR rooms.ended_at IS NOT NULL) THEN "gave_up"
                    ELSE "ongoing"
                END AS attempt_status,
                CASE
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 2 THEN difficulties.points
                    WHEN user_challenge.room_id IS NOT NULL AND user_challenge.completed_at IS NOT NULL THEN difficulties.points
                    WHEN user_challenge.room_id IS NULL
                        AND user_challenge.completed_at IS NOT NULL
                        AND NOT EXISTS (
                            SELECT 1
                            FROM user_challenge AS previous_completion
                            WHERE previous_completion.user_id = user_challenge.user_id
                                AND previous_completion.challenge_id = user_challenge.challenge_id
                                AND previous_completion.completed_at IS NOT NULL
                                AND previous_completion.uc_id <> user_challenge.uc_id
                                AND previous_completion.completed_at <= user_challenge.completed_at
                        )
                    THEN difficulties.points
                    ELSE 0
                END AS awarded_points
             FROM user_challenge
             INNER JOIN challenges ON challenges.challenge_id = user_challenge.challenge_id
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             LEFT JOIN rooms ON rooms.room_id = user_challenge.room_id
             LEFT JOIN room_players ON room_players.room_id = user_challenge.room_id AND room_players.user_id = user_challenge.user_id
             LEFT JOIN pvp_players ON pvp_players.pvp_id = user_challenge.pvp_id AND pvp_players.user_id = user_challenge.user_id
             WHERE user_challenge.user_id = ?
                AND COALESCE(user_challenge.completed_at, user_challenge.started_at) BETWEEN ? AND ?
             ORDER BY COALESCE(user_challenge.completed_at, user_challenge.started_at) DESC
             LIMIT ?'
        );
        $statement->bind_param('issi', $userId, $start, $end, $limit);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    public function countCompletedForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM user_challenge
             WHERE user_id = ?
                AND completed_at IS NOT NULL'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function totalCompletedPointsForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT COALESCE(SUM(challenge_points.points), 0) AS total_points
             FROM (
                SELECT user_challenge.challenge_id, MAX(difficulties.points) AS points
                FROM user_challenge
                INNER JOIN challenges ON challenges.challenge_id = user_challenge.challenge_id
                INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
                WHERE user_challenge.user_id = ?
                    AND user_challenge.completed_at IS NOT NULL
                GROUP BY user_challenge.challenge_id
             ) AS challenge_points'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total_points'] ?? 0);
    }

    public function findById(int $userChallengeId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT uc_id, challenge_id, user_id, room_id, pvp_id, started_at, completed_at
             FROM user_challenge
             WHERE uc_id = ?
             LIMIT 1'
        );
        $statement->bind_param('i', $userChallengeId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function findOwnedOngoing(int $userChallengeId, int $userId, int $challengeId): ?array
    {
        if ($userChallengeId <= 0 || $userId <= 0 || $challengeId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT uc_id, challenge_id, user_id, room_id, pvp_id, started_at, completed_at
             FROM user_challenge
             WHERE uc_id = ?
                AND user_id = ?
                AND challenge_id = ?
                AND completed_at IS NULL
             LIMIT 1'
        );
        $statement->bind_param('iii', $userChallengeId, $userId, $challengeId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function markCompleted(int $userChallengeId, int $userId, int $challengeId): ?array
    {
        $statement = $this->connection->prepare(
            'UPDATE user_challenge
             SET completed_at = CURRENT_TIMESTAMP
             WHERE uc_id = ?
                AND user_id = ?
                AND challenge_id = ?
                AND completed_at IS NULL'
        );
        $statement->bind_param('iii', $userChallengeId, $userId, $challengeId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();

        if (!$updated) {
            return null;
        }

        return $this->findById($userChallengeId);
    }

    public function countCompletedByChallenge(int $challengeId): int
    {
        if ($challengeId <= 0) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM user_challenge
             WHERE challenge_id = ?
                AND completed_at IS NOT NULL'
        );
        $statement->bind_param('i', $challengeId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function countOutcomesByChallenge(int $challengeId): int
    {
        if ($challengeId <= 0) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM user_challenge
             INNER JOIN challenges ON challenges.challenge_id = user_challenge.challenge_id
             LEFT JOIN rooms ON rooms.room_id = user_challenge.room_id
             LEFT JOIN room_players ON room_players.room_id = user_challenge.room_id AND room_players.user_id = user_challenge.user_id
             LEFT JOIN pvp_players ON pvp_players.pvp_id = user_challenge.pvp_id AND pvp_players.user_id = user_challenge.user_id
             WHERE user_challenge.challenge_id = ?
                AND (
                    user_challenge.completed_at IS NOT NULL
                    OR (user_challenge.room_id IS NOT NULL AND (room_players.status = 3 OR rooms.ended_at IS NOT NULL))
                    OR (user_challenge.pvp_id IS NOT NULL AND pvp_players.status IN (2, 3))
                )'
        );
        $statement->bind_param('i', $challengeId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCompletedByChallenge(int $challengeId, int $limit = 200): array
    {
        if ($challengeId <= 0) {
            return [];
        }

        $limit = max(1, min(500, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.user_id,
                user_challenge.started_at,
                user_challenge.completed_at,
                users.username,
                users.email,
                user_details.firstname,
                user_details.lastname,
                images.source AS avatar_url
             FROM user_challenge
             INNER JOIN users ON users.user_id = user_challenge.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images ON images.img_id = user_details.image_id
             WHERE user_challenge.challenge_id = ?
                AND user_challenge.completed_at IS NOT NULL
                AND users.date_deleted IS NULL
             ORDER BY user_challenge.completed_at DESC
             LIMIT ?'
        );
        $statement->bind_param('ii', $challengeId, $limit);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOutcomesByChallengePaged(int $challengeId, int $limit = 20, int $offset = 0): array
    {
        if ($challengeId <= 0) {
            return [];
        }

        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.room_id,
                user_challenge.pvp_id,
                user_challenge.started_at,
                user_challenge.completed_at,
                room_players.strict_mode_score,
                rooms.strict_mode AS room_strict_mode,
                rooms.status AS room_status,
                rooms.ended_at AS room_ended_at,
                room_players.status AS room_player_status,
                pvp_players.status AS pvp_player_status,
                pvp_players.created_at AS pvp_created_at,
                users.username,
                users.email,
                user_details.firstname,
                user_details.lastname,
                avatar_images.source AS avatar_url,
                CASE
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 2 THEN "win"
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 3 THEN "loss"
                    WHEN user_challenge.room_id IS NOT NULL AND (room_players.status = 3 OR rooms.ended_at IS NOT NULL) THEN
                        CASE
                            WHEN user_challenge.completed_at IS NOT NULL THEN "pass"
                            ELSE "failed"
                        END
                    WHEN user_challenge.completed_at IS NOT NULL THEN "done"
                    ELSE "ongoing"
                END AS outcome_type
             FROM user_challenge
             INNER JOIN users ON users.user_id = user_challenge.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
             LEFT JOIN rooms ON rooms.room_id = user_challenge.room_id
             LEFT JOIN room_players ON room_players.room_id = user_challenge.room_id AND room_players.user_id = user_challenge.user_id
             LEFT JOIN pvp_players ON pvp_players.pvp_id = user_challenge.pvp_id AND pvp_players.user_id = user_challenge.user_id
             WHERE user_challenge.challenge_id = ?
                AND (
                    user_challenge.completed_at IS NOT NULL
                    OR (user_challenge.room_id IS NOT NULL AND (room_players.status = 3 OR rooms.ended_at IS NOT NULL))
                    OR (user_challenge.pvp_id IS NOT NULL AND pvp_players.status IN (2, 3))
                )
             ORDER BY COALESCE(user_challenge.completed_at, pvp_players.created_at, rooms.ended_at, user_challenge.started_at) DESC
             LIMIT ? OFFSET ?'
        );
        $statement->bind_param('iii', $challengeId, $limit, $offset);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOutcomesByChallengeAndDateRange(
        int $challengeId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 1000
    ): array {
        if ($challengeId <= 0) {
            return [];
        }

        $limit = max(1, min(2000, $limit));
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.room_id,
                user_challenge.pvp_id,
                user_challenge.started_at,
                user_challenge.completed_at,
                room_players.strict_mode_score,
                rooms.strict_mode AS room_strict_mode,
                rooms.status AS room_status,
                rooms.ended_at AS room_ended_at,
                room_players.status AS room_player_status,
                pvp_players.status AS pvp_player_status,
                pvp_players.created_at AS pvp_created_at,
                users.username,
                users.email,
                user_details.firstname,
                user_details.lastname,
                avatar_images.source AS avatar_url,
                CASE
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 2 THEN "win"
                    WHEN user_challenge.pvp_id IS NOT NULL AND pvp_players.status = 3 THEN "loss"
                    WHEN user_challenge.room_id IS NOT NULL AND (room_players.status = 3 OR rooms.ended_at IS NOT NULL) THEN
                        CASE
                            WHEN user_challenge.completed_at IS NOT NULL THEN "pass"
                            ELSE "failed"
                        END
                    WHEN user_challenge.completed_at IS NOT NULL THEN "done"
                    ELSE "ongoing"
                END AS outcome_type
             FROM user_challenge
             INNER JOIN users ON users.user_id = user_challenge.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
             LEFT JOIN rooms ON rooms.room_id = user_challenge.room_id
             LEFT JOIN room_players ON room_players.room_id = user_challenge.room_id AND room_players.user_id = user_challenge.user_id
             LEFT JOIN pvp_players ON pvp_players.pvp_id = user_challenge.pvp_id AND pvp_players.user_id = user_challenge.user_id
             WHERE user_challenge.challenge_id = ?
                AND COALESCE(user_challenge.completed_at, pvp_players.created_at, rooms.ended_at, user_challenge.started_at) >= ?
                AND COALESCE(user_challenge.completed_at, pvp_players.created_at, rooms.ended_at, user_challenge.started_at) <= ?
                AND (
                    user_challenge.completed_at IS NOT NULL
                    OR (user_challenge.room_id IS NOT NULL AND (room_players.status = 3 OR rooms.ended_at IS NOT NULL))
                    OR (user_challenge.pvp_id IS NOT NULL AND pvp_players.status IN (2, 3))
                )
             ORDER BY COALESCE(user_challenge.completed_at, pvp_players.created_at, rooms.ended_at, user_challenge.started_at) DESC
             LIMIT ?'
        );
        $statement->bind_param('issi', $challengeId, $start, $end, $limit);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    public function completedCountsByDateForChallenge(
        int $challengeId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): array {
        if ($challengeId <= 0) {
            return [];
        }

        $start = $startDate->format('Y-m-d');
        $end = $endDate->format('Y-m-d');
        $statement = $this->connection->prepare(
            'SELECT DATE(completed_at) AS completed_date, COUNT(*) AS total
             FROM user_challenge
             WHERE challenge_id = ?
                AND completed_at IS NOT NULL
                AND DATE(completed_at) BETWEEN ? AND ?
             GROUP BY DATE(completed_at)
             ORDER BY completed_date ASC'
        );
        $statement->bind_param('iss', $challengeId, $start, $end);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['completed_date']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCompletedByChallengePaged(int $challengeId, int $limit = 20, int $offset = 0): array
    {
        if ($challengeId <= 0) {
            return [];
        }

        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.user_id,
                user_challenge.started_at,
                user_challenge.completed_at,
                users.username,
                users.email,
                user_details.firstname,
                user_details.lastname,
                avatar_images.source AS avatar_url
             FROM user_challenge
             INNER JOIN users ON users.user_id = user_challenge.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
             WHERE user_challenge.challenge_id = ?
                AND user_challenge.completed_at IS NOT NULL
             ORDER BY user_challenge.completed_at DESC
             LIMIT ? OFFSET ?'
        );
        $statement->bind_param('iii', $challengeId, $limit, $offset);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCompletedByChallengeAndDateRange(
        int $challengeId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 1000
    ): array {
        if ($challengeId <= 0) {
            return [];
        }

        $limit = max(1, min(2000, $limit));
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT
                user_challenge.uc_id,
                user_challenge.challenge_id,
                user_challenge.user_id,
                user_challenge.started_at,
                user_challenge.completed_at,
                users.username,
                users.email,
                user_details.firstname,
                user_details.lastname,
                avatar_images.source AS avatar_url
             FROM user_challenge
             INNER JOIN users ON users.user_id = user_challenge.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
             WHERE user_challenge.challenge_id = ?
                AND user_challenge.completed_at IS NOT NULL
                AND user_challenge.completed_at >= ?
                AND user_challenge.completed_at <= ?
             ORDER BY user_challenge.completed_at DESC
             LIMIT ?'
        );
        $statement->bind_param('issi', $challengeId, $start, $end, $limit);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    public function deleteOngoingForUser(int $userChallengeId, int $userId): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM user_challenge
             WHERE uc_id = ?
                AND user_id = ?
                AND completed_at IS NULL'
        );
        $statement->bind_param('ii', $userChallengeId, $userId);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();

        return $deleted;
    }

    public function pvpDurationSeconds(int $pvpId): int
    {
        if ($pvpId <= 0) {
            return 0;
        }

        $statement = $this->connection->prepare(
            'SELECT MIN(started_at) AS started_at
             FROM user_challenge
             WHERE pvp_id = ?'
        );
        $statement->bind_param('i', $pvpId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        $startedAt = trim((string) ($row['started_at'] ?? ''));
        if ($startedAt === '') {
            return 0;
        }

        $startedAtTs = strtotime($startedAt);
        if ($startedAtTs === false) {
            return 0;
        }

        return max(0, time() - $startedAtTs);
    }

    public function hasOwnedOngoing(int $userChallengeId, int $userId): bool
    {
        if ($userChallengeId <= 0 || $userId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT uc_id
             FROM user_challenge
             WHERE uc_id = ?
                AND user_id = ?
                AND completed_at IS NULL
             LIMIT 1'
        );
        $statement->bind_param('ii', $userChallengeId, $userId);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }
}
