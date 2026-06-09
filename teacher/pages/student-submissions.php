<?php
$studentSubmissionId = max(0, (int) ($_GET['id'] ?? 0));
$studentSubmissionProfile = $userRepository instanceof UserRepository
    ? $userRepository->findSessionUser($studentSubmissionId)
    : null;
$studentSubmissionRangeOptions = [7, 30, 365];
$studentSubmissionRangeDays = isset($_GET['range']) ? (int) $_GET['range'] : 30;

if (!in_array($studentSubmissionRangeDays, $studentSubmissionRangeOptions, true)) {
    $studentSubmissionRangeDays = 30;
}

$studentSubmissionEndDate = new DateTimeImmutable('today');
$studentSubmissionStartDate = $studentSubmissionEndDate->modify('-' . ($studentSubmissionRangeDays - 1) . ' days');
$studentSubmissionCompletedCounts = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->completedCountsByDate($studentSubmissionId, $studentSubmissionStartDate, $studentSubmissionEndDate)
    : [];
$studentSubmissionAttemptRows = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->listAttemptHistory($studentSubmissionId, 500)
    : [];
$studentSubmissionRows = [];
$studentSubmissionChartLabels = [];
$studentSubmissionChartValues = [];
$studentSubmissionSearchBase = './?c=student-submissions&id=' . $studentSubmissionId;

$studentSubmissionFormatDuration = static function (int $totalSeconds): string {
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

for ($dayIndex = 0; $dayIndex < $studentSubmissionRangeDays; $dayIndex++) {
    $date = $studentSubmissionStartDate->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $studentSubmissionChartLabels[] = $date->format('M j');
    $studentSubmissionChartValues[] = (int) ($studentSubmissionCompletedCounts[$dateKey] ?? 0);
}

foreach ($studentSubmissionAttemptRows as $attemptRow) {
    $isRoomAttempt = (int) ($attemptRow['room_id'] ?? 0) > 0;
    $isPvpAttempt = (int) ($attemptRow['pvp_id'] ?? 0) > 0;
    $isStrictRoomAttempt = $isRoomAttempt && (int) ($attemptRow['room_strict_mode'] ?? 0) === 1;
    $strictModeScore = max(0, min(100, (int) ($attemptRow['strict_mode_score'] ?? 0)));
    $attemptStatus = (string) ($attemptRow['attempt_status'] ?? '');
    $status = match ($attemptStatus) {
        'pvp_win' => 'completed',
        'pvp_loss' => 'failed',
        default => (!empty($attemptRow['completed_at'])
            ? 'completed'
            : ($isRoomAttempt ? 'failed' : 'ongoing')),
    };
    $modeLabel = $isPvpAttempt ? '1v1' : ($isRoomAttempt ? 'Room' : 'Solo');
    $startedAt = new DateTimeImmutable((string) $attemptRow['started_at']);
    $completedAt = !empty($attemptRow['completed_at'])
        ? new DateTimeImmutable((string) $attemptRow['completed_at'])
        : null;
    $durationLabel = $status === 'failed' ? 'Failed' : 'Ongoing';
    $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A');
    $statusBadgeLabel = match ($attemptStatus) {
        'pvp_win' => 'Win',
        'pvp_loss' => 'Loss',
        default => ucfirst($status),
    };

    if ($completedAt instanceof DateTimeImmutable) {
        $durationSeconds = max(0, $completedAt->getTimestamp() - $startedAt->getTimestamp());
        $durationLabel = $studentSubmissionFormatDuration($durationSeconds);
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - Completed in ' . $durationLabel;
    } elseif ($attemptStatus === 'pvp_loss') {
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - 1v1 duel result recorded as a loss.';
    } elseif ($attemptStatus === 'pvp_win') {
        $durationLabel = 'Win';
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - 1v1 duel result recorded as a win.';
    } elseif ($status === 'failed') {
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - This room run was not completed.';
    } else {
        $ongoingSeconds = max(0, time() - $startedAt->getTimestamp());
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - Running for ' . $studentSubmissionFormatDuration($ongoingSeconds);
    }

    if ($isStrictRoomAttempt && $status !== 'ongoing') {
        $statusBadgeLabel = $strictModeScore . '%';
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A')
            . ($completedAt instanceof DateTimeImmutable
                ? ' - Completed in ' . $durationLabel
                : ' - Strict mode result recorded at ' . $strictModeScore . '%.');
    }

    $studentSubmissionRows[] = [
        'challengeId' => (int) $attemptRow['challenge_id'],
        'title' => (string) $attemptRow['name'],
        'status' => $status,
        'statusBadgeLabel' => $statusBadgeLabel,
        'modeLabel' => $modeLabel,
        'isStrictRoomAttempt' => $isStrictRoomAttempt,
        'strictModeScore' => $strictModeScore,
        'level' => ucfirst(strtolower((string) ($attemptRow['difficulty_name'] ?? 'Beginner'))),
        'startedAt' => $startedAt,
        'completedAt' => $completedAt,
        'duration' => $durationLabel,
        'durationDetails' => $durationDetails,
        'summary' => $status === 'completed'
            ? ($isStrictRoomAttempt
                ? 'Submitted this strict mode room challenge and recorded the final match score.'
                : ($isPvpAttempt && $attemptStatus === 'pvp_win'
                    ? 'Won this 1v1 duel challenge and locked in the match result.'
                    : 'Completed this CSS matching challenge and locked in the solve.'))
            : ($status === 'failed'
                ? ($isStrictRoomAttempt
                    ? 'Submitted this strict mode room challenge and recorded the final match score.'
                    : ($isPvpAttempt && $attemptStatus === 'pvp_loss'
                        ? 'Lost this 1v1 duel challenge and recorded the match result.'
                        : 'Started this CSS matching challenge inside a room, but the run was not completed.'))
                : 'Started this CSS matching challenge and still has an active run.'),
        'href' => $status === 'ongoing'
            ? './?c=pixelwar&intro=1&challenge_id=' . (int) $attemptRow['challenge_id'] . ($isPvpAttempt ? '&pvp_id=' . (int) ($attemptRow['pvp_id'] ?? 0) : '')
            : './?c=challenge&id=' . (int) $attemptRow['challenge_id'],
        'points' => (int) ($attemptRow['awarded_points'] ?? 0),
    ];
}

$studentSubmissionName = trim((string) (($studentSubmissionProfile['firstname'] ?? '') . ' ' . ($studentSubmissionProfile['lastname'] ?? '')));
$studentSubmissionName = $studentSubmissionName !== ''
    ? $studentSubmissionName
    : trim((string) ($studentSubmissionProfile['username'] ?? 'Student'));

$studentSubmissionPaginationBase = './?c=student-submissions&id=' . $studentSubmissionId . '&range=' . $studentSubmissionRangeDays;
$studentSubmissionSearchInput = trim((string) ($_GET['search'] ?? ''));
$studentSubmissionInitialStatusFilter = isset($_GET['status']) && in_array((string) $_GET['status'], ['all', 'completed', 'ongoing', 'failed'], true)
    ? (string) $_GET['status']
    : 'all';
?>

<main class="teacher-shell analytics-page teacher-student-submissions-page relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>

    <section class="container relative grid min-w-0 max-w-full gap-5">
        <?php if ($studentSubmissionProfile === null) : ?>
            <div class="student-submissions-hero-card mb-5 rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-coral">Missing Student</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight md:text-5xl">Student not found.</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-arcade-ink/70">The requested student profile is unavailable or has been removed.</p>
                <a href="./?c=students" class="mt-4 inline-flex items-center justify-center rounded-xl border-2 border-arcade-ink bg-white px-4 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">Back to Students</a>
            </div>
        <?php else : ?>
            <div class="student-submissions-hero-card mb-5 rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Student Submissions</p>
                        <h1 class="mt-3 text-3xl font-bold leading-tight md:text-5xl"><?= htmlspecialchars($studentSubmissionName, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="mt-2 max-w-2xl text-sm leading-7 text-arcade-ink/70">Review the student's attempt history, outcome type, and earned points.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white" data-bs-toggle="modal" data-bs-target="#student-submissions-export-modal">
                            <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Export CSV</span>
                        </button>
                        <a href="./?c=student-view&id=<?= (int) $studentSubmissionId ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-4 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
                            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Back to Student</span>
                        </a>
                    </div>
                </div>
            </div>

            <section class="student-submissions-content-card min-w-0 rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <article class="student-submissions-chart-card mb-5 rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Submission Chart</p>
                                <h2 class="mt-2 text-xl font-bold">Challenge History</h2>
                            </div>
                            <p class="text-sm font-bold text-arcade-ink/60">Last <?= (int) $studentSubmissionRangeDays ?> Days</p>
                        </div>

                        <div class="analytics-range-row">
                            <?php foreach ($studentSubmissionRangeOptions as $rangeOption) : ?>
                                <a
                                    href="<?= htmlspecialchars($studentSubmissionSearchBase . '&range=' . $rangeOption, ENT_QUOTES, 'UTF-8') ?>"
                                    class="analytics-range-chip <?= $studentSubmissionRangeDays === $rangeOption ? 'is-active' : '' ?>"
                                >
                                    Last <?= (int) $rangeOption ?> Days
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-chart-shell" aria-label="Student submission chart for the last <?= (int) $studentSubmissionRangeDays ?> days">
                            <div class="analytics-chart-summary">
                                <div class="analytics-chart-chip">
                                    <span class="analytics-chart-chip__dot"></span>
                                    <span>Daily completed challenges</span>
                                </div>
                                <p class="analytics-chart-summary__copy">Shows the student solve count day by day for the selected range.</p>
                            </div>
                            <div class="analytics-chart-stage">
                                <canvas id="student-submissions-chart" height="230"></canvas>
                            </div>
                        </div>
                    </div>
                </article>

                <div class="grid min-w-0 gap-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <label class="block">
                        <span class="text-sm font-bold">Search challenges</span>
                        <input id="student-submissions-search" type="search" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 text-sm outline-none transition focus:border-arcade-orange" placeholder="Search title, status, level, or details..." value="<?= htmlspecialchars($studentSubmissionSearchInput, ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button class="analytics-filter <?= $studentSubmissionInitialStatusFilter === 'all' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="all">All</button>
                        <button class="analytics-filter <?= $studentSubmissionInitialStatusFilter === 'completed' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="completed">Completed</button>
                        <button class="analytics-filter <?= $studentSubmissionInitialStatusFilter === 'ongoing' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="ongoing">Ongoing</button>
                        <button class="analytics-filter <?= $studentSubmissionInitialStatusFilter === 'failed' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-status-filter="failed">Failed</button>
                    </div>
                </div>

                <div class="mt-5 max-w-full overflow-x-auto overflow-y-hidden rounded-2xl border-2 border-arcade-ink/10 bg-white">
                    <?php if ($studentSubmissionRows === []) : ?>
                        <p class="px-4 py-5 text-sm font-bold text-arcade-ink/55">No challenge activity yet.</p>
                    <?php endif; ?>
                    <div class="min-w-[52rem]">
                    <?php foreach ($studentSubmissionRows as $row) : ?>
                        <article
                            class="analytics-row grid gap-2 border-b border-arcade-ink/10 px-4 py-3 last:border-b-0 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.55fr)_minmax(0,0.65fr)_minmax(0,1fr)_auto] lg:items-center"
                            data-analytics-row
                            data-search="<?= htmlspecialchars(strtolower($row['title'] . ' ' . $row['status'] . ' ' . $row['level'] . ' ' . $row['summary']), ENT_QUOTES, 'UTF-8') ?>"
                            data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="min-w-0">
                                <p class="text-sm font-bold"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs font-semibold text-arcade-ink/55"><?= htmlspecialchars($row['summary'], ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full <?= $row['modeLabel'] === '1v1' ? 'bg-arcade-cyan/30' : ($row['modeLabel'] === 'Room' ? 'bg-arcade-orange/20' : 'bg-arcade-mint/35') ?> px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-arcade-ink">
                                        <?= htmlspecialchars($row['modeLabel'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="rounded-full bg-arcade-orange/12 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-arcade-orange">
                                        +<?= (int) $row['points'] ?> pts
                                    </span>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <span class="rounded-full <?= $row['status'] === 'completed' ? 'bg-arcade-mint/70' : ($row['status'] === 'failed' ? 'bg-arcade-coral/30' : 'bg-arcade-yellow/70') ?> px-3 py-1 text-xs font-bold">
                                    <?= htmlspecialchars((string) ($row['statusBadgeLabel'] ?? ucfirst($row['status'])), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <p class="min-w-0 text-xs font-bold text-arcade-ink/60"><?= htmlspecialchars($row['level'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($row['duration'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="min-w-0 text-xs font-semibold text-arcade-ink/55"><?= htmlspecialchars($row['durationDetails'], ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                    <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <button id="student-submissions-prev" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Prev</button>
                    <span id="student-submissions-page-status" class="text-xs font-bold text-arcade-ink/60"></span>
                    <button id="student-submissions-next" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Next</button>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>

<?php if ($studentSubmissionProfile !== null) : ?>
    <div class="modal fade" id="student-submissions-export-modal" tabindex="-1" aria-labelledby="student-submissions-export-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
                <input type="hidden" name="c" value="student-submissions">
                <input type="hidden" name="id" value="<?= (int) $studentSubmissionId ?>">
                <input type="hidden" name="export" value="csv">
                <div class="modal-header border-0 px-5 pb-2 pt-5">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Export Records</p>
                        <h2 class="modal-title mt-2 text-2xl font-bold" id="student-submissions-export-modal-title">Choose date range</h2>
                    </div>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-5 pb-5 pt-2">
                    <p class="text-sm font-semibold leading-6 text-arcade-ink/65">Export this student's submission records as CSV for the selected date range.</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="export-date-field">
                            <span>Start Date</span>
                            <input type="date" name="export_start_date" value="<?= htmlspecialchars($studentSubmissionStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($studentSubmissionEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                        <label class="export-date-field">
                            <span>End Date</span>
                            <input type="date" name="export_end_date" value="<?= htmlspecialchars($studentSubmissionEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($studentSubmissionEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
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
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script>
(() => {
    const rows = Array.from(document.querySelectorAll('[data-analytics-row]'));
    const searchInput = document.getElementById('student-submissions-search');
    const filterButtons = Array.from(document.querySelectorAll('[data-status-filter]'));
    const previousButton = document.getElementById('student-submissions-prev');
    const nextButton = document.getElementById('student-submissions-next');
    const pageStatus = document.getElementById('student-submissions-page-status');
    const pageSize = 20;
    let currentPage = 1;
    let activeStatus = <?= json_encode($studentSubmissionInitialStatusFilter, JSON_UNESCAPED_SLASHES) ?>;

    const canvas = document.getElementById('student-submissions-chart');

    if (canvas && typeof window.Chart !== 'undefined') {
        const context = canvas.getContext('2d');
        if (context) {
            const labels = <?= json_encode($studentSubmissionChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
            const values = <?= json_encode($studentSubmissionChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
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
