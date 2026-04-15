<?php

class UserRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function findSignupConflict(string $username, string $email): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT username, email FROM users WHERE date_deleted IS NULL AND (username = ? OR email = ?) LIMIT 1'
        );
        $statement->bind_param('ss', $username, $email);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $statement = $this->connection->prepare('SELECT user_id FROM users WHERE username = ? AND date_deleted IS NULL LIMIT 1');
        $statement->bind_param('s', $username);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->connection->prepare('SELECT user_id FROM users WHERE email = ? AND date_deleted IS NULL LIMIT 1');
        $statement->bind_param('s', $email);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        $statement = $this->connection->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? AND date_deleted IS NULL LIMIT 1');
        $statement->bind_param('si', $email, $userId);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    public function findLoginUser(string $identity): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT user_id, role_id, username, email, password, is_verified FROM users WHERE date_deleted IS NULL AND (username = ? OR email = ?) LIMIT 1'
        );
        $statement->bind_param('ss', $identity, $identity);
        $statement->execute();
        $user = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $user ?: null;
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

    public function findUserDetailsAvatar(int $userId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT user_details.image_id, images.source AS avatar_url
             FROM user_details
             LEFT JOIN images ON images.img_id = user_details.image_id
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

    public function upsertUserDetails(int $userId, int $imageId, string $firstname, string $lastname): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO user_details (user_id, image_id, firstname, lastname)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE image_id = VALUES(image_id), firstname = VALUES(firstname), lastname = VALUES(lastname)'
        );
        $statement->bind_param('iiss', $userId, $imageId, $firstname, $lastname);
        $statement->execute();
        $statement->close();
    }
}
