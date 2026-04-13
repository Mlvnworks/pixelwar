<?php
require_once __DIR__ . '/../classes/challenge-catalog.php';

$username = $_SESSION['username'] ?? 'Pixel Rookie';
$currentYear = (int) date('Y');
$yearStart = new DateTimeImmutable($currentYear . '-01-01');
$today = new DateTimeImmutable('today');
$currentSeason = 'Season 01: Arcade Dawn';
$seasonEndsAt = new DateTimeImmutable($currentYear . '-06-30');
$seasonDaysLeft = max(0, (int) $today->diff($seasonEndsAt)->format('%r%a'));
$currentRankPoints = 340;
$rankRequirementPoints = 500;
$rankProgressPercent = min(100, (int) round(($currentRankPoints / $rankRequirementPoints) * 100));
$solvePattern = [0, 1, 0, 2, 3, 0, 1, 4, 2, 0, 0, 1, 3, 4, 1, 0, 2, 2, 5, 1, 0, 3, 4, 0, 1, 2, 5, 3, 0, 1, 4, 2, 0, 3, 5];
$challengeNames = ['Button Border Basics', 'Card Shadow Match', 'Hero Text Alignment', 'Badge Color Tune', 'Spacing Sprint', 'Selector Stack', 'CTA Polish', 'Panel Radius Run'];
$activityDays = [];
$solvedChallengeRows = [];

for ($dayIndex = 0; $dayIndex < 235; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $solves = $solvePattern[$dayIndex % count($solvePattern)];
    $activityDays[] = [
        'date' => $date,
        'solves' => $solves,
        'level' => min($solves, 5),
    ];

    for ($solveIndex = 0; $solveIndex < $solves; $solveIndex++) {
        $challengeName = $challengeNames[($dayIndex + $solveIndex) % count($challengeNames)];
        $solvedChallengeRows[] = [
            'date' => $date,
            'title' => $challengeName,
            'result' => 'Completed',
            'points' => 20 + (($dayIndex + $solveIndex) % 5) * 10,
        ];
    }
}

$recommendedChallenges = ChallengeCatalog::all();
?>

<main class="home-dashboard relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_14%,rgba(255,209,102,0.28),transparent_22%),radial-gradient(circle_at_88%_18%,rgba(76,201,240,0.2),transparent_24%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_36%)]"></div>
    <div class="home-dashboard__grid absolute inset-0"></div>

    <section class="container relative">
        <div class="mb-5 rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Player Dashboard</p>
            <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold leading-tight md:text-5xl">
                        Welcome <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>,
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-arcade-ink/70">
                        Keep your streak alive, climb the ranks, and clear recommended CSS challenges one design at a time.
                    </p>
                </div>
                <div class="flex flex-nowrap items-center gap-2 py-1">
                    <a href="./?c=pixelwar&intro=1" class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-3 py-2 text-xs font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white sm:px-4 sm:text-sm">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M4 2.5v11l9-5.5-9-5.5Z" />
                        </svg>
                        <span>Start Challenge</span>
                    </a>
                    <a href="./?c=pixelwar&intro=1&mode=1v1" class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-3 py-2 text-xs font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white sm:px-4 sm:text-sm">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M5 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm6 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2 13c0-2.2 1.4-4 3-4s3 1.8 3 4H2Zm6 0c0-2.2 1.4-4 3-4s3 1.8 3 4H8Z" />
                        </svg>
                        <span>1v1</span>
                    </a>
                    <a href="./?c=pixelwar&intro=1&mode=room" class="inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-xs font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow sm:px-4 sm:text-sm">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M2 3h12v10H2V3Zm2 2v2h2V5H4Zm3 0v2h2V5H7Zm3 0v2h2V5h-2ZM4 9v2h2V9H4Zm3 0v2h2V9H7Zm3 0v2h2V9h-2Z" />
                        </svg>
                        <span>Join Room</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[0.82fr_1.18fr]">
            <section class="grid gap-5">
                <article class="ranking-card rounded-[24px] border-4 border-arcade-ink bg-white/90 p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <div class="rank-highlight rounded-[20px] border-2 border-arcade-ink/10 bg-arcade-yellow/20 p-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Ranking</p>
                            <h2 class="mt-3 text-3xl font-bold text-arcade-orange">Beginner</h2>
                            <p class="mt-1 text-sm leading-6 text-arcade-ink/65">Current rank</p>
                        </div>
                        <div class="rank-badge grid h-24 w-24 place-items-center rounded-[24px] border-4 border-arcade-ink bg-arcade-yellow shadow-[7px_7px_0_#26190f]" aria-label="Beginner rank badge">
                            <span class="font-arcade text-2xl text-arcade-orange">B</span>
                        </div>
                    </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        <div class="season-badge rounded-2xl border-4 border-arcade-ink bg-arcade-yellow p-3 shadow-[5px_5px_0_#26190f]">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/60">Current Season</p>
                            <p class="mt-1 text-base font-extrabold"><?= htmlspecialchars($currentSeason, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="season-badge rounded-2xl border-4 border-arcade-ink bg-arcade-cyan p-3 shadow-[5px_5px_0_#26190f]">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/60">Season Ends</p>
                            <p class="mt-1 text-base font-extrabold"><?= (int) $seasonDaysLeft ?> days left</p>
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
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="./?c=player-analytics" class="rounded-xl border-2 border-arcade-ink bg-white px-3 py-1.5 text-xs font-bold text-arcade-ink no-underline shadow-[0_3px_0_rgba(38,25,15,0.35)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">Rank Details</a>
                            <a href="./?c=player-analytics&view=leaderboard" class="rounded-xl border-2 border-arcade-ink bg-arcade-orange px-3 py-1.5 text-xs font-bold text-white no-underline shadow-[0_3px_0_rgba(38,25,15,0.35)] transition hover:-translate-y-0.5 hover:bg-arcade-cyan hover:text-arcade-ink">Leaderboard</a>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-3">
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
                                    <p class="mt-0.5 text-xl font-bold">34</p>
                                </div>
                            </div>
                        </div>
                        <div class="ranking-stat-card rounded-xl border-2 border-arcade-ink/10 bg-arcade-cream p-3">
                            <div class="flex items-center gap-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-arcade-orange text-white" aria-hidden="true">
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" focusable="false">
                                        <path fill="currentColor" d="M8.8 1.5c.3 2.1 1.4 3 2.3 3.9.9.9 1.6 1.9 1.6 3.6A4.7 4.7 0 0 1 8 13.8 4.7 4.7 0 0 1 3.3 9c0-2 1.1-3.4 2.3-4.7.7-.8 1.5-1.6 1.8-2.8h1.4ZM8 11.9A2.8 2.8 0 0 0 10.8 9c0-.9-.4-1.5-1-2.2-.5-.5-1.1-1.1-1.6-2.1-.4.7-.9 1.3-1.4 1.8C6 7.4 5.2 8.2 5.2 9A2.8 2.8 0 0 0 8 11.9Z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Streak</p>
                                    <p class="mt-0.5 text-xl font-bold">7d</p>
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
                        <div class="home-activity-grid" aria-label="<?= (int) $currentYear ?> challenge solving chart with 235 days">
                            <?php foreach ($activityDays as $activityDay) : ?>
                                <span
                                    class="home-activity-cell home-activity-cell--<?= (int) $activityDay['level'] ?>"
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

                    <div class="mt-5 border-t-2 border-arcade-ink/10 pt-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-bold">Solved Challenges</h3>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/55">20 per page</p>
                                <a href="./?c=player-analytics&status=completed" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/50">Review All</a>
                            </div>
                        </div>

                        <div class="mt-3 overflow-hidden rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/70">
                            <?php foreach ($solvedChallengeRows as $rowIndex => $solvedChallenge) : ?>
                                <article class="solved-row flex flex-col gap-1 border-b border-arcade-ink/10 px-3 py-2 last:border-b-0 sm:flex-row sm:items-center sm:justify-between" data-solved-row data-row-index="<?= (int) $rowIndex ?>">
                                    <div>
                                        <p class="text-sm font-bold"><?= htmlspecialchars($solvedChallenge['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-xs font-semibold text-arcade-ink/55"><?= htmlspecialchars($solvedChallenge['date']->format('M j, Y'), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($solvedChallenge['result'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                    <span class="text-xs font-bold text-arcade-orange">+<?= (int) $solvedChallenge['points'] ?> pts</span>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <button id="solved-prev" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Prev</button>
                            <span id="solved-page-status" class="text-xs font-bold text-arcade-ink/60"></span>
                            <button id="solved-next" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Next</button>
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
                                        <span class="rounded-full bg-arcade-cyan/30 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['estimate'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['reward'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                                        <span>By <?= htmlspecialchars($challenge['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="mt-1.5 text-sm leading-6 text-arcade-ink/68"><?= htmlspecialchars($challenge['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-2 font-mono text-xs font-bold text-arcade-ink/55">Focus: <?= htmlspecialchars($challenge['focus'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <a href="./?c=challenge&slug=<?= urlencode($challenge['slug']) ?>" class="inline-flex shrink-0 justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
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

<script>
(() => {
    const rows = Array.from(document.querySelectorAll('[data-solved-row]'));
    const previousButton = document.getElementById('solved-prev');
    const nextButton = document.getElementById('solved-next');
    const pageStatus = document.getElementById('solved-page-status');
    const pageSize = 20;
    let currentPage = 1;
    const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));

    const renderSolvedPage = () => {
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        rows.forEach((row, index) => {
            row.hidden = index < start || index >= end;
        });

        if (pageStatus) {
            pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;
        }

        if (previousButton) {
            previousButton.disabled = currentPage === 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage === totalPages;
        }
    };

    previousButton?.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        renderSolvedPage();
    });

    nextButton?.addEventListener('click', () => {
        currentPage = Math.min(totalPages, currentPage + 1);
        renderSolvedPage();
    });

    renderSolvedPage();
})();
</script>
