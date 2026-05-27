<?php
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$analyticsTrackedDays = 30;
$analyticsEndDate = new DateTimeImmutable('today');
$analyticsStartDate = $analyticsEndDate->modify('-' . ($analyticsTrackedDays - 1) . ' days');
$activityCounts = $activityLogRepository instanceof ActivityLogRepository && $teacherId > 0
    ? $activityLogRepository->countByDayAndCategory($teacherId, (int) $analyticsEndDate->format('Y'))
    : [];
$teacherActivityDays = [];
$teacherChartLabels = [];
$teacherChallengeValues = [];
$teacherRoomValues = [];

for ($dayIndex = 0; $dayIndex < $analyticsTrackedDays; $dayIndex++) {
    $date = $analyticsStartDate->modify('+' . $dayIndex . ' days');
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

$latestCreatedChallenges = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->searchCreatedChallenges('', '', $teacherId, 10)
    : [];
$latestCreatedRooms = $roomRepository instanceof RoomRepository && $teacherId > 0
    ? $roomRepository->listLatestForOwner($teacherId, 10)
    : [];
$latestCreatedItems = [];

foreach ($latestCreatedChallenges as $challenge) {
    $latestCreatedItems[] = [
        'type' => 'challenge',
        'title' => (string) ($challenge['name'] ?? 'Untitled Challenge'),
        'description' => $tools->formatExcerpt((string) ($challenge['instruction'] ?? '')),
        'difficulty_name' => (string) ($challenge['difficulty_name'] ?? 'easy'),
        'points' => (int) ($challenge['points'] ?? 0),
        'created_at' => (string) ($challenge['date_created'] ?? ''),
        'author' => (string) ($challenge['author'] ?? $teacherName),
        'href' => './?c=challenge-view&id=' . (int) ($challenge['challenge_id'] ?? 0),
        'open_label' => 'Open Challenge',
        'meta_label' => 'Challenge',
        'accent_class' => 'bg-arcade-coral/20',
        'accent_text' => 'Challenge',
    ];
}

foreach ($latestCreatedRooms as $room) {
    $latestCreatedItems[] = [
        'type' => 'room',
        'title' => (string) ($room['room_name'] ?? 'Untitled Room'),
        'description' => $tools->formatExcerpt((string) ($room['room_description'] ?? '')),
        'difficulty_name' => '',
        'points' => (int) ($room['timer_limit'] ?? 0),
        'created_at' => (string) ($room['created_at'] ?? ''),
        'author' => $teacherName,
        'href' => './?c=room-view&id=' . (int) ($room['room_id'] ?? 0),
        'open_label' => 'Open Room',
        'meta_label' => 'Room',
        'accent_class' => 'bg-arcade-cyan/25',
        'accent_text' => 'Room',
        'challenge_name' => (string) ($room['challenge_name'] ?? 'Unknown Challenge'),
    ];
}

usort($latestCreatedItems, static function (array $left, array $right): int {
    $leftTime = strtotime((string) ($left['created_at'] ?? '')) ?: 0;
    $rightTime = strtotime((string) ($right['created_at'] ?? '')) ?: 0;

    return $rightTime <=> $leftTime;
});

$latestCreatedItems = array_slice($latestCreatedItems, 0, 10);
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Teacher Dashboard</p>
            <div class="mt-3 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <h1 class="text-3xl font-black leading-tight md:text-5xl">Hello, Teacher <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Track creation activity, room activity, and the challenges that are getting the most classroom attention.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="./?c=create-room" class="teacher-button teacher-button--light gap-2">
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
                        <p class="text-sm font-black text-arcade-ink/58">Last <?= (int) $analyticsTrackedDays ?> Days</p>
                        <a href="./?c=activity-logs" class="teacher-link-button gap-2">
                            <i data-lucide="activity" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Activity Logs</span>
                        </a>
                    </div>
                </div>

                <div class="teacher-chart-shell mt-4" aria-label="Teacher activity chart for the last <?= (int) $analyticsTrackedDays ?> days">
                    <div class="teacher-chart-stage">
                        <canvas id="teacher-dashboard-chart" height="220"></canvas>
                    </div>
                </div>
            </section>

            <section class="teacher-panel self-start rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-row items-end justify-between gap-3">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Latest Created</p>
                        <h2 class="mt-2 text-2xl font-black">Challenges and Rooms</h2>
                    </div>
                </div>

                <div class="teacher-created-grid teacher-created-grid--compact mt-4">
                        <?php if ($latestCreatedItems === []) : ?>
                        <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-5">
                            <p class="text-sm font-black text-arcade-ink/58">You have not created any challenges or rooms yet.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="./?c=create-challenge" class="teacher-link-button">Create Challenge</a>
                                <a href="./?c=create-room" class="teacher-link-button">Create Room</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($latestCreatedItems as $item) : ?>
                        <?php
                        $difficulty = strtolower((string) ($item['difficulty_name'] ?? 'easy'));
                        $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', $difficulty);
                        $createdAt = (string) ($item['created_at'] ?? '');
                        $createdLabel = $createdAt !== '' ? date('M j, Y', strtotime($createdAt)) : 'Recently';
                        ?>
                        <article class="challenge-card teacher-created-challenge rounded-[18px] border-2 border-arcade-ink/12 bg-white p-4 transition hover:-translate-y-1 hover:border-arcade-orange hover:shadow-[0_6px_0_rgba(38,25,15,0.18)]">
                            <div class="flex h-full flex-col gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full <?= htmlspecialchars((string) ($item['accent_class'] ?? 'bg-arcade-coral/20'), ENT_QUOTES, 'UTF-8') ?> px-3 py-1 text-xs font-bold"><?= htmlspecialchars((string) ($item['accent_text'] ?? 'Item'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (($item['type'] ?? '') === 'challenge') : ?>
                                            <span class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= (int) ($item['points'] ?? 0) ?> points</span>
                                        <?php else : ?>
                                            <span class="rounded-full bg-arcade-yellow/30 px-3 py-1 text-xs font-bold"><?= (int) ($item['points'] ?? 0) > 0 ? (int) ($item['points'] ?? 0) . ' min timer' : 'No timer' ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars((string) ($item['title'] ?? 'Untitled'), ENT_QUOTES, 'UTF-8') ?></h3>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-bold text-arcade-ink/55">
                                        <span>By <?= htmlspecialchars((string) ($item['author'] ?? $teacherName), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span>Created <?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <?php if (($item['type'] ?? '') === 'room') : ?>
                                        <p class="mt-2 text-xs font-bold uppercase tracking-[0.12em] text-arcade-orange">Challenge: <?= htmlspecialchars((string) ($item['challenge_name'] ?? 'Unknown Challenge'), ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                    <p class="teacher-card-description mt-1.5 text-sm leading-6 text-arcade-ink/68"><?= (string) ($item['description'] ?? '') ?></p>
                                </div>
                                <a href="<?= htmlspecialchars((string) ($item['href'] ?? './?c=dashboard'), ENT_QUOTES, 'UTF-8') ?>" class="teacher-small-button mt-auto"><?= htmlspecialchars((string) ($item['open_label'] ?? 'Open'), ENT_QUOTES, 'UTF-8') ?></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script>
(() => {
    if (window.lucide) {
        window.lucide.createIcons();
    } else {
        window.addEventListener('load', () => window.lucide?.createIcons());
    }

    const canvas = document.getElementById('teacher-dashboard-chart');
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
                        maxTicksLimit: 12,
                        font: { weight: '700' },
                    },
                    grid: {
                        display: false,
                    },
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
