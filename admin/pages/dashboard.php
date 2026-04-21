<?php
$adminName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$studentCount = $userRepository instanceof UserRepository ? $userRepository->countUsersByRole(3) : 0;
$studentCountToday = $userRepository instanceof UserRepository ? $userRepository->countUsersRegisteredTodayByRole(3) : 0;
$teacherCount = $userRepository instanceof UserRepository ? $userRepository->countUsersByRole(2) : 0;
$teacherCountToday = $userRepository instanceof UserRepository ? $userRepository->countUsersRegisteredTodayByRole(2) : 0;
$roomCount = 0;
$roomCountToday = 0;
$summaryCards = [
    [
        'label' => 'Students',
        'value' => $studentCount,
        'today' => $studentCountToday,
    ],
    [
        'label' => 'Teachers',
        'value' => $teacherCount,
        'today' => $teacherCountToday,
    ],
    [
        'label' => 'Rooms Created',
        'value' => $roomCount,
        'today' => $roomCountToday,
    ],
];

$chartEndDate = new DateTimeImmutable('today');
$chartStartDate = $chartEndDate->modify('-29 days');
$studentCountsByDate = $userRepository instanceof UserRepository
    ? $userRepository->countRegistrationsByDayAndRole(3, $chartStartDate, $chartEndDate)
    : [];
$teacherCountsByDate = $userRepository instanceof UserRepository
    ? $userRepository->countRegistrationsByDayAndRole(2, $chartStartDate, $chartEndDate)
    : [];
$chartLabels = [];
$studentChartValues = [];
$teacherChartValues = [];
$roomChartValues = [];
$chartPeak = 0;

for ($offset = 0; $offset < 30; $offset++) {
    $date = $chartStartDate->modify('+' . $offset . ' days');
    $dateKey = $date->format('Y-m-d');
    $studentTotal = (int) ($studentCountsByDate[$dateKey] ?? 0);
    $teacherTotal = (int) ($teacherCountsByDate[$dateKey] ?? 0);
    $roomTotal = 0;
    $chartLabels[] = $date->format('M j');
    $studentChartValues[] = $studentTotal;
    $teacherChartValues[] = $teacherTotal;
    $roomChartValues[] = $roomTotal;
    $chartPeak = max($chartPeak, $studentTotal, $teacherTotal, $roomTotal);
}

$latestLogs = $activityLogRepository instanceof ActivityLogRepository
    ? $activityLogRepository->listLatestOverall(100)
    : [];

$roleLabels = [
    1 => 'Admin',
    2 => 'Teacher',
    3 => 'Student',
];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="grid gap-2">
            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Dashboard</p>
            <h1 class="text-3xl font-bold leading-tight md:text-4xl">Welcome, <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="max-w-3xl text-sm font-medium leading-7 text-arcade-ink/65 md:text-base">
                Monitor account growth and review the latest platform activity in one place.
            </p>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <?php foreach ($summaryCards as $summaryCard) : ?>
                <article class="teacher-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-arcade-ink/60"><?= htmlspecialchars($summaryCard['label'], ENT_QUOTES, 'UTF-8') ?></p>
                            <strong class="mt-2 block text-3xl font-bold leading-none md:text-4xl"><?= (int) $summaryCard['value'] ?></strong>
                        </div>
                        <span class="teacher-pill">+<?= (int) $summaryCard['today'] ?> today</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="teacher-panel p-5 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Growth</p>
                    <h2 class="mt-1 text-2xl font-bold">Last 30 days</h2>
                    <p class="mt-1 text-sm font-medium text-arcade-ink/60">
                        Daily totals for students added, teachers added, and room creation.
                    </p>
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">
                    Peak <?= (int) $chartPeak ?> in one day
                </p>
            </div>

            <div class="mt-5 h-[20rem] rounded-2xl border border-arcade-ink/10 bg-white/70 p-4">
                <canvas id="admin-activity-chart" aria-label="Admin growth chart for the last 30 days"></canvas>
            </div>
        </section>

        <section class="teacher-panel p-5 md:p-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Activity Logs</p>
                    <h2 class="mt-1 text-2xl font-bold">Latest overall 100 records</h2>
                </div>
                <a href="./?c=logs" class="teacher-button teacher-button--light gap-2">
                    <i data-lucide="list" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Full Logs</span>
                </a>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-white/80">
                <?php if ($latestLogs === []) : ?>
                    <div class="px-4 py-5 text-sm font-medium text-arcade-ink/55">
                        No activity logs recorded yet.
                    </div>
                <?php else : ?>
                    <div class="max-h-[38rem] overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="sticky top-0 bg-white/95">
                                <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                    <th class="px-4 py-3 font-semibold">Date</th>
                                    <th class="px-4 py-3 font-semibold">User</th>
                                    <th class="px-4 py-3 font-semibold">Role</th>
                                    <th class="px-4 py-3 font-semibold">Category</th>
                                    <th class="px-4 py-3 font-semibold">Log</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestLogs as $log) : ?>
                                    <?php
                                    $fullname = trim(((string) ($log['firstname'] ?? '')) . ' ' . ((string) ($log['lastname'] ?? '')));
                                    $displayUser = $fullname !== ''
                                        ? $fullname
                                        : (trim((string) ($log['username'] ?? '')) ?: ('User #' . (int) ($log['user_id'] ?? 0)));
                                    $roleText = $roleLabels[(int) ($log['role_id'] ?? 0)] ?? 'Unknown';
                                    ?>
                                    <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                        <td class="px-4 py-3 font-medium text-arcade-ink/65 whitespace-nowrap">
                                            <?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) $log['date_created'])), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-arcade-ink"><?= htmlspecialchars($displayUser, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-xs text-arcade-ink/55"><?= htmlspecialchars((string) ($log['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-arcade-ink/65"><?= htmlspecialchars($roleText, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3">
                                            <span class="teacher-pill bg-arcade-peach/60"><?= htmlspecialchars(ucfirst((string) ($log['category'] ?? 'general')), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="px-4 py-3 leading-6 text-arcade-ink/75"><?= htmlspecialchars((string) ($log['log_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </section>
</main>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const chartElement = document.getElementById('admin-activity-chart');
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
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Students',
                    data: <?= json_encode($studentChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
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
                    label: 'Teachers',
                    data: <?= json_encode($teacherChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
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
                },
                {
                    label: 'Rooms',
                    data: <?= json_encode($roomChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.12)',
                    fill: false,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointHoverBackgroundColor: '#7c3aed',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    tension: 0.35,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'start',
                    labels: {
                        color: textColor,
                        boxWidth: 12,
                        boxHeight: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        font: {
                            size: 12,
                            weight: '600',
                        },
                    },
                },
                tooltip: {
                    displayColors: true,
                    backgroundColor: '#111827',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    padding: 10,
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y}`,
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: mutedColor,
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8,
                        font: {
                            size: 11,
                            weight: '600',
                        },
                    },
                    border: {
                        display: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: mutedColor,
                        precision: 0,
                        font: {
                            size: 11,
                            weight: '600',
                        },
                    },
                    grid: {
                        color: gridColor,
                        drawBorder: false,
                    },
                    border: {
                        display: false,
                    },
                },
            },
        },
    });
});
</script>
