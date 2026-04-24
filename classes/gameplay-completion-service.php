<?php

final class GameplayCompletionService
{
    public function __construct(
        private mysqli $connection,
        private UserChallengeRepository $userChallenges,
        private ChallengeRepository $challenges,
        private ActivityLogRepository $activityLogs
    ) {
    }

    /**
     * @return array{
     *     user_challenge_id:int,
     *     challenge_id:int,
     *     challenge_name:string,
     *     difficulty:string,
     *     points:int,
     *     challenge_points:int,
     *     awarded_once:bool,
     *     started_at:string,
     *     completed_at:string,
     *     duration_seconds:int
     * }
     */
    public function complete(int $userId, int $userChallengeId, int $challengeId): array
    {
        if ($userId <= 0 || $userChallengeId <= 0 || $challengeId <= 0) {
            throw new InvalidArgumentException('Invalid challenge completion request.');
        }

        $ongoingRun = $this->userChallenges->findOwnedOngoing($userChallengeId, $userId, $challengeId);

        if ($ongoingRun === null) {
            throw new RuntimeException('No active challenge run was found to complete.');
        }

        $challenge = $this->challenges->findCreatedChallenge($challengeId);

        if ($challenge === null) {
            $this->userChallenges->deleteOngoingForUser($userChallengeId, $userId);
            throw new RuntimeException('This challenge is no longer available. Your run was ended.');
        }

        if ((int) ($challenge['status'] ?? 0) !== 1) {
            $this->userChallenges->deleteOngoingForUser($userChallengeId, $userId);
            throw new RuntimeException('This challenge is not available publicly right now. Your run was ended.');
        }

        $alreadyRewarded = $this->userChallenges->hasCompletedRecordForChallenge($userId, $challengeId, $userChallengeId);
        $challengePoints = (int) ($challenge['points'] ?? 0);
        $awardedPoints = $alreadyRewarded ? 0 : $challengePoints;

        try {
            $this->connection->begin_transaction();

            $completedRun = $this->userChallenges->markCompleted($userChallengeId, $userId, $challengeId);

            if ($completedRun === null) {
                throw new RuntimeException('Unable to complete this challenge run.');
            }

            $this->activityLogs->create(
                $userId,
                'challenge',
                'Completed challenge "' . (string) $challenge['name'] . '".'
            );

            $this->connection->commit();
        } catch (Throwable $err) {
            try {
                $this->connection->rollback();
            } catch (Throwable) {
            }

            throw $err;
        }

        $startedAt = new DateTimeImmutable((string) $completedRun['started_at']);
        $completedAt = new DateTimeImmutable((string) $completedRun['completed_at']);

        return [
            'user_challenge_id' => (int) $completedRun['uc_id'],
            'challenge_id' => (int) $challenge['challenge_id'],
            'challenge_name' => (string) $challenge['name'],
            'difficulty' => ucfirst(strtolower((string) ($challenge['difficulty_name'] ?? 'Easy'))),
            'points' => $awardedPoints,
            'challenge_points' => $challengePoints,
            'awarded_once' => !$alreadyRewarded,
            'started_at' => $startedAt->format(DATE_ATOM),
            'completed_at' => $completedAt->format(DATE_ATOM),
            'duration_seconds' => max(0, $completedAt->getTimestamp() - $startedAt->getTimestamp()),
        ];
    }
}
