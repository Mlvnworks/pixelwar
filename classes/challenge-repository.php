<?php

final class ChallengeRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function findDifficultyByName(string $name): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT difficulty_id, name, description, points
             FROM difficulties
             WHERE LOWER(name) = LOWER(?)
             LIMIT 1'
        );
        $statement->bind_param('s', $name);
        $statement->execute();
        $difficulty = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $difficulty ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDifficulties(): array
    {
        $statement = $this->connection->prepare(
            'SELECT difficulty_id, name, description, points
             FROM difficulties
             ORDER BY difficulty_id ASC'
        );
        $statement->execute();
        $difficulties = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $difficulties;
    }

    public function createChallenge(
        int $userId,
        int $difficultyId,
        string $name,
        string $instruction,
        string $htmlSource,
        string $cssSource,
        int $status
    ): int {
        $statement = $this->connection->prepare(
            'INSERT INTO challenges (user_id, difficulty_id, name, instruction, html_source, css_source, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('iissssi', $userId, $difficultyId, $name, $instruction, $htmlSource, $cssSource, $status);
        $statement->execute();
        $challengeId = (int) $statement->insert_id;
        $statement->close();

        return $challengeId;
    }

    public function updateChallenge(
        int $challengeId,
        int $difficultyId,
        string $name,
        string $instruction,
        string $htmlSource,
        string $cssSource,
        int $status
    ): void {
        $statement = $this->connection->prepare(
            'UPDATE challenges
             SET difficulty_id = ?, name = ?, instruction = ?, html_source = ?, css_source = ?, status = ?
             WHERE challenge_id = ? AND date_deleted IS NULL'
        );
        $statement->bind_param('issssii', $difficultyId, $name, $instruction, $htmlSource, $cssSource, $status, $challengeId);
        $statement->execute();
        $statement->close();
    }

    public function updateChallengeVisibilityForOwner(int $challengeId, int $ownerId, int $status): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE challenges
             SET status = ?
             WHERE challenge_id = ?
                AND user_id = ?
                AND date_deleted IS NULL'
        );
        $statement->bind_param('iii', $status, $challengeId, $ownerId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();

        return $updated;
    }

    public function softDeleteChallengeForOwner(int $challengeId, int $ownerId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE challenges
             SET date_deleted = CURRENT_TIMESTAMP
             WHERE challenge_id = ?
                AND user_id = ?
                AND date_deleted IS NULL'
        );
        $statement->bind_param('ii', $challengeId, $ownerId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();

        return $updated;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listLatestCreated(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                challenges.challenge_id,
                challenges.name,
                challenges.instruction,
                challenges.status,
                challenges.date_created,
                difficulties.name AS difficulty_name,
                difficulties.points,
                users.username AS author
             FROM challenges
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = challenges.user_id
             WHERE challenges.date_deleted IS NULL
             ORDER BY challenges.date_created DESC
             LIMIT ?'
        );
        $statement->bind_param('i', $limit);
        $statement->execute();
        $challenges = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $challenges;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listLatestPublicCreated(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                challenges.challenge_id,
                challenges.name,
                challenges.instruction,
                challenges.status,
                challenges.date_created,
                difficulties.name AS difficulty_name,
                difficulties.points,
                users.username AS author
             FROM challenges
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = challenges.user_id
             WHERE challenges.date_deleted IS NULL
                AND challenges.status = 1
             ORDER BY challenges.date_created DESC
             LIMIT ?'
        );
        $statement->bind_param('i', $limit);
        $statement->execute();
        $challenges = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $challenges;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchCreatedChallenges(string $search = '', string $difficulty = '', ?int $userId = null, int $limit = 60): array
    {
        $limit = max(1, min(100, $limit));
        $search = trim($search);
        $difficulty = strtolower(trim($difficulty));
        $conditions = [];
        $types = '';
        $values = [];

        if ($search !== '') {
            $conditions[] = '(challenges.name LIKE ? OR challenges.instruction LIKE ? OR users.username LIKE ?)';
            $searchValue = '%' . $search . '%';
            $types .= 'sss';
            $values[] = $searchValue;
            $values[] = $searchValue;
            $values[] = $searchValue;
        }

        if ($difficulty !== '' && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $conditions[] = 'LOWER(difficulties.name) = ?';
            $types .= 's';
            $values[] = $difficulty;
        }

        if ($userId !== null && $userId > 0) {
            $conditions[] = 'challenges.user_id = ?';
            $types .= 'i';
            $values[] = $userId;
        }

        $conditions[] = 'challenges.date_deleted IS NULL';
        $where = ' WHERE ' . implode(' AND ', $conditions);
        $sql = 'SELECT
                challenges.challenge_id,
                challenges.name,
                challenges.instruction,
                challenges.status,
                challenges.date_created,
                difficulties.name AS difficulty_name,
                difficulties.points,
                users.username AS author
             FROM challenges
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = challenges.user_id'
            . $where
            . ' ORDER BY challenges.date_created DESC
             LIMIT ?';

        $types .= 'i';
        $values[] = $limit;

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$values);
        $statement->execute();
        $challenges = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $challenges;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchPublicCreatedChallenges(string $search = '', string $difficulty = '', int $limit = 60): array
    {
        $limit = max(1, min(100, $limit));
        $search = trim($search);
        $difficulty = strtolower(trim($difficulty));
        $conditions = ['challenges.date_deleted IS NULL', 'challenges.status = 1'];
        $types = '';
        $values = [];

        if ($search !== '') {
            $conditions[] = '(challenges.name LIKE ? OR challenges.instruction LIKE ? OR users.username LIKE ?)';
            $searchValue = '%' . $search . '%';
            $types .= 'sss';
            $values[] = $searchValue;
            $values[] = $searchValue;
            $values[] = $searchValue;
        }

        if ($difficulty !== '' && in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $conditions[] = 'LOWER(difficulties.name) = ?';
            $types .= 's';
            $values[] = $difficulty;
        }

        $where = ' WHERE ' . implode(' AND ', $conditions);
        $sql = 'SELECT
                challenges.challenge_id,
                challenges.name,
                challenges.instruction,
                challenges.status,
                challenges.date_created,
                difficulties.name AS difficulty_name,
                difficulties.points,
                users.username AS author
             FROM challenges
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = challenges.user_id'
            . $where
            . ' ORDER BY challenges.date_created DESC
             LIMIT ?';

        $types .= 'i';
        $values[] = $limit;

        $statement = $this->connection->prepare($sql);
        $statement->bind_param($types, ...$values);
        $statement->execute();
        $challenges = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $challenges;
    }

    public function findCreatedChallenge(int $challengeId): ?array
    {
        if ($challengeId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                challenges.challenge_id,
                challenges.user_id,
                challenges.name,
                challenges.instruction,
                challenges.html_source,
                challenges.css_source,
                challenges.status,
                challenges.date_created,
                difficulties.name AS difficulty_name,
                difficulties.description AS difficulty_description,
                difficulties.points,
                users.username AS author,
                user_details.firstname,
                user_details.lastname,
                images.source AS author_avatar_url
             FROM challenges
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = challenges.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             LEFT JOIN images ON images.img_id = user_details.image_id
             WHERE challenges.challenge_id = ?
                AND challenges.date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('i', $challengeId);
        $statement->execute();
        $challenge = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $challenge ?: null;
    }

    public function findPublicCreatedChallenge(int $challengeId): ?array
    {
        if ($challengeId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                challenges.challenge_id,
                challenges.user_id,
                challenges.name,
                challenges.instruction,
                challenges.html_source,
                challenges.css_source,
                challenges.status,
                challenges.date_created,
                difficulties.name AS difficulty_name,
                difficulties.description AS difficulty_description,
                difficulties.points,
                users.username AS author,
                user_details.firstname,
                user_details.lastname
             FROM challenges
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = challenges.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             WHERE challenges.challenge_id = ?
                AND challenges.status = 1
                AND challenges.date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('i', $challengeId);
        $statement->execute();
        $challenge = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $challenge ?: null;
    }

    public function findCreatedChallengeForOwner(int $challengeId, int $ownerId): ?array
    {
        if ($challengeId <= 0 || $ownerId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                challenges.challenge_id,
                challenges.user_id,
                challenges.name,
                challenges.instruction,
                challenges.html_source,
                challenges.css_source,
                challenges.status,
                challenges.date_created,
                difficulties.name AS difficulty_name,
                difficulties.description AS difficulty_description,
                difficulties.points,
                users.username AS author,
                user_details.firstname,
                user_details.lastname
             FROM challenges
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = challenges.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             WHERE challenges.challenge_id = ?
                AND challenges.user_id = ?
                AND challenges.date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('ii', $challengeId, $ownerId);
        $statement->execute();
        $challenge = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $challenge ?: null;
    }
}
