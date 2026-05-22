<?php
$teacherViewId = max(0, (int) ($_GET['id'] ?? 0));
$teacherViewProfile = $userRepository instanceof UserRepository
    ? $userRepository->findSessionUser($teacherViewId)
    : null;
$teacherViewDetails = $teacherViewId > 0 && $userRepository instanceof UserRepository
    ? $userRepository->findUserDetailsAvatar($teacherViewId)
    : null;
$teacherLogPage = max(1, (int) ($_GET['log_page'] ?? 1));
$teacherLogsPerPage = 20;
$teacherLogs = $activityLogRepository instanceof ActivityLogRepository
    ? $activityLogRepository->listLatestForUser($teacherViewId, 200)
    : [];
$teacherLogTotal = count($teacherLogs);
$teacherLogPages = max(1, (int) ceil($teacherLogTotal / $teacherLogsPerPage));
$teacherLogPage = min($teacherLogPage, $teacherLogPages);
$teacherLogOffset = ($teacherLogPage - 1) * $teacherLogsPerPage;
$teacherLogRows = array_slice($teacherLogs, $teacherLogOffset, $teacherLogsPerPage);
$currentYear = (int) date('Y');
$yearStart = new DateTimeImmutable($currentYear . '-01-01');
$yearEnd = new DateTimeImmutable($currentYear . '-12-31');
$activityCounts = $activityLogRepository instanceof ActivityLogRepository && $teacherViewId > 0
    ? $activityLogRepository->countByDayAndCategory($teacherViewId, $currentYear)
    : [];
$teacherChallengeCreatedCount = $activityLogRepository instanceof ActivityLogRepository && $teacherViewId > 0
    ? $activityLogRepository->countForUserByCategory($teacherViewId, 'challenge', 'Created challenge')
    : 0;
$teacherRoomCreatedCount = $activityLogRepository instanceof ActivityLogRepository && $teacherViewId > 0
    ? $activityLogRepository->countForUserByCategory($teacherViewId, 'room')
    : 0;
$teacherActivityDays = [];
$teacherChartLabels = [];
$teacherChallengeValues = [];
$teacherRoomValues = [];

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
    $teacherChartLabels[] = $date->format('M j');
    $teacherChallengeValues[] = $challengeCreated;
    $teacherRoomValues[] = $roomCreated;
}

$teacherFirstname = trim((string) ($teacherViewProfile['firstname'] ?? ''));
$teacherLastname = trim((string) ($teacherViewProfile['lastname'] ?? ''));
$teacherDisplayName = trim($teacherFirstname . ' ' . $teacherLastname) ?: trim((string) ($teacherViewProfile['username'] ?? 'Teacher'));
$teacherAvatarUrl = trim((string) ($teacherViewProfile['avatar_url'] ?? ($teacherViewDetails['avatar_url'] ?? '')));
$teacherInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $teacherDisplayName) ?: 'TR', 0, 2));
$teacherEmail = trim((string) ($teacherViewProfile['email'] ?? ''));
$teacherUsername = trim((string) ($teacherViewProfile['username'] ?? ''));
$teacherRegisteredAt = trim((string) ($teacherViewProfile['registration_date'] ?? ''));
$teacherVerified = (int) ($teacherViewProfile['is_verified'] ?? 0) === 1;
$teacherHasProfile = $teacherViewDetails !== null;
$teacherStatusLabel = $teacherVerified
    ? 'Profile ready'
    : ($teacherHasProfile ? 'Not set' : 'Pending setup');
$teacherStatusClass = $teacherVerified
    ? 'bg-arcade-mint/35'
    : ($teacherHasProfile ? 'bg-arcade-cyan/25' : 'bg-arcade-yellow/35');

$teacherViewBuildQuery = static function (array $overrides = []) use ($teacherViewId, $teacherLogPage): string {
    $query = [
        'c' => 'teacher-view',
        'id' => $teacherViewId,
        'log_page' => $teacherLogPage,
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
        <a href="./?c=teachers" class="teacher-button teacher-button--light gap-2 w-fit no-underline">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            <span>Back to Teachers</span>
        </a>

        <?php if ($teacherViewProfile === null || (int) ($teacherViewProfile['role_id'] ?? 0) !== 2) : ?>
            <section class="teacher-panel p-6">
                <h1 class="text-2xl font-bold">Teacher not found</h1>
                <p class="mt-2 text-sm font-medium leading-7 text-arcade-ink/62">The requested teacher profile is unavailable or has been removed.</p>
            </section>
        <?php else : ?>
            <section class="student-view-shell grid gap-5 xl:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
                <aside class="teacher-panel min-w-0 p-5 md:p-6">
                    <div class="flex flex-col items-start gap-4 sm:flex-row">
                        <span class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-[24px] border-4 border-arcade-ink bg-arcade-yellow font-arcade text-xl text-arcade-ink shadow-[6px_6px_0_rgba(38,25,15,0.18)]">
                            <?php if ($teacherAvatarUrl !== '') : ?>
                                <img src="<?= htmlspecialchars($teacherAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                            <?php else : ?>
                                <?= htmlspecialchars($teacherInitials, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <div class="min-w-0 w-full">
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Teacher Profile</p>
                            <h1 class="mt-1 break-words text-3xl font-bold leading-tight"><?= htmlspecialchars($teacherDisplayName, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="mt-2 break-all text-sm font-medium text-arcade-ink/60">@<?= htmlspecialchars($teacherUsername, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="break-all text-sm font-medium text-arcade-ink/60"><?= htmlspecialchars($teacherEmail, ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="teacher-pill <?= htmlspecialchars($teacherStatusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($teacherStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="teacher-pill <?= $teacherHasProfile ? 'bg-arcade-cyan/25' : 'bg-white' ?>"><?= $teacherHasProfile ? 'Profile ready' : 'No profile yet' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Role</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink">Teacher</p>
                        </div>
                        <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Joined</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= htmlspecialchars(date('M j, Y', strtotime($teacherRegisteredAt !== '' ? $teacherRegisteredAt : 'now')), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Challenges Created</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= (int) $teacherChallengeCreatedCount ?></p>
                        </div>
                        <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Rooms Created</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= (int) $teacherRoomCreatedCount ?></p>
                        </div>
                    </div>
                </aside>

                <section class="grid min-w-0 gap-5">
                    <article class="teacher-panel min-w-0 p-5 md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Account Snapshot</p>
                                <h2 class="mt-1 text-2xl font-bold">Teacher account data</h2>
                            </div>
                            <p class="text-sm font-medium text-arcade-ink/55"><?= (int) $teacherLogTotal ?> recent log<?= $teacherLogTotal === 1 ? '' : 's' ?></p>
                        </div>

                        <div class="mt-5 grid gap-3 lg:grid-cols-2">
                            <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Full name</p>
                                <p class="mt-1 break-words text-sm font-semibold text-arcade-ink"><?= htmlspecialchars($teacherDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Username</p>
                                <p class="mt-1 break-all text-sm font-semibold text-arcade-ink"><?= htmlspecialchars($teacherUsername, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-4 lg:col-span-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Email</p>
                                <p class="mt-1 break-all text-sm font-semibold text-arcade-ink"><?= htmlspecialchars($teacherEmail, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    </article>

                    <article class="teacher-panel min-w-0 p-5 md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Analytics</p>
                                <h2 class="mt-1 text-2xl font-bold">Teacher Creation Activity</h2>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-arcade-ink/58"><?= (int) $currentYear ?> Activity</p>
                                <a href="./?c=teacher-activity&id=<?= (int) $teacherViewId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                    <i data-lucide="activity" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>View Records</span>
                                </a>
                            </div>
                        </div>

                        <div class="admin-teacher-chart-shell mt-4" aria-label="<?= (int) $currentYear ?> teacher activity chart">
                            <div class="admin-teacher-chart-stage">
                                <canvas id="admin-teacher-activity-chart" aria-label="<?= (int) $currentYear ?> teacher creation chart"></canvas>
                            </div>
                        </div>
                    </article>

                    <article class="teacher-panel min-w-0 p-5 md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Recent Activity</p>
                                <h2 class="mt-1 text-2xl font-bold">Activity logs</h2>
                            </div>
                            <p class="text-sm font-medium text-arcade-ink/55">Latest account records</p>
                        </div>

                        <div class="mt-5 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-white/80">
                            <?php if ($teacherLogRows === []) : ?>
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
                                            <?php foreach ($teacherLogRows as $logRow) : ?>
                                                <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-arcade-ink/65"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($logRow['date_created'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
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
                                Page <?= (int) $teacherLogPage ?> of <?= (int) $teacherLogPages ?> · <?= (int) $teacherLogTotal ?> record<?= $teacherLogTotal === 1 ? '' : 's' ?>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars($teacherViewBuildQuery(['log_page' => max(1, $teacherLogPage - 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teacherLogPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                                    <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>Previous</span>
                                </a>
                                <a href="<?= htmlspecialchars($teacherViewBuildQuery(['log_page' => min($teacherLogPages, $teacherLogPage + 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teacherLogPage >= $teacherLogPages ? 'pointer-events-none opacity-50' : '' ?>">
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

<style>
.admin-teacher-chart-shell {
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.78);
    padding: 1rem;
}

.admin-teacher-chart-stage {
    position: relative;
    height: 18rem;
    width: 100%;
}

body.pixelwar-dark-mode .admin-teacher-chart-shell {
    border-color: rgba(148, 163, 184, 0.14);
    background: rgba(15, 23, 42, 0.42);
}
</style>

<script>
(() => {
    if (window.lucide) {
        window.lucide.createIcons();
    } else {
        window.addEventListener('load', () => window.lucide?.createIcons());
    }

    const chartElement = document.getElementById('admin-teacher-activity-chart');
    if (!chartElement || typeof window.Chart === 'undefined') {
        return;
    }

    const rootStyles = getComputedStyle(document.body);
    const textColor = rootStyles.getPropertyValue('--admin-text').trim() || '#111827';
    const mutedColor = rootStyles.getPropertyValue('--admin-text-muted').trim() || '#6b7280';
    const gridColor = document.body.classList.contains('pixelwar-dark-mode')
        ? 'rgba(148, 163, 184, 0.14)'
        : 'rgba(17, 24, 39, 0.08)';

    const chartContext = chartElement.getContext('2d');
    if (!chartContext) {
        return;
    }

    new window.Chart(chartContext, {
        type: 'line',
        data: {
            labels: <?= json_encode($teacherChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Challenges',
                    data: <?= json_encode($teacherChallengeValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.12)',
                    fill: false,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: '#f59e0b',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    tension: 0.35,
                },
                {
                    label: 'Rooms',
                    data: <?= json_encode($teacherRoomValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.12)',
                    fill: false,
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
                    backgroundColor: document.body.classList.contains('pixelwar-dark-mode') ? '#0f172a' : '#ffffff',
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
})();
</script>
