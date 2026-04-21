<?php
require_once __DIR__ . '/../classes/challenge-catalog.php';

$username = $_SESSION['username'] ?? 'Pixel Rookie';
$currentYear = (int) date('Y');
$yearStart = new DateTimeImmutable($currentYear . '-01-01');
$today = new DateTimeImmutable('today');
$currentStudentId = (int) ($_SESSION['user_id'] ?? 0);
$currentSeason = 'Season 01: Arcade Dawn';
$seasonEndsAt = new DateTimeImmutable($currentYear . '-06-30');
$seasonDaysLeft = max(0, (int) $today->diff($seasonEndsAt)->format('%r%a'));
$currentRankPoints = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->totalCompletedPointsForUser($currentStudentId)
    : 0;
$rankRequirementPoints = 500;
$rankProgressPercent = min(100, (int) round(($currentRankPoints / $rankRequirementPoints) * 100));
$analyticsTrackedDays = 235;
$analyticsEndDate = $yearStart->modify('+' . ($analyticsTrackedDays - 1) . ' days');
$completedCountsByDate = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->completedCountsByDate($currentStudentId, $yearStart, $analyticsEndDate)
    : [];
$attemptHistoryRows = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->listAttemptHistory($currentStudentId, 50)
    : [];
$completedChallengeCount = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->countCompletedForUser($currentStudentId)
    : 0;
$activityDays = [];
$solvedChallengeRows = [];

for ($dayIndex = 0; $dayIndex < $analyticsTrackedDays; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $solves = $completedCountsByDate[$dateKey] ?? 0;
    $activityDays[] = [
        'date' => $date,
        'solves' => $solves,
        'level' => min($solves, 5),
    ];
}

foreach ($attemptHistoryRows as $attemptRow) {
    if (!empty($attemptRow['completed_at'])) {
        $completedAt = new DateTimeImmutable((string) $attemptRow['completed_at']);
        $solvedChallengeRows[] = [
            'date' => $completedAt,
            'title' => (string) $attemptRow['name'],
            'result' => 'Completed',
            'points' => (int) ($attemptRow['points'] ?? 0),
        ];
    }
}

$createdChallengeRows = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->listLatestCreated(6)
    : [];
$ongoingChallengeLookup = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->ongoingChallengeIdLookup($currentStudentId)
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
        ];
    }
}

$firstRecommendedChallenge = array_values($recommendedChallenges)[0] ?? null;
$startChallengeHref = $firstRecommendedChallenge['href'] ?? './?c=challenges';
$latestSolvedChallenges = array_slice($solvedChallengeRows, 0, 5);
$joinRoomAvatarInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $username)) ?: 'PR', 0, 2));
$joinRoomAvatarColor = (string) ($_SESSION['avatar_color'] ?? 'yellow');
$joinRoomAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
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
                        Welcome <span class="home-welcome-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span>,
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-arcade-ink/70">
                        Keep your streak alive, climb the ranks, and clear recommended CSS challenges one design at a time.
                    </p>
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
                <article class="ranking-card rounded-[24px] border-4 border-arcade-ink bg-white/90 p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <div class="rank-highlight rounded-[20px] border-2 border-arcade-ink/10 bg-arcade-yellow/20 p-3">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="rank-eyebrow font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Ranking</p>
                                <h2 class="rank-title mt-3 text-3xl font-bold text-arcade-orange">Beginner</h2>
                                <p class="rank-caption mt-1 text-sm leading-6 text-arcade-ink/65">Current rank</p>
                            </div>
                            <div class="rank-badge grid h-24 w-24 place-items-center rounded-[24px] border-4 border-arcade-ink bg-arcade-yellow shadow-[7px_7px_0_#26190f]" aria-label="Beginner rank badge">
                                <span class="font-arcade text-2xl text-arcade-orange">B</span>
                            </div>
                        </div>
                        <div class="mt-4 rounded-2xl border-4 border-arcade-ink bg-arcade-cream p-3 shadow-[4px_4px_0_rgba(38,25,15,0.22)]">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-arcade-orange">Rank Progress</p>
                                <p class="text-sm font-extrabold text-arcade-ink"><?= (int) $currentRankPoints ?> / <?= (int) $rankRequirementPoints ?> points</p>
                            </div>
                            <div class="mt-2 h-4 overflow-hidden rounded-full border-2 border-arcade-ink bg-white">
                                <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $rankProgressPercent ?>%;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="ranking-season-grid mt-4 grid gap-2 sm:grid-cols-2">
                        <div class="season-badge rounded-2xl border-4 border-arcade-ink bg-arcade-yellow p-3 shadow-[5px_5px_0_#26190f]">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/60">Current Season</p>
                            <p class="mt-1 text-base font-extrabold"><?= htmlspecialchars($currentSeason, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="season-badge rounded-2xl border-4 border-arcade-ink bg-arcade-cyan p-3 shadow-[5px_5px_0_#26190f]">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/60">Season Ends</p>
                            <p class="mt-1 text-base font-extrabold"><?= (int) $seasonDaysLeft ?> days left</p>
                        </div>
                    </div>

                    <div class="ranking-stat-grid mt-4 grid gap-2 sm:grid-cols-2">
                        <div class="ranking-stat-card rounded-xl border-2 border-arcade-ink/10 bg-arcade-cream p-3">
                            <div class="flex items-center gap-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-arcade-yellow text-arcade-ink" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" focusable="false">
                                        <path fill="currentColor" d="M3 2h10v2h-1v1.5A4 4 0 0 1 8.8 9.4V12H11v2H5v-2h2.2V9.4A4 4 0 0 1 4 5.5V4H3V2Zm3 2v1.5a2 2 0 0 0 4 0V4H6Z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Leaderboard</p>
                                    <p class="mt-0.5 text-xl font-bold">#128</p>
                                </div>
                            </div>
                        </div>
                        <div class="ranking-stat-card rounded-xl border-2 border-arcade-ink/10 bg-arcade-cream p-3">
                            <div class="flex items-center gap-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-arcade-cyan text-arcade-ink" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" focusable="false">
                                        <path fill="currentColor" d="M6.5 11.2 3.7 8.4l1.1-1.1 1.7 1.7 4.7-4.7 1.1 1.1-5.8 5.8ZM2 2h12v12H2V2Zm2 2v8h8V4H4Z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Solved</p>
                                    <p class="mt-0.5 text-xl font-bold"><?= (int) $completedChallengeCount ?></p>
                                </div>
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
                            <p class="text-sm font-bold text-arcade-ink/60"><?= (int) $currentYear ?> Activity</p>
                            <a href="./?c=player-analytics" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/50">Open Analytics</a>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto pb-2">
                        <div class="home-activity-grid" aria-label="<?= (int) $currentYear ?> challenge solving chart with <?= (int) $analyticsTrackedDays ?> days">
                            <?php foreach ($activityDays as $activityDay) : ?>
                                <span
                                    class="home-activity-cell home-activity-cell--<?= (int) $activityDay['level'] ?>"
                                    tabindex="0"
                                    role="button"
                                    aria-label="<?= htmlspecialchars($activityDay['date']->format('M j, Y'), ENT_QUOTES, 'UTF-8') ?>: <?= (int) $activityDay['solves'] ?> solved challenges"
                                    data-tooltip="<?= htmlspecialchars($activityDay['date']->format('M j, Y'), ENT_QUOTES, 'UTF-8') ?>: <?= (int) $activityDay['solves'] ?> solved"
                                    title="<?= htmlspecialchars($activityDay['date']->format('M j, Y'), ENT_QUOTES, 'UTF-8') ?>: <?= (int) $activityDay['solves'] ?> solved"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                        <span>Less</span>
                        <span class="home-activity-cell home-activity-cell--0"></span>
                        <span class="home-activity-cell home-activity-cell--1"></span>
                        <span class="home-activity-cell home-activity-cell--3"></span>
                        <span class="home-activity-cell home-activity-cell--5"></span>
                        <span>More</span>
                    </div>

                    <div class="solved-challenges-panel mt-5 border-t-2 border-arcade-ink/10 pt-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-bold">Solved Challenges</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="./?c=player-analytics&status=completed" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/50">Review All</a>
                            </div>
                        </div>

                        <div class="mt-3 overflow-hidden rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/70">
                            <?php if ($latestSolvedChallenges === []) : ?>
                                <p class="px-3 py-4 text-sm font-bold text-arcade-ink/55">No completed challenges yet.</p>
                            <?php else : ?>
                                <?php foreach ($latestSolvedChallenges as $solvedChallenge) : ?>
                                    <article class="solved-row flex flex-col gap-1 border-b border-arcade-ink/10 px-3 py-2 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-bold"><?= htmlspecialchars($solvedChallenge['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <p class="text-xs font-semibold text-arcade-ink/55"><?= htmlspecialchars($solvedChallenge['date']->format('M j, Y'), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($solvedChallenge['result'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                        <span class="text-xs font-bold text-arcade-orange">+<?= (int) $solvedChallenge['points'] ?> pts</span>
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
                                    </div>
                                    <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                                        <span>By <?= htmlspecialchars($challenge['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="mt-1.5 text-sm leading-6 text-arcade-ink/68"><?= htmlspecialchars($challenge['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <a href="<?= htmlspecialchars($challenge['href'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex shrink-0 justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
                                    Train
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</main>

<div id="home-activity-tooltip" class="home-activity-tooltip" role="status" hidden></div>

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

                        <label class="join-room-field join-room-name-field min-w-0 flex-1" for="join-room-player-name">
                            <span>Player name confirmation</span>
                            <span class="join-room-name-control">
                                <input id="join-room-player-name" name="player_name" type="text" autocomplete="name" value="<?= htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8') ?>" required>
                                <span class="join-room-edit-icon" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" focusable="false">
                                        <path fill="currentColor" d="M11.7 1.6 14.4 4.3 5.9 12.8 3 13.5l.7-2.9 8-9Zm-1.1 2.2-5.5 5.5 1.6 1.6 5.5-5.5-1.6-1.6Z" />
                                    </svg>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <label class="join-room-field mt-4" for="join-room-code">
                    <span>Room code</span>
                    <input id="join-room-code" name="room_code" type="text" inputmode="text" autocomplete="off" placeholder="PIXEL-123" required>
                </label>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Join Room</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const tooltip = document.getElementById('home-activity-tooltip');
    const cells = Array.from(document.querySelectorAll('.home-activity-cell[data-tooltip]'));

    if (!tooltip || cells.length === 0) {
        return;
    }

    const showTooltip = (cell) => {
        const text = cell.dataset.tooltip || '';
        if (text === '') {
            return;
        }

        const rect = cell.getBoundingClientRect();
        tooltip.textContent = text;
        tooltip.hidden = false;
        tooltip.style.left = `${Math.min(window.innerWidth - 12, Math.max(12, rect.left + rect.width / 2))}px`;
        tooltip.style.top = `${Math.max(12, rect.top - 10)}px`;
    };

    const hideTooltip = () => {
        tooltip.hidden = true;
    };

    cells.forEach((cell) => {
        cell.addEventListener('mouseenter', () => showTooltip(cell));
        cell.addEventListener('focus', () => showTooltip(cell));
        cell.addEventListener('click', () => showTooltip(cell));
        cell.addEventListener('mouseleave', hideTooltip);
        cell.addEventListener('blur', hideTooltip);
    });

    window.addEventListener('scroll', hideTooltip, { passive: true });
    window.addEventListener('resize', hideTooltip);
})();
</script>
