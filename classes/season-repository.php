<?php

final class SeasonRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $result = $this->connection->query(
            'SELECT
                seasons.season_id,
                seasons.name,
                seasons.start_date,
                seasons.end_date,
                CASE
                    WHEN seasons.start_date <= CURRENT_TIMESTAMP AND seasons.end_date >= CURRENT_TIMESTAMP THEN "active"
                    WHEN seasons.start_date > CURRENT_TIMESTAMP THEN "upcoming"
                    ELSE "ended"
                END AS season_status,
                COALESCE(challenge_stats.total_attempts, 0) AS total_attempts,
                COALESCE(progress_stats.total_players, 0) AS total_players,
                COALESCE(progress_stats.total_points, 0) AS total_points
             FROM seasons
             LEFT JOIN (
                SELECT COALESCE(user_challenge.season_id, first_season.season_id) AS season_id, COUNT(*) AS total_attempts
                FROM user_challenge
                CROSS JOIN (
                    SELECT season_id
                    FROM seasons
                    ORDER BY start_date ASC, season_id ASC
                    LIMIT 1
                ) AS first_season
                GROUP BY COALESCE(user_challenge.season_id, first_season.season_id)
             ) AS challenge_stats ON challenge_stats.season_id = seasons.season_id
             LEFT JOIN (
                SELECT COALESCE(player_progress.season_id, first_season.season_id) AS season_id, COUNT(*) AS total_players, COALESCE(SUM(points), 0) AS total_points
                FROM player_progress
                CROSS JOIN (
                    SELECT season_id
                    FROM seasons
                    ORDER BY start_date ASC, season_id ASC
                    LIMIT 1
                ) AS first_season
                GROUP BY COALESCE(player_progress.season_id, first_season.season_id)
             ) AS progress_stats ON progress_stats.season_id = seasons.season_id
             ORDER BY seasons.start_date DESC, seasons.season_id DESC'
        );

        return $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create(string $name, string $startDate, string $endDate): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO seasons (name, start_date, end_date)
             VALUES (?, ?, ?)'
        );
        $statement->bind_param('sss', $name, $startDate, $endDate);
        $statement->execute();
        $seasonId = (int) $statement->insert_id;
        $statement->close();

        return $seasonId;
    }

    public function findById(int $seasonId): ?array
    {
        if ($seasonId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                seasons.season_id,
                seasons.name,
                seasons.start_date,
                seasons.end_date,
                CASE
                    WHEN seasons.start_date <= CURRENT_TIMESTAMP AND seasons.end_date >= CURRENT_TIMESTAMP THEN "active"
                    WHEN seasons.start_date > CURRENT_TIMESTAMP THEN "upcoming"
                    ELSE "ended"
                END AS season_status
             FROM seasons
             WHERE seasons.season_id = ?
             LIMIT 1'
        );
        $statement->bind_param('i', $seasonId);
        $statement->execute();
        $season = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $season ?: null;
    }

    public function findActive(): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT
                seasons.season_id,
                seasons.name,
                seasons.start_date,
                seasons.end_date,
                "active" AS season_status
             FROM seasons
             WHERE seasons.start_date <= CURRENT_TIMESTAMP
                AND seasons.end_date >= CURRENT_TIMESTAMP
             ORDER BY seasons.start_date DESC, seasons.season_id DESC
             LIMIT 1'
        );
        $statement->execute();
        $season = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $season ?: null;
    }

    public function update(int $seasonId, string $name, string $startDate, string $endDate): bool
    {
        if ($seasonId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE seasons
             SET name = ?, start_date = ?, end_date = ?
             WHERE season_id = ?'
        );
        $statement->bind_param('sssi', $name, $startDate, $endDate, $seasonId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function delete(int $seasonId): bool
    {
        if ($seasonId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'DELETE FROM seasons
             WHERE season_id = ?'
        );
        $statement->bind_param('i', $seasonId);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();

        return $deleted;
    }

    public function exists(int $seasonId): bool
    {
        if ($seasonId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT 1
             FROM seasons
             WHERE season_id = ?
             LIMIT 1'
        );
        $statement->bind_param('i', $seasonId);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $exists;
    }

    public function nameExists(string $name, int $excludeSeasonId = 0): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM seasons
             WHERE name = ?
                AND (? <= 0 OR season_id <> ?)
             LIMIT 1'
        );
        $statement->bind_param('sii', $name, $excludeSeasonId, $excludeSeasonId);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $exists;
    }

    public function overlaps(string $startDate, string $endDate, int $excludeSeasonId = 0): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM seasons
             WHERE start_date < ?
                AND end_date > ?
                AND (? <= 0 OR season_id <> ?)
             LIMIT 1'
        );
        $statement->bind_param('ssii', $endDate, $startDate, $excludeSeasonId, $excludeSeasonId);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $exists;
    }

    public function hasRecords(int $seasonId): bool
    {
        if ($seasonId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT
                (
                    SELECT COUNT(*)
                    FROM user_challenge
                    WHERE season_id = ?
                        OR (season_id IS NULL AND ? = (SELECT season_id FROM seasons ORDER BY start_date ASC, season_id ASC LIMIT 1))
                ) AS attempts,
                (
                    SELECT COUNT(*)
                    FROM player_progress
                    WHERE season_id = ?
                        OR (season_id IS NULL AND ? = (SELECT season_id FROM seasons ORDER BY start_date ASC, season_id ASC LIMIT 1))
                ) AS progress_rows'
        );
        $statement->bind_param('iiii', $seasonId, $seasonId, $seasonId, $seasonId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return ((int) ($row['attempts'] ?? 0) + (int) ($row['progress_rows'] ?? 0)) > 0;
    }
}
