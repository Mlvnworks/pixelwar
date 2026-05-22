<?php
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$allowedAnalyticsRanges = [7, 30, 365];
$selectedRangeDays = isset($_GET['range']) ? (int) $_GET['range'] : 30;

if (!in_array($selectedRangeDays, $allowedAnalyticsRanges, true)) {
    $selectedRangeDays = 30;
}

$analyticsEndDate = new DateTimeImmutable('today');
$analyticsStartDate = $analyticsEndDate->modify('-' . ($selectedRangeDays - 1) . ' days');
$activityCounts = $activityLogRepository instanceof ActivityLogRepository && $teacherId > 0
    ? $activityLogRepository->countCreationByDayAndCategoryInRange($teacherId, $analyticsStartDate, $analyticsEndDate)
    : [];
$activityLogsPage = max(1, (int) ($_GET['page'] ?? 1));
$activityLogsPerPage = 12;
$activityLogsTotal = $activityLogRepository instanceof ActivityLogRepository && $teacherId > 0
    ? $activityLogRepository->countCreationLogsForUser($teacherId)
    : 0;
$activityLogsTotalPages = max(1, (int) ceil($activityLogsTotal / $activityLogsPerPage));
$activityLogsPage = min($activityLogsPage, $activityLogsTotalPages);
$activityLogsOffset = ($activityLogsPage - 1) * $activityLogsPerPage;
$teacherActivityLogs = $activityLogRepository instanceof ActivityLogRepository && $teacherId > 0
    ? $activityLogRepository->listCreationLogsForUserPaged($teacherId, $activityLogsPerPage, $activityLogsOffset)
    : [];
$teacherChartLabels = [];
$teacherChallengeValues = [];
$teacherRoomValues = [];
$rangeQueryBase = './?c=activity-logs';
$paginationBase = './?c=activity-logs&range=' . $selectedRangeDays;

for ($dayIndex = 0; $dayIndex < $selectedRangeDays; $dayIndex++) {
    $date = $analyticsStartDate->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $dailyCounts = $activityCounts[$dateKey] ?? [];
    $teacherChartLabels[] = $date->format('M j');
    $teacherChallengeValues[] = (int) ($dailyCounts['challenge'] ?? 0);
    $teacherRoomValues[] = (int) ($dailyCounts['room'] ?? 0);
}
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Activity Logs</p>
                    <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl">Creation Records</h1>
                </div>
                <div class="flex flex-nowrap items-center gap-2">
                    <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#teacher-activity-export-modal">
                        <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </button>
                    <a href="./?c=dashboard" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>
        </article>

        <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Analytics</p>
                        <h2 class="mt-2 text-2xl font-black">Creation Activity</h2>
                    </div>
                    <p class="text-sm font-black text-arcade-ink/58">Last <?= (int) $selectedRangeDays ?> Days</p>
                </div>

                <div class="teacher-range-row">
                    <?php foreach ($allowedAnalyticsRanges as $rangeOption) : ?>
                        <a
                            href="<?= htmlspecialchars($rangeQueryBase . '?range=' . $rangeOption, ENT_QUOTES, 'UTF-8') ?>"
                            class="teacher-range-chip <?= $selectedRangeDays === $rangeOption ? 'is-active' : '' ?>"
                        >
                            Last <?= (int) $rangeOption ?> Days
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="teacher-chart-shell" aria-label="Teacher creation activity chart for the last <?= (int) $selectedRangeDays ?> days">
                    <div class="teacher-chart-stage">
                        <canvas id="teacher-activity-chart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="grid gap-3">
                <?php if ($teacherActivityLogs === []) : ?>
                    <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-4 text-sm font-black text-arcade-ink/55">
                        No creation logs yet.
                    </div>
                <?php endif; ?>

                <?php foreach ($teacherActivityLogs as $activityLog) : ?>
                    <?php
                    $category = strtolower((string) ($activityLog['category'] ?? 'general'));
                    $categoryLabel = ucfirst($category);
                    $logText = (string) ($activityLog['log_text'] ?? '');
                    $createdAt = (string) ($activityLog['date_created'] ?? '');
                    $timeLabel = $createdAt !== '' ? date('M j, Y g:i A', strtotime($createdAt)) : '';
                    ?>
                    <article class="teacher-log-card rounded-2xl border-2 border-arcade-ink/12 bg-arcade-cream/72 p-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-3">
                                <span class="teacher-log-badge <?= $category === 'room' ? 'teacher-log-badge--room' : 'teacher-log-badge--challenge' ?>">
                                    <?= htmlspecialchars(strtoupper(substr($categoryLabel, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <div>
                                    <p class="text-sm font-black uppercase tracking-[0.16em] text-arcade-orange"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                    <h2 class="mt-1 text-base font-black"><?= htmlspecialchars($logText, ENT_QUOTES, 'UTF-8') ?></h2>
                                </div>
                            </div>
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/45"><?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <a
                    href="<?= htmlspecialchars($paginationBase . '&page=' . max(1, $activityLogsPage - 1), ENT_QUOTES, 'UTF-8') ?>"
                    class="teacher-link-button <?= $activityLogsPage <= 1 ? 'pointer-events-none opacity-45' : '' ?>"
                >
                    Prev
                </a>
                <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/55">
                    <?= $activityLogsTotal === 0 ? 'No records' : ('Page ' . $activityLogsPage . ' of ' . $activityLogsTotalPages . ' - ' . $activityLogsTotal . ' records') ?>
                </span>
                <a
                    href="<?= htmlspecialchars($paginationBase . '&page=' . min($activityLogsTotalPages, $activityLogsPage + 1), ENT_QUOTES, 'UTF-8') ?>"
                    class="teacher-link-button <?= $activityLogsPage >= $activityLogsTotalPages ? 'pointer-events-none opacity-45' : '' ?>"
                >
                    Next
                </a>
            </div>
        </section>
    </section>
</main>

<div class="modal fade" id="teacher-activity-export-modal" tabindex="-1" aria-labelledby="teacher-activity-export-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
            <input type="hidden" name="c" value="activity-logs">
            <input type="hidden" name="export" value="csv">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Export Records</p>
                    <h2 class="modal-title mt-2 text-2xl font-bold" id="teacher-activity-export-modal-title">Choose date range</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-5 pb-5 pt-2">
                <p class="text-sm font-semibold leading-6 text-arcade-ink/65">Export room and challenge creation records as CSV for the exact date range you choose.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="teacher-export-date-field">
                        <span>Start Date</span>
                        <input type="date" name="export_start_date" value="<?= htmlspecialchars($analyticsStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($analyticsEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label class="teacher-export-date-field">
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
    if (window.lucide) {
        window.lucide.createIcons();
    } else {
        window.addEventListener('load', () => window.lucide?.createIcons());
    }

    const canvas = document.getElementById('teacher-activity-chart');
    if (!canvas || typeof window.Chart === 'undefined') {
        return;
    }

    const context = canvas.getContext('2d');
    if (!context) {
        return;
    }

    const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
    const challengeGradient = context.createLinearGradient(0, 0, 0, canvas.height || 220);
    challengeGradient.addColorStop(0, isDarkMode ? 'rgba(255, 140, 66, 0.42)' : 'rgba(255, 140, 66, 0.28)');
    challengeGradient.addColorStop(1, 'rgba(255, 140, 66, 0)');
    const roomGradient = context.createLinearGradient(0, 0, 0, canvas.height || 220);
    roomGradient.addColorStop(0, isDarkMode ? 'rgba(76, 201, 240, 0.36)' : 'rgba(76, 201, 240, 0.24)');
    roomGradient.addColorStop(1, 'rgba(76, 201, 240, 0)');

    new window.Chart(context, {
        type: 'line',
        data: {
            labels: <?= json_encode($teacherChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Challenges created',
                    data: <?= json_encode($teacherChallengeValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                    fill: true,
                    backgroundColor: challengeGradient,
                    borderColor: '#ff8c42',
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    pointHoverBackgroundColor: '#ffd166',
                    pointHoverBorderColor: '#26190f',
                    tension: 0.3,
                },
                {
                    label: 'Rooms created',
                    data: <?= json_encode($teacherRoomValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                    fill: true,
                    backgroundColor: roomGradient,
                    borderColor: '#4cc9f0',
                    borderWidth: 3,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBorderWidth: 2,
                    pointHoverBackgroundColor: '#8bd3c7',
                    pointHoverBorderColor: '#26190f',
                    tension: 0.3,
                }
            ],
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
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        color: isDarkMode ? '#fff7e8' : '#26190f',
                        font: {
                            weight: '800',
                        },
                    },
                },
                tooltip: {
                    backgroundColor: isDarkMode ? '#1f160f' : '#fffdf6',
                    titleColor: isDarkMode ? '#fff7e8' : '#26190f',
                    bodyColor: isDarkMode ? '#fff7e8' : '#26190f',
                    borderColor: '#26190f',
                    borderWidth: 2,
                    padding: 12,
                    titleFont: { weight: '800' },
                    bodyFont: { weight: '700' },
                },
            },
            scales: {
                x: {
                    ticks: {
                        color: isDarkMode ? 'rgba(255,247,232,0.72)' : 'rgba(38,25,15,0.58)',
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
                        color: isDarkMode ? 'rgba(255,247,232,0.72)' : 'rgba(38,25,15,0.58)',
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
})();
</script>
