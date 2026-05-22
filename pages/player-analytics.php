<?php
$currentStudentId = (int) ($_SESSION['user_id'] ?? 0);
$initialStatusFilter = isset($_GET['status']) && in_array((string) $_GET['status'], ['completed', 'ongoing', 'failed'], true)
    ? (string) $_GET['status']
    : 'all';
$allowedAnalyticsRanges = [7, 30, 365];
$selectedRangeDays = isset($_GET['range']) ? (int) $_GET['range'] : 30;

if (!in_array($selectedRangeDays, $allowedAnalyticsRanges, true)) {
    $selectedRangeDays = 30;
}

$analyticsEndDate = new DateTimeImmutable('today');
$analyticsStartDate = $analyticsEndDate->modify('-' . ($selectedRangeDays - 1) . ' days');
$completedCountsByDate = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->completedCountsByDate($currentStudentId, $analyticsStartDate, $analyticsEndDate)
    : [];
$attemptHistoryRows = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->listAttemptHistory($currentStudentId, 500)
    : [];
$activityChartLabels = [];
$activityChartValues = [];
$analyticsRows = [];
$rangeQueryBase = './?c=player-analytics&status=' . urlencode($initialStatusFilter);

$formatDurationLabel = static function (int $totalSeconds): string {
    $safeSeconds = max(0, $totalSeconds);
    $hours = intdiv($safeSeconds, 3600);
    $minutes = intdiv($safeSeconds % 3600, 60);
    $seconds = $safeSeconds % 60;

    if ($hours > 0) {
        return sprintf('%dh %02dm', $hours, $minutes);
    }

    if ($minutes > 0) {
        return sprintf('%dm %02ds', $minutes, $seconds);
    }

    return sprintf('%ds', $seconds);
};

for ($dayIndex = 0; $dayIndex < $selectedRangeDays; $dayIndex++) {
    $date = $analyticsStartDate->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $solves = $completedCountsByDate[$dateKey] ?? 0;
    $activityChartLabels[] = $date->format('M j');
    $activityChartValues[] = $solves;
}

foreach ($attemptHistoryRows as $attemptRow) {
    $isRoomAttempt = (int) ($attemptRow['room_id'] ?? 0) > 0;
    $status = !empty($attemptRow['completed_at'])
        ? 'completed'
        : ($isRoomAttempt ? 'failed' : 'ongoing');
    $startedAt = new DateTimeImmutable((string) $attemptRow['started_at']);
    $completedAt = !empty($attemptRow['completed_at'])
        ? new DateTimeImmutable((string) $attemptRow['completed_at'])
        : null;
    $durationLabel = $status === 'failed' ? 'Failed' : 'Ongoing';
    $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A');

    if ($completedAt instanceof DateTimeImmutable) {
        $durationSeconds = max(0, $completedAt->getTimestamp() - $startedAt->getTimestamp());
        $durationLabel = $formatDurationLabel($durationSeconds);
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - Completed in ' . $durationLabel;
    } elseif ($status === 'failed') {
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - This room run was not completed.';
    } else {
        $ongoingSeconds = max(0, time() - $startedAt->getTimestamp());
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - Running for ' . $formatDurationLabel($ongoingSeconds);
    }

    $analyticsRows[] = [
        'challengeId' => (int) $attemptRow['challenge_id'],
        'title' => (string) $attemptRow['name'],
        'status' => $status,
        'level' => ucfirst(strtolower((string) ($attemptRow['difficulty_name'] ?? 'Beginner'))),
        'startedAt' => $startedAt,
        'completedAt' => $completedAt,
        'duration' => $durationLabel,
        'durationDetails' => $durationDetails,
        'summary' => $status === 'completed'
            ? 'Completed this CSS matching challenge and locked in the solve.'
            : ($status === 'failed'
                ? 'Started this CSS matching challenge inside a room, but the run was not completed.'
                : 'Started this CSS matching challenge and still has an active run.'),
        'href' => $status === 'ongoing'
            ? './?c=pixelwar&intro=1&challenge_id=' . (int) $attemptRow['challenge_id']
            : './?c=challenge&id=' . (int) $attemptRow['challenge_id'],
        'points' => (int) ($attemptRow['awarded_points'] ?? 0),
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
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-arcade-ink/70">Search completed, ongoing, and failed attempts with completion details and solving duration.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="inline-flex justify-center rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white" data-bs-toggle="modal" data-bs-target="#analytics-export-modal">Export CSV</button>
                    <a href="./?c=home" class="inline-flex justify-center rounded-xl border-2 border-arcade-ink/10 bg-white px-4 py-2 text-sm font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/50">Back Home</a>
                </div>
            </div>
        </div>

        <section class="rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <article class="mb-5 rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Solving Chart</p>
                            <h2 class="mt-2 text-xl font-bold">Challenge Solving</h2>
                        </div>
                        <p class="text-sm font-bold text-arcade-ink/60">Last <?= (int) $selectedRangeDays ?> Days</p>
                    </div>

                    <div class="analytics-range-row">
                        <?php foreach ($allowedAnalyticsRanges as $rangeOption) : ?>
                            <a
                                href="<?= htmlspecialchars($rangeQueryBase . '&range=' . $rangeOption, ENT_QUOTES, 'UTF-8') ?>"
                                class="analytics-range-chip <?= $selectedRangeDays === $rangeOption ? 'is-active' : '' ?>"
                            >
                                Last <?= (int) $rangeOption ?> Days
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="analytics-chart-shell" aria-label="Challenge solving chart for the last <?= (int) $selectedRangeDays ?> days">
                        <div class="analytics-chart-summary">
                            <div class="analytics-chart-chip">
                                <span class="analytics-chart-chip__dot"></span>
                                <span>Daily completed challenges</span>
                            </div>
                            <p class="analytics-chart-summary__copy">Shows your completed challenge count day by day for the selected range.</p>
                        </div>
                        <div class="analytics-chart-stage">
                            <canvas id="player-analytics-chart" height="230"></canvas>
                        </div>
                    </div>
                </div>
            </article>

            <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                <label class="block">
                    <span class="text-sm font-bold">Search challenges</span>
                    <input id="analytics-search" type="search" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 text-sm outline-none transition focus:border-arcade-orange" placeholder="Search title, status, level, or details...">
                </label>
                <div class="flex gap-2">
                    <button class="analytics-filter <?= $initialStatusFilter === 'all' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="all">All</button>
                    <button class="analytics-filter <?= $initialStatusFilter === 'completed' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="completed">Completed</button>
                    <button class="analytics-filter <?= $initialStatusFilter === 'ongoing' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="ongoing">Ongoing</button>
                    <button class="analytics-filter <?= $initialStatusFilter === 'failed' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="failed">Failed</button>
                </div>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border-2 border-arcade-ink/10 bg-white">
                <?php if ($analyticsRows === []) : ?>
                    <p class="px-4 py-5 text-sm font-bold text-arcade-ink/55">No challenge activity yet.</p>
                <?php endif; ?>
                <?php foreach ($analyticsRows as $row) : ?>
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
                            <span class="rounded-full <?= $row['status'] === 'completed' ? 'bg-arcade-mint/70' : ($row['status'] === 'failed' ? 'bg-arcade-coral/30' : 'bg-arcade-yellow/70') ?> px-3 py-1 text-xs font-bold">
                                <?= htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p class="text-xs font-bold text-arcade-ink/60"><?= htmlspecialchars($row['level'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($row['duration'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs font-semibold text-arcade-ink/55"><?= htmlspecialchars($row['durationDetails'], ENT_QUOTES, 'UTF-8') ?></p>
                        <a href="<?= htmlspecialchars($row['href'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-3 py-1.5 text-xs font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
                            Train again
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

<div class="modal fade" id="analytics-export-modal" tabindex="-1" aria-labelledby="analytics-export-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
            <input type="hidden" name="c" value="player-analytics">
            <input type="hidden" name="export" value="csv">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Export Records</p>
                    <h2 class="modal-title mt-2 text-2xl font-bold" id="analytics-export-modal-title">Choose date range</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-5 pb-5 pt-2">
                <p class="text-sm font-semibold leading-6 text-arcade-ink/65">Export your solving records as CSV for the exact date range you choose.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="export-date-field">
                        <span>Start Date</span>
                        <input type="date" name="export_start_date" value="<?= htmlspecialchars($analyticsStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($analyticsEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label class="export-date-field">
                        <span>End Date</span>
                        <input type="date" name="export_end_date" value="<?= htmlspecialchars($analyticsEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($analyticsEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Export CSV</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
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
    let activeStatus = <?= json_encode($initialStatusFilter, JSON_UNESCAPED_SLASHES) ?>;

    const canvas = document.getElementById('player-analytics-chart');

    if (canvas && typeof window.Chart !== 'undefined') {
        const context = canvas.getContext('2d');
        if (context) {
            const labels = <?= json_encode($activityChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const values = <?= json_encode($activityChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
            const gradient = context.createLinearGradient(0, 0, 0, canvas.height || 230);
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
                            titleFont: { weight: '800' },
                            bodyFont: { weight: '700' },
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
                                font: { weight: '700' },
                            },
                            grid: { display: false },
                            border: {
                                color: isDarkMode ? 'rgba(255,247,232,0.12)' : 'rgba(38,25,15,0.12)',
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: isDarkMode ? 'rgba(255,247,232,0.7)' : 'rgba(38,25,15,0.58)',
                                font: { weight: '700' },
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
        }
    }

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
