<?php
$studentViewId = max(0, (int) ($_GET['id'] ?? 0));
$studentViewProfile = $userRepository instanceof UserRepository
    ? $userRepository->findSessionUser($studentViewId)
    : null;
$studentViewDetails = $studentViewId > 0 && $userRepository instanceof UserRepository
    ? $userRepository->findUserDetailsAvatar($studentViewId)
    : null;
$studentLogPage = max(1, (int) ($_GET['log_page'] ?? 1));
$studentLogsPerPage = 20;
$studentLogs = $activityLogRepository instanceof ActivityLogRepository
    ? $activityLogRepository->listLatestForUser($studentViewId, 200)
    : [];
$studentLogTotal = count($studentLogs);
$studentLogPages = max(1, (int) ceil($studentLogTotal / $studentLogsPerPage));
$studentLogPage = min($studentLogPage, $studentLogPages);
$studentLogOffset = ($studentLogPage - 1) * $studentLogsPerPage;
$studentLogRows = array_slice($studentLogs, $studentLogOffset, $studentLogsPerPage);

$analyticsTrackedDays = 235;
$currentYear = (int) date('Y');
$yearStart = new DateTimeImmutable($currentYear . '-01-01');
$analyticsEndDate = $yearStart->modify('+' . ($analyticsTrackedDays - 1) . ' days');
$completedCountsByDate = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->completedCountsByDate($studentViewId, $yearStart, $analyticsEndDate)
    : [];
$totalSolveCount = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->countCompletedForUser($studentViewId)
    : 0;
$totalPoints = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->totalCompletedPointsForUser($studentViewId)
    : 0;
$rankRequirementPoints = 500;
$rankProgressPercent = min(100, (int) round(($totalPoints / max(1, $rankRequirementPoints)) * 100));
$activityDays = [];
$studentChartLabels = [];
$studentChartValues = [];

for ($dayIndex = 0; $dayIndex < $analyticsTrackedDays; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $solves = (int) ($completedCountsByDate[$dateKey] ?? 0);
    $activityDays[] = [
        'date' => $date,
        'solves' => $solves,
        'level' => min($solves, 5),
    ];
    $studentChartLabels[] = $date->format('M j');
    $studentChartValues[] = $solves;
}

$studentFirstname = trim((string) ($studentViewProfile['firstname'] ?? ''));
$studentLastname = trim((string) ($studentViewProfile['lastname'] ?? ''));
$studentDisplayName = trim($studentFirstname . ' ' . $studentLastname) ?: trim((string) ($studentViewProfile['username'] ?? 'Student'));
$studentAvatarUrl = trim((string) ($studentViewProfile['avatar_url'] ?? ($studentViewDetails['avatar_url'] ?? '')));
$studentInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $studentDisplayName) ?: 'ST', 0, 2));
$studentEmail = trim((string) ($studentViewProfile['email'] ?? ''));
$studentUsername = trim((string) ($studentViewProfile['username'] ?? ''));
$studentNumber = trim((string) ($studentViewDetails['student_number'] ?? ''));
$studentIdPictureUrl = trim((string) ($studentViewDetails['id_picture_url'] ?? ''));
$studentJoinedAt = trim((string) ($studentLogs[0]['date_created'] ?? ($studentViewProfile['registration_date'] ?? '')));
$studentActiveState = (int) ($studentViewProfile['is_active'] ?? 0);
$studentStatusLabel = $studentActiveState === 1 ? 'Verified' : ($studentActiveState === -1 ? 'Rejected' : 'Pending');
$studentStatusClass = $studentActiveState === 1 ? 'bg-arcade-mint/35' : ($studentActiveState === -1 ? 'bg-arcade-coral/35' : 'bg-arcade-yellow/35');
$dummyRank = 'Beginner';

$studentViewBuildQuery = static function (array $overrides = []) use ($studentViewId, $studentLogPage): string {
    $query = [
        'c' => 'student-view',
        'id' => $studentViewId,
        'log_page' => $studentLogPage,
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
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <a href="./?c=students" class="teacher-button teacher-button--light gap-2 w-fit no-underline">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            <span>Back to Students</span>
        </a>

        <?php if ($studentViewProfile === null) : ?>
            <section class="teacher-panel p-6">
                <h1 class="text-2xl font-bold">Student not found</h1>
                <p class="mt-2 text-sm font-medium leading-7 text-arcade-ink/62">The requested student profile is unavailable or has been removed.</p>
            </section>
        <?php else : ?>
            <section class="student-view-shell grid gap-5 xl:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
                <aside class="teacher-panel min-w-0 p-5 md:p-6">
                    <div class="flex flex-col items-start gap-4 sm:flex-row">
                        <span class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-[24px] border-4 border-arcade-ink bg-arcade-yellow font-arcade text-xl text-arcade-ink shadow-[6px_6px_0_rgba(38,25,15,0.18)]">
                            <?php if ($studentAvatarUrl !== '') : ?>
                                <img src="<?= htmlspecialchars($studentAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                            <?php else : ?>
                                <?= htmlspecialchars($studentInitials, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <div class="min-w-0 w-full">
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Student Profile</p>
                            <h1 class="mt-1 break-words text-3xl font-bold leading-tight"><?= htmlspecialchars($studentDisplayName, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="mt-2 break-all text-sm font-medium text-arcade-ink/60">@<?= htmlspecialchars($studentUsername, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="break-all text-sm font-medium text-arcade-ink/60"><?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="teacher-pill <?= htmlspecialchars($studentStatusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($studentStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="teacher-pill bg-arcade-cyan/25"><?= (int) ($studentViewProfile['is_verified'] ?? 0) === 1 ? 'Email verified' : 'Email pending' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student ID</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= htmlspecialchars($studentNumber !== '' ? $studentNumber : 'Not assigned yet', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Joined</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= htmlspecialchars(date('M j, Y', strtotime((string) ($studentViewProfile['registration_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-[22px] border border-arcade-ink/10 bg-white/80 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Rank Progress</p>
                                <h2 class="mt-1 text-xl font-bold"><?= htmlspecialchars($dummyRank, ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <span class="teacher-pill bg-arcade-yellow/35"><?= (int) $totalPoints ?> pts</span>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full border border-arcade-ink/10 bg-arcade-cream">
                            <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $rankProgressPercent ?>%;"></span>
                        </div>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55"><?= (int) $totalPoints ?> / <?= (int) $rankRequirementPoints ?> points</p>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <article class="teacher-panel min-w-0 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Total Solve</p>
                            <strong class="mt-1 block text-2xl font-bold"><?= (int) $totalSolveCount ?></strong>
                        </article>
                        <article class="teacher-panel min-w-0 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Points</p>
                            <strong class="mt-1 block text-2xl font-bold"><?= (int) $totalPoints ?></strong>
                        </article>
                    </div>

                    <div class="mt-5 rounded-[22px] border border-arcade-ink/10 bg-white/80 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Submitted ID</p>
                            <?php if ($studentIdPictureUrl !== '') : ?>
                                <button type="button" class="teacher-link-button" data-bs-toggle="modal" data-bs-target="#admin-student-id-modal">Open</button>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-arcade-cream/70">
                            <div class="grid min-h-[12rem] place-items-center p-3">
                                <?php if ($studentIdPictureUrl !== '') : ?>
                                    <img src="<?= htmlspecialchars($studentIdPictureUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Student ID preview" class="max-h-[16rem] w-full object-contain">
                                <?php else : ?>
                                    <span class="px-3 text-center text-xs font-semibold leading-5 text-arcade-ink/45">No ID picture uploaded.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="grid min-w-0 gap-5">
                    <article class="teacher-panel min-w-0 p-5 md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Solving Analytics</p>
                                <h2 class="mt-1 text-2xl font-bold"><?= (int) $currentYear ?> activity</h2>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-arcade-ink/55"><?= (int) $totalSolveCount ?> total solve<?= $totalSolveCount === 1 ? '' : 's' ?></p>
                                <a href="./?c=student-submissions&id=<?= (int) $studentViewId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                    <i data-lucide="list-checks" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>View Submissions</span>
                                </a>
                            </div>
                        </div>

                        <div class="admin-student-chart-shell mt-4" aria-label="<?= (int) $currentYear ?> student solving chart">
                            <div class="admin-student-chart-stage">
                                <canvas id="admin-student-activity-chart" aria-label="<?= (int) $currentYear ?> student solving analytics chart"></canvas>
                            </div>
                        </div>
                    </article>

                    <article class="teacher-panel min-w-0 p-5 md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Student Logs</p>
                                <h2 class="mt-1 text-2xl font-bold">Latest activity</h2>
                            </div>
                            <p class="text-sm font-medium text-arcade-ink/55"><?= (int) $studentLogTotal ?> record<?= $studentLogTotal === 1 ? '' : 's' ?></p>
                        </div>

                        <div class="mt-5 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-white/80">
                            <?php if ($studentLogRows === []) : ?>
                                <div class="px-4 py-5 text-sm font-medium text-arcade-ink/55">No activity logs recorded yet.</div>
                            <?php else : ?>
                                <div class="max-h-[34rem] overflow-auto">
                                    <table class="min-w-[42rem] w-full text-left text-sm">
                                        <thead class="sticky top-0 bg-white/95">
                                            <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                                <th class="px-4 py-3 font-semibold">Date</th>
                                                <th class="px-4 py-3 font-semibold">Category</th>
                                                <th class="px-4 py-3 font-semibold">Log</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($studentLogRows as $logRow) : ?>
                                                <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-arcade-ink/65"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) $logRow['date_created'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="px-4 py-3">
                                                        <span class="teacher-pill bg-arcade-peach/60"><?= htmlspecialchars(ucfirst((string) ($logRow['category'] ?? 'general')), ENT_QUOTES, 'UTF-8') ?></span>
                                                    </td>
                                                    <td class="px-4 py-3 leading-6 text-arcade-ink/75"><?= htmlspecialchars((string) ($logRow['log_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 border-t border-arcade-ink/10 pt-4 md:flex-row md:items-center md:justify-between">
                            <p class="text-sm font-medium text-arcade-ink/55">
                                Page <?= (int) $studentLogPage ?> of <?= (int) $studentLogPages ?> · <?= (int) $studentLogTotal ?> record<?= $studentLogTotal === 1 ? '' : 's' ?>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars($studentViewBuildQuery(['log_page' => max(1, $studentLogPage - 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $studentLogPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                                    <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>Previous</span>
                                </a>
                                <a href="<?= htmlspecialchars($studentViewBuildQuery(['log_page' => min($studentLogPages, $studentLogPage + 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $studentLogPage >= $studentLogPages ? 'pointer-events-none opacity-50' : '' ?>">
                                    <span>Next</span>
                                    <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </section>
            </section>
        <?php endif; ?>
    </section>
</main>

<?php if ($studentViewProfile !== null && $studentIdPictureUrl !== '') : ?>
    <div class="modal fade" id="admin-student-id-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.18)]">
                <div class="flex items-center justify-between gap-3 border-b border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student ID Preview</p>
                        <h2 class="mt-1 text-lg font-bold"><?= htmlspecialchars($studentDisplayName, ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink transition hover:bg-arcade-yellow/35" data-bs-dismiss="modal" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <div class="overflow-hidden rounded-[22px] border border-arcade-ink/10 bg-white">
                        <img src="<?= htmlspecialchars($studentIdPictureUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Student ID preview" class="max-h-[70vh] w-full object-contain p-3">
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.student-view-shell,
.student-view-shell > *,
.student-view-shell article,
.student-view-shell aside {
    min-width: 0;
}

.admin-student-chart-shell {
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.78);
    padding: 1rem;
}

.admin-student-chart-stage {
    position: relative;
    width: 100%;
    min-height: 21rem;
}

body.pixelwar-dark-mode .admin-student-chart-shell {
    border-color: rgba(148, 163, 184, 0.16);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.82) 100%);
}
</style>

<script>
(() => {
    const initializePage = () => {
        window.lucide?.createIcons();

        const chartElement = document.getElementById('admin-student-activity-chart');
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
        const solveGradient = chartContext.createLinearGradient(0, 0, 0, canvas.height || 220);
        solveGradient.addColorStop(0, isDarkMode ? 'rgba(245, 158, 11, 0.34)' : 'rgba(245, 158, 11, 0.22)');
        solveGradient.addColorStop(1, 'rgba(245, 158, 11, 0)');

        new window.Chart(chartElement, {
            type: 'line',
            data: {
                labels: <?= json_encode($studentChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                datasets: [
                    {
                        label: 'Solved challenges',
                        data: <?= json_encode($studentChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                        borderColor: '#f59e0b',
                        backgroundColor: solveGradient,
                        fill: true,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#f59e0b',
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
    };

    if (window.lucide && typeof window.Chart !== 'undefined') {
        initializePage();
        return;
    }

    window.addEventListener('load', initializePage, { once: true });
})();
</script>
