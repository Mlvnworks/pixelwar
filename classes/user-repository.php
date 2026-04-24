<?php

class UserRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function findSignupConflict(string $username, string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT username, email
             FROM users
             WHERE
                username = ?
                OR email = ?
                OR username LIKE CONCAT(\'deleted::\', ?, \'::%\')
                OR email LIKE CONCAT(\'deleted::\', ?, \'::%\')
             LIMIT 1'
        );
        $statement->bind_param('ssss', $username, $email, $username, $email);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
             FROM users
             WHERE username = ? OR username LIKE CONCAT(\'deleted::\', ?, \'::%\')
             LIMIT 1'
        );
        $statement->bind_param('ss', $username, $username);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
             FROM users
             WHERE email = ? OR email LIKE CONCAT(\'deleted::\', ?, \'::%\')
             LIMIT 1'
        );
        $statement->bind_param('ss', $email, $email);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
             FROM users
             WHERE user_id <> ?
                AND (email = ? OR email LIKE CONCAT(\'deleted::\', ?, \'::%\'))
             LIMIT 1'
        );
        $statement->bind_param('iss', $userId, $email, $email);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function findLoginUser(string $identity): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT user_id, role_id, username, email, password, is_verified, is_active FROM users WHERE date_deleted IS NULL AND (username = ? OR email = ?) LIMIT 1'
        );
        $statement->bind_param('ss', $identity, $identity);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    public function findDeletedLoginUser(string $identity): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT user_id, role_id, username, email, date_deleted
             FROM users
             WHERE date_deleted IS NOT NULL
                AND (
                    username LIKE CONCAT(\'deleted::\', ?, \'::%\')
                    OR email LIKE CONCAT(\'deleted::\', ?, \'::%\')
                )
             LIMIT 1'
        );
        $statement->bind_param('ss', $identity, $identity);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    public function findAuthUserById(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT user_id, role_id, username, email, password, is_verified, is_active
             FROM users
             WHERE user_id = ? AND date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUsersByRole(int $roleId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                users.user_id,
                users.username,
                users.email,
                users.is_verified,
                users.registration_date,
                user_details.ud_id AS user_details_id,
                user_details.firstname,
                user_details.lastname,
                images.source AS avatar_url
             FROM users
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images ON images.img_id = user_details.image_id
             WHERE users.role_id = ? AND users.date_deleted IS NULL
             ORDER BY users.registration_date DESC
             LIMIT ?'
        );
        $statement->bind_param('ii', $roleId, $limit);
        $statement->execute();
        $users = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $users;
    }

    public function countUsersByRole(int $roleId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM users
             WHERE role_id = ? AND date_deleted IS NULL'
        );
        $statement->bind_param('i', $roleId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function countUsersByRoleFiltered(int $roleId, string $search = '', ?int $activeStatus = null): int
    {
        $search = trim($search);
        $sql = 'SELECT COUNT(*) AS total
                FROM users
                LEFT JOIN user_details ON user_details.user_id = users.user_id
                WHERE users.role_id = ?
                    AND users.date_deleted IS NULL';
        $types = 'i';
        $params = [$roleId];

        if ($activeStatus !== null) {
            $sql .= ' AND users.is_active = ?';
            $types .= 'i';
            $params[] = $activeStatus;
        }

        if ($search !== '') {
            $sql .= ' AND (
                users.username LIKE ?
                OR users.email LIKE ?
                OR CONCAT(COALESCE(user_details.firstname, \'\'), \' \', COALESCE(user_details.lastname, \'\')) LIKE ?
            )';
            $searchLike = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function countUsersByRoleAndVerificationFiltered(int $roleId, string $search = '', ?int $isVerified = null): int
    {
        $search = trim($search);
        $sql = 'SELECT COUNT(*) AS total
                FROM users
                LEFT JOIN user_details ON user_details.user_id = users.user_id
                WHERE users.role_id = ?
                    AND users.date_deleted IS NULL';
        $types = 'i';
        $params = [$roleId];

        if ($isVerified !== null) {
            $sql .= ' AND users.is_verified = ?';
            $types .= 'i';
            $params[] = $isVerified;
        }

        if ($search !== '') {
            $sql .= ' AND (
                users.username LIKE ?
                OR users.email LIKE ?
                OR CONCAT(COALESCE(user_details.firstname, \'\'), \' \', COALESCE(user_details.lastname, \'\')) LIKE ?
            )';
            $searchLike = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function countUsersRegisteredTodayByRole(int $roleId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM users
             WHERE role_id = ?
                AND date_deleted IS NULL
                AND DATE(registration_date) = CURDATE()'
        );
        $statement->bind_param('i', $roleId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    public function countPendingStudentReviews(string $search = '', ?int $activeStatus = 0): int
    {
        $search = trim($search);
        $sql = 'SELECT COUNT(*) AS total
                FROM users
                INNER JOIN user_details ON user_details.user_id = users.user_id
                LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
                LEFT JOIN images AS id_images ON id_images.img_id = user_details.id_picture
                WHERE users.role_id = 3
                    AND users.date_deleted IS NULL
                    AND users.is_verified = 1';
        $types = '';
        $params = [];

        if ($activeStatus !== null) {
            $sql .= ' AND users.is_active = ?';
            $types .= 'i';
            $params[] = $activeStatus;
        }

        if ($search !== '') {
            $sql .= ' AND (
                users.username LIKE ?
                OR users.email LIKE ?
                OR user_details.student_number LIKE ?
                OR CONCAT(COALESCE(user_details.firstname, \'\'), \' \', COALESCE(user_details.lastname, \'\')) LIKE ?
            )';
            $searchLike = '%' . $search . '%';
            $types .= 'ssss';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $statement = $this->connection->prepare($sql);

        if ($types !== '') {
            $statement->bind_param($types, ...$params);
        }

        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUsersByRoleFiltered(int $roleId, string $search = '', ?int $activeStatus = null, int $limit = 25, int $offset = 0): array
    {
        $search = trim($search);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $sql = 'SELECT
                    users.user_id,
                    users.username,
                    users.email,
                    users.is_verified,
                    users.is_active,
                    users.registration_date,
                    user_details.ud_id AS user_details_id,
                    user_details.firstname,
                    user_details.lastname,
                    user_details.student_number,
                    images.source AS avatar_url
                FROM users
                LEFT JOIN user_details ON user_details.user_id = users.user_id
                LEFT JOIN images ON images.img_id = user_details.image_id
                WHERE users.role_id = ?
                    AND users.date_deleted IS NULL';
        $types = 'i';
        $params = [$roleId];

        if ($activeStatus !== null) {
            $sql .= ' AND users.is_active = ?';
            $types .= 'i';
            $params[] = $activeStatus;
        }

        if ($search !== '') {
            $sql .= ' AND (
                users.username LIKE ?
                OR users.email LIKE ?
                OR CONCAT(COALESCE(user_details.firstname, \'\'), \' \', COALESCE(user_details.lastname, \'\')) LIKE ?
            )';
            $searchLike = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $sql .= ' ORDER BY users.registration_date DESC LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $users = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $users;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUsersByRoleAndVerificationFiltered(int $roleId, string $search = '', ?int $isVerified = null, int $limit = 25, int $offset = 0): array
    {
        $search = trim($search);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $sql = 'SELECT
                    users.user_id,
                    users.username,
                    users.email,
                    users.is_verified,
                    users.is_active,
                    users.registration_date,
                    user_details.ud_id AS user_details_id,
                    user_details.firstname,
                    user_details.lastname,
                    images.source AS avatar_url
                FROM users
                LEFT JOIN user_details ON user_details.user_id = users.user_id
                LEFT JOIN images ON images.img_id = user_details.image_id
                WHERE users.role_id = ?
                    AND users.date_deleted IS NULL';
        $types = 'i';
        $params = [$roleId];

        if ($isVerified !== null) {
            $sql .= ' AND users.is_verified = ?';
            $types .= 'i';
            $params[] = $isVerified;
        }

        if ($search !== '') {
            $sql .= ' AND (
                users.username LIKE ?
                OR users.email LIKE ?
                OR CONCAT(COALESCE(user_details.firstname, \'\'), \' \', COALESCE(user_details.lastname, \'\')) LIKE ?
            )';
            $searchLike = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $sql .= ' ORDER BY users.registration_date DESC LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $users = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $users;
    }

    public function updateStudentAccount(int $userId, string $username, string $email, string $firstname, string $lastname, ?string $studentNumber = null): void
    {
        $statement = $this->connection->prepare(
            'UPDATE users
             SET username = ?, email = ?
             WHERE user_id = ? AND date_deleted IS NULL'
        );
        $statement->bind_param('ssi', $username, $email, $userId);
        $statement->execute();
        $statement->close();

        $statement = $this->connection->prepare(
            'UPDATE user_details
             SET firstname = ?, lastname = ?, student_number = ?
             WHERE user_id = ?'
        );
        $statement->bind_param('sssi', $firstname, $lastname, $studentNumber, $userId);
        $statement->execute();
        $statement->close();
    }

    public function softDeleteUser(int $userId): void
    {
        $statement = $this->connection->prepare(
            'SELECT username, email
             FROM users
             WHERE user_id = ? AND date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$user) {
            return;
        }

        $username = trim((string) ($user['username'] ?? ''));
        $email = trim((string) ($user['email'] ?? ''));
        $deletedUsername = 'deleted::' . $username . '::' . $userId;
        $deletedEmail = 'deleted::' . $email . '::' . $userId;
        $statement = $this->connection->prepare(
            'UPDATE users
             SET username = ?, email = ?, date_deleted = CURRENT_TIMESTAMP
             WHERE user_id = ? AND date_deleted IS NULL'
        );
        $statement->bind_param('ssi', $deletedUsername, $deletedEmail, $userId);
        $statement->execute();
        $statement->close();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPendingStudentReviews(string $search = '', ?int $activeStatus = 0, int $limit = 20, int $offset = 0): array
    {
        $search = trim($search);
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $sql = 'SELECT
                    users.user_id,
                    users.username,
                    users.email,
                    users.registration_date,
                    users.is_verified,
                    users.is_active,
                    user_details.ud_id,
                    user_details.firstname,
                    user_details.lastname,
                    user_details.student_number,
                    avatar_images.source AS avatar_url,
                    id_images.source AS id_picture_url
                FROM users
                INNER JOIN user_details ON user_details.user_id = users.user_id
                LEFT JOIN images AS avatar_images ON avatar_images.img_id = user_details.image_id
                LEFT JOIN images AS id_images ON id_images.img_id = user_details.id_picture
                WHERE users.role_id = 3
                    AND users.date_deleted IS NULL
                    AND users.is_verified = 1';
        $types = '';
        $params = [];

        if ($activeStatus !== null) {
            $sql .= ' AND users.is_active = ?';
            $types .= 'i';
            $params[] = $activeStatus;
        }

        if ($search !== '') {
            $sql .= ' AND (
                users.username LIKE ?
                OR users.email LIKE ?
                OR user_details.student_number LIKE ?
                OR CONCAT(COALESCE(user_details.firstname, \'\'), \' \', COALESCE(user_details.lastname, \'\')) LIKE ?
            )';
            $searchLike = '%' . $search . '%';
            $types .= 'ssss';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $sql .= ' ORDER BY users.registration_date ASC LIMIT ? OFFSET ?';
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$params);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    public function countRegistrationsByDayAndRole(int $roleId, DateTimeInterface $startDate, DateTimeInterface $endDate): array
    {
        $start = $startDate->format('Y-m-d 00:00:00');
        $end = $endDate->format('Y-m-d 23:59:59');
        $statement = $this->connection->prepare(
            'SELECT DATE(registration_date) AS registration_day, COUNT(*) AS total
             FROM users
             WHERE role_id = ?
                AND date_deleted IS NULL
                AND registration_date >= ?
                AND registration_date <= ?
             GROUP BY DATE(registration_date)
             ORDER BY registration_day ASC'
        );
        $statement->bind_param('iss', $roleId, $start, $end);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['registration_day']] = (int) ($row['total'] ?? 0);
        }

        return $counts;
    }

    public function userDetailsExist(int $userId): bool
    {
        $statement = $this->connection->prepare('SELECT ud_id FROM user_details WHERE user_id = ? LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function findSessionUser(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                users.user_id,
                users.role_id,
                users.username,
                users.email,
                users.is_verified,
                users.is_active,
                user_details.firstname,
                user_details.lastname,
                images.source AS avatar_url
             FROM users
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images ON images.img_id = user_details.image_id
             WHERE users.user_id = ? AND users.date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    public function usernameExistsForOtherUser(string $username, int $userId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT user_id
             FROM users
             WHERE user_id <> ?
                AND (username = ? OR username LIKE CONCAT(\'deleted::\', ?, \'::%\'))
             LIMIT 1'
        );
        $statement->bind_param('iss', $userId, $username, $username);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function findVerificationUser(int $userId): ?array
    {
        $statement = $this->connection->prepare('SELECT username, email, is_verified FROM users WHERE user_id = ? AND date_deleted IS NULL LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    public function findUserForSettings(int $userId): ?array
    {
        $statement = $this->connection->prepare('SELECT username, email, is_verified FROM users WHERE user_id = ? AND date_deleted IS NULL LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    public function findBasicUser(int $userId): array
    {
        $statement = $this->connection->prepare('SELECT username, email FROM users WHERE user_id = ? LIMIT 1');
        $statement->bind_param('i', $userId);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: [];
    }

    public function createStudent(string $username, string $email, string $passwordHash): int
    {
        $roleId = 3;
        $isVerified = 0;
        $statement = $this->connection->prepare(
            'INSERT INTO users (role_id, username, email, password, is_verified) VALUES (?, ?, ?, ?, ?)'
        );
        $statement->bind_param('isssi', $roleId, $username, $email, $passwordHash, $isVerified);
        $statement->execute();
        $userId = (int) $statement->insert_id;
        $statement->close();

        return $userId;
    }

    public function createTeacher(string $username, string $email, string $passwordHash): int
    {
        $roleId = 2;
        $isVerified = 0;
        $statement = $this->connection->prepare(
            'INSERT INTO users (role_id, username, email, password, is_verified) VALUES (?, ?, ?, ?, ?)'
        );
        $statement->bind_param('isssi', $roleId, $username, $email, $passwordHash, $isVerified);
        $statement->execute();
        $userId = (int) $statement->insert_id;
        $statement->close();

        return $userId;
    }

    public function updateEmailVerificationState(int $userId, string $email, int $isVerified): void
    {
        $statement = $this->connection->prepare('UPDATE users SET email = ?, is_verified = ? WHERE user_id = ?');
        $statement->bind_param('sii', $email, $isVerified, $userId);
        $statement->execute();
        $statement->close();
    }

    public function markEmailVerified(int $userId): void
    {
        $verified = 1;
        $statement = $this->connection->prepare('UPDATE users SET is_verified = ? WHERE user_id = ?');
        $statement->bind_param('ii', $verified, $userId);
        $statement->execute();
        $statement->close();
    }

    public function updateActiveState(int $userId, int $isActive): void
    {
        $statement = $this->connection->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
        $statement->bind_param('ii', $isActive, $userId);
        $statement->execute();
        $statement->close();
    }

    public function updateTeacherSetupCredentials(int $userId, string $username, string $passwordHash, int $isVerified): void
    {
        $statement = $this->connection->prepare(
            'UPDATE users
             SET username = ?, password = ?, is_verified = ?
             WHERE user_id = ?'
        );
        $statement->bind_param('ssii', $username, $passwordHash, $isVerified, $userId);
        $statement->execute();
        $statement->close();
    }

    public function updateAdminSetupCredentials(int $userId, string $username, string $email, string $passwordHash, int $isVerified): void
    {
        $statement = $this->connection->prepare(
            'UPDATE users
             SET username = ?, email = ?, password = ?, is_verified = ?
             WHERE user_id = ? AND date_deleted IS NULL'
        );
        $statement->bind_param('sssii', $username, $email, $passwordHash, $isVerified, $userId);
        $statement->execute();
        $statement->close();
    }

    public function updateUserVerifiedState(int $userId, int $isVerified): void
    {
        $statement = $this->connection->prepare(
            'UPDATE users
             SET is_verified = ?
             WHERE user_id = ? AND date_deleted IS NULL'
        );
        $statement->bind_param('ii', $isVerified, $userId);
        $statement->execute();
        $statement->close();
    }

    public function updateTeacherAccount(int $userId, string $username, string $email, string $firstname, string $lastname): void
    {
        $statement = $this->connection->prepare(
            'UPDATE users
             SET username = ?, email = ?
             WHERE user_id = ? AND date_deleted IS NULL'
        );
        $statement->bind_param('ssi', $username, $email, $userId);
        $statement->execute();
        $statement->close();

        $statement = $this->connection->prepare(
            'UPDATE user_details
             SET firstname = ?, lastname = ?
             WHERE user_id = ?'
        );
        $statement->bind_param('ssi', $firstname, $lastname, $userId);
        $statement->execute();
        $statement->close();
    }

    public function findUserDetailsAvatar(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT
                user_details.image_id,
                user_details.id_picture,
                user_details.student_number,
                images.source AS avatar_url,
                id_images.source AS id_picture_url
             FROM user_details
             LEFT JOIN images ON images.img_id = user_details.image_id
             LEFT JOIN images AS id_images ON id_images.img_id = user_details.id_picture
             WHERE user_details.user_id = ?
             LIMIT 1'
        );
        $statement->bind_param('i', $userId);
        $statement->execute();
        $details = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $details ?: null;
    }

    public function insertImage(string $source): int
    {
        $statement = $this->connection->prepare('INSERT INTO images (source) VALUES (?)');
        $statement->bind_param('s', $source);
        $statement->execute();
        $imageId = (int) $statement->insert_id;
        $statement->close();

        return $imageId;
    }

    public function upsertUserDetails(
        int $userId,
        int $imageId,
        string $firstname,
        string $lastname,
        ?int $idPicture = null,
        ?string $studentNumber = null
    ): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO user_details (user_id, image_id, id_picture, firstname, lastname, student_number)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                image_id = VALUES(image_id),
                id_picture = VALUES(id_picture),
                firstname = VALUES(firstname),
                lastname = VALUES(lastname),
                student_number = VALUES(student_number)'
        );
        $statement->bind_param('iiisss', $userId, $imageId, $idPicture, $firstname, $lastname, $studentNumber);
        $statement->execute();
        $statement->close();
    }
}
