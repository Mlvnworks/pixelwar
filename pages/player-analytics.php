<?php
$analyticsRows = [];
$analyticsTitles = ['Button Border Basics', 'Card Shadow Match', 'Hero Text Alignment', 'Badge Color Tune', 'Spacing Sprint', 'Selector Stack', 'CTA Polish', 'Panel Radius Run', 'Grid Gap Drill', 'Text Contrast Fix'];
$analyticsStatuses = ['completed', 'ongoing'];
$analyticsStart = new DateTimeImmutable(date('Y') . '-01-08');
$currentYear = (int) date('Y');
$yearStart = new DateTimeImmutable($currentYear . '-01-01');
$solvePattern = [0, 1, 0, 2, 3, 0, 1, 4, 2, 0, 0, 1, 3, 4, 1, 0, 2, 2, 5, 1, 0, 3, 4, 0, 1, 2, 5, 3, 0, 1, 4, 2, 0, 3, 5];
$activityDays = [];

for ($dayIndex = 0; $dayIndex < 235; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $solves = $solvePattern[$dayIndex % count($solvePattern)];
    $activityDays[] = [
        'date' => $date,
        'solves' => $solves,
        'level' => min($solves, 5),
    ];
}

for ($index = 0; $index < 64; $index++) {
    $status = $analyticsStatuses[$index % 4 === 0 ? 1 : 0];
    $startedAt = $analyticsStart->modify('+' . $index . ' days');
    $durationMinutes = 6 + ($index % 9) * 4;
    $completedAt = $status === 'completed' ? $startedAt->modify('+' . $durationMinutes . ' minutes') : null;

    $analyticsRows[] = [
        'title' => $analyticsTitles[$index % count($analyticsTitles)],
        'status' => $status,
        'level' => $index % 3 === 0 ? 'Novice' : 'Beginner',
        'startedAt' => $startedAt,
        'completedAt' => $completedAt,
        'duration' => $status === 'completed' ? $durationMinutes . ' min' : 'Ongoing',
        'summary' => $status === 'completed'
            ? 'Matched the target selectors and completed the CSS design.'
            : 'Started solving and still needs final property placement.',
    ];
}
?>

<main class="analytics-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_16%_14%,rgba(255,209,102,0.28),transparent_22%),radial-gradient(circle_at_86%_18%,rgba(76,201,240,0.2),transparent_24%)]"></div>
    <div class="analytics-page__grid absolute inset-0"></div>

    <section class="container relative">
        <div class="mb-5 rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Player Analytics</p>
            <div class="mt-3 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold leading-tight md:text-5xl">Challenge History</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-arcade-ink/70">Search completed and ongoing attempts with completion details and solving duration.</p>
                </div>
                <a href="./?c=home" class="inline-flex justify-center rounded-xl border-2 border-arcade-ink/10 bg-white px-4 py-2 text-sm font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/50">Back Home</a>
            </div>
        </div>

        <section class="rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <article class="mb-5 rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Solving Chart</p>
                        <h2 class="mt-2 text-xl font-bold"><?= (int) $currentYear ?> Activity</h2>
                    </div>
                    <p class="text-sm font-bold text-arcade-ink/60">235 tracked days</p>
                </div>

                <div class="mt-4 overflow-x-auto pb-2">
                    <div class="analytics-activity-grid" aria-label="<?= (int) $currentYear ?> challenge solving chart with 235 days">
                        <?php foreach ($activityDays as $activityDay) : ?>
                            <span
                                class="analytics-activity-cell analytics-activity-cell--<?= (int) $activityDay['level'] ?>"
                                title="<?= htmlspecialchars($activityDay['date']->format('M j, Y'), ENT_QUOTES, 'UTF-8') ?>: <?= (int) $activityDay['solves'] ?> solved"></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                    <span>Less</span>
                    <span class="analytics-activity-cell analytics-activity-cell--0"></span>
                    <span class="analytics-activity-cell analytics-activity-cell--1"></span>
                    <span class="analytics-activity-cell analytics-activity-cell--3"></span>
                    <span class="analytics-activity-cell analytics-activity-cell--5"></span>
                    <span>More</span>
                </div>
            </article>

            <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <label class="block">
                    <span class="text-sm font-bold">Search challenges</span>
                    <input id="analytics-search" type="search" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 text-sm outline-none transition focus:border-arcade-orange" placeholder="Search title, status, level, or details...">
                </label>
                <div class="flex gap-2">
                    <button class="analytics-filter is-active rounded-xl border-2 border-arcade-ink/10 bg-arcade-yellow px-3 py-2 text-xs font-bold" type="button" data-status-filter="all">All</button>
                    <button class="analytics-filter rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-2 text-xs font-bold" type="button" data-status-filter="completed">Completed</button>
                    <button class="analytics-filter rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-2 text-xs font-bold" type="button" data-status-filter="ongoing">Ongoing</button>
                </div>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border-2 border-arcade-ink/10 bg-white">
                <?php foreach ($analyticsRows as $rowIndex => $row) : ?>
                    <article
                        class="analytics-row grid gap-2 border-b border-arcade-ink/10 px-4 py-3 last:border-b-0 lg:grid-cols-[1.2fr_0.55fr_0.65fr_1fr_auto] lg:items-center"
                        data-analytics-row
                        data-search="<?= htmlspecialchars(strtolower($row['title'] . ' ' . $row['status'] . ' ' . $row['level'] . ' ' . $row['summary']), ENT_QUOTES, 'UTF-8') ?>"
                        data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <div>
                            <p class="text-sm font-bold"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-xs font-semibold text-arcade-ink/55"><?= htmlspecialchars($row['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div>
                            <span class="rounded-full <?= $row['status'] === 'completed' ? 'bg-arcade-mint/70' : 'bg-arcade-yellow/70' ?> px-3 py-1 text-xs font-bold">
                                <?= htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p class="text-xs font-bold text-arcade-ink/60"><?= htmlspecialchars($row['level'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($row['duration'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs font-semibold text-arcade-ink/55">
                            Started <?= htmlspecialchars($row['startedAt']->format('M j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($row['completedAt'] !== null) : ?>
                                <br>Completed <?= htmlspecialchars($row['completedAt']->format('M j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
                        <a href="./?c=pixelwar&intro=1" class="inline-flex justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-3 py-1.5 text-xs font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
                            Open
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <button id="analytics-prev" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Prev</button>
                <span id="analytics-page-status" class="text-xs font-bold text-arcade-ink/60"></span>
                <button id="analytics-next" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Next</button>
            </div>
        </section>
    </section>
</main>

<script>
(() => {
    const rows = Array.from(document.querySelectorAll('[data-analytics-row]'));
    const searchInput = document.getElementById('analytics-search');
    const filterButtons = Array.from(document.querySelectorAll('[data-status-filter]'));
    const previousButton = document.getElementById('analytics-prev');
    const nextButton = document.getElementById('analytics-next');
    const pageStatus = document.getElementById('analytics-page-status');
    const pageSize = 20;
    let currentPage = 1;
    let activeStatus = 'all';

    const matchingRows = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        return rows.filter((row) => {
            const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
            const matchesSearch = query === '' || (row.dataset.search || '').includes(query);
            return matchesStatus && matchesSearch;
        });
    };

    const renderRows = () => {
        const matches = matchingRows();
        const totalPages = Math.max(1, Math.ceil(matches.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * pageSize;
        const visibleRows = new Set(matches.slice(start, start + pageSize));

        rows.forEach((row) => {
            row.hidden = !visibleRows.has(row);
        });

        if (pageStatus) {
            pageStatus.textContent = matches.length === 0
                ? 'No records found'
                : `Page ${currentPage} of ${totalPages} - ${matches.length} records`;
        }

        if (previousButton) {
            previousButton.disabled = currentPage === 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage === totalPages || matches.length === 0;
        }
    };

    searchInput?.addEventListener('input', () => {
        currentPage = 1;
        renderRows();
    });

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeStatus = button.dataset.statusFilter || 'all';
            currentPage = 1;
            filterButtons.forEach((filterButton) => {
                filterButton.classList.toggle('is-active', filterButton === button);
                filterButton.classList.toggle('bg-arcade-yellow', filterButton === button);
                filterButton.classList.toggle('bg-white', filterButton !== button);
            });
            renderRows();
        });
    });

    previousButton?.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        renderRows();
    });

    nextButton?.addEventListener('click', () => {
        currentPage += 1;
        renderRows();
    });

    renderRows();
})();
</script>
