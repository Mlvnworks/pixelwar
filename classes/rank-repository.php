<?php

final class RankRepository
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
            'SELECT rank_id, name, points_requirements
             FROM ranks
             ORDER BY points_requirements ASC, rank_id ASC'
        );

        return $result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * @return array{
     *     current_name: string,
     *     current_requirement: int,
     *     next_name: string|null,
     *     next_requirement: int|null,
     *     progress_percent: int,
     *     display_requirement: int,
     *     is_max_rank: bool,
     *     badge_initial: string
     * }
     */
    public function progressForPoints(int $points): array
    {
        $points = max(0, $points);
        $ranks = $this->listAll();

        if ($ranks === []) {
            return $this->fallbackProgress($points);
        }

        $currentRank = null;
        $nextRank = null;

        foreach ($ranks as $rank) {
            $requirement = (int) ($rank['points_requirements'] ?? 0);

            if ($requirement <= $points) {
                $currentRank = $rank;
                continue;
            }

            $nextRank = $rank;
            break;
        }

        if ($currentRank === null) {
            $nextRequirement = max(1, (int) ($nextRank['points_requirements'] ?? 1));

            return [
                'current_name' => 'Unranked',
                'current_requirement' => 0,
                'next_name' => (string) ($nextRank['name'] ?? 'Next Rank'),
                'next_requirement' => $nextRequirement,
                'progress_percent' => min(100, (int) round(($points / $nextRequirement) * 100)),
                'display_requirement' => $nextRequirement,
                'is_max_rank' => false,
                'badge_initial' => 'U',
            ];
        }

        $currentRequirement = (int) ($currentRank['points_requirements'] ?? 0);
        $currentName = (string) ($currentRank['name'] ?? 'Rank');
        $nextRequirement = $nextRank !== null ? (int) ($nextRank['points_requirements'] ?? 0) : null;

        if ($nextRank === null || $nextRequirement === null || $nextRequirement <= $currentRequirement) {
            return [
                'current_name' => $currentName,
                'current_requirement' => $currentRequirement,
                'next_name' => null,
                'next_requirement' => null,
                'progress_percent' => 100,
                'display_requirement' => max($currentRequirement, $points),
                'is_max_rank' => true,
                'badge_initial' => $this->badgeInitial($currentName),
            ];
        }

        $progressRange = max(1, $nextRequirement - $currentRequirement);
        $progressValue = max(0, $points - $currentRequirement);

        return [
            'current_name' => $currentName,
            'current_requirement' => $currentRequirement,
            'next_name' => (string) ($nextRank['name'] ?? 'Next Rank'),
            'next_requirement' => $nextRequirement,
            'progress_percent' => min(100, (int) round(($progressValue / $progressRange) * 100)),
            'display_requirement' => $nextRequirement,
            'is_max_rank' => false,
            'badge_initial' => $this->badgeInitial($currentName),
        ];
    }

    /**
     * @return array{
     *     current_name: string,
     *     current_requirement: int,
     *     next_name: string,
     *     next_requirement: int,
     *     progress_percent: int,
     *     display_requirement: int,
     *     is_max_rank: bool,
     *     badge_initial: string
     * }
     */
    private function fallbackProgress(int $points): array
    {
        $requirement = 500;

        return [
            'current_name' => 'Beginner',
            'current_requirement' => 0,
            'next_name' => 'Next Rank',
            'next_requirement' => $requirement,
            'progress_percent' => min(100, (int) round(($points / $requirement) * 100)),
            'display_requirement' => $requirement,
            'is_max_rank' => false,
            'badge_initial' => 'B',
        ];
    }

    private function badgeInitial(string $rankName): string
    {
        $initial = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $rankName) ?: 'R', 0, 1));

        return $initial !== '' ? $initial : 'R';
    }

    public function create(string $name, int $pointsRequirements): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO ranks (name, points_requirements)
             VALUES (?, ?)'
        );
        $statement->bind_param('si', $name, $pointsRequirements);
        $statement->execute();
        $rankId = (int) $statement->insert_id;
        $statement->close();

        return $rankId;
    }

    public function exists(int $rankId): bool
    {
        if ($rankId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT 1
             FROM ranks
             WHERE rank_id = ?
             LIMIT 1'
        );
        $statement->bind_param('i', $rankId);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $exists;
    }

    public function nameExists(string $name, int $excludeRankId = 0): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM ranks
             WHERE name = ?
                AND (? <= 0 OR rank_id <> ?)
             LIMIT 1'
        );
        $statement->bind_param('sii', $name, $excludeRankId, $excludeRankId);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $exists;
    }

    public function pointsRequirementExists(int $pointsRequirements, int $excludeRankId = 0): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM ranks
             WHERE points_requirements = ?
                AND (? <= 0 OR rank_id <> ?)
             LIMIT 1'
        );
        $statement->bind_param('iii', $pointsRequirements, $excludeRankId, $excludeRankId);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();

        return $exists;
    }

    public function update(int $rankId, string $name, int $pointsRequirements): bool
    {
        if ($rankId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE ranks
             SET name = ?, points_requirements = ?
             WHERE rank_id = ?'
        );
        $statement->bind_param('sii', $name, $pointsRequirements, $rankId);
        $statement->execute();
        $updated = $statement->affected_rows >= 0;
        $statement->close();

        return $updated;
    }

    public function delete(int $rankId): bool
    {
        if ($rankId <= 0) {
            return false;
        }

        $statement = $this->connection->prepare(
            'DELETE FROM ranks
             WHERE rank_id = ?'
        );
        $statement->bind_param('i', $rankId);
        $statement->execute();
        $deleted = $statement->affected_rows > 0;
        $statement->close();

        return $deleted;
    }
}
