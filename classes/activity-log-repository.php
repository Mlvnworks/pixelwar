<?php

final class ActivityLogRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function create(int $userId, string $category, string $logText): int
    {
        $category = strtolower(trim($category));

        if ($category === '') {
            $category = 'general';
        }

        $statement = $this->connection->prepare(
            'INSERT INTO activity_logs (user_id, category, log_text) VALUES (?, ?, ?)'
        );
        $statement->bind_param('iss', $userId, $category, $logText);
        $statement->execute();
        $activityLogId = (int) $statement->insert_id;
        $statement->close();

        return $activityLogId;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function countByDayAndCategory(int $userId, int $year): array
    {
        $start = sprintf('%04d-01-01 00:00:00', $year);
        $end = sprintf('%04d-01-01 00:00:00', $year + 1);
        $statement = $this->connection->prepare(
            'SELECT DATE(date_created) AS activity_date, category, COUNT(*) AS total
             FROM activity_logs
             WHERE user_id = ?
                AND date_created >= ?
                AND date_created < ?
                AND (
                    category <> \'challenge\'
                    OR log_text LIKE \'Created challenge%\'
                )
             GROUP BY DATE(date_created), category
             ORDER BY activity_date ASC'
        );
        $statement->bind_param('iss', $userId, $start, $end);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $counts = [];

        foreach ($rows as $row) {
            $date = (string) $row['activity_date'];
            $category = strtolower((string) $row['category']);
            $counts[$date][$category] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listLatestForUser(int $userId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->connection->prepare(
            'SELECT al_id, category, log_text, date_created
             FROM activity_logs
             WHERE user_id = ?
             ORDER BY date_created DESC
             LIMIT ?'
        );
        $statement->bind_param('ii', $userId, $limit);
        $statement->execute();
        $logs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $logs;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listLatestOverall(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                activity_logs.al_id,
                activity_logs.user_id,
                activity_logs.category,
                activity_logs.log_text,
                activity_logs.date_created,
                users.username,
                users.email,
                users.role_id,
                user_details.firstname,
                user_details.lastname
             FROM activity_logs
             LEFT JOIN users ON users.user_id = activity_logs.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             ORDER BY activity_logs.date_created DESC
             LIMIT ?'
        );
        $statement->bind_param('i', $limit);
        $statement->execute();
        $logs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $logs;
    }

    public function countByCategory(string $category): int
    {
        $normalizedCategory = strtolower(trim($category));
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM activity_logs
             WHERE category = ?'
        );
        $statement->bind_param('s', $normalizedCategory);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function countTodayByCategory(string $category): int
    {
        $normalizedCategory = strtolower(trim($category));
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM activity_logs
             WHERE category = ?
                AND DATE(date_created) = CURDATE()'
        );
        $statement->bind_param('s', $normalizedCategory);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<string, int>
     */
    public function countAllByDay(DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT DATE(date_created) AS activity_date, COUNT(*) AS total
             FROM activity_logs
             WHERE date_created >= ?
                AND date_created <= ?
             GROUP BY DATE(date_created)
             ORDER BY activity_date ASC'
        );
        $statement->bind_param('ss', $start, $end);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['activity_date']] = (int) $row['total'];
        }

        return $counts;
    }
}
