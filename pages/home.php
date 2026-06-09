<?php
require_once __DIR__ . '/../classes/challenge-catalog.php';

$username = $_SESSION['username'] ?? 'Pixel Rookie';
$playerDisplayName = trim((string) ($_SESSION['firstname'] ?? '') . ' ' . (string) ($_SESSION['lastname'] ?? ''));
$playerDisplayName = $playerDisplayName !== '' ? $playerDisplayName : (string) $username;
$today = new DateTimeImmutable('today');
$currentStudentId = (int) ($_SESSION['user_id'] ?? 0);
$activeSeason = $seasonRepository instanceof SeasonRepository ? $seasonRepository->findActive() : null;
$currentSeason = $activeSeason !== null ? (string) ($activeSeason['name'] ?? 'Current Season') : 'No active season';
$seasonEndsLabel = 'Not scheduled';

if ($activeSeason !== null && trim((string) ($activeSeason['end_date'] ?? '')) !== '') {
    try {
        $seasonEndsAt = new DateTimeImmutable((string) $activeSeason['end_date']);
        $now = new DateTimeImmutable('now');
        $secondsLeft = max(0, $seasonEndsAt->getTimestamp() - $now->getTimestamp());
        $daysLeft = intdiv($secondsLeft, 86400);
        $hoursLeft = intdiv($secondsLeft % 86400, 3600);

        $seasonEndsLabel = $daysLeft > 0
            ? $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') . ' left'
            : ($hoursLeft > 0
                ? $hoursLeft . ' hour' . ($hoursLeft === 1 ? '' : 's') . ' left'
                : 'Ends today');
    } catch (Throwable) {
        $seasonEndsLabel = 'Not scheduled';
    }
}
$currentRankPoints = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->totalCompletedPointsForUser($currentStudentId)
    : 0;
if ($userRepository instanceof UserRepository) {
    $currentRankPoints = $userRepository->totalPlayerProgressPointsForUser($currentStudentId);
}
$leaderboardRank = $userRepository instanceof UserRepository
    ? $userRepository->leaderboardRankForUser($currentStudentId)
    : null;
$rankProgress = $rankRepository instanceof RankRepository
    ? $rankRepository->progressForPoints($currentRankPoints)
    : [
        'current_name' => 'Beginner',
        'display_requirement' => 500,
        'progress_percent' => min(100, (int) round(($currentRankPoints / 500) * 100)),
        'badge_initial' => 'B',
        'next_name' => 'Next Rank',
        'is_max_rank' => false,
    ];
$rankRequirementPoints = (int) ($rankProgress['display_requirement'] ?? 500);
$rankProgressPercent = (int) ($rankProgress['progress_percent'] ?? 0);
$currentRankName = (string) ($rankProgress['current_name'] ?? 'Beginner');
$currentRankInitial = (string) ($rankProgress['badge_initial'] ?? 'B');
$nextRankName = (string) ($rankProgress['next_name'] ?? '');
$isMaxRank = (bool) ($rankProgress['is_max_rank'] ?? false);
$analyticsTrackedDays = 30;
$analyticsEndDate = $today;
$analyticsStartDate = $analyticsEndDate->modify('-' . ($analyticsTrackedDays - 1) . ' days');
$completedCountsByDate = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->completedCountsByDate($currentStudentId, $analyticsStartDate, $analyticsEndDate)
    : [];
$attemptHistoryRows = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->listAttemptHistory($currentStudentId, 5)
    : [];
$completedChallengeCount = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->countCompletedForUser($currentStudentId)
    : 0;
$activityDays = [];
$latestSubmissionRows = [];
$activityChartLabels = [];
$activityChartValues = [];

for ($dayIndex = 0; $dayIndex < $analyticsTrackedDays; $dayIndex++) {
    $date = $analyticsStartDate->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $solves = $completedCountsByDate[$dateKey] ?? 0;
    $activityDays[] = [
        'date' => $date,
        'solves' => $solves,
        'level' => min($solves, 5),
    ];
    $activityChartLabels[] = $date->format('M j');
    $activityChartValues[] = $solves;
}

foreach ($attemptHistoryRows as $attemptRow) {
    $startedAt = new DateTimeImmutable((string) $attemptRow['started_at']);
    $completedAt = !empty($attemptRow['completed_at'])
        ? new DateTimeImmutable((string) $attemptRow['completed_at'])
        : null;
    $isRoomAttempt = (int) ($attemptRow['room_id'] ?? 0) > 0;
    $isPvpAttempt = (int) ($attemptRow['pvp_id'] ?? 0) > 0;
    $isStrictRoomAttempt = $isRoomAttempt && (int) ($attemptRow['room_strict_mode'] ?? 0) === 1;
    $strictModeScore = max(0, min(100, (int) ($attemptRow['strict_mode_score'] ?? 0)));
    $attemptStatus = (string) ($attemptRow['attempt_status'] ?? '');
    $modeLabel = $isPvpAttempt ? '1v1' : ($isRoomAttempt ? 'Room' : 'Solo');
    $resultLabel = match ($attemptStatus) {
        'pvp_win' => 'Win',
        'pvp_loss' => 'Loss',
        'gave_up' => 'Failed',
        default => ($completedAt instanceof DateTimeImmutable
            ? 'Completed'
            : ($isRoomAttempt ? 'Failed' : 'Ongoing')),
    };

    if ($isStrictRoomAttempt && $resultLabel !== 'Ongoing') {
        $resultLabel = $strictModeScore . '%';
    }

    $latestSubmissionRows[] = [
        'date' => $completedAt ?? $startedAt,
        'title' => (string) $attemptRow['name'],
        'result' => $resultLabel,
        'mode' => $modeLabel,
        'points' => (int) ($attemptRow['awarded_points'] ?? 0),
    ];
}

$createdChallengeRows = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->listLatestPublicCreated(6)
    : [];
$ongoingChallengeLookup = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->ongoingChallengeIdLookup($currentStudentId)
    : [];
$completedChallengeLookup = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->completedChallengeIdLookup($currentStudentId)
    : [];
$recommendedChallenges = [];

foreach ($createdChallengeRows as $challengeRow) {
    $difficulty = ucfirst(strtolower((string) ($challengeRow['difficulty_name'] ?? 'Easy')));
    $challengeId = (int) $challengeRow['challenge_id'];
    $recommendedChallenges[] = [
        'id' => $challengeId,
        'title' => (string) $challengeRow['name'],
        'level' => $difficulty,
        'levelClass' => 'challenge-difficulty--' . strtolower($difficulty),
        'reward' => '+' . (int) ($challengeRow['points'] ?? 0) . ' pts',
        'author' => (string) ($challengeRow['author'] ?? 'Teacher'),
        'description' => (string) $challengeRow['instruction'],
        'href' => './?c=challenge&id=' . $challengeId,
        'isOngoing' => isset($ongoingChallengeLookup[$challengeId]),
        'isCompleted' => isset($completedChallengeLookup[$challengeId]),
    ];
}

if ($recommendedChallenges === []) {
    foreach (ChallengeCatalog::all() as $catalogChallenge) {
        $recommendedChallenges[] = [
            'title' => $catalogChallenge['title'],
            'level' => $catalogChallenge['level'],
            'levelClass' => $catalogChallenge['levelClass'],
            'reward' => $catalogChallenge['reward'],
            'author' => $catalogChallenge['author'],
            'description' => $catalogChallenge['description'],
            'href' => './?c=challenge&slug=' . urlencode((string) $catalogChallenge['slug']),
            'isOngoing' => false,
            'isCompleted' => false,
        ];
    }
}

$firstRecommendedChallenge = array_values($recommendedChallenges)[0] ?? null;
$startChallengeHref = $firstRecommendedChallenge['href'] ?? './?c=challenges';
$latestSubmissions = $latestSubmissionRows;
$joinRoomAvatarInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $username)) ?: 'PR', 0, 2));
$joinRoomAvatarColor = (string) ($_SESSION['avatar_color'] ?? 'yellow');
$joinRoomAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$roomNotice = trim((string) ($_GET['room_notice'] ?? ''));
$pvpNotice = trim((string) ($_GET['pvp_notice'] ?? ''));
$pvpDurationSeconds = max(0, (int) ($_GET['pvp_duration'] ?? 0));
$formatHomeDuration = static function (int $seconds): string {
    $seconds = max(0, $seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;

    if ($hours > 0) {
        return sprintf('%dh %02dm %02ds', $hours, $minutes, $remainingSeconds);
    }

    if ($minutes > 0) {
        return sprintf('%dm %ds', $minutes, $remainingSeconds);
    }

    return sprintf('%ds', $remainingSeconds);
};
$pvpResultTitle = $pvpNotice === 'win' ? 'Victory' : 'Defeat';
$pvpResultMessage = $pvpNotice === 'win'
    ? 'Your opponent gave up. You won the 1v1 match.'
    : 'You gave up the 1v1 match.';
?>

<main class="home-dashboard relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_14%,rgba(255,209,102,0.28),transparent_22%),radial-gradient(circle_at_88%_18%,rgba(76,201,240,0.2),transparent_24%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_36%)]"></div>
    <div class="home-dashboard__grid absolute inset-0"></div>

    <section class="container relative">
        <div class="mb-5 rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Player Dashboard</p>
            <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="home-welcome-title text-3xl font-bold leading-tight md:text-5xl">
                        Hello, <span class="home-welcome-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>
                    </h1>
                </div>
                <div class="home-hero-actions flex flex-nowrap items-center gap-2 py-1">
                    <a href="<?= htmlspecialchars($startChallengeHref, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-3 py-2 text-xs font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white sm:px-4 sm:text-sm">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M4 2.5v11l9-5.5-9-5.5Z" />
                        </svg>
                        <span>Start Challenge</span>
                    </a>
                    <a href="./?c=versus" class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-3 py-2 text-xs font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white sm:px-4 sm:text-sm">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M5 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm6 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2 13c0-2.2 1.4-4 3-4s3 1.8 3 4H2Zm6 0c0-2.2 1.4-4 3-4s3 1.8 3 4H8Z" />
                        </svg>
                        <span>1v1</span>
                    </a>
                    <button type="button" class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-xs font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow sm:px-4 sm:text-sm" data-bs-toggle="modal" data-bs-target="#join-room-modal">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M2 3h12v10H2V3Zm2 2v2h2V5H4Zm3 0v2h2V5H7Zm3 0v2h2V5h-2ZM4 9v2h2V9H4Zm3 0v2h2V9H7Zm3 0v2h2V9h-2Z" />
                        </svg>
                        <span>Join Room</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[0.82fr_1.18fr]">
            <section class="grid gap-5">
                <article class="ranking-card relative rounded-[24px] border-4 border-arcade-ink bg-white/90 p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <a href="./?c=ranks" class="absolute right-4 top-4 z-[2] grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-sm font-black text-arcade-ink no-underline shadow-[4px_4px_0_#26190f] transition hover:-translate-y-1 hover:bg-arcade-cyan md:right-5 md:top-5" aria-label="View rank requirements">?</a>
                    <div class="rank-highlight rounded-[20px] border-2 border-arcade-ink/10 bg-arcade-yellow/20 p-3">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="rank-eyebrow font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Ranking</p>
                                <h2 class="rank-title mt-3 text-3xl font-bold text-arcade-orange"><?= htmlspecialchars($currentRankName, ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="rank-caption mt-1 text-sm leading-6 text-arcade-ink/65">Current rank</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <div class="rank-badge grid h-24 w-24 place-items-center rounded-[24px] border-4 border-arcade-ink bg-arcade-yellow shadow-[7px_7px_0_#26190f]" aria-label="<?= htmlspecialchars($currentRankName, ENT_QUOTES, 'UTF-8') ?> rank badge">
                                    <span class="font-arcade text-2xl text-arcade-orange"><?= htmlspecialchars($currentRankInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 rounded-2xl border-4 border-arcade-ink bg-arcade-cream p-3 shadow-[4px_4px_0_rgba(38,25,15,0.22)]">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-arcade-orange">Rank Progress</p>
                                <p class="text-sm font-extrabold text-arcade-ink">
                                    <?= (int) $currentRankPoints ?><?= $isMaxRank ? ' points' : ' / ' . (int) $rankRequirementPoints . ' points' ?>
                                </p>
                            </div>
                            <div class="mt-2 h-4 overflow-hidden rounded-full border-2 border-arcade-ink bg-white">
                                <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $rankProgressPercent ?>%;"></span>
                            </div>
                            <?php if (!$isMaxRank && $nextRankName !== '') : ?>
                                <p class="mt-2 text-xs font-bold uppercase tracking-[0.12em] text-arcade-ink/55">Next: <?= htmlspecialchars($nextRankName, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="ranking-season-grid mt-4 grid gap-2 sm:grid-cols-2">
                        <div class="season-badge rounded-2xl border-4 border-arcade-ink bg-arcade-yellow p-3 shadow-[5px_5px_0_#26190f]">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/60">Current Season</p>
                            <p class="mt-1 text-base font-extrabold"><?= htmlspecialchars($currentSeason, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="season-badge rounded-2xl border-4 border-arcade-ink bg-arcade-cyan p-3 shadow-[5px_5px_0_#26190f]">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/60">Season Ends</p>
                            <p class="mt-1 text-base font-extrabold"><?= htmlspecialchars($seasonEndsLabel, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <div class="ranking-stat-grid mt-4 grid gap-2">
                        <div class="ranking-stat-card rounded-xl border-2 border-arcade-ink/10 bg-arcade-cream p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-arcade-yellow text-arcade-ink" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" focusable="false">
                                        <path fill="currentColor" d="M3 2h10v2h-1v1.5A4 4 0 0 1 8.8 9.4V12H11v2H5v-2h2.2V9.4A4 4 0 0 1 4 5.5V4H3V2Zm3 2v1.5a2 2 0 0 0 4 0V4H6Z" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Leaderboard</p>
                                    <p class="mt-0.5 text-xl font-bold"><?= $leaderboardRank !== null ? (int) $leaderboardRank : 'N/A' ?></p>
                                </div>
                                </div>
                                <a href="./?c=leaderboards" class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink no-underline shadow-[3px_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" aria-label="Open leaderboards">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                        <path fill="currentColor" d="M5.5 3h5v2h2v2a3 3 0 0 1-3 3h-.2A3.7 3.7 0 0 1 8.8 11v1.5H11V14H5v-1.5h2.2V11a3.7 3.7 0 0 1-.5-1H6.5a3 3 0 0 1-3-3V5h2V3Zm5 3V4.5h-5V6a2.5 2.5 0 0 0 5 0ZM5.5 8.4A4 4 0 0 1 5.5 7V6.5h-1V7a2 2 0 0 0 1 1.7Zm5 0a2 2 0 0 0 1-1.7v-.5h-1V7a4 4 0 0 1 0 1.4Z" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-[24px] border-4 border-arcade-ink/10 bg-white/85 p-4 shadow-arcade md:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Player Analytics</p>
                            <h2 class="mt-3 text-xl font-bold">Challenge Solving</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold text-arcade-ink/60">Last 30 Days</p>
                            <a href="./?c=player-analytics" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/50">Open Analytics</a>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto pb-2">
                        <div class="home-chart-shell" aria-label="Challenge solving chart for the last <?= (int) $analyticsTrackedDays ?> days">
                            <div class="home-chart-stage">
                                <canvas id="home-analytics-chart" height="210"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="solved-challenges-panel mt-5 border-t-2 border-arcade-ink/10 pt-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-bold">Latest Submissions</h3>
                        </div>

                        <div class="mt-3 overflow-hidden rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/70">
                            <?php if ($latestSubmissions === []) : ?>
                                <p class="px-3 py-4 text-sm font-bold text-arcade-ink/55">No submissions yet.</p>
                            <?php else : ?>
                                <?php foreach ($latestSubmissions as $submissionRow) : ?>
                                    <article class="solved-row flex flex-col gap-1 border-b border-arcade-ink/10 px-3 py-2 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-bold"><?= htmlspecialchars($submissionRow['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-semibold text-arcade-ink/55">
                                                <span><?= htmlspecialchars($submissionRow['date']->format('M j, Y'), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($submissionRow['result'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="rounded-full <?= $submissionRow['mode'] === '1v1' ? 'bg-arcade-cyan/30' : ($submissionRow['mode'] === 'Room' ? 'bg-arcade-orange/20' : 'bg-arcade-mint/35') ?> px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-arcade-ink">
                                                    <?= htmlspecialchars($submissionRow['mode'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-arcade-orange">+<?= (int) $submissionRow['points'] ?> pts</span>
                                    </article>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </section>

            <section class="self-start rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold">Recommended Challenges</h2>
                        <p class="mt-1 text-sm leading-6 text-arcade-ink/65">Pick a CSS challenge and keep building your design-matching streak.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="./?c=challenges" class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-3 py-1.5 text-sm font-bold text-arcade-ink no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                            <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M3 2h10v12H3V2Zm2 2v2h6V4H5Zm0 4v2h3V8H5Zm5 0v2h1V8h-1Zm-5 4h6v-1H5v1Z" />
                            </svg>
                            <span>Challenges</span>
                        </a>
                    </div>
                </div>

                <div class="mt-4 grid gap-3">
                    <?php foreach ($recommendedChallenges as $challenge) : ?>
                        <article class="challenge-card rounded-[18px] border-2 border-arcade-ink/12 bg-white p-4 transition hover:-translate-y-1 hover:border-arcade-orange hover:shadow-[0_6px_0_rgba(38,25,15,0.18)]">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="challenge-difficulty <?= htmlspecialchars($challenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['level'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['reward'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($challenge['isOngoing'])) : ?>
                                            <span class="rounded-full border-2 border-arcade-ink bg-arcade-cyan px-3 py-1 text-xs font-bold text-arcade-ink">Ongoing</span>
                                        <?php endif; ?>
                                        <?php if (!empty($challenge['isCompleted'])) : ?>
                                            <span class="rounded-full border-2 border-arcade-ink bg-arcade-yellow px-3 py-1 text-xs font-bold text-arcade-ink">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                                        <span>By <?= htmlspecialchars($challenge['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="mt-1.5 text-sm leading-6 text-arcade-ink/68"><?= $tools->formatExcerpt($challenge['description']) ?></p>
                                </div>
                                <a href="<?= htmlspecialchars($challenge['href'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex shrink-0 justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
                                    <?= !empty($challenge['isCompleted']) ? 'Train Again' : 'Train' ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</main>

<?php if ($pvpNotice === 'win' || $pvpNotice === 'loss') : ?>
    <div class="modal fade pvp-result-modal" id="pvp-result-modal" tabindex="-1" aria-labelledby="pvp-result-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pvp-result-modal__content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel shadow-[8px_8px_0_#26190f]">
                <div class="modal-body p-5 md:p-6">
                    <?php if ($pvpNotice === 'win') : ?>
                        <div class="pvp-result-modal__confetti" aria-hidden="true">
                            <?php for ($confettiIndex = 0; $confettiIndex < 18; $confettiIndex++) : ?>
                                <span class="pvp-result-modal__confetti-piece pvp-result-modal__confetti-piece--<?= (int) (($confettiIndex % 4) + 1) ?>"></span>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <div class="pvp-result-modal__header">
                        <div class="pvp-result-modal__badge pvp-result-modal__badge--<?= htmlspecialchars($pvpNotice, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                            <?php if ($pvpNotice === 'win') : ?>
                                <svg class="h-7 w-7" viewBox="0 0 16 16" focusable="false">
                                    <path fill="currentColor" d="M3 2h10v2h-1v1.5A4 4 0 0 1 8.8 9.4V12H11v2H5v-2h2.2V9.4A4 4 0 0 1 4 5.5V4H3V2Zm3 2v1.5a2 2 0 0 0 4 0V4H6Z" />
                                </svg>
                            <?php else : ?>
                                <svg class="h-7 w-7" viewBox="0 0 16 16" focusable="false">
                                    <path fill="currentColor" d="M8 1.5a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13Zm0 3.1L4.7 8l.9.9L7.4 7v4h1.2V7l1.8 1.9.9-.9L8 4.6Z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="pvp-result-modal__copy">
                            <p class="pvp-result-modal__eyebrow <?= $pvpNotice === 'win' ? 'text-arcade-cyan' : 'text-arcade-coral' ?>">1v1 Result</p>
                            <h2 id="pvp-result-modal-title" class="pvp-result-modal__title"><?= htmlspecialchars($pvpResultTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="pvp-result-modal__message"><?= htmlspecialchars($pvpResultMessage, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <div class="pvp-result-modal__summary mt-5">
                        <article class="pvp-result-modal__stat">
                            <p class="pvp-result-modal__stat-label">Result</p>
                            <p class="pvp-result-modal__stat-value"><?= htmlspecialchars($pvpResultTitle, ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                        <article class="pvp-result-modal__stat">
                            <p class="pvp-result-modal__stat-label">Duration</p>
                            <p class="pvp-result-modal__stat-value"><?= htmlspecialchars($formatHomeDuration($pvpDurationSeconds), ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                    </div>

                    <div class="pvp-result-modal__footer mt-5">
                        <button type="button" class="pvp-result-modal__close rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const modalElement = document.getElementById('pvp-result-modal');
            if (modalElement && window.bootstrap?.Modal) {
                const modal = new window.bootstrap.Modal(modalElement);
                window.addEventListener('load', () => modal.show(), { once: true });
            }

            const params = new URLSearchParams(window.location.search);
            if (params.has('pvp_notice') || params.has('pvp_duration')) {
                params.delete('pvp_notice');
                params.delete('pvp_duration');
                const nextQuery = params.toString();
                const nextUrl = `${window.location.pathname}${nextQuery !== '' ? `?${nextQuery}` : ''}${window.location.hash}`;
                window.history.replaceState({}, '', nextUrl);
            }
        })();
    </script>
<?php endif; ?>

<div class="modal fade join-room-modal" id="join-room-modal" tabindex="-1" aria-labelledby="join-room-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
            <input type="hidden" name="c" value="room">

            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Join Room</p>
                    <h2 class="modal-title mt-2 text-2xl font-bold" id="join-room-modal-title">Enter room details</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-5 pb-5 pt-2">
                <div class="join-room-profile rounded-[22px] border-2 border-arcade-ink/10 bg-white/75 p-4">
                    <div class="flex items-center gap-3">
                        <div class="join-room-avatar join-room-avatar--<?= htmlspecialchars($joinRoomAvatarColor, ENT_QUOTES, 'UTF-8') ?> grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl border-4 border-arcade-ink shadow-[5px_5px_0_rgba(38,25,15,0.18)]" aria-label="Player avatar">
                            <?php if ($joinRoomAvatarUrl !== '') : ?>
                                <img src="<?= htmlspecialchars($joinRoomAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                            <?php else : ?>
                                <span class="font-arcade text-sm text-arcade-ink"><?= htmlspecialchars($joinRoomAvatarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="join-room-field join-room-name-field min-w-0 flex-1">
                            <span>Player</span>
                            <div class="join-room-name-control">
                                <span class="truncate font-bold text-arcade-ink"><?= htmlspecialchars($playerDisplayName, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <label class="join-room-field mt-4" for="join-room-code">
                    <span>Room code</span>
                    <input id="join-room-code" name="room_code" type="text" inputmode="text" autocomplete="off" placeholder="ROOM-0001" required>
                </label>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Join Room</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($roomNotice === 'ended_incomplete') : ?>
    <div class="modal fade" id="room-ended-modal" tabindex="-1" aria-labelledby="room-ended-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
                <div class="modal-header border-0 px-5 pb-2 pt-5">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-coral">Room Ended</p>
                        <h2 class="modal-title mt-2 text-2xl font-bold" id="room-ended-modal-title">The room was ended.</h2>
                    </div>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-5 pb-5 pt-2">
                    <p class="text-sm font-semibold leading-7 text-arcade-ink/75">Your challenge run was not completed.</p>
                    <div class="mt-5 flex justify-end">
                        <button type="button" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script>
(() => {
    <?php if ($roomNotice === 'ended_incomplete') : ?>
    const roomEndedModalElement = document.getElementById('room-ended-modal');
    if (roomEndedModalElement && window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(roomEndedModalElement).show();
    }
    <?php endif; ?>

    const canvas = document.getElementById('home-analytics-chart');

    if (!canvas || typeof window.Chart === 'undefined') {
        return;
    }

    const labels = <?= json_encode($activityChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const values = <?= json_encode($activityChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    const gradient = context.createLinearGradient(0, 0, 0, canvas.height || 210);
    gradient.addColorStop(0, isDarkMode ? 'rgba(255, 140, 66, 0.52)' : 'rgba(255, 140, 66, 0.4)');
    gradient.addColorStop(0.55, isDarkMode ? 'rgba(255, 209, 102, 0.24)' : 'rgba(255, 209, 102, 0.18)');
    gradient.addColorStop(1, 'rgba(255, 209, 102, 0)');

    new window.Chart(context, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Solved challenges',
                data: values,
                fill: true,
                backgroundColor: gradient,
                borderColor: '#ff8c42',
                borderWidth: 3,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBorderWidth: 2,
                pointHoverBackgroundColor: '#ffd166',
                pointHoverBorderColor: '#26190f',
                tension: 0.32,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#1f160f' : '#fffdf6',
                    titleColor: isDarkMode ? '#fff7e8' : '#26190f',
                    bodyColor: isDarkMode ? '#fff7e8' : '#26190f',
                    borderColor: '#26190f',
                    borderWidth: 2,
                    padding: 12,
                    displayColors: false,
                    titleFont: {
                        weight: '800',
                    },
                    bodyFont: {
                        weight: '700',
                    },
                    callbacks: {
                        title(items) {
                            return items[0]?.label || '';
                        },
                        label(item) {
                            return `${item.raw || 0} solved challenge${item.raw === 1 ? '' : 's'}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        color: isDarkMode ? 'rgba(255,247,232,0.7)' : 'rgba(38,25,15,0.58)',
                        autoSkip: true,
                        maxTicksLimit: 10,
                        font: {
                            weight: '700',
                        },
                    },
                    grid: {
                        display: false,
                    },
                    border: {
                        color: isDarkMode ? 'rgba(255,247,232,0.12)' : 'rgba(38,25,15,0.12)',
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: isDarkMode ? 'rgba(255,247,232,0.7)' : 'rgba(38,25,15,0.58)',
                        font: {
                            weight: '700',
                        },
                    },
                    grid: {
                        color: isDarkMode ? 'rgba(255,247,232,0.08)' : 'rgba(38,25,15,0.08)',
                    },
                    border: {
                        color: isDarkMode ? 'rgba(255,247,232,0.12)' : 'rgba(38,25,15,0.12)',
                    },
                },
            },
        },
    });
})();
</script>
