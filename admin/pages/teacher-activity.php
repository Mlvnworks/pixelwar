<?php
$teacherActivityId = max(0, (int) ($_GET['id'] ?? 0));
$teacherActivityProfile = $userRepository instanceof UserRepository
    ? $userRepository->findSessionUser($teacherActivityId)
    : null;
$teacherActivityPage = max(1, (int) ($_GET['page'] ?? 1));
$teacherActivityPerPage = 10;
$teacherActivityTypeOptions = ['all', 'challenge', 'room'];
$teacherActivityListType = strtolower(trim((string) ($_GET['type'] ?? 'all')));
if (!in_array($teacherActivityListType, $teacherActivityTypeOptions, true)) {
    $teacherActivityListType = 'all';
}

$teacherActivitySearch = trim((string) ($_GET['search'] ?? ''));
$teacherActivitySearchLower = function_exists('mb_strtolower')
    ? mb_strtolower($teacherActivitySearch, 'UTF-8')
    : strtolower($teacherActivitySearch);

$teacherChallenges = $challengeRepository instanceof ChallengeRepository && $teacherActivityId > 0
    ? $challengeRepository->listCreatedChallengesForUser($teacherActivityId, 1000)
    : [];
$teacherRooms = $roomRepository instanceof RoomRepository && $teacherActivityId > 0
    ? $roomRepository->listForOwner($teacherActivityId, 1000)
    : [];
$teacherActivityRangeOptions = [7, 30, 365];
$teacherActivityRangeDays = (int) ($_GET['range'] ?? 30);
if (!in_array($teacherActivityRangeDays, $teacherActivityRangeOptions, true)) {
    $teacherActivityRangeDays = 30;
}

$teacherActivityEndDate = new DateTimeImmutable('today');
$teacherActivityStartDate = $teacherActivityEndDate->modify('-' . ($teacherActivityRangeDays - 1) . ' days');
$teacherActivityDailyCounts = $activityLogRepository instanceof ActivityLogRepository && $teacherActivityId > 0
    ? $activityLogRepository->countCreationByDayAndCategoryInRange($teacherActivityId, $teacherActivityStartDate, $teacherActivityEndDate)
    : [];

$teacherActivityChartLabels = [];
$teacherActivityChallengeValues = [];
$teacherActivityRoomValues = [];

for ($offset = 0; $offset < $teacherActivityRangeDays; $offset++) {
    $chartDate = $teacherActivityStartDate->modify('+' . $offset . ' days');
    $chartKey = $chartDate->format('Y-m-d');
    $teacherActivityChartLabels[] = $chartDate->format('M j');
    $dailyCounts = $teacherActivityDailyCounts[$chartKey] ?? [];
    $teacherActivityChallengeValues[] = (int) ($dailyCounts['challenge'] ?? 0);
    $teacherActivityRoomValues[] = (int) ($dailyCounts['room'] ?? 0);
}

$teacherActivityRows = [];

foreach ($teacherChallenges as $challenge) {
    $teacherActivityRows[] = [
        'type' => 'challenge',
        'search_blob' => implode(' ', [
            (string) ($challenge['name'] ?? ''),
            (string) ($challenge['instruction'] ?? ''),
            (string) ($challenge['difficulty_name'] ?? ''),
            (string) ($challenge['author'] ?? ''),
        ]),
        'created_at' => (string) ($challenge['date_created'] ?? ''),
        'record' => $challenge,
    ];
}

foreach ($teacherRooms as $roomRow) {
    $teacherActivityRows[] = [
        'type' => 'room',
        'search_blob' => implode(' ', [
            (string) ($roomRow['room_name'] ?? ''),
            (string) ($roomRow['room_description'] ?? ''),
            (string) ($roomRow['challenge_name'] ?? ''),
            (string) ($roomRow['room_code'] ?? ''),
        ]),
        'created_at' => (string) ($roomRow['created_at'] ?? ''),
        'record' => $roomRow,
    ];
}

$teacherActivityRows = array_values(array_filter(
    $teacherActivityRows,
    static function (array $row) use ($teacherActivityListType, $teacherActivitySearchLower): bool {
        if ($teacherActivityListType !== 'all' && (string) ($row['type'] ?? '') !== $teacherActivityListType) {
            return false;
        }

        if ($teacherActivitySearchLower === '') {
            return true;
        }

        $haystack = (string) ($row['search_blob'] ?? '');
        $haystack = function_exists('mb_strtolower')
            ? mb_strtolower($haystack, 'UTF-8')
            : strtolower($haystack);

        return str_contains($haystack, $teacherActivitySearchLower);
    }
));

usort(
    $teacherActivityRows,
    static fn(array $left, array $right): int => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''))
);

$teacherActivityTotal = count($teacherActivityRows);
$teacherActivityPages = max(1, (int) ceil($teacherActivityTotal / $teacherActivityPerPage));
$teacherActivityPage = min($teacherActivityPage, $teacherActivityPages);
$teacherActivityOffset = ($teacherActivityPage - 1) * $teacherActivityPerPage;
$teacherActivityPageRows = array_slice($teacherActivityRows, $teacherActivityOffset, $teacherActivityPerPage);

$teacherChallengeTotal = count($teacherChallenges);
$teacherRoomTotal = count($teacherRooms);

$teacherActivityName = trim((string) ($teacherActivityProfile['firstname'] ?? '') . ' ' . (string) ($teacherActivityProfile['lastname'] ?? ''));
$teacherActivityName = $teacherActivityName !== '' ? $teacherActivityName : trim((string) ($teacherActivityProfile['username'] ?? 'Teacher'));

$teacherActivityBuildQuery = static function (array $overrides = []) use ($teacherActivityId, $teacherActivityPage, $teacherActivityRangeDays, $teacherActivityListType, $teacherActivitySearch): string {
    $query = [
        'c' => 'teacher-activity',
        'id' => $teacherActivityId,
        'page' => $teacherActivityPage,
        'range' => $teacherActivityRangeDays,
        'type' => $teacherActivityListType,
        'search' => $teacherActivitySearch,
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
        <div class="flex flex-wrap gap-2">
            <a href="./?c=teachers" class="teacher-button teacher-button--light gap-2 no-underline">
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                <span>Back to Teachers</span>
            </a>
            <?php if ($teacherActivityId > 0) : ?>
                <a href="./?c=teacher-view&id=<?= (int) $teacherActivityId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                    <i data-lucide="user-round-search" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Back to Teacher</span>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($teacherActivityProfile === null || (int) ($teacherActivityProfile['role_id'] ?? 0) !== 2) : ?>
            <section class="teacher-panel p-6">
                <h1 class="text-2xl font-bold">Teacher not found</h1>
                <p class="mt-2 text-sm font-medium leading-7 text-arcade-ink/62">The requested teacher account is unavailable or has been removed.</p>
            </section>
        <?php else : ?>
            <section class="grid gap-5">
                <section class="teacher-panel p-5 md:p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Teacher Activity</p>
                            <h1 class="mt-1 text-3xl font-bold leading-tight md:text-4xl"><?= htmlspecialchars($teacherActivityName, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/65 md:text-base">
                                Review created challenges and room records for this teacher.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="teacher-panel p-5 md:p-6">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Analytics</p>
                            <h2 class="mt-1 text-2xl font-bold">Creation Activity</h2>
                        </div>
                        <div class="admin-range-row">
                            <?php foreach ($teacherActivityRangeOptions as $rangeOption) : ?>
                                <?php $isTeacherActivityRangeActive = $teacherActivityRangeDays === $rangeOption; ?>
                                <a
                                    href="<?= htmlspecialchars($teacherActivityBuildQuery(['range' => $rangeOption, 'page' => 1]), ENT_QUOTES, 'UTF-8') ?>"
                                    class="admin-range-chip <?= $isTeacherActivityRangeActive ? 'admin-range-chip--active' : '' ?>"
                                >
                                    Last <?= (int) $rangeOption ?> Days
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="admin-teacher-chart-shell mt-4" aria-label="Teacher creation activity chart for the last <?= (int) $teacherActivityRangeDays ?> days">
                        <div class="admin-teacher-chart-stage">
                            <canvas id="admin-teacher-activity-chart" aria-label="Teacher creation chart"></canvas>
                        </div>
                    </div>
                </section>

                <section class="teacher-panel p-5 md:p-6">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Records</p>
                            <h2 class="mt-1 text-2xl font-bold">Challenge and room activity</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="teacher-pill bg-arcade-peach/60"><?= (int) $teacherChallengeTotal ?> challenges</span>
                            <span class="teacher-pill bg-white"><?= (int) $teacherRoomTotal ?> rooms</span>
                            <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#admin-teacher-activity-export-modal">
                                <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Export CSV</span>
                            </button>
                        </div>
                    </div>

                    <form method="get" action="./" class="mt-5 admin-activity-filter-grid">
                        <input type="hidden" name="c" value="teacher-activity">
                        <input type="hidden" name="id" value="<?= (int) $teacherActivityId ?>">
                        <input type="hidden" name="range" value="<?= (int) $teacherActivityRangeDays ?>">
                        <div>
                            <label class="admin-activity-filter-label" for="admin-teacher-activity-search">Search</label>
                            <input
                                id="admin-teacher-activity-search"
                                class="admin-activity-filter-input"
                                type="search"
                                name="search"
                                value="<?= htmlspecialchars($teacherActivitySearch, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Search challenge or room activity"
                            >
                        </div>
                        <div>
                            <label class="admin-activity-filter-label" for="admin-teacher-activity-type">Filter</label>
                            <select id="admin-teacher-activity-type" name="type" class="admin-activity-filter-input">
                                <option value="all" <?= $teacherActivityListType === 'all' ? 'selected' : '' ?>>All records</option>
                                <option value="challenge" <?= $teacherActivityListType === 'challenge' ? 'selected' : '' ?>>Challenges only</option>
                                <option value="room" <?= $teacherActivityListType === 'room' ? 'selected' : '' ?>>Rooms only</option>
                            </select>
                        </div>
                        <div class="admin-activity-filter-actions">
                            <button type="submit" class="teacher-button teacher-button--primary gap-2">
                                <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Apply</span>
                            </button>
                            <a href="./?c=teacher-activity&id=<?= (int) $teacherActivityId ?>&range=<?= (int) $teacherActivityRangeDays ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>

                    <?php if ($teacherActivityPageRows === []) : ?>
                        <div class="mt-5 rounded-2xl border border-dashed border-arcade-ink/14 bg-white/80 px-4 py-5 text-sm font-medium text-arcade-ink/55">
                            No matching activity records were found for this teacher.
                        </div>
                    <?php else : ?>
                        <div class="mt-5 grid gap-3 lg:grid-cols-2">
                            <?php foreach ($teacherActivityPageRows as $activityRow) : ?>
                                <?php
                                $activityType = (string) ($activityRow['type'] ?? 'challenge');
                                $activityRecord = (array) ($activityRow['record'] ?? []);
                                $createdAt = (string) ($activityRow['created_at'] ?? '');
                                ?>
                                <article class="rounded-2xl border border-arcade-ink/10 bg-white/80 p-4">
                                    <?php if ($activityType === 'challenge') : ?>
                                        <?php
                                        $difficulty = strtolower((string) ($activityRecord['difficulty_name'] ?? 'easy'));
                                        $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', $difficulty);
                                        ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Challenge</span>
                                            <span class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= (int) ($activityRecord['points'] ?? 0) ?> points</span>
                                        </div>
                                        <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars((string) ($activityRecord['name'] ?? 'Untitled Challenge'), ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="mt-2 text-sm leading-6 text-arcade-ink/68"><?= $tools->formatExcerpt((string) ($activityRecord['instruction'] ?? '')) ?></p>
                                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs font-semibold text-arcade-ink/55">
                                            <span>Created <?= htmlspecialchars($createdAt !== '' ? date('M j, Y', strtotime($createdAt)) : 'Recently', ENT_QUOTES, 'UTF-8') ?></span>
                                            <a href="./?c=challenge-view&id=<?= (int) ($activityRecord['challenge_id'] ?? 0) ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                                <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                                <span>Open</span>
                                            </a>
                                        </div>
                                    <?php else : ?>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700">Room</span>
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600"><?= htmlspecialchars(trim((string) ($activityRecord['room_code'] ?? '')) !== '' ? (string) ($activityRecord['room_code'] ?? '') : 'No code', ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                        <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars((string) ($activityRecord['room_name'] ?? 'Untitled Room'), ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="mt-2 text-sm leading-6 text-arcade-ink/68"><?= $tools->formatExcerpt((string) ($activityRecord['room_description'] ?? '')) ?></p>
                                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs font-semibold text-arcade-ink/55">
                                            <span>
                                                Created <?= htmlspecialchars($createdAt !== '' ? date('M j, Y', strtotime($createdAt)) : 'Recently', ENT_QUOTES, 'UTF-8') ?>
                                                · Challenge: <?= htmlspecialchars((string) ($activityRecord['challenge_name'] ?? 'Unknown Challenge'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <a href="./?c=room-view&id=<?= (int) ($activityRecord['room_id'] ?? 0) ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                                <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                                <span>Open</span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 border-t border-arcade-ink/10 pt-4 md:flex-row md:items-center md:justify-between">
                            <p class="text-sm font-medium text-arcade-ink/55">
                                Page <?= (int) $teacherActivityPage ?> of <?= (int) $teacherActivityPages ?> · <?= (int) $teacherActivityTotal ?> record<?= $teacherActivityTotal === 1 ? '' : 's' ?>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars($teacherActivityBuildQuery(['page' => max(1, $teacherActivityPage - 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teacherActivityPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                                    <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>Previous</span>
                                </a>
                                <a href="<?= htmlspecialchars($teacherActivityBuildQuery(['page' => min($teacherActivityPages, $teacherActivityPage + 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teacherActivityPage >= $teacherActivityPages ? 'pointer-events-none opacity-50' : '' ?>">
                                    <span>Next</span>
                                    <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
        <?php endif; ?>
    </section>
</main>

<div class="modal fade" id="admin-teacher-activity-export-modal" tabindex="-1" aria-labelledby="admin-teacher-activity-export-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[26px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[10px_10px_0_rgba(15,23,42,0.18)]">
            <form method="get" action="./">
                <input type="hidden" name="c" value="teacher-activity">
                <input type="hidden" name="id" value="<?= (int) $teacherActivityId ?>">
                <input type="hidden" name="export" value="csv">
                <div class="modal-header border-0 px-5 pt-5 pb-0">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Export CSV</p>
                        <h2 class="modal-title mt-2 text-2xl font-bold" id="admin-teacher-activity-export-modal-title">Choose date range</h2>
                    </div>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-5 py-4">
                    <div class="grid gap-3">
                        <label class="admin-export-field">
                            <span class="admin-activity-filter-label">Type</span>
                            <select class="admin-activity-filter-input" name="export_type">
                                <option value="all">Challenge and room</option>
                                <option value="challenge">Challenge only</option>
                                <option value="room">Room only</option>
                            </select>
                        </label>
                        <label class="admin-export-field">
                            <span class="admin-activity-filter-label">Start Date</span>
                            <input
                                class="admin-activity-filter-input"
                                type="date"
                                name="export_start_date"
                                max="<?= htmlspecialchars((new DateTimeImmutable('today'))->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >
                        </label>
                        <label class="admin-export-field">
                            <span class="admin-activity-filter-label">End Date</span>
                            <input
                                class="admin-activity-filter-input"
                                type="date"
                                name="export_end_date"
                                max="<?= htmlspecialchars((new DateTimeImmutable('today'))->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 px-5 pt-0 pb-5">
                    <button type="button" class="teacher-button teacher-button--light gap-2" data-bs-dismiss="modal">
                        <span>Cancel</span>
                    </button>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Export</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.admin-range-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

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

.admin-teacher-chart-shell {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1.5rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 250, 252, 0.92) 100%);
    padding: 1rem;
}

.admin-teacher-chart-stage {
    position: relative;
    width: 100%;
    min-height: 21rem;
}

.admin-activity-filter-grid {
    display: grid;
    gap: 1rem;
}

.admin-activity-filter-label {
    display: block;
    margin-bottom: 0.45rem;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.6);
}

.admin-activity-filter-input {
    width: 100%;
    min-height: 3rem;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.96);
    padding: 0.8rem 0.95rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
}

.admin-activity-filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: end;
}

.admin-export-field {
    display: grid;
    gap: 0.45rem;
}

@media (min-width: 960px) {
    .admin-activity-filter-grid {
        grid-template-columns: minmax(0, 1.4fr) minmax(15rem, 0.8fr) auto;
        align-items: end;
    }
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

body.pixelwar-dark-mode .admin-teacher-chart-shell {
    border-color: rgba(148, 163, 184, 0.16);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.82) 100%);
}

body.pixelwar-dark-mode .admin-activity-filter-label {
    color: rgba(226, 232, 240, 0.7);
}

body.pixelwar-dark-mode .admin-activity-filter-input {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(15, 23, 42, 0.92);
    color: #e2e8f0;
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();
});

(() => {
    const chartElement = document.getElementById('admin-teacher-activity-chart');
    if (!chartElement || typeof window.Chart === 'undefined') {
        return;
    }

    const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
    const textColor = isDarkMode ? '#e2e8f0' : '#0f172a';
    const mutedColor = isDarkMode ? '#94a3b8' : '#64748b';
    const gridColor = isDarkMode ? 'rgba(148, 163, 184, 0.18)' : 'rgba(15, 23, 42, 0.08)';
    const tooltipBackground = isDarkMode ? '#0f172a' : '#ffffff';

    new window.Chart(chartElement, {
        type: 'line',
        data: {
            labels: <?= json_encode($teacherActivityChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Challenges',
                    data: <?= json_encode($teacherActivityChallengeValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.16)',
                    fill: true,
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
                    data: <?= json_encode($teacherActivityRoomValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
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
})();
</script>
