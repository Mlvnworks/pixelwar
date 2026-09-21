<?php
$currentStudentId = (int) ($_SESSION['user_id'] ?? 0);
$initialModeFilter = isset($_GET['mode']) && in_array((string) $_GET['mode'], ['solo', 'pvp', 'room'], true)
    ? (string) $_GET['mode']
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
$rangeQueryBase = './?c=player-analytics&mode=' . urlencode($initialModeFilter);

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
    $isPvpAttempt = (int) ($attemptRow['pvp_id'] ?? 0) > 0;
    $isStrictRoomAttempt = $isRoomAttempt && (int) ($attemptRow['room_mode'] ?? 0) === 1;
    $isHardCodeRoomAttempt = $isRoomAttempt && (int) ($attemptRow['room_mode'] ?? 0) === 3;
    $strictModeScore = max(0, min(100, (int) ($attemptRow['strict_mode_score'] ?? 0)));
    $attemptStatus = (string) ($attemptRow['attempt_status'] ?? '');
    $status = match ($attemptStatus) {
        'pvp_win' => 'completed',
        'pvp_loss' => 'failed',
        default => (!empty($attemptRow['completed_at'])
            ? 'completed'
            : ($isRoomAttempt ? 'failed' : 'ongoing')),
    };
    $modeKey = $isPvpAttempt ? 'pvp' : ($isRoomAttempt ? 'room' : 'solo');
    $modeLabel = $isPvpAttempt ? '1v1' : ($isRoomAttempt ? 'Room' : 'Solo Practice');
    $roomModeLabel = $isRoomAttempt
        ? match ((int) ($attemptRow['room_mode'] ?? 0)) {
            1 => 'Strict Mode',
            3 => 'Hard Code Mode',
            default => 'Practice Mode',
        }
        : null;
    $hardCodeGrade = isset($attemptRow['hard_code_grade']) ? max(0, (int) $attemptRow['hard_code_grade']) : null;
    $hardCodeRoomPoints = max(0, (int) ($attemptRow['room_points'] ?? 0));
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
        $durationLabel = $formatDurationLabel($durationSeconds);
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
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A') . ' - Running for ' . $formatDurationLabel($ongoingSeconds);
    }

    if ($isStrictRoomAttempt && $status !== 'ongoing') {
        $statusBadgeLabel = $strictModeScore . '%';
        $durationDetails = 'Taken ' . $startedAt->format('M j, Y g:i A')
            . ($completedAt instanceof DateTimeImmutable
                ? ' - Completed in ' . $durationLabel
                : ' - Strict mode result recorded at ' . $strictModeScore . '%.');
    }
    if ($isHardCodeRoomAttempt && $status === 'completed') {
        $statusBadgeLabel = 'Submitted';
    }

    $analyticsRows[] = [
        'challengeId' => (int) $attemptRow['challenge_id'],
        'title' => (string) $attemptRow['name'],
        'status' => $status,
        'statusBadgeLabel' => $statusBadgeLabel,
        'modeLabel' => $modeLabel,
        'modeKey' => $modeKey,
        'roomModeLabel' => $roomModeLabel,
        'isStrictRoomAttempt' => $isStrictRoomAttempt,
        'isHardCodeRoomAttempt' => $isHardCodeRoomAttempt,
        'hardCodeGrade' => $hardCodeGrade,
        'hardCodeRoomPoints' => $hardCodeRoomPoints,
        'strictModeScore' => $strictModeScore,
        'level' => ucfirst(strtolower((string) ($attemptRow['difficulty_name'] ?? 'Beginner'))),
        'startedAt' => $startedAt,
        'completedAt' => $completedAt,
        'duration' => $durationLabel,
        'durationDetails' => $durationDetails,
        'href' => $status === 'ongoing'
            ? './?c=pixelwar&intro=1&challenge_id=' . (int) $attemptRow['challenge_id'] . ($isPvpAttempt ? '&pvp_id=' . (int) ($attemptRow['pvp_id'] ?? 0) : '')
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
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="inline-flex justify-center rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white" data-bs-toggle="modal" data-bs-target="#analytics-export-modal">Export</button>
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
                    <input id="analytics-search" type="search" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 text-sm outline-none transition focus:border-arcade-orange" placeholder="Search challenge name, ID, status, or level...">
                </label>
                <div class="flex gap-2">
                    <button class="analytics-filter <?= $initialModeFilter === 'all' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-mode-filter="all">All</button>
                    <button class="analytics-filter <?= $initialModeFilter === 'solo' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-mode-filter="solo">Solo Practice</button>
                    <button class="analytics-filter <?= $initialModeFilter === 'pvp' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-mode-filter="pvp">1v1</button>
                    <button class="analytics-filter <?= $initialModeFilter === 'room' ? 'is-active bg-arcade-yellow' : 'bg-white' ?> rounded-xl border-2 border-arcade-ink/10 px-3 py-2 text-xs font-bold" type="button" data-mode-filter="room">Room</button>
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
                        data-search="<?= htmlspecialchars(strtolower($row['title'] . ' challenge id #' . $row['challengeId'] . ' ' . $row['challengeId'] . ' ' . $row['status'] . ' ' . $row['modeLabel'] . ' ' . ($row['roomModeLabel'] ?? '') . ' ' . $row['level']), ENT_QUOTES, 'UTF-8') ?>"
                        data-mode="<?= htmlspecialchars($row['modeKey'], ENT_QUOTES, 'UTF-8') ?>">
                        <div>
                            <p class="text-sm font-bold"><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-xs font-semibold text-arcade-ink/55">Challenge ID: #<?= (int) $row['challengeId'] ?></p>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <span class="rounded-full <?= $row['modeLabel'] === '1v1' ? 'bg-arcade-cyan/30' : ($row['modeLabel'] === 'Room' ? 'bg-arcade-orange/20' : 'bg-arcade-mint/35') ?> px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-arcade-ink">
                                    <?= htmlspecialchars($row['modeLabel'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if ($row['roomModeLabel'] !== null) : ?>
                                    <span class="rounded-full <?= $row['isHardCodeRoomAttempt'] ? 'bg-arcade-coral/25' : ($row['isStrictRoomAttempt'] ? 'bg-arcade-yellow/60' : 'bg-arcade-mint/35') ?> px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-arcade-ink">
                                        <?= htmlspecialchars($row['roomModeLabel'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endif; ?>
                                <span class="rounded-full bg-arcade-orange/12 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-arcade-orange">
                                    +<?= (int) $row['points'] ?> pts
                                </span>
                            </div>
                        </div>
                        <div>
                            <span class="rounded-full <?= $row['status'] === 'completed' ? 'bg-arcade-mint/70' : ($row['status'] === 'failed' ? 'bg-arcade-coral/30' : 'bg-arcade-yellow/70') ?> px-3 py-1 text-xs font-bold">
                                <?= htmlspecialchars((string) ($row['statusBadgeLabel'] ?? ucfirst($row['status'])), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p class="text-xs font-bold text-arcade-ink/60"><?= htmlspecialchars($row['level'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($row['duration'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs font-semibold text-arcade-ink/55"><?= htmlspecialchars($row['durationDetails'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="flex flex-wrap justify-end gap-2">
                            <?php if ($row['isHardCodeRoomAttempt']) : ?>
                                <button type="button"
                                    class="inline-flex justify-center rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-3 py-1.5 text-xs font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow"
                                    data-view-hard-code-grade
                                    data-challenge-name="<?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-grade="<?= $row['hardCodeGrade'] === null ? '' : (int) $row['hardCodeGrade'] ?>"
                                    data-room-points="<?= (int) $row['hardCodeRoomPoints'] ?>">
                                    View Grade
                                </button>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($row['href'], ENT_QUOTES, 'UTF-8') ?>" class="inline-flex justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-3 py-1.5 text-xs font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
                                Train again
                            </a>
                        </div>
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

<div class="modal fade" id="hard-code-grade-view-modal" tabindex="-1" aria-labelledby="hard-code-grade-view-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[26px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Hard Code Result</p>
                    <h2 id="hard-code-grade-view-title" class="modal-title mt-2 text-2xl font-bold" data-grade-modal-challenge>Submission Grade</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-5 pb-5 pt-2">
                <div class="rounded-[20px] border-2 border-arcade-ink/10 bg-white p-5 text-center">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-arcade-ink/55">Teacher Grade</p>
                    <p class="mt-3 text-4xl font-black text-arcade-orange" data-grade-modal-value>Pending grading</p>
                    <p class="mt-2 text-sm font-semibold text-arcade-ink/60" data-grade-modal-note>Your teacher has not graded this submission yet.</p>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="analytics-export-modal" tabindex="-1" aria-labelledby="analytics-export-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
            <input type="hidden" name="c" value="player-analytics">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Export Records</p>
                    <h2 class="modal-title mt-2 text-2xl font-bold" id="analytics-export-modal-title">Choose date range</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-5 pb-5 pt-2">
                <p class="text-sm font-semibold leading-6 text-arcade-ink/65">Choose a file format, game mode, and date range for your solving records.</p>
                <label class="export-date-field mt-4">
                    <span>File Format</span>
                    <select name="export" required>
                        <option value="csv">CSV spreadsheet</option>
                        <option value="pdf">PDF document</option>
                    </select>
                </label>
                <label class="export-date-field mt-4">
                    <span>Game Mode</span>
                    <select id="analytics-export-mode" name="export_mode" required>
                        <option value="all"<?= $initialModeFilter === 'all' ? ' selected' : '' ?>>All game modes</option>
                        <option value="solo"<?= $initialModeFilter === 'solo' ? ' selected' : '' ?>>Solo Practice</option>
                        <option value="pvp"<?= $initialModeFilter === 'pvp' ? ' selected' : '' ?>>1v1</option>
                        <option value="room"<?= $initialModeFilter === 'room' ? ' selected' : '' ?>>Room</option>
                    </select>
                </label>
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
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Download</button>
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
    const filterButtons = Array.from(document.querySelectorAll('[data-mode-filter]'));
    const previousButton = document.getElementById('analytics-prev');
    const nextButton = document.getElementById('analytics-next');
    const pageStatus = document.getElementById('analytics-page-status');
    const exportModalElement = document.getElementById('analytics-export-modal');
    const exportModeSelect = document.getElementById('analytics-export-mode');
    const pageSize = 20;
    let currentPage = 1;
    let activeMode = <?= json_encode($initialModeFilter, JSON_UNESCAPED_SLASHES) ?>;
    const gradeModalElement = document.getElementById('hard-code-grade-view-modal');
    const gradeModal = gradeModalElement && window.bootstrap?.Modal
        ? window.bootstrap.Modal.getOrCreateInstance(gradeModalElement)
        : null;
    const gradeModalChallenge = gradeModalElement?.querySelector('[data-grade-modal-challenge]');
    const gradeModalValue = gradeModalElement?.querySelector('[data-grade-modal-value]');
    const gradeModalNote = gradeModalElement?.querySelector('[data-grade-modal-note]');

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
            const matchesMode = activeMode === 'all' || row.dataset.mode === activeMode;
            const matchesSearch = query === '' || (row.dataset.search || '').includes(query);
            return matchesMode && matchesSearch;
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
            activeMode = button.dataset.modeFilter || 'all';
            currentPage = 1;
            filterButtons.forEach((filterButton) => {
                filterButton.classList.toggle('is-active', filterButton === button);
                filterButton.classList.toggle('bg-arcade-yellow', filterButton === button);
                filterButton.classList.toggle('bg-white', filterButton !== button);
            });
            renderRows();
        });
    });

    exportModalElement?.addEventListener('show.bs.modal', () => {
        if (exportModeSelect instanceof HTMLSelectElement) {
            exportModeSelect.value = activeMode;
        }
    });

    document.querySelectorAll('[data-view-hard-code-grade]').forEach((button) => {
        button.addEventListener('click', () => {
            const grade = button.dataset.grade || '';
            const roomPoints = Math.max(0, Number.parseInt(button.dataset.roomPoints || '0', 10) || 0);
            if (gradeModalChallenge) {
                gradeModalChallenge.textContent = button.dataset.challengeName || 'Submission Grade';
            }
            if (gradeModalValue) {
                gradeModalValue.textContent = grade === '' ? 'Pending grading' : `${grade} / ${roomPoints}`;
            }
            if (gradeModalNote) {
                gradeModalNote.textContent = grade === ''
                    ? 'Your teacher has not graded this submission yet.'
                    : 'This grade was assigned by your teacher.';
            }
            gradeModal?.show();
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
