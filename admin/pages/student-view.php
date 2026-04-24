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
                            <p class="text-sm font-medium text-arcade-ink/55"><?= (int) $totalSolveCount ?> total solve<?= $totalSolveCount === 1 ? '' : 's' ?></p>
                        </div>

                        <div class="admin-student-activity-scroll mt-4">
                            <div class="admin-student-activity-grid" aria-label="<?= (int) $currentYear ?> student solving analytics">
                                <?php foreach ($activityDays as $activityDay) : ?>
                                    <?php
                                    $level = (int) ($activityDay['level'] ?? 0);
                                    $tooltip = $activityDay['date']->format('M j, Y')
                                        . ': ' . (int) ($activityDay['solves'] ?? 0) . ' solve'
                                        . ((int) ($activityDay['solves'] ?? 0) === 1 ? '' : 's');
                                    ?>
                                    <span
                                        class="admin-student-activity-cell admin-student-activity-cell--<?= $level ?>"
                                        tabindex="0"
                                        role="button"
                                        aria-label="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                                        data-tooltip="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') ?>"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-black text-arcade-ink/55">
                            <span>Less</span>
                            <span class="admin-student-activity-cell admin-student-activity-cell--0"></span>
                            <span class="admin-student-activity-cell admin-student-activity-cell--1"></span>
                            <span class="admin-student-activity-cell admin-student-activity-cell--3"></span>
                            <span class="admin-student-activity-cell admin-student-activity-cell--5"></span>
                            <span>More</span>
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

.admin-student-activity-grid {
    display: grid;
    grid-auto-flow: column;
    grid-template-rows: repeat(7, 0.82rem);
    grid-auto-columns: 0.82rem;
    gap: 0.28rem;
    min-width: max-content;
    padding: 0.2rem;
}

.admin-student-activity-scroll {
    margin-inline: -0.25rem;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    padding: 0.3rem 0.25rem 1rem;
    scrollbar-width: thin;
}

.admin-student-activity-cell {
    position: relative;
    display: inline-block;
    width: 0.82rem;
    height: 0.82rem;
    border: 1px solid rgba(17, 24, 39, 0.12);
    border-radius: 0.22rem;
    background: rgba(17, 24, 39, 0.06);
    outline: none;
    transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.admin-student-activity-cell--1 {
    background: rgba(245, 158, 11, 0.18);
}

.admin-student-activity-cell--2 {
    background: rgba(245, 158, 11, 0.34);
}

.admin-student-activity-cell--3 {
    background: rgba(249, 115, 22, 0.46);
}

.admin-student-activity-cell--4 {
    background: rgba(249, 115, 22, 0.66);
}

.admin-student-activity-cell--5 {
    background: #f59e0b;
}

.admin-student-activity-cell:hover,
.admin-student-activity-cell:focus {
    z-index: 5;
    border-color: #111827;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.22);
    transform: scale(1.18);
}

.admin-student-chart-tooltip {
    position: fixed;
    z-index: 9999;
    max-width: min(18rem, calc(100vw - 1.5rem));
    border: 1px solid rgba(17, 24, 39, 0.12);
    border-radius: 0.75rem;
    background: #ffffff;
    padding: 0.55rem 0.75rem;
    color: #111827;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.35;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    pointer-events: none;
    transform: translate(-50%, 0);
}

body.pixelwar-dark-mode .admin-student-activity-cell {
    border-color: rgba(148, 163, 184, 0.22);
    background: rgba(148, 163, 184, 0.08);
}

body.pixelwar-dark-mode .admin-student-activity-cell--1 {
    background: rgba(251, 191, 36, 0.2);
}

body.pixelwar-dark-mode .admin-student-activity-cell--2 {
    background: rgba(251, 191, 36, 0.34);
}

body.pixelwar-dark-mode .admin-student-activity-cell--3 {
    background: rgba(251, 146, 60, 0.42);
}

body.pixelwar-dark-mode .admin-student-activity-cell--4 {
    background: rgba(251, 146, 60, 0.62);
}

body.pixelwar-dark-mode .admin-student-activity-cell--5 {
    background: #fbbf24;
}

body.pixelwar-dark-mode .admin-student-chart-tooltip {
    border-color: rgba(148, 163, 184, 0.28);
    background: #111827;
    color: #f8fafc;
    box-shadow: 0 14px 28px rgba(2, 6, 23, 0.4);
}
</style>

<script>
(() => {
    if (window.lucide) {
        window.lucide.createIcons();
    } else {
        window.addEventListener('load', () => window.lucide?.createIcons());
    }

    const cells = document.querySelectorAll('.admin-student-activity-cell[data-tooltip]');
    if (cells.length === 0) {
        return;
    }

    const tooltip = document.createElement('div');
    tooltip.className = 'admin-student-chart-tooltip';
    tooltip.hidden = true;
    document.body.appendChild(tooltip);

    const positionTooltip = (cell) => {
        const rect = cell.getBoundingClientRect();
        tooltip.textContent = cell.getAttribute('data-tooltip') || '';
        tooltip.hidden = false;

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

    const hideTooltip = () => {
        tooltip.hidden = true;
    };

    cells.forEach((cell) => {
        cell.addEventListener('mouseenter', () => positionTooltip(cell));
        cell.addEventListener('focus', () => positionTooltip(cell));
        cell.addEventListener('mouseleave', hideTooltip);
        cell.addEventListener('blur', hideTooltip);
    });

    window.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);
})();
</script>
