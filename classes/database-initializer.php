<?php

final class DatabaseInitializer
{
    private string $host;
    private string $user;
    private string $password;
    private string $database;
    private int $port;

    public function __construct(string $host, string $user, string $password, string $database, int $port)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
    }

    public function initialize(): mysqli
    {
        $this->assertSafeDatabaseName();

        $connection = new mysqli($this->connectionHost(), $this->user, $this->password, '', $this->port);
        $connection->set_charset('utf8mb4');
        $connection->query(sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $this->database
        ));
        $connection->select_db($this->database);

        $this->runPreSchemaMigrations($connection);
        $this->runSchema($connection);
        $this->runMigrations($connection);
        $this->seedRoles($connection);
        $this->seedDefaultAdmin($connection);
        $this->seedDefaultTeacher($connection);

        return $connection;
    }

    private function runSchema(mysqli $connection): void
    {
        foreach ($this->schemaStatements() as $statement) {
            $connection->query($statement);
        }
    }

    /**
     * Add CREATE TABLE IF NOT EXISTS statements here once the schema is finalized.
     * The database itself is created now; tables stay empty until the real structure is provided.
     *
     * @return array<int, string>
     */
    private function schemaStatements(): array
    {
        return [
            'CREATE TABLE IF NOT EXISTS `images` (
                `img_id` INT NOT NULL AUTO_INCREMENT,
                `source` VARCHAR(255) NOT NULL,
                `date_added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`img_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `roles` (
                `role_id` INT NOT NULL AUTO_INCREMENT,
                `role` VARCHAR(50) NOT NULL,
                PRIMARY KEY (`role_id`),
                UNIQUE KEY `roles_role_unique` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `users` (
                `user_id` INT NOT NULL AUTO_INCREMENT,
                `role_id` INT NOT NULL,
                `username` VARCHAR(50) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `is_verified` INT NOT NULL DEFAULT 0,
                `registration_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `date_deleted` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`user_id`),
                UNIQUE KEY `users_username_unique` (`username`),
                UNIQUE KEY `users_email_unique` (`email`),
                KEY `users_role_id_index` (`role_id`),
                CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `verifications` (
                `ev_id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `type` VARCHAR(80) NOT NULL,
                `token` VARCHAR(255) NOT NULL,
                `request_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `status` INT NOT NULL DEFAULT 0,
                PRIMARY KEY (`ev_id`),
                KEY `verifications_user_id_index` (`user_id`),
                KEY `verifications_token_index` (`token`),
                CONSTRAINT `verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `user_details` (
                `ud_id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `image_id` INT NOT NULL,
                `firstname` VARCHAR(100) NOT NULL,
                `lastname` VARCHAR(100) NOT NULL,
                PRIMARY KEY (`ud_id`),
                UNIQUE KEY `user_details_user_id_unique` (`user_id`),
                KEY `user_details_image_id_index` (`image_id`),
                CONSTRAINT `user_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
                CONSTRAINT `user_details_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `images` (`img_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        ];
    }

    private function runPreSchemaMigrations(mysqli $connection): void
    {
        if ($this->tableExists($connection, 'image') && !$this->tableExists($connection, 'images')) {
            $connection->query('RENAME TABLE `image` TO `images`');
        }
    }

    private function runMigrations(mysqli $connection): void
    {
        if (
            $this->columnExists($connection, 'verifications', 'stats')
            && !$this->columnExists($connection, 'verifications', 'status')
        ) {
            $connection->query('ALTER TABLE `verifications` CHANGE `stats` `status` INT NOT NULL DEFAULT 0');
        }

        $connection->query("UPDATE `roles` SET `role` = 'student' WHERE `role_id` = 3 AND `role` = 'player'");
    }

    private function tableExists(mysqli $connection, string $table): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) AS table_count
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?'
        );
        $statement->bind_param('s', $table);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['table_count'] ?? 0) > 0;
    }

    private function seedRoles(mysqli $connection): void
    {
        $roles = [
            1 => 'admin',
            2 => 'teacher',
            3 => 'student',
        ];
        $statement = $connection->prepare(
            'INSERT INTO `roles` (`role_id`, `role`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `role` = VALUES(`role`)'
        );

        foreach ($roles as $roleId => $role) {
            $statement->bind_param('is', $roleId, $role);
            $statement->execute();
        }

        $statement->close();
    }

    private function seedDefaultAdmin(mysqli $connection): void
    {
        $username = 'admin';
        $email = 'admin@pixelwar.local';
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $roleId = 1;
        $isVerified = 1;

        $statement = $connection->prepare(
            'INSERT INTO `users` (`role_id`, `username`, `email`, `password`, `is_verified`)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`), `is_verified` = VALUES(`is_verified`)'
        );
        $statement->bind_param('isssi', $roleId, $username, $email, $passwordHash, $isVerified);
        $statement->execute();
        $statement->close();
    }

    private function seedDefaultTeacher(mysqli $connection): void
    {
        $username = 'teacher';
        $email = 'teacher@pixelwar.local';
        $passwordHash = password_hash('teacher123', PASSWORD_DEFAULT);
        $roleId = 2;
        $isVerified = 1;

        $statement = $connection->prepare(
            'INSERT INTO `users` (`role_id`, `username`, `email`, `password`, `is_verified`)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`), `is_verified` = VALUES(`is_verified`)'
        );
        $statement->bind_param('isssi', $roleId, $username, $email, $passwordHash, $isVerified);
        $statement->execute();
        $statement->close();
    }

    private function columnExists(mysqli $connection, string $table, string $column): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) AS column_count
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?'
        );
        $statement->bind_param('ss', $table, $column);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['column_count'] ?? 0) > 0;
    }

    private function assertSafeDatabaseName(): void
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $this->database) !== 1) {
            throw new RuntimeException('DB_NAME may only contain letters, numbers, and underscores.');
        }
    }

    private function connectionHost(): string
    {
        return $this->host === 'localhost' ? '127.0.0.1' : $this->host;
    }
}
