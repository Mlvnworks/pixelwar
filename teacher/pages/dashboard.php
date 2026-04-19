<?php
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
$teacherChallenges = array_values(ChallengeCatalog::all());
$currentYear = (int) date('Y');
$yearStart = new DateTimeImmutable($currentYear . '-01-01');
$activityPattern = [0, 1, 0, 2, 3, 0, 1, 4, 2, 0, 1, 2, 0, 3, 1, 0, 4, 2, 1, 0, 3, 5, 0, 1, 2, 0, 4, 1, 3, 0, 2];
$teacherActivityDays = [];

for ($dayIndex = 0; $dayIndex < 235; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $challengeCreated = $activityPattern[$dayIndex % count($activityPattern)];
    $roomCreated = $activityPattern[($dayIndex + 9) % count($activityPattern)] > 2 ? 1 : 0;
    $totalActivity = $challengeCreated + $roomCreated;

    $teacherActivityDays[] = [
        'date' => $date,
        'challenges' => $challengeCreated,
        'rooms' => $roomCreated,
        'level' => min(5, $totalActivity),
    ];
}

$teacherActivityLogs = [
    ['type' => 'Challenge', 'title' => 'Created Button Border Basics remix', 'meta' => 'Easy · border, radius, padding', 'time' => 'Today, 9:42 AM'],
    ['type' => 'Room', 'title' => 'Opened Arcade Dawn practice room', 'meta' => '6 players invited · Card Shadow Match', 'time' => 'Today, 8:15 AM'],
    ['type' => 'Challenge', 'title' => 'Updated Hero Text Alignment hints', 'meta' => 'Hard · typography tuning', 'time' => 'Yesterday, 4:20 PM'],
    ['type' => 'Room', 'title' => 'Started 1v1 CSS sprint room', 'meta' => '2 players joined · Button Border Basics', 'time' => 'Yesterday, 11:05 AM'],
    ['type' => 'Challenge', 'title' => 'Published Panel Radius Run draft', 'meta' => 'Medium · layout polish', 'time' => 'Apr 12, 2026'],
];

$latestCreatedChallenges = [];
for ($index = 0; $index < 10; $index++) {
    $challenge = $teacherChallenges[$index % count($teacherChallenges)];
    $latestCreatedChallenges[] = [
        'rank' => $index + 1,
        'slug' => (string) $challenge['slug'],
        'title' => (string) $challenge['title'],
        'level' => (string) $challenge['level'],
        'levelClass' => (string) $challenge['levelClass'],
        'estimate' => (string) $challenge['estimate'],
        'reward' => (string) $challenge['reward'],
        'author' => (string) $challenge['author'],
        'description' => (string) $challenge['description'],
        'focus' => (string) $challenge['focus'],
        'created' => (new DateTimeImmutable('today'))->modify('-' . $index . ' days')->format('M j'),
        'rooms' => 18 - min(12, $index),
    ];
}
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Teacher Dashboard</p>
            <div class="mt-3 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <h1 class="text-3xl font-black leading-tight md:text-5xl">Welcome <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?>,</h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Track creation activity, room activity, and the challenges that are getting the most classroom attention.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="./?c=students" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="users" class="h-4 w-4" aria-hidden="true"></i>
                        <span>View Students</span>
                    </a>
                    <a href="../?c=room" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="messages-square" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Create Room</span>
                    </a>
                    <a href="./?c=create-challenge" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="square-plus" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Create Challenge</span>
                    </a>
                </div>
            </div>
        </article>

        <div class="grid items-start gap-5 xl:grid-cols-[1.15fr_0.85fr]">
            <section class="teacher-panel self-start rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Analytics</p>
                        <h2 class="mt-2 text-2xl font-black">Teacher Creation Activity</h2>
                    </div>
                    <p class="text-sm font-black text-arcade-ink/58"><?= (int) $currentYear ?> Activity</p>
                </div>

                <div class="teacher-activity-scroll mt-4">
                    <div class="teacher-activity-grid" aria-label="<?= (int) $currentYear ?> teacher activity chart">
                        <?php foreach ($teacherActivityDays as $activityDay) : ?>
                            <?php
                            $challengeLabel = (int) $activityDay['challenges'] === 1 ? 'challenge' : 'challenges';
                            $roomLabel = (int) $activityDay['rooms'] === 1 ? 'room' : 'rooms';
                            $tooltip = $activityDay['date']->format('M j, Y')
                                . ': ' . (int) $activityDay['challenges'] . ' ' . $challengeLabel . ' created'
                                . ', ' . (int) $activityDay['rooms'] . ' ' . $roomLabel . ' created';
                            ?>
                            <span
                                class="teacher-activity-cell teacher-activity-cell--<?= (int) $activityDay['level'] ?>"
                                tabindex="0"
                                role="button"
                                aria-label="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                                data-tooltip="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                                title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-black text-arcade-ink/55">
                    <span>Less</span>
                    <span class="teacher-activity-cell teacher-activity-cell--0"></span>
                    <span class="teacher-activity-cell teacher-activity-cell--1"></span>
                    <span class="teacher-activity-cell teacher-activity-cell--3"></span>
                    <span class="teacher-activity-cell teacher-activity-cell--5"></span>
                    <span>More</span>
                </div>

                <div class="mt-5 border-t-2 border-arcade-ink/10 pt-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Last Activity</p>
                            <h2 class="mt-2 text-2xl font-black">Rooms & Challenges</h2>
                        </div>
                        <a href="./?c=challenges" class="teacher-link-button">View All</a>
                    </div>

                    <div class="mt-4 grid gap-3">
                        <?php foreach ($teacherActivityLogs as $activityLog) : ?>
                            <article class="teacher-log-card rounded-2xl border-2 border-arcade-ink/12 bg-arcade-cream/72 p-3">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="teacher-log-badge <?= $activityLog['type'] === 'Room' ? 'teacher-log-badge--room' : 'teacher-log-badge--challenge' ?>">
                                            <?= htmlspecialchars(substr($activityLog['type'], 0, 1), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <div>
                                            <p class="text-sm font-black uppercase tracking-[0.16em] text-arcade-orange"><?= htmlspecialchars($activityLog['type'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <h3 class="mt-1 text-base font-black"><?= htmlspecialchars($activityLog['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                            <p class="mt-1 text-sm font-bold leading-6 text-arcade-ink/58"><?= htmlspecialchars($activityLog['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    </div>
                                    <p class="text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/45"><?= htmlspecialchars($activityLog['time'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <aside class="teacher-panel self-start rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-row items-end justify-between gap-3">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Latest Created</p>
                        <h2 class="mt-2 text-2xl font-black">Challenges</h2>
                    </div>
                    <a href="./?c=challenges" class="teacher-link-button">Challenges</a>
                </div>

                <div class="mt-4 grid gap-3">
                    <?php foreach ($latestCreatedChallenges as $challenge) : ?>
                        <article class="challenge-card teacher-created-challenge rounded-[18px] border-2 border-arcade-ink/12 bg-white p-4 transition hover:-translate-y-1 hover:border-arcade-orange hover:shadow-[0_6px_0_rgba(38,25,15,0.18)]">
                            <div class="flex flex-col gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="challenge-difficulty <?= htmlspecialchars($challenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['level'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="rounded-full bg-arcade-cyan/30 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['estimate'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['reward'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                                        <span>By <?= htmlspecialchars($challenge['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span>Created <?= htmlspecialchars($challenge['created'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="teacher-card-description mt-1.5 text-sm leading-6 text-arcade-ink/68"><?= htmlspecialchars($challenge['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-2 font-mono text-xs font-bold text-arcade-ink/55">Focus: <?= htmlspecialchars($challenge['focus'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <a href="../?c=challenge&slug=<?= urlencode($challenge['slug']) ?>" class="inline-flex shrink-0 justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
                                    Open
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>
    </section>
</main>

<script>
(() => {
    if (window.lucide) {
        window.lucide.createIcons();
    } else {
        window.addEventListener('load', () => window.lucide?.createIcons());
    }

    const cells = document.querySelectorAll('.teacher-activity-cell[data-tooltip]');
    if (cells.length === 0) {
        return;
    }

    const tooltip = document.createElement('div');
    tooltip.className = 'teacher-chart-tooltip';
    tooltip.hidden = true;
    document.body.appendChild(tooltip);

    const positionTooltip = (cell) => {
        const rect = cell.getBoundingClientRect();
        const text = cell.getAttribute('data-tooltip') || '';
        tooltip.textContent = text;
        tooltip.hidden = false;

        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportPadding = 12;
        let left = rect.left + rect.width / 2;
        let top = rect.top - tooltipRect.height - 10;

        if (top < viewportPadding) {
            top = rect.bottom + 10;
        }

        const minLeft = viewportPadding + tooltipRect.width / 2;
        const maxLeft = window.innerWidth - viewportPadding - tooltipRect.width / 2;
        left = Math.max(minLeft, Math.min(maxLeft, left));

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    };

    const hideTooltip = () => {
        tooltip.hidden = true;
    };

    cells.forEach((cell) => {
        cell.addEventListener('mouseenter', () => positionTooltip(cell));
        cell.addEventListener('mousemove', () => positionTooltip(cell));
        cell.addEventListener('focus', () => positionTooltip(cell));
        cell.addEventListener('mouseleave', hideTooltip);
        cell.addEventListener('blur', hideTooltip);
    });

    window.addEventListener('scroll', hideTooltip, { passive: true });
    window.addEventListener('resize', hideTooltip);
})();
</script>
