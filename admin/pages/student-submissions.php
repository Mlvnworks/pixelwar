<?php
$studentSubmissionId = max(0, (int) ($_GET['id'] ?? 0));
$studentSubmissionProfile = $userRepository instanceof UserRepository
    ? $userRepository->findSessionUser($studentSubmissionId)
    : null;
$studentSubmissionPage = max(1, (int) ($_GET['page'] ?? 1));
$studentSubmissionPerPage = 15;
$studentSubmissionRangeOptions = [7, 30, 365];
$studentSubmissionRangeDays = (int) ($_GET['range'] ?? 30);
if (!in_array($studentSubmissionRangeDays, $studentSubmissionRangeOptions, true)) {
    $studentSubmissionRangeDays = 30;
}

$studentSubmissionAnalyticsEndDate = new DateTimeImmutable('today');
$studentSubmissionAnalyticsStartDate = $studentSubmissionAnalyticsEndDate->modify('-' . ($studentSubmissionRangeDays - 1) . ' days');
$studentSubmissionAttemptCounts = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->attemptCountsByDate($studentSubmissionId, $studentSubmissionAnalyticsStartDate, $studentSubmissionAnalyticsEndDate)
    : [];
$studentSubmissionRows = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->listAttemptHistory($studentSubmissionId, 500)
    : [];
$studentSubmissionTotal = count($studentSubmissionRows);
$studentSubmissionPages = max(1, (int) ceil($studentSubmissionTotal / $studentSubmissionPerPage));
$studentSubmissionPage = min($studentSubmissionPage, $studentSubmissionPages);
$studentSubmissionOffset = ($studentSubmissionPage - 1) * $studentSubmissionPerPage;
$studentSubmissionPageRows = array_slice($studentSubmissionRows, $studentSubmissionOffset, $studentSubmissionPerPage);

$studentSubmissionName = trim((string) ($studentSubmissionProfile['firstname'] ?? '') . ' ' . (string) ($studentSubmissionProfile['lastname'] ?? ''));
$studentSubmissionName = $studentSubmissionName !== '' ? $studentSubmissionName : trim((string) ($studentSubmissionProfile['username'] ?? 'Student'));

$studentSubmissionChartLabels = [];
$studentSubmissionChartValues = [];

for ($dayIndex = 0; $dayIndex < $studentSubmissionRangeDays; $dayIndex++) {
    $date = $studentSubmissionAnalyticsStartDate->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $studentSubmissionChartLabels[] = $date->format('M j');
    $studentSubmissionChartValues[] = (int) ($studentSubmissionAttemptCounts[$dateKey] ?? 0);
}

$studentSubmissionBuildQuery = static function (array $overrides = []) use ($studentSubmissionId, $studentSubmissionPage, $studentSubmissionRangeDays): string {
    $query = [
        'c' => 'student-submissions',
        'id' => $studentSubmissionId,
        'page' => $studentSubmissionPage,
        'range' => $studentSubmissionRangeDays,
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }

        $query[$key] = $value;
    }

    return './?' . http_build_query($query);
};

$formatDuration = static function (?string $startedAt, ?string $completedAt, string $attemptStatus = 'ongoing'): string {
    if ($attemptStatus === 'gave_up') {
        return 'Gave Up';
    }

    if (!$startedAt || !$completedAt) {
        return 'In progress';
    }

    try {
        $start = new DateTimeImmutable($startedAt);
        $end = new DateTimeImmutable($completedAt);
        $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());
    } catch (Throwable) {
        return 'Unavailable';
    }

    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;
    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0) {
        $parts[] = $minutes . 'm';
    }
    if ($remainingSeconds > 0 || $parts === []) {
        $parts[] = $remainingSeconds . 's';
    }

    return implode(' ', $parts);
};

$formatSubmissionType = static function (array $row): string {
    if ((int) ($row['pvp_id'] ?? 0) > 0) {
        return '1v1';
    }

    if ((int) ($row['room_id'] ?? 0) > 0) {
        return 'room';
    }

    return 'solo';
};

$formatSubmissionOutcome = static function (string $attemptStatus): array {
    return match ($attemptStatus) {
        'pvp_win' => ['Win', 'bg-arcade-cyan/35'],
        'pvp_loss' => ['Loss', 'bg-arcade-coral/25'],
        'completed' => ['Done', 'bg-arcade-mint/35'],
        'gave_up' => ['Failed', 'bg-arcade-coral/25'],
        default => ['In progress', 'bg-arcade-yellow/35'],
    };
};
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <?php if ($studentSubmissionProfile === null) : ?>
            <article class="teacher-panel p-5 md:p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-coral">Missing Student</p>
                <h1 class="mt-3 text-3xl font-bold">Student not found.</h1>
                <p class="mt-2 text-sm font-medium leading-7 text-arcade-ink/62">The student account may have been removed or the link is invalid.</p>
                <a href="./?c=students" class="teacher-button teacher-button--light mt-4 w-fit no-underline">Back to Students</a>
            </article>
        <?php else : ?>
            <article class="teacher-panel p-5 md:p-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Student Submissions</p>
                        <h1 class="mt-2 text-3xl font-bold leading-tight md:text-4xl"><?= htmlspecialchars($studentSubmissionName, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="mt-2 text-sm font-medium leading-7 text-arcade-ink/65">
                            Review the student's challenge attempts, outcome type, and awarded points.
                        </p>
                    </div>
                    <a href="./?c=student-view&id=<?= (int) $studentSubmissionId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Back to Student</span>
                    </a>
                </div>
            </article>

            <article class="teacher-panel p-5 md:p-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Analytics</p>
                            <h2 class="mt-1 text-2xl font-bold">Submission Timeline</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php foreach ($studentSubmissionRangeOptions as $rangeOption) : ?>
                                <a
                                    href="<?= htmlspecialchars($studentSubmissionBuildQuery(['range' => $rangeOption, 'page' => 1]), ENT_QUOTES, 'UTF-8') ?>"
                                    class="admin-range-chip <?= $studentSubmissionRangeDays === $rangeOption ? 'admin-range-chip--active' : '' ?>"
                                >
                                    Last <?= (int) $rangeOption ?> Days
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="admin-student-submission-chart-shell">
                        <div class="admin-student-submission-chart-stage">
                            <canvas id="admin-student-submission-chart" aria-label="Student submission timeline chart"></canvas>
                        </div>
                    </div>
                </div>
            </article>

            <section class="teacher-panel p-0 overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-arcade-ink/10 px-5 py-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Records</p>
                        <h2 class="mt-1 text-2xl font-bold">Submission outcomes</h2>
                    </div>
                    <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#admin-student-submissions-export-modal">
                        <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </button>
                </div>

                <?php if ($studentSubmissionPageRows === []) : ?>
                    <div class="px-5 py-6 text-sm font-medium text-arcade-ink/55">
                        No challenge submissions recorded yet for this student.
                    </div>
                <?php else : ?>
                    <div class="max-h-[42rem] overflow-auto">
                        <table class="min-w-[72rem] w-full text-left text-sm">
                            <thead class="sticky top-0 z-[1] bg-white/95">
                                <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                    <th class="px-4 py-3 font-semibold">Challenge</th>
                                    <th class="px-4 py-3 font-semibold">Type</th>
                                    <th class="px-4 py-3 font-semibold">Outcome</th>
                                    <th class="px-4 py-3 font-semibold">Difficulty</th>
                                    <th class="px-4 py-3 font-semibold">Started</th>
                                    <th class="px-4 py-3 font-semibold">Duration</th>
                                    <th class="px-4 py-3 font-semibold">Awarded</th>
                                    <th class="px-4 py-3 font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentSubmissionPageRows as $row) : ?>
                                    <?php
                                    $attemptStatus = (string) ($row['attempt_status'] ?? (trim((string) ($row['completed_at'] ?? '')) !== '' ? 'completed' : 'ongoing'));
                                    $difficulty = ucfirst(strtolower((string) ($row['difficulty_name'] ?? 'Unknown')));
                                    $submissionType = $formatSubmissionType($row);
                                    [$outcomeLabel, $outcomeClass] = $formatSubmissionOutcome($attemptStatus);
                                    ?>
                                    <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                        <td class="px-4 py-3">
                                            <div class="min-w-0">
                                                <div class="truncate font-semibold text-arcade-ink"><?= htmlspecialchars((string) ($row['name'] ?? 'Untitled Challenge'), ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="mt-1 text-xs leading-5 text-arcade-ink/55"><?= $tools->formatExcerpt((string) ($row['instruction'] ?? '')) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="teacher-pill bg-arcade-yellow/35">
                                                <?= htmlspecialchars($submissionType, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="teacher-pill <?= $outcomeClass ?>">
                                                <?= htmlspecialchars($outcomeLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-arcade-ink/65"><?= htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-arcade-ink/65"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($row['started_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 font-semibold text-arcade-ink"><?= htmlspecialchars($formatDuration((string) ($row['started_at'] ?? ''), (string) ($row['completed_at'] ?? ''), $attemptStatus), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 font-semibold text-arcade-ink"><?= (int) ($row['awarded_points'] ?? 0) ?> pts</td>
                                        <td class="px-4 py-3">
                                            <a href="./?c=challenge-view&id=<?= (int) ($row['challenge_id'] ?? 0) ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                                <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                                <span>Open</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <div class="teacher-panel px-5 py-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm font-medium text-arcade-ink/55">
                        Page <?= (int) $studentSubmissionPage ?> of <?= (int) $studentSubmissionPages ?> · <?= (int) $studentSubmissionTotal ?> submission<?= $studentSubmissionTotal === 1 ? '' : 's' ?>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <a href="<?= htmlspecialchars($studentSubmissionBuildQuery(['page' => max(1, $studentSubmissionPage - 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $studentSubmissionPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                            <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Previous</span>
                        </a>
                        <a href="<?= htmlspecialchars($studentSubmissionBuildQuery(['page' => min($studentSubmissionPages, $studentSubmissionPage + 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $studentSubmissionPage >= $studentSubmissionPages ? 'pointer-events-none opacity-50' : '' ?>">
                            <span>Next</span>
                            <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php if ($studentSubmissionProfile !== null) : ?>
    <div class="modal fade" id="admin-student-submissions-export-modal" tabindex="-1" aria-labelledby="admin-student-submissions-export-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
                <input type="hidden" name="c" value="student-submissions">
                <input type="hidden" name="id" value="<?= (int) $studentSubmissionId ?>">
                <input type="hidden" name="export" value="csv">
                <div class="modal-header border-0 px-5 pb-2 pt-5">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Export Records</p>
                        <h2 class="modal-title mt-2 text-2xl font-bold" id="admin-student-submissions-export-modal-title">Choose date range</h2>
                    </div>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-5 pb-5 pt-2">
                    <p class="text-sm font-semibold leading-6 text-arcade-ink/65">Export student submission records as CSV for the exact date range you choose.</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="admin-export-date-field">
                            <span>Start Date</span>
                            <input type="date" name="export_start_date" value="<?= htmlspecialchars($studentSubmissionAnalyticsStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($studentSubmissionAnalyticsEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                        <label class="admin-export-date-field">
                            <span>End Date</span>
                            <input type="date" name="export_end_date" value="<?= htmlspecialchars($studentSubmissionAnalyticsEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars($studentSubmissionAnalyticsEndDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                        </label>
                    </div>
                    <div class="mt-5 flex justify-end gap-3">
                        <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">Export CSV</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<style>
.admin-range-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.6rem;
    padding: 0.65rem 1rem;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.88);
    color: #475569;
    font-size: 0.84rem;
    font-weight: 700;
    line-height: 1;
    text-decoration: none;
    transition: background-color 180ms ease, color 180ms ease, border-color 180ms ease, transform 180ms ease;
}

.admin-range-chip:hover {
    color: #0f172a;
    border-color: rgba(59, 130, 246, 0.28);
    background: rgba(255, 255, 255, 0.96);
    transform: translateY(-1px);
}

.admin-range-chip--active,
.admin-range-chip--active:hover {
    border-color: rgba(37, 99, 235, 0.2);
    background: rgba(219, 234, 254, 0.95);
    color: #1d4ed8;
}

.admin-student-submission-chart-shell {
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.78);
    padding: 1rem;
}

.admin-student-submission-chart-stage {
    position: relative;
    width: 100%;
    min-height: 20rem;
}

.admin-export-date-field {
    display: grid;
    gap: 0.45rem;
}

.admin-export-date-field span {
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.6);
}

.admin-export-date-field input {
    min-height: 3rem;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.96);
    padding: 0.8rem 0.95rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
}

body.pixelwar-dark-mode .admin-range-chip {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(15, 23, 42, 0.82);
    color: #cbd5e1;
}

body.pixelwar-dark-mode .admin-range-chip:hover {
    border-color: rgba(96, 165, 250, 0.34);
    background: rgba(15, 23, 42, 0.95);
    color: #eff6ff;
}

body.pixelwar-dark-mode .admin-range-chip--active,
body.pixelwar-dark-mode .admin-range-chip--active:hover {
    border-color: rgba(96, 165, 250, 0.28);
    background: rgba(30, 41, 59, 0.96);
    color: #93c5fd;
}

body.pixelwar-dark-mode .admin-student-submission-chart-shell {
    border-color: rgba(148, 163, 184, 0.16);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.82) 100%);
}

body.pixelwar-dark-mode .admin-export-date-field span {
    color: rgba(226, 232, 240, 0.7);
}

body.pixelwar-dark-mode .admin-export-date-field input {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(15, 23, 42, 0.92);
    color: #e2e8f0;
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const chartElement = document.getElementById('admin-student-submission-chart');
    if (!chartElement || typeof window.Chart === 'undefined') {
        return;
    }

    const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
    const textColor = isDarkMode ? '#e2e8f0' : '#0f172a';
    const mutedColor = isDarkMode ? '#94a3b8' : '#64748b';
    const gridColor = isDarkMode ? 'rgba(148, 163, 184, 0.18)' : 'rgba(15, 23, 42, 0.08)';
    const tooltipBackground = isDarkMode ? '#0f172a' : '#ffffff';
    const chartContext = chartElement.getContext('2d');

    if (!chartContext) {
        return;
    }

    const canvas = chartElement;
    const submissionGradient = chartContext.createLinearGradient(0, 0, 0, canvas.height || 220);
    submissionGradient.addColorStop(0, isDarkMode ? 'rgba(37, 99, 235, 0.32)' : 'rgba(37, 99, 235, 0.18)');
    submissionGradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

    new window.Chart(chartElement, {
        type: 'line',
        data: {
            labels: <?= json_encode($studentSubmissionChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Daily submissions',
                    data: <?= json_encode($studentSubmissionChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: submissionGradient,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: '#2563eb',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    tension: 0.35,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'start',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        boxWidth: 10,
                        boxHeight: 10,
                        font: {
                            size: 12,
                            weight: '600',
                        },
                    },
                },
                tooltip: {
                    backgroundColor: tooltipBackground,
                    titleColor: textColor,
                    bodyColor: textColor,
                    borderColor: gridColor,
                    borderWidth: 1,
                    displayColors: true,
                    padding: 10,
                },
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    ticks: {
                        color: mutedColor,
                        maxTicksLimit: 12,
                        font: {
                            size: 11,
                            weight: '500',
                        },
                    },
                    grid: {
                        display: false,
                    },
                    border: {
                        color: gridColor,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: mutedColor,
                        font: {
                            size: 11,
                            weight: '500',
                        },
                    },
                    grid: {
                        color: gridColor,
                    },
                    border: {
                        color: gridColor,
                    },
                },
            },
        },
    });
});
</script>
