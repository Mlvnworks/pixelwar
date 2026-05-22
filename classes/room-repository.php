<?php

final class RoomRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function create(
        int $userId,
        int $challengeId,
        string $roomName,
        string $roomDescription,
        int $timerLimit,
        int $strictMode
    ): int {
        $roomCode = $this->generateUniqueRoomCode();
        $statement = $this->connection->prepare(
            'INSERT INTO rooms (user_id, challenge_id, room_code, room_name, room_description, timer_limit, strict_mode)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param('iisssii', $userId, $challengeId, $roomCode, $roomName, $roomDescription, $timerLimit, $strictMode);
        $statement->execute();
        $roomId = (int) $statement->insert_id;
        $statement->close();

        return $roomId;
    }

    public function updateForOwner(
        int $roomId,
        int $ownerId,
        int $challengeId,
        string $roomName,
        string $roomDescription,
        int $timerLimit,
        int $strictMode
    ): bool {
        $statement = $this->connection->prepare(
            'UPDATE rooms
             SET challenge_id = ?, room_name = ?, room_description = ?, timer_limit = ?, strict_mode = ?
             WHERE room_id = ?
                AND user_id = ?
                AND date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param(
            'issiiii',
            $challengeId,
            $roomName,
            $roomDescription,
            $timerLimit,
            $strictMode,
            $roomId,
            $ownerId
        );
        $statement->execute();
        $affected = $statement->affected_rows >= 0;
        $statement->close();

        return $affected;
    }

    public function softDeleteForOwner(int $roomId, int $ownerId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE rooms
             SET date_deleted = CURRENT_TIMESTAMP
             WHERE room_id = ?
                AND user_id = ?
                AND date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('ii', $roomId, $ownerId);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();

        return $deleted;
    }

    public function updateStatusForOwner(int $roomId, int $ownerId, int $status): bool
    {
        $normalizedStatus = $status === 0 ? 0 : 1;
        $statement = $this->connection->prepare(
            'UPDATE rooms
             SET status = ?
             WHERE room_id = ?
                AND user_id = ?
                AND date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('iii', $normalizedStatus, $roomId, $ownerId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function markStartedForOwner(int $roomId, int $ownerId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE rooms
             SET started_at = COALESCE(started_at, CURRENT_TIMESTAMP)
             WHERE room_id = ?
                AND user_id = ?
                AND date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('ii', $roomId, $ownerId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function markEndedForOwner(int $roomId, int $ownerId): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE rooms
             SET ended_at = COALESCE(ended_at, CURRENT_TIMESTAMP),
                 status = 0
             WHERE room_id = ?
                AND user_id = ?
                AND date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('ii', $roomId, $ownerId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function markEndedIfExpired(int $roomId): bool
    {
        if ($roomId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE rooms
             SET ended_at = COALESCE(ended_at, CURRENT_TIMESTAMP),
                 status = 0
             WHERE room_id = ?
                AND date_deleted IS NULL
                AND started_at IS NOT NULL
                AND ended_at IS NULL
                AND timer_limit > 0
                AND TIMESTAMPADD(MINUTE, timer_limit, started_at) <= CURRENT_TIMESTAMP
             LIMIT 1'
        );
        $statement->bind_param('i', $roomId);
        $statement->execute();
        $updated = $statement->affected_rows > 0;
        $statement->close();

        return $updated;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForOwner(int $userId, int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                rooms.room_id,
                rooms.user_id,
                rooms.challenge_id,
                rooms.room_code,
                rooms.room_name,
                rooms.room_description,
                rooms.status,
                rooms.timer_limit,
                rooms.strict_mode,
                rooms.started_at,
                rooms.ended_at,
                rooms.created_at,
                challenges.name AS challenge_name
             FROM rooms
             INNER JOIN challenges ON challenges.challenge_id = rooms.challenge_id
             WHERE rooms.user_id = ?
                AND rooms.date_deleted IS NULL
             ORDER BY rooms.created_at DESC
             LIMIT ?'
        );
        $statement->bind_param('ii', $userId, $limit);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    public function countForOwner(int $userId): int
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total
             FROM rooms
             WHERE user_id = ?
                AND date_deleted IS NULL'
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
    public function listLatestForOwner(int $userId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->connection->prepare(
            'SELECT
                rooms.room_id,
                rooms.user_id,
                rooms.challenge_id,
                rooms.room_code,
                rooms.room_name,
                rooms.room_description,
                rooms.status,
                rooms.timer_limit,
                rooms.strict_mode,
                rooms.started_at,
                rooms.ended_at,
                rooms.created_at,
                challenges.name AS challenge_name
             FROM rooms
             INNER JOIN challenges ON challenges.challenge_id = rooms.challenge_id
             WHERE rooms.user_id = ?
                AND rooms.date_deleted IS NULL
             ORDER BY rooms.created_at DESC
             LIMIT ?'
        );
        $statement->bind_param('ii', $userId, $limit);
        $statement->execute();
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        return $rows;
    }

    public function findById(int $roomId): ?array
    {
        if ($roomId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                rooms.room_id,
                rooms.user_id,
                rooms.challenge_id,
                rooms.room_code,
                rooms.room_name,
                rooms.room_description,
                rooms.status,
                rooms.timer_limit,
                rooms.strict_mode,
                rooms.started_at,
                rooms.ended_at,
                rooms.created_at,
                challenges.name AS challenge_name,
                challenges.instruction AS challenge_instruction,
                challenges.html_source,
                challenges.css_source,
                difficulties.name AS difficulty_name,
                difficulties.points,
                users.username AS teacher_username,
                user_details.firstname AS teacher_firstname,
                user_details.lastname AS teacher_lastname
             FROM rooms
             INNER JOIN challenges ON challenges.challenge_id = rooms.challenge_id
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = rooms.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             WHERE rooms.room_id = ?
                AND rooms.date_deleted IS NULL
                AND challenges.date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('i', $roomId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function findByCode(string $roomCode): ?array
    {
        $normalizedCode = strtoupper(trim($roomCode));
        if ($normalizedCode === '') {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                rooms.room_id,
                rooms.user_id,
                rooms.challenge_id,
                rooms.room_code,
                rooms.room_name,
                rooms.room_description,
                rooms.status,
                rooms.timer_limit,
                rooms.strict_mode,
                rooms.started_at,
                rooms.ended_at,
                rooms.created_at,
                challenges.name AS challenge_name,
                challenges.instruction AS challenge_instruction,
                challenges.html_source,
                challenges.css_source,
                difficulties.name AS difficulty_name,
                difficulties.points,
                users.username AS teacher_username,
                user_details.firstname AS teacher_firstname,
                user_details.lastname AS teacher_lastname
             FROM rooms
             INNER JOIN challenges ON challenges.challenge_id = rooms.challenge_id
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = rooms.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             WHERE rooms.date_deleted IS NULL
                AND challenges.date_deleted IS NULL
                AND UPPER(rooms.room_code) = ?
             LIMIT 1'
        );
        $statement->bind_param('s', $normalizedCode);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    public function findByIdForOwner(int $roomId, int $ownerId): ?array
    {
        if ($roomId <= 0 || $ownerId <= 0) {
            return null;
        }

        $statement = $this->connection->prepare(
            'SELECT
                rooms.room_id,
                rooms.user_id,
                rooms.challenge_id,
                rooms.room_code,
                rooms.room_name,
                rooms.room_description,
                rooms.status,
                rooms.timer_limit,
                rooms.strict_mode,
                rooms.started_at,
                rooms.ended_at,
                rooms.created_at,
                challenges.name AS challenge_name,
                challenges.instruction AS challenge_instruction,
                challenges.html_source,
                challenges.css_source,
                difficulties.name AS difficulty_name,
                difficulties.points,
                users.username AS teacher_username,
                user_details.firstname AS teacher_firstname,
                user_details.lastname AS teacher_lastname
             FROM rooms
             INNER JOIN challenges ON challenges.challenge_id = rooms.challenge_id
             INNER JOIN difficulties ON difficulties.difficulty_id = challenges.difficulty_id
             INNER JOIN users ON users.user_id = rooms.user_id
             LEFT JOIN user_details ON user_details.user_id = users.user_id
             WHERE rooms.room_id = ?
                AND rooms.user_id = ?
                AND rooms.date_deleted IS NULL
                AND challenges.date_deleted IS NULL
             LIMIT 1'
        );
        $statement->bind_param('ii', $roomId, $ownerId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();

        return $row ?: null;
    }

    private function generateUniqueRoomCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = 'ROOM-' . $this->randomCodeSegment($alphabet, 6);
            if (!$this->roomCodeExists($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Room code could not be generated right now.');
    }

    private function roomCodeExists(string $roomCode): bool
    {
        $statement = $this->connection->prepare(
            'SELECT room_id
             FROM rooms
             WHERE UPPER(room_code) = ?
             LIMIT 1'
        );
        $normalizedCode = strtoupper(trim($roomCode));
        $statement->bind_param('s', $normalizedCode);
        $statement->execute();
        $exists = $statement->get_result()->fetch_assoc() !== null;
        $statement->close();

        return $exists;
    }

    private function randomCodeSegment(string $alphabet, int $length): string
    {
        $maxIndex = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
