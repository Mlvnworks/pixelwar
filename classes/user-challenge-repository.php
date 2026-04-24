<?php

final class UserChallengeRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function findOngoing(int $userId, int $challengeId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT uc_id, challenge_id, user_id, started_at, completed_at
             FROM user_challenge
             WHERE user_id = ?
                AND challenge_id = ?
                AND completed_at IS NULL
             ORDER BY started_at DESC
             LIMIT 1'
        );
        $statement->bind_param('ii', $userId, $challengeId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function startOrFindOngoing(int $userId, int $challengeId): array
    {
        $ongoing = $this->findOngoing($userId, $challengeId);

        if ($ongoing !== null) {
            $ongoing['was_created'] = false;
            return $ongoing;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO user_challenge (challenge_id, user_id) VALUES (?, ?)'
        );
        $statement->bind_param('ii', $challengeId, $userId);
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
            'SELECT DISTINCT challenge_id
             FROM user_challenge
             WHERE user_id = ?
                AND completed_at IS NULL'
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
                user_challenge.started_at,
                user_challenge.completed_at,
                challenges.name,
                challenges.instruction,
                difficulties.name AS difficulty_name,
                difficulties.points,
                CASE
                    WHEN user_challenge.completed_at IS NOT NULL
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
            'SELECT uc_id, challenge_id, user_id, started_at, completed_at
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
            'SELECT uc_id, challenge_id, user_id, started_at, completed_at
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
}
