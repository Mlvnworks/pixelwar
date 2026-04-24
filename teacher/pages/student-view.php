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

for ($dayIndex = 0; $dayIndex < $analyticsTrackedDays; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $solves = (int) ($completedCountsByDate[$dateKey] ?? 0);
    $activityDays[] = [
        'date' => $date,
        'solves' => $solves,
        'level' => min($solves, 5),
    ];
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
$studentActiveState = (int) ($studentViewProfile['is_active'] ?? 0);
$studentStatusLabel = $studentActiveState === 1 ? 'Verified' : ($studentActiveState === -1 ? 'Rejected' : 'Pending');
$studentStatusClass = $studentActiveState === 1 ? 'student-view-pill--verified' : ($studentActiveState === -1 ? 'student-view-pill--rejected' : 'student-view-pill--pending');
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
            <section class="teacher-panel student-view-panel p-6">
                <h1 class="text-2xl font-bold">Student not found</h1>
                <p class="student-view-muted mt-2 text-sm font-medium leading-7">The requested student profile is unavailable or has been removed.</p>
            </section>
        <?php else : ?>
            <section class="student-view-shell grid gap-5 xl:grid-cols-[minmax(0,0.78fr)_minmax(0,1.22fr)]">
                <aside class="teacher-panel student-view-panel min-w-0 p-5 md:p-6">
                    <div class="flex flex-col items-start gap-4 sm:flex-row">
                        <span class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-[24px] border-4 border-arcade-ink bg-arcade-yellow font-arcade text-xl text-arcade-ink shadow-[6px_6px_0_rgba(38,25,15,0.18)]">
                            <?php if ($studentAvatarUrl !== '') : ?>
                                <img src="<?= htmlspecialchars($studentAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                            <?php else : ?>
                                <?= htmlspecialchars($studentInitials, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <div class="min-w-0 w-full">
                            <p class="student-view-label text-sm font-semibold uppercase tracking-[0.08em]">Student Profile</p>
                            <h1 class="mt-1 break-words text-3xl font-bold leading-tight"><?= htmlspecialchars($studentDisplayName, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="student-view-muted mt-2 break-all text-sm font-medium">@<?= htmlspecialchars($studentUsername, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="student-view-muted break-all text-sm font-medium"><?= htmlspecialchars($studentEmail, ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="teacher-pill student-view-pill <?= htmlspecialchars($studentStatusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($studentStatusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="teacher-pill student-view-pill student-view-pill--info"><?= (int) ($studentViewProfile['is_verified'] ?? 0) === 1 ? 'Email verified' : 'Email pending' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div class="student-view-surface rounded-2xl px-4 py-3">
                            <p class="student-view-label text-[11px] font-semibold uppercase tracking-[0.08em]">Student ID</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= htmlspecialchars($studentNumber !== '' ? $studentNumber : 'Not assigned yet', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="student-view-surface rounded-2xl px-4 py-3">
                            <p class="student-view-label text-[11px] font-semibold uppercase tracking-[0.08em]">Joined</p>
                            <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= htmlspecialchars(date('M j, Y', strtotime((string) ($studentViewProfile['registration_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <div class="student-view-surface mt-5 rounded-[22px] p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="student-view-label text-[11px] font-semibold uppercase tracking-[0.08em]">Rank Progress</p>
                                <h2 class="mt-1 text-xl font-bold"><?= htmlspecialchars($dummyRank, ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <span class="teacher-pill student-view-pill student-view-pill--points"><?= (int) $totalPoints ?> pts</span>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full border border-arcade-ink/10 bg-arcade-cream">
                            <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $rankProgressPercent ?>%;"></span>
                        </div>
                        <p class="student-view-label mt-2 text-xs font-semibold uppercase tracking-[0.08em]"><?= (int) $totalPoints ?> / <?= (int) $rankRequirementPoints ?> points</p>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <article class="teacher-panel student-view-panel min-w-0 px-4 py-3">
                            <p class="student-view-label text-xs font-semibold uppercase tracking-[0.08em]">Total Solve</p>
                            <strong class="mt-1 block text-2xl font-bold"><?= (int) $totalSolveCount ?></strong>
                        </article>
                        <article class="teacher-panel student-view-panel min-w-0 px-4 py-3">
                            <p class="student-view-label text-xs font-semibold uppercase tracking-[0.08em]">Points</p>
                            <strong class="mt-1 block text-2xl font-bold"><?= (int) $totalPoints ?></strong>
                        </article>
                    </div>

                    <div class="student-view-surface mt-5 rounded-[22px] p-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="student-view-label text-sm font-semibold uppercase tracking-[0.08em]">Submitted ID</p>
                        </div>
                        <div class="student-view-soft-panel mt-3 overflow-hidden rounded-2xl">
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
                    <article class="teacher-panel student-view-panel min-w-0 p-5 md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="student-view-label text-sm font-semibold uppercase tracking-[0.08em]">Solving Analytics</p>
                                <h2 class="mt-1 text-2xl font-bold"><?= (int) $currentYear ?> activity</h2>
                            </div>
                            <p class="student-view-muted text-sm font-medium"><?= (int) $totalSolveCount ?> total solve<?= $totalSolveCount === 1 ? '' : 's' ?></p>
                        </div>

                        <div class="teacher-student-activity-scroll mt-4">
                            <div class="teacher-student-activity-grid" aria-label="<?= (int) $currentYear ?> student solving analytics">
                                <?php foreach ($activityDays as $activityDay) : ?>
                                    <?php
                                    $level = (int) ($activityDay['level'] ?? 0);
                                    $tooltip = $activityDay['date']->format('M j, Y')
                                        . ': ' . (int) ($activityDay['solves'] ?? 0) . ' solve'
                                        . ((int) ($activityDay['solves'] ?? 0) === 1 ? '' : 's');
                                    ?>
                                    <span
                                        class="teacher-student-activity-cell teacher-student-activity-cell--<?= $level ?>"
                                        tabindex="0"
                                        role="button"
                                        aria-label="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                                        data-tooltip="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="student-view-label mt-3 flex flex-wrap items-center gap-2 text-xs font-black">
                            <span>Less</span>
                            <span class="teacher-student-activity-cell teacher-student-activity-cell--0"></span>
                            <span class="teacher-student-activity-cell teacher-student-activity-cell--1"></span>
                            <span class="teacher-student-activity-cell teacher-student-activity-cell--3"></span>
                            <span class="teacher-student-activity-cell teacher-student-activity-cell--5"></span>
                            <span>More</span>
                        </div>
                    </article>

                    <article class="teacher-panel student-view-panel min-w-0 p-5 md:p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="student-view-label text-sm font-semibold uppercase tracking-[0.08em]">Student Logs</p>
                                <h2 class="mt-1 text-2xl font-bold">Latest activity</h2>
                            </div>
                            <p class="student-view-muted text-sm font-medium"><?= (int) $studentLogTotal ?> record<?= $studentLogTotal === 1 ? '' : 's' ?></p>
                        </div>

                        <div class="student-view-surface mt-5 overflow-hidden rounded-2xl">
                            <?php if ($studentLogRows === []) : ?>
                                <div class="px-4 py-5 text-sm font-medium text-arcade-ink/55">No activity logs recorded yet.</div>
                            <?php else : ?>
                                <div class="max-h-[34rem] overflow-auto">
                                    <table class="min-w-[42rem] w-full text-left text-sm">
                                        <thead class="student-view-table-head sticky top-0">
                                            <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em]">
                                                <th class="px-4 py-3 font-semibold">Date</th>
                                                <th class="px-4 py-3 font-semibold">Category</th>
                                                <th class="px-4 py-3 font-semibold">Log</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($studentLogRows as $logRow) : ?>
                                                <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                                    <td class="student-view-muted px-4 py-3 whitespace-nowrap font-medium"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) $logRow['date_created'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="px-4 py-3">
                                                        <span class="teacher-pill student-view-pill student-view-pill--category"><?= htmlspecialchars(ucfirst((string) ($logRow['category'] ?? 'general')), ENT_QUOTES, 'UTF-8') ?></span>
                                                    </td>
                                                    <td class="student-view-copy px-4 py-3 leading-6"><?= htmlspecialchars((string) ($logRow['log_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 border-t border-arcade-ink/10 pt-4 md:flex-row md:items-center md:justify-between">
                            <p class="student-view-muted text-sm font-medium">
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

<div id="teacher-student-analytics-tooltip" class="analytics-activity-tooltip" role="status" hidden></div>

<style>
.student-view-shell,
.student-view-shell > *,
.student-view-shell article,
.student-view-shell aside {
    min-width: 0;
}

.student-view-panel {
    border-color: inherit;
}

.student-view-label {
    color: rgba(38, 25, 15, 0.72);
}

.student-view-muted {
    color: rgba(38, 25, 15, 0.66);
}

.student-view-copy {
    color: rgba(38, 25, 15, 0.82);
}

.student-view-table-head {
    background: rgba(255, 255, 255, 0.94);
    color: rgba(38, 25, 15, 0.7);
}

.student-view-surface {
    border: 1px solid rgba(38, 25, 15, 0.1);
    background: rgba(255, 255, 255, 0.82) !important;
}

.student-view-soft-panel {
    border: 1px solid rgba(38, 25, 15, 0.1);
    background: rgba(255, 247, 232, 0.78) !important;
}

.student-view-pill {
    background: rgba(255, 247, 232, 0.78) !important;
}

.student-view-pill--info {
    background: rgba(76, 201, 240, 0.24) !important;
}

.student-view-pill--points {
    background: rgba(255, 209, 102, 0.38) !important;
}

.student-view-pill--category {
    background: rgba(255, 217, 168, 0.58) !important;
}

.student-view-pill--verified {
    background: rgba(139, 211, 199, 0.42) !important;
}

.student-view-pill--pending {
    background: rgba(255, 209, 102, 0.42) !important;
}

.student-view-pill--rejected {
    background: rgba(249, 115, 115, 0.32) !important;
}

.teacher-student-activity-grid {
    display: grid;
    grid-auto-flow: column;
    grid-template-rows: repeat(7, 0.92rem);
    grid-auto-columns: 0.92rem;
    gap: 0.28rem;
    min-width: max-content;
    padding: 0.2rem;
}

.teacher-student-activity-scroll {
    margin-inline: -0.25rem;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    padding: 0.3rem 0.25rem 1rem;
    scrollbar-width: thin;
}

.teacher-student-activity-cell {
    position: relative;
    display: inline-block;
    width: 0.92rem;
    height: 0.92rem;
    border: 1px solid rgba(38, 25, 15, 0.1);
    border-radius: 0.32rem;
    background: rgba(38, 25, 15, 0.06);
    outline: none;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.teacher-student-activity-cell--1 {
    background: rgba(255, 209, 102, 0.52);
}

.teacher-student-activity-cell--2 {
    background: rgba(255, 209, 102, 0.78);
}

.teacher-student-activity-cell--3 {
    background: rgba(255, 140, 66, 0.74);
}

.teacher-student-activity-cell--4 {
    background: rgba(255, 140, 66, 0.9);
}

.teacher-student-activity-cell--5 {
    background: #f97373;
}

.teacher-student-activity-cell:hover,
.teacher-student-activity-cell:focus {
    z-index: 5;
    border-color: rgba(38, 25, 15, 0.3);
    box-shadow: 0 8px 18px rgba(38, 25, 15, 0.14);
    transform: translateY(-1px) scale(1.08);
}

.analytics-activity-tooltip {
    position: fixed;
    z-index: 9999;
    max-width: min(18rem, calc(100vw - 1.5rem));
    border: 2px solid #26190f;
    border-radius: 0.9rem;
    background: #fffdf6;
    color: #26190f;
    padding: 0.55rem 0.75rem;
    font-size: 0.72rem;
    font-weight: 800;
    line-height: 1.35;
    box-shadow: 6px 6px 0 rgba(38, 25, 15, 0.18);
    pointer-events: none;
    transform: translate(-50%, 0);
}

body.pixelwar-dark-mode .student-view-surface {
    border-color: rgba(255, 247, 232, 0.1);
    background: rgba(31, 22, 15, 0.94) !important;
}

body.pixelwar-dark-mode .student-view-panel {
    border-color: rgba(255, 247, 232, 0.72) !important;
    background: linear-gradient(145deg, #24170d 0%, #1a1310 100%) !important;
    box-shadow: 8px 8px 0 rgba(0, 0, 0, 0.42) !important;
}

body.pixelwar-dark-mode .student-view-soft-panel {
    border-color: rgba(255, 247, 232, 0.1);
    background: rgba(42, 29, 19, 0.94) !important;
}

body.pixelwar-dark-mode .student-view-pill {
    background: rgba(255, 247, 232, 0.08) !important;
}

body.pixelwar-dark-mode .student-view-pill--info {
    background: rgba(76, 201, 240, 0.18) !important;
}

body.pixelwar-dark-mode .student-view-label {
    color: rgba(255, 247, 232, 0.78);
}

body.pixelwar-dark-mode .student-view-muted {
    color: rgba(255, 247, 232, 0.72);
}

body.pixelwar-dark-mode .student-view-copy {
    color: rgba(255, 247, 232, 0.88);
}

body.pixelwar-dark-mode .student-view-table-head {
    background: rgba(31, 22, 15, 0.96);
    color: rgba(255, 247, 232, 0.74);
}

body.pixelwar-dark-mode .teacher-student-activity-cell {
    border-color: rgba(255, 247, 232, 0.26);
    background: rgba(255, 247, 232, 0.14);
}

body.pixelwar-dark-mode .teacher-student-activity-cell--1 {
    background: rgba(255, 209, 102, 0.38);
}

body.pixelwar-dark-mode .teacher-student-activity-cell--2 {
    background: rgba(255, 209, 102, 0.58);
}

body.pixelwar-dark-mode .teacher-student-activity-cell--3 {
    background: rgba(255, 140, 66, 0.62);
}

body.pixelwar-dark-mode .teacher-student-activity-cell--4 {
    background: rgba(255, 140, 66, 0.82);
}

body.pixelwar-dark-mode .teacher-student-activity-cell--5 {
    background: #ff8c42;
}

body.pixelwar-dark-mode .student-view-pill--points {
    background: rgba(255, 209, 102, 0.24) !important;
}

body.pixelwar-dark-mode .student-view-pill--category {
    background: rgba(255, 217, 168, 0.18) !important;
}

body.pixelwar-dark-mode .student-view-pill--verified {
    background: rgba(139, 211, 199, 0.22) !important;
}

body.pixelwar-dark-mode .student-view-pill--pending {
    background: rgba(255, 209, 102, 0.24) !important;
}

body.pixelwar-dark-mode .student-view-pill--rejected {
    background: rgba(249, 115, 115, 0.2) !important;
}
</style>

<script>
(() => {
    const tooltip = document.getElementById('teacher-student-analytics-tooltip');
    const cells = Array.from(document.querySelectorAll('.teacher-student-activity-cell[data-tooltip]'));

    const hideTooltip = () => {
        if (tooltip) {
            tooltip.hidden = true;
        }
    };

    const showTooltip = (cell) => {
        if (!tooltip) {
            return;
        }

        tooltip.textContent = cell.getAttribute('data-tooltip') || '';
        tooltip.hidden = false;

        const rect = cell.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportPadding = 12;
        let left = rect.left + rect.width / 2;
        let top = rect.top - tooltipRect.height - 10;

        if (top < viewportPadding) {
            top = rect.bottom + 10;
        }

        const minLeft = viewportPadding + tooltipRect.width / 2;
        const maxLeft = window.innerWidth - viewportPadding - tooltipRect.width / 2;
        left = Math.max(minLeft, Math.min(maxLeft, left));

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    };

    cells.forEach((cell) => {
        cell.addEventListener('mouseenter', () => showTooltip(cell));
        cell.addEventListener('focus', () => showTooltip(cell));
        cell.addEventListener('mouseleave', hideTooltip);
        cell.addEventListener('blur', hideTooltip);
    });

    window.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);
})();
</script>
