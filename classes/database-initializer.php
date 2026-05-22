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
        $this->seedDifficulties($connection);
        $this->seedDefaultAdmin($connection);

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
                `is_active` INT NOT NULL DEFAULT 0,
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
                `id_picture` INT NULL DEFAULT NULL,
                `firstname` VARCHAR(100) NOT NULL,
                `lastname` VARCHAR(100) NOT NULL,
                `student_number` VARCHAR(100) NULL DEFAULT NULL,
                PRIMARY KEY (`ud_id`),
                UNIQUE KEY `user_details_user_id_unique` (`user_id`),
                KEY `user_details_image_id_index` (`image_id`),
                KEY `user_details_id_picture_index` (`id_picture`),
                CONSTRAINT `user_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
                CONSTRAINT `user_details_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `images` (`img_id`),
                CONSTRAINT `user_details_id_picture_foreign` FOREIGN KEY (`id_picture`) REFERENCES `images` (`img_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `difficulties` (
                `difficulty_id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(50) NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `points` INT NOT NULL,
                PRIMARY KEY (`difficulty_id`),
                UNIQUE KEY `difficulties_name_unique` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `challenges` (
                `challenge_id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `difficulty_id` INT NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `instruction` TEXT NOT NULL,
                `html_source` VARCHAR(255) NOT NULL,
                `css_source` VARCHAR(255) NOT NULL,
                `status` INT NOT NULL DEFAULT 0,
                `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `date_deleted` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`challenge_id`),
                KEY `challenges_user_id_index` (`user_id`),
                KEY `challenges_difficulty_id_index` (`difficulty_id`),
                KEY `challenges_status_index` (`status`),
                CONSTRAINT `challenges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
                CONSTRAINT `challenges_difficulty_id_foreign` FOREIGN KEY (`difficulty_id`) REFERENCES `difficulties` (`difficulty_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `rooms` (
                `room_id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `challenge_id` INT NOT NULL,
                `room_code` VARCHAR(100) NOT NULL,
                `room_name` VARCHAR(150) NOT NULL,
                `room_description` VARCHAR(255) NOT NULL,
                `status` INT NOT NULL DEFAULT 1,
                `timer_limit` INT NOT NULL DEFAULT 0,
                `strict_mode` INT NOT NULL DEFAULT 0,
                `started_at` TIMESTAMP NULL DEFAULT NULL,
                `ended_at` TIMESTAMP NULL DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `date_deleted` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`room_id`),
                KEY `rooms_room_code_index` (`room_code`),
                KEY `rooms_user_id_index` (`user_id`),
                KEY `rooms_challenge_id_index` (`challenge_id`),
                KEY `rooms_status_index` (`status`),
                CONSTRAINT `rooms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
                CONSTRAINT `rooms_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `room_players` (
                `rp_id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `room_id` INT NOT NULL,
                `status` INT NOT NULL DEFAULT 0,
                `last_seen_at` TIMESTAMP NULL NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `started_at` TIMESTAMP NULL DEFAULT NULL,
                `completed_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`rp_id`),
                KEY `room_players_user_id_index` (`user_id`),
                KEY `room_players_room_id_index` (`room_id`),
                KEY `room_players_status_index` (`status`),
                CONSTRAINT `room_players_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
                CONSTRAINT `room_players_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `user_challenge` (
                `uc_id` INT NOT NULL AUTO_INCREMENT,
                `challenge_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                `room_id` INT NULL DEFAULT NULL,
                `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `completed_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`uc_id`),
                KEY `user_challenge_challenge_id_index` (`challenge_id`),
                KEY `user_challenge_user_id_index` (`user_id`),
                KEY `user_challenge_room_id_index` (`room_id`),
                CONSTRAINT `user_challenge_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`),
                CONSTRAINT `user_challenge_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
                CONSTRAINT `user_challenge_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'CREATE TABLE IF NOT EXISTS `activity_logs` (
                `al_id` INT NOT NULL AUTO_INCREMENT,
                `user_id` INT NOT NULL,
                `category` VARCHAR(50) NOT NULL,
                `log_text` VARCHAR(255) NOT NULL,
                `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`al_id`),
                KEY `activity_logs_user_id_index` (`user_id`),
                KEY `activity_logs_category_index` (`category`),
                CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
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

        if (
            $this->tableExists($connection, 'activity_logs')
            && !$this->columnExists($connection, 'activity_logs', 'category')
        ) {
            $connection->query("ALTER TABLE `activity_logs` ADD `category` VARCHAR(50) NOT NULL DEFAULT 'general' AFTER `user_id`");
            $connection->query('ALTER TABLE `activity_logs` ADD KEY `activity_logs_category_index` (`category`)');
        }

        if ($this->tableExists($connection, 'challenges') && !$this->columnExists($connection, 'challenges', 'date_deleted')) {
            $connection->query('ALTER TABLE `challenges` ADD `date_deleted` TIMESTAMP NULL DEFAULT NULL AFTER `date_created`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'date_deleted')) {
            $connection->query('ALTER TABLE `rooms` ADD `date_deleted` TIMESTAMP NULL DEFAULT NULL AFTER `created_at`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'user_id')) {
            $connection->query('ALTER TABLE `rooms` ADD `user_id` INT NOT NULL AFTER `room_id`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'challenge_id')) {
            $connection->query('ALTER TABLE `rooms` ADD `challenge_id` INT NOT NULL AFTER `user_id`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'room_code')) {
            $connection->query("ALTER TABLE `rooms` ADD `room_code` VARCHAR(100) NOT NULL DEFAULT '' AFTER `challenge_id`");
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'room_name')) {
            $connection->query('ALTER TABLE `rooms` ADD `room_name` VARCHAR(150) NOT NULL AFTER `room_code`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'room_description')) {
            $connection->query('ALTER TABLE `rooms` ADD `room_description` VARCHAR(255) NOT NULL AFTER `room_name`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'status')) {
            $connection->query('ALTER TABLE `rooms` ADD `status` INT NOT NULL DEFAULT 1 AFTER `room_description`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'timer_limit')) {
            $connection->query('ALTER TABLE `rooms` ADD `timer_limit` INT NOT NULL DEFAULT 0 AFTER `status`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'strict_mode')) {
            $connection->query('ALTER TABLE `rooms` ADD `strict_mode` INT NOT NULL DEFAULT 0 AFTER `timer_limit`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'started_at')) {
            $connection->query('ALTER TABLE `rooms` ADD `started_at` TIMESTAMP NULL DEFAULT NULL AFTER `strict_mode`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'ended_at')) {
            $connection->query('ALTER TABLE `rooms` ADD `ended_at` TIMESTAMP NULL DEFAULT NULL AFTER `started_at`');
        }

        if ($this->tableExists($connection, 'rooms') && !$this->columnExists($connection, 'rooms', 'created_at')) {
            $connection->query('ALTER TABLE `rooms` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `ended_at`');
        }

        if (
            $this->tableExists($connection, 'rooms')
            && $this->columnExists($connection, 'rooms', 'room_code')
            && !$this->indexExists($connection, 'rooms', 'rooms_room_code_index')
        ) {
            $connection->query('ALTER TABLE `rooms` ADD KEY `rooms_room_code_index` (`room_code`)');
        }

        if (
            $this->tableExists($connection, 'rooms')
            && $this->columnExists($connection, 'rooms', 'user_id')
            && !$this->indexExists($connection, 'rooms', 'rooms_user_id_index')
        ) {
            $connection->query('ALTER TABLE `rooms` ADD KEY `rooms_user_id_index` (`user_id`)');
        }

        if (
            $this->tableExists($connection, 'rooms')
            && $this->columnExists($connection, 'rooms', 'challenge_id')
            && !$this->indexExists($connection, 'rooms', 'rooms_challenge_id_index')
        ) {
            $connection->query('ALTER TABLE `rooms` ADD KEY `rooms_challenge_id_index` (`challenge_id`)');
        }

        if (
            $this->tableExists($connection, 'rooms')
            && $this->columnExists($connection, 'rooms', 'status')
            && !$this->indexExists($connection, 'rooms', 'rooms_status_index')
        ) {
            $connection->query('ALTER TABLE `rooms` ADD KEY `rooms_status_index` (`status`)');
        }

        if (
            $this->tableExists($connection, 'rooms')
            && $this->columnExists($connection, 'rooms', 'user_id')
            && !$this->constraintExists($connection, 'rooms', 'rooms_user_id_foreign')
        ) {
            $connection->query('ALTER TABLE `rooms` ADD CONSTRAINT `rooms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)');
        }

        if (
            $this->tableExists($connection, 'rooms')
            && $this->columnExists($connection, 'rooms', 'challenge_id')
            && !$this->constraintExists($connection, 'rooms', 'rooms_challenge_id_foreign')
        ) {
            $connection->query('ALTER TABLE `rooms` ADD CONSTRAINT `rooms_challenge_id_foreign` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`challenge_id`)');
        }

        if ($this->tableExists($connection, 'room_players') && !$this->columnExists($connection, 'room_players', 'user_id')) {
            $connection->query('ALTER TABLE `room_players` ADD `user_id` INT NOT NULL AFTER `rp_id`');
        }

        if ($this->tableExists($connection, 'room_players') && !$this->columnExists($connection, 'room_players', 'room_id')) {
            $connection->query('ALTER TABLE `room_players` ADD `room_id` INT NOT NULL AFTER `user_id`');
        }

        if ($this->tableExists($connection, 'room_players') && !$this->columnExists($connection, 'room_players', 'status')) {
            $connection->query('ALTER TABLE `room_players` ADD `status` INT NOT NULL DEFAULT 0 AFTER `room_id`');
        }

        if ($this->tableExists($connection, 'room_players') && !$this->columnExists($connection, 'room_players', 'last_seen_at')) {
            $connection->query('ALTER TABLE `room_players` ADD `last_seen_at` TIMESTAMP NULL NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`');
        }

        if ($this->tableExists($connection, 'room_players') && !$this->columnExists($connection, 'room_players', 'started_at')) {
            $connection->query('ALTER TABLE `room_players` ADD `started_at` TIMESTAMP NULL DEFAULT NULL AFTER `last_seen_at`');
        }

        if ($this->tableExists($connection, 'room_players') && !$this->columnExists($connection, 'room_players', 'completed_at')) {
            $connection->query('ALTER TABLE `room_players` ADD `completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `started_at`');
        }

        if (
            $this->tableExists($connection, 'room_players')
            && $this->columnExists($connection, 'room_players', 'user_id')
            && !$this->indexExists($connection, 'room_players', 'room_players_user_id_index')
        ) {
            $connection->query('ALTER TABLE `room_players` ADD KEY `room_players_user_id_index` (`user_id`)');
        }

        if (
            $this->tableExists($connection, 'room_players')
            && $this->columnExists($connection, 'room_players', 'room_id')
            && !$this->indexExists($connection, 'room_players', 'room_players_room_id_index')
        ) {
            $connection->query('ALTER TABLE `room_players` ADD KEY `room_players_room_id_index` (`room_id`)');
        }

        if (
            $this->tableExists($connection, 'room_players')
            && $this->columnExists($connection, 'room_players', 'status')
            && !$this->indexExists($connection, 'room_players', 'room_players_status_index')
        ) {
            $connection->query('ALTER TABLE `room_players` ADD KEY `room_players_status_index` (`status`)');
        }

        if (
            $this->tableExists($connection, 'room_players')
            && $this->columnExists($connection, 'room_players', 'user_id')
            && !$this->constraintExists($connection, 'room_players', 'room_players_user_id_foreign')
        ) {
            $connection->query('ALTER TABLE `room_players` ADD CONSTRAINT `room_players_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)');
        }

        if (
            $this->tableExists($connection, 'room_players')
            && $this->columnExists($connection, 'room_players', 'room_id')
            && !$this->constraintExists($connection, 'room_players', 'room_players_room_id_foreign')
        ) {
            $connection->query('ALTER TABLE `room_players` ADD CONSTRAINT `room_players_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)');
        }

        if ($this->tableExists($connection, 'user_challenge') && !$this->columnExists($connection, 'user_challenge', 'room_id')) {
            $connection->query('ALTER TABLE `user_challenge` ADD `room_id` INT NULL DEFAULT NULL AFTER `user_id`');
        }

        if (
            $this->tableExists($connection, 'user_challenge')
            && $this->columnExists($connection, 'user_challenge', 'room_id')
            && !$this->indexExists($connection, 'user_challenge', 'user_challenge_room_id_index')
        ) {
            $connection->query('ALTER TABLE `user_challenge` ADD KEY `user_challenge_room_id_index` (`room_id`)');
        }

        if (
            $this->tableExists($connection, 'user_challenge')
            && $this->columnExists($connection, 'user_challenge', 'room_id')
            && !$this->constraintExists($connection, 'user_challenge', 'user_challenge_room_id_foreign')
        ) {
            $connection->query('ALTER TABLE `user_challenge` ADD CONSTRAINT `user_challenge_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)');
        }

        if ($this->tableExists($connection, 'users') && !$this->columnExists($connection, 'users', 'is_active')) {
            $connection->query('ALTER TABLE `users` ADD `is_active` INT NOT NULL DEFAULT 0 AFTER `is_verified`');
        }

        if ($this->tableExists($connection, 'user_details') && !$this->columnExists($connection, 'user_details', 'id_picture')) {
            $connection->query('ALTER TABLE `user_details` ADD `id_picture` INT NULL DEFAULT NULL AFTER `image_id`');
        }

        if ($this->tableExists($connection, 'user_details') && !$this->columnExists($connection, 'user_details', 'student_number')) {
            $connection->query('ALTER TABLE `user_details` ADD `student_number` VARCHAR(100) NULL DEFAULT NULL AFTER `lastname`');
        }

        if (
            $this->tableExists($connection, 'user_details')
            && $this->columnExists($connection, 'user_details', 'id_picture')
            && !$this->indexExists($connection, 'user_details', 'user_details_id_picture_index')
        ) {
            $connection->query('ALTER TABLE `user_details` ADD KEY `user_details_id_picture_index` (`id_picture`)');
        }

        if (
            $this->tableExists($connection, 'user_details')
            && $this->columnExists($connection, 'user_details', 'id_picture')
            && !$this->constraintExists($connection, 'user_details', 'user_details_id_picture_foreign')
        ) {
            $connection->query('ALTER TABLE `user_details` ADD CONSTRAINT `user_details_id_picture_foreign` FOREIGN KEY (`id_picture`) REFERENCES `images` (`img_id`)');
        }

        if (
            $this->tableExists($connection, 'users')
            && $this->tableExists($connection, 'user_details')
            && $this->columnExists($connection, 'users', 'id_picture')
            && $this->columnExists($connection, 'user_details', 'id_picture')
        ) {
            $connection->query(
                'UPDATE `user_details`
                 INNER JOIN `users` ON `users`.`user_id` = `user_details`.`user_id`
                 SET `user_details`.`id_picture` = `users`.`id_picture`
                 WHERE `users`.`id_picture` IS NOT NULL
                    AND `user_details`.`id_picture` IS NULL'
            );
        }

        if (
            $this->tableExists($connection, 'users')
            && $this->tableExists($connection, 'user_details')
            && $this->columnExists($connection, 'users', 'student_number')
            && $this->columnExists($connection, 'user_details', 'student_number')
        ) {
            $connection->query(
                'UPDATE `user_details`
                 INNER JOIN `users` ON `users`.`user_id` = `user_details`.`user_id`
                 SET `user_details`.`student_number` = `users`.`student_number`
                 WHERE `users`.`student_number` IS NOT NULL
                    AND (`user_details`.`student_number` IS NULL OR `user_details`.`student_number` = \'\')'
            );
        }

        if ($this->tableExists($connection, 'users') && $this->constraintExists($connection, 'users', 'users_id_picture_foreign')) {
            $connection->query('ALTER TABLE `users` DROP FOREIGN KEY `users_id_picture_foreign`');
        }

        if ($this->tableExists($connection, 'users') && $this->indexExists($connection, 'users', 'users_id_picture_index')) {
            $connection->query('ALTER TABLE `users` DROP INDEX `users_id_picture_index`');
        }

        if ($this->tableExists($connection, 'users') && $this->columnExists($connection, 'users', 'id_picture')) {
            $connection->query('ALTER TABLE `users` DROP COLUMN `id_picture`');
        }

        if ($this->tableExists($connection, 'users') && $this->columnExists($connection, 'users', 'student_number')) {
            $connection->query('ALTER TABLE `users` DROP COLUMN `student_number`');
        }
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

    private function indexExists(mysqli $connection, string $table, string $index): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) AS index_count
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND INDEX_NAME = ?'
        );
        $statement->bind_param('ss', $table, $index);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['index_count'] ?? 0) > 0;
    }

    private function constraintExists(mysqli $connection, string $table, string $constraint): bool
    {
        $statement = $connection->prepare(
            'SELECT COUNT(*) AS constraint_count
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND CONSTRAINT_NAME = ?'
        );
        $statement->bind_param('ss', $table, $constraint);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return (int) ($row['constraint_count'] ?? 0) > 0;
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

    private function seedDifficulties(mysqli $connection): void
    {
        $difficulties = [
            1 => ['easy', 'Intro-friendly CSS matching challenge.', 20],
            2 => ['medium', 'Requires combining layout, spacing, and visual details.', 40],
            3 => ['hard', 'Advanced matching challenge with stricter visual precision.', 80],
        ];
        $statement = $connection->prepare(
            'INSERT INTO `difficulties` (`difficulty_id`, `name`, `description`, `points`) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `name` = VALUES(`name`),
                `description` = VALUES(`description`),
                `points` = VALUES(`points`)'
        );

        foreach ($difficulties as $difficultyId => $difficulty) {
            [$name, $description, $points] = $difficulty;
            $statement->bind_param('issi', $difficultyId, $name, $description, $points);
            $statement->execute();
        }

        $statement->close();
    }

    private function seedDefaultAdmin(mysqli $connection): void
    {
        $existingAdmins = $connection->query("SELECT COUNT(*) AS total FROM `users` WHERE `role_id` = 1 AND `date_deleted` IS NULL");
        $existingAdminsRow = $existingAdmins ? $existingAdmins->fetch_assoc() : null;
        if ((int) ($existingAdminsRow['total'] ?? 0) > 0) {
            return;
        }

        $username = defined('BOOTSTRAP_ADMIN_USERNAME') ? BOOTSTRAP_ADMIN_USERNAME : 'admin';
        $email = defined('BOOTSTRAP_ADMIN_EMAIL') ? BOOTSTRAP_ADMIN_EMAIL : 'admin@pixelwar.local';
        $bootstrapPassword = defined('BOOTSTRAP_ADMIN_PASSWORD') ? BOOTSTRAP_ADMIN_PASSWORD : 'admin123';

        if ($username === '' || $email === '' || $bootstrapPassword === '') {
            throw new RuntimeException('Bootstrap admin credentials are incomplete. Set BOOTSTRAP_ADMIN_USERNAME, BOOTSTRAP_ADMIN_EMAIL, and BOOTSTRAP_ADMIN_PASSWORD.');
        }

        $passwordHash = password_hash($bootstrapPassword, PASSWORD_DEFAULT);
        $roleId = 1;
        $isVerified = 0;
        $isActive = 1;

        $statement = $connection->prepare(
            'INSERT INTO `users` (`role_id`, `username`, `email`, `password`, `is_verified`, `is_active`)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('isssii', $roleId, $username, $email, $passwordHash, $isVerified, $isActive);
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
