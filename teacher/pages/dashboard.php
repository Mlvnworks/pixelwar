<?php
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$currentYear = (int) date('Y');
$yearStart = new DateTimeImmutable($currentYear . '-01-01');
$yearEnd = new DateTimeImmutable($currentYear . '-12-31');
$activityCounts = $activityLogRepository instanceof ActivityLogRepository && $teacherId > 0
    ? $activityLogRepository->countByDayAndCategory($teacherId, $currentYear)
    : [];
$teacherActivityDays = [];

for ($dayIndex = 0, $totalDays = (int) $yearStart->diff($yearEnd)->days + 1; $dayIndex < $totalDays; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $dailyCounts = $activityCounts[$dateKey] ?? [];
    $challengeCreated = (int) ($dailyCounts['challenge'] ?? 0);
    $roomCreated = (int) ($dailyCounts['room'] ?? 0);
    $totalActivity = $challengeCreated + $roomCreated;

    $teacherActivityDays[] = [
        'date' => $date,
        'challenges' => $challengeCreated,
        'rooms' => $roomCreated,
        'level' => min(5, $totalActivity),
    ];
}

$latestCreatedChallenges = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->listLatestCreated(10)
    : [];
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

        <div class="grid items-start gap-5">
            <section class="teacher-panel self-start rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Analytics</p>
                        <h2 class="mt-2 text-2xl font-black">Teacher Creation Activity</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-black text-arcade-ink/58"><?= (int) $currentYear ?> Activity</p>
                        <a href="./?c=activity-logs" class="teacher-link-button gap-2">
                            <i data-lucide="activity" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Activity Logs</span>
                        </a>
                    </div>
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

            </section>

            <section class="teacher-panel self-start rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-row items-end justify-between gap-3">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Latest Created</p>
                        <h2 class="mt-2 text-2xl font-black">Challenges</h2>
                    </div>
                    <a href="./?c=challenges" class="teacher-link-button">Challenges</a>
                </div>

                <div class="teacher-created-grid teacher-created-grid--compact mt-4">
                    <?php if ($latestCreatedChallenges === []) : ?>
                        <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-5">
                            <p class="text-sm font-black text-arcade-ink/58">No teacher-created challenges yet.</p>
                            <a href="./?c=create-challenge" class="teacher-link-button mt-3">Create Challenge</a>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($latestCreatedChallenges as $challenge) : ?>
                        <?php
                        $difficulty = strtolower((string) ($challenge['difficulty_name'] ?? 'easy'));
                        $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', $difficulty);
                        $createdAt = (string) ($challenge['date_created'] ?? '');
                        $createdLabel = $createdAt !== '' ? date('M j, Y', strtotime($createdAt)) : 'Recently';
                        ?>
                        <article class="challenge-card teacher-created-challenge rounded-[18px] border-2 border-arcade-ink/12 bg-white p-4 transition hover:-translate-y-1 hover:border-arcade-orange hover:shadow-[0_6px_0_rgba(38,25,15,0.18)]">
                            <div class="flex h-full flex-col gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= (int) ($challenge['points'] ?? 0) ?> points</span>
                                    </div>
                                    <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars((string) $challenge['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                                        <span>By <?= htmlspecialchars((string) $challenge['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span>Created <?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <p class="teacher-card-description mt-1.5 text-sm leading-6 text-arcade-ink/68"><?= htmlspecialchars((string) $challenge['instruction'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <a href="./?c=challenge-view&id=<?= (int) $challenge['challenge_id'] ?>" class="teacher-small-button mt-auto">Open</a>
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
