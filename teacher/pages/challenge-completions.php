<?php
$challengeId = (int) ($_GET['id'] ?? 0);
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$challenge = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->findCreatedChallengeForOwner($challengeId, $teacherId)
    : null;
$allowedAnalyticsRanges = [7, 30, 365];
$selectedRangeDays = isset($_GET['range']) ? (int) $_GET['range'] : 30;

if (!in_array($selectedRangeDays, $allowedAnalyticsRanges, true)) {
    $selectedRangeDays = 30;
}

$analyticsEndDate = new DateTimeImmutable('today');
$analyticsStartDate = $analyticsEndDate->modify('-' . ($selectedRangeDays - 1) . ' days');
$completionCounts = $userChallengeRepository instanceof UserChallengeRepository && $challengeId > 0
    ? $userChallengeRepository->completedCountsByDateForChallenge($challengeId, $analyticsStartDate, $analyticsEndDate)
    : [];
$completionRowsPage = max(1, (int) ($_GET['page'] ?? 1));
$completionRowsPerPage = 12;
$completionRowsTotal = $userChallengeRepository instanceof UserChallengeRepository && $challengeId > 0
    ? $userChallengeRepository->countOutcomesByChallenge($challengeId)
    : 0;
$completionRowsTotalPages = max(1, (int) ceil($completionRowsTotal / $completionRowsPerPage));
$completionRowsPage = min($completionRowsPage, $completionRowsTotalPages);
$completionRowsOffset = ($completionRowsPage - 1) * $completionRowsPerPage;
$completionRows = $userChallengeRepository instanceof UserChallengeRepository && $challengeId > 0
    ? $userChallengeRepository->listOutcomesByChallengePaged($challengeId, $completionRowsPerPage, $completionRowsOffset)
    : [];
$chartLabels = [];
$chartValues = [];
$rangeQueryBase = './?c=challenge-completions&id=' . $challengeId;
$paginationBase = './?c=challenge-completions&id=' . $challengeId . '&range=' . $selectedRangeDays;

$formatDuration = static function (?string $startedAt, ?string $completedAt): string {
    if (!$startedAt || !$completedAt) {
        return 'Unavailable';
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

$formatCompletionType = static function (array $row): string {
    if ((int) ($row['pvp_id'] ?? 0) > 0) {
        return '1v1';
    }

    if ((int) ($row['room_id'] ?? 0) > 0) {
        return 'room';
    }

    return 'solo';
};

for ($dayIndex = 0; $dayIndex < $selectedRangeDays; $dayIndex++) {
    $date = $analyticsStartDate->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $chartLabels[] = $date->format('M j');
    $chartValues[] = (int) ($completionCounts[$dateKey] ?? 0);
}
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <?php if ($challenge === null) : ?>
            <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-coral">Missing Challenge</p>
                <h1 class="mt-3 text-3xl font-black">Challenge not found.</h1>
                <p class="mt-2 text-sm font-bold leading-7 text-arcade-ink/62">The challenge may have been removed or the link is invalid.</p>
                <a href="./?c=dashboard" class="teacher-button teacher-button--light mt-4">Back to Dashboard</a>
            </article>
        <?php else : ?>
            <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Outcome Records</p>
                        <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars((string) $challenge['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    </div>
                    <div class="flex flex-nowrap items-center gap-2">
                        <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#challenge-completions-export-modal">
                            <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Export CSV</span>
                        </button>
                        <a href="./?c=challenge-view&id=<?= (int) $challengeId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Back to Challenge</span>
                        </a>
                    </div>
                </div>
            </article>

            <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Analytics</p>
                            <h2 class="mt-2 text-2xl font-black">Challenge Outcomes</h2>
                        </div>
                        <p class="text-sm font-black text-arcade-ink/58">Last <?= (int) $selectedRangeDays ?> Days</p>
                    </div>

                    <div class="teacher-range-row">
                        <?php foreach ($allowedAnalyticsRanges as $rangeOption) : ?>
                            <a
                                href="<?= htmlspecialchars($rangeQueryBase . '&range=' . $rangeOption, ENT_QUOTES, 'UTF-8') ?>"
                                class="teacher-range-chip <?= $selectedRangeDays === $rangeOption ? 'is-active' : '' ?>"
                            >
                                Last <?= (int) $rangeOption ?> Days
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="teacher-chart-shell" aria-label="Challenge completions chart for the last <?= (int) $selectedRangeDays ?> days">
                        <div class="teacher-chart-stage">
                            <canvas id="teacher-challenge-completions-chart" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-0 shadow-[7px_7px_0_#26190f] overflow-hidden">
                <?php if ($completionRows === []) : ?>
                    <div class="px-5 py-6 text-sm font-bold text-arcade-ink/55">
                        No outcome records yet for this challenge.
                    </div>
                <?php else : ?>
                    <div class="max-h-[42rem] overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="sticky top-0 z-[1] bg-white/95">
                                <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                    <th class="px-4 py-3 font-semibold">Player</th>
                                    <th class="px-4 py-3 font-semibold">Type</th>
                                    <th class="px-4 py-3 font-semibold">Started</th>
                                    <th class="px-4 py-3 font-semibold">Outcome</th>
                                    <th class="px-4 py-3 font-semibold">Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completionRows as $row) : ?>
                                    <?php
                                    $firstname = trim((string) ($row['firstname'] ?? ''));
                                    $lastname = trim((string) ($row['lastname'] ?? ''));
                                    $displayName = trim($firstname . ' ' . $lastname) ?: (string) ($row['username'] ?? 'Player');
                                    $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'PL', 0, 2));
                                    $completionType = $formatCompletionType($row);
                                    $outcomeType = (string) ($row['outcome_type'] ?? 'done');
                                    $resultLabel = match ($outcomeType) {
                                        'win' => 'Win',
                                        'loss' => 'Loss',
                                        'pass' => 'Pass',
                                        'failed' => 'Failed',
                                        default => 'Done',
                                    };
                                    $resultClass = match ($outcomeType) {
                                        'win' => 'bg-arcade-cyan/70',
                                        'loss' => 'bg-arcade-coral/30',
                                        'pass' => 'bg-arcade-mint/70',
                                        'failed' => 'bg-arcade-coral/30',
                                        default => 'bg-arcade-yellow/70',
                                    };
                                    ?>
                                    <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-bold text-arcade-ink">
                                                    <?php if (trim((string) ($row['avatar_url'] ?? '')) !== '') : ?>
                                                        <img src="<?= htmlspecialchars((string) $row['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                                    <?php else : ?>
                                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="truncate font-bold text-arcade-ink"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs font-bold text-arcade-ink/55">@<?= htmlspecialchars((string) ($row['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs font-bold text-arcade-ink/55"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-black text-arcade-ink"><?= htmlspecialchars($completionType, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap font-bold text-arcade-ink/65"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($row['started_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap font-bold text-arcade-ink/65">
                                            <span class="rounded-full <?= htmlspecialchars($resultClass, ENT_QUOTES, 'UTF-8') ?> px-3 py-1 text-xs font-bold">
                                                <?= htmlspecialchars($resultLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-black text-arcade-ink"><?= htmlspecialchars($formatDuration((string) ($row['started_at'] ?? ''), (string) ($row['completed_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-between gap-3 border-t border-arcade-ink/10 px-4 py-3">
                    <a
                        href="<?= htmlspecialchars($paginationBase . '&page=' . max(1, $completionRowsPage - 1), ENT_QUOTES, 'UTF-8') ?>"
                        class="teacher-link-button <?= $completionRowsPage <= 1 ? 'pointer-events-none opacity-45' : '' ?>"
                    >
                        Prev
                    </a>
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/55">
                        <?= $completionRowsTotal === 0 ? 'No records' : ('Page ' . $completionRowsPage . ' of ' . $completionRowsTotalPages . ' - ' . $completionRowsTotal . ' records') ?>
                    </span>
                    <a
                        href="<?= htmlspecialchars($paginationBase . '&page=' . min($completionRowsTotalPages, $completionRowsPage + 1), ENT_QUOTES, 'UTF-8') ?>"
                        class="teacher-link-button <?= $completionRowsPage >= $completionRowsTotalPages ? 'pointer-events-none opacity-45' : '' ?>"
                    >
                        Next
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>

<?php if ($challenge !== null) : ?>
    <div class="modal fade" id="challenge-completions-export-modal" tabindex="-1" aria-labelledby="challenge-completions-export-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
                <input type="hidden" name="c" value="challenge-completions">
                <input type="hidden" name="id" value="<?= (int) $challengeId ?>">
                <input type="hidden" name="export" value="csv">
                <div class="modal-header border-0 px-5 pb-2 pt-5">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Export Records</p>
                        <h2 class="modal-title mt-2 text-2xl font-bold" id="challenge-completions-export-modal-title">Choose date range</h2>
                    </div>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-5 pb-5 pt-2">
                    <p class="text-sm font-semibold leading-6 text-arcade-ink/65">Export completion records as CSV for the exact date range you choose.</p>
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
<?php endif; ?>
<script>
(() => {
    window.addEventListener('load', () => window.lucide?.createIcons());

    const canvas = document.getElementById('teacher-challenge-completions-chart');
    if (!canvas || typeof window.Chart === 'undefined') {
        return;
    }

    const context = canvas.getContext('2d');
    if (!context) {
        return;
    }

    const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
    const gradient = context.createLinearGradient(0, 0, 0, canvas.height || 220);
    gradient.addColorStop(0, isDarkMode ? 'rgba(255, 140, 66, 0.42)' : 'rgba(255, 140, 66, 0.28)');
    gradient.addColorStop(1, 'rgba(255, 140, 66, 0)');

    new window.Chart(context, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Challenge completions',
                data: <?= json_encode($chartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                fill: true,
                backgroundColor: gradient,
                borderColor: '#ff8c42',
                borderWidth: 3,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBorderWidth: 2,
                pointHoverBackgroundColor: '#ffd166',
                pointHoverBorderColor: '#26190f',
                tension: 0.3,
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
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        color: isDarkMode ? '#fff7e8' : '#26190f',
                        font: { weight: '800' },
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
