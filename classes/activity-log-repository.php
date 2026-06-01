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
     * @return array<string, array<string, int>>
     */
    public function countCreationByDayAndCategoryInRange(
        int $userId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): array {
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT DATE(date_created) AS activity_date, category, COUNT(*) AS total
             FROM activity_logs
             WHERE user_id = ?
                AND date_created >= ?
                AND date_created <= ?
                AND category IN (\'challenge\', \'room\')
                AND log_text LIKE \'Created %\'
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
    public function listCreationLogsForUser(int $userId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $statement = $this->connection->prepare(
            'SELECT al_id, category, log_text, date_created
             FROM activity_logs
             WHERE user_id = ?
                AND category IN (\'challenge\', \'room\')
                AND log_text LIKE \'Created %\'
             ORDER BY date_created DESC
             LIMIT ?'
        );
        $statement->bind_param('ii', $userId, $limit);
        $statement->execute();
        $logs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $logs;
    }

    public function countCreationLogsForUser(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM activity_logs
             WHERE user_id = ?
                AND category IN (\'challenge\', \'room\')
                AND log_text LIKE \'Created %\''
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCreationLogsForUserPaged(int $userId, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $statement = $this->connection->prepare(
            'SELECT al_id, category, log_text, date_created
             FROM activity_logs
             WHERE user_id = ?
                AND category IN (\'challenge\', \'room\')
                AND log_text LIKE \'Created %\'
             ORDER BY date_created DESC
             LIMIT ? OFFSET ?'
        );
        $statement->bind_param('iii', $userId, $limit, $offset);
        $statement->execute();
        $logs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $logs;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCreationLogsForUserByDateRange(
        int $userId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 1000
    ): array {
        $limit = max(1, min(2000, $limit));
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT al_id, category, log_text, date_created
             FROM activity_logs
             WHERE user_id = ?
                AND date_created >= ?
                AND date_created <= ?
                AND category IN (\'challenge\', \'room\')
                AND log_text LIKE \'Created %\'
             ORDER BY date_created DESC
             LIMIT ?'
        );
        $statement->bind_param('issi', $userId, $start, $end, $limit);
        $statement->execute();
        $logs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $logs;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRoomCreationLogsForUser(int $userId, int $limit = 500): array
    {
        $limit = max(1, min(2000, $limit));
        $statement = $this->connection->prepare(
            'SELECT al_id, category, log_text, date_created
             FROM activity_logs
             WHERE user_id = ?
                AND category = \'room\'
                AND log_text LIKE \'Created %\'
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
    public function listRoomCreationLogsForUserByDateRange(
        int $userId,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 2000
    ): array {
        $limit = max(1, min(5000, $limit));
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT al_id, category, log_text, date_created
             FROM activity_logs
             WHERE user_id = ?
                AND category = \'room\'
                AND log_text LIKE \'Created %\'
                AND date_created >= ?
                AND date_created <= ?
             ORDER BY date_created DESC
             LIMIT ?'
        );
        $statement->bind_param('issi', $userId, $start, $end, $limit);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOverallPaginated(int $limit = 100, int $offset = 0, ?string $category = null): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $normalizedCategory = $category !== null ? strtolower(trim($category)) : null;
        $where = '';
        $types = '';
        $params = [];

        if ($normalizedCategory !== null && $normalizedCategory !== '' && $normalizedCategory !== 'all') {
            $where = 'WHERE LOWER(activity_logs.category) = ?';
            $types .= 's';
            $params[] = $normalizedCategory;
        }

        $sql = 'SELECT
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
             ' . $where . '
             ORDER BY activity_logs.date_created DESC
             LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $logs = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $logs;
    }

    public function countOverall(?string $category = null): int
    {
        $normalizedCategory = $category !== null ? strtolower(trim($category)) : null;

        if ($normalizedCategory !== null && $normalizedCategory !== '' && $normalizedCategory !== 'all') {
            $statement = $this->connection->prepare(
                'SELECT COUNT(*) AS total
                 FROM activity_logs
                 WHERE LOWER(category) = ?'
            );
            $statement->bind_param('s', $normalizedCategory);
            $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            $statement->close();

            return (int) ($row['total'] ?? 0);
        }

        $result = $this->connection->query('SELECT COUNT(*) AS total FROM activity_logs');
        $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<string, int>
     */
    public function countGroupedByCategory(): array
    {
        $result = $this->connection->query(
            'SELECT LOWER(COALESCE(NULLIF(TRIM(category), \'\'), \'general\')) AS category_name,
                    COUNT(*) AS total
             FROM activity_logs
             GROUP BY category_name
             ORDER BY category_name ASC'
        );

        if (!$result instanceof mysqli_result) {
            return [];
        }

        $counts = [];
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $counts[(string) ($row['category_name'] ?? 'general')] = (int) ($row['total'] ?? 0);
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOverallByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?string $category = null,
        int $limit = 5000
    ): array {
        $limit = max(1, min(10000, $limit));
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $normalizedCategory = $category !== null ? strtolower(trim($category)) : null;

        $sql = 'SELECT
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
             WHERE activity_logs.date_created >= ?
                AND activity_logs.date_created <= ?';
        $types = 'ss';
        $params = [$start, $end];

        if ($normalizedCategory !== null && $normalizedCategory !== '' && $normalizedCategory !== 'all') {
            $sql .= ' AND activity_logs.category = ?';
            $types .= 's';
            $params[] = $normalizedCategory;
        }

        $sql .= ' ORDER BY activity_logs.date_created DESC LIMIT ?';
        $types .= 'i';
        $params[] = $limit;

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
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

    public function countForUserByCategory(int $userId, string $category, ?string $logPrefix = null): int
    {
        $normalizedCategory = strtolower(trim($category));
        $sql = 'SELECT COUNT(*) AS total
                FROM activity_logs
                WHERE user_id = ?
                    AND category = ?';
        $types = 'is';
        $params = [$userId, $normalizedCategory];

        if ($logPrefix !== null && $logPrefix !== '') {
            $sql .= ' AND log_text LIKE ?';
            $types .= 's';
            $params[] = $logPrefix . '%';
        }

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
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
