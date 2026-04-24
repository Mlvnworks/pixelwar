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
$teacherStatusLabel = $teacherVerified ? 'Profile ready' : 'Pending setup';
$teacherStatusClass = $teacherVerified ? 'bg-arcade-mint/35' : 'bg-arcade-yellow/35';

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

                        <div class="admin-teacher-activity-scroll mt-4">
                            <div class="admin-teacher-activity-grid" aria-label="<?= (int) $currentYear ?> teacher activity chart">
                                <?php foreach ($teacherActivityDays as $activityDay) : ?>
                                    <?php
                                    $challengeLabel = (int) $activityDay['challenges'] === 1 ? 'challenge' : 'challenges';
                                    $roomLabel = (int) $activityDay['rooms'] === 1 ? 'room' : 'rooms';
                                    $tooltip = $activityDay['date']->format('M j, Y')
                                        . ': ' . (int) $activityDay['challenges'] . ' ' . $challengeLabel . ' created'
                                        . ', ' . (int) $activityDay['rooms'] . ' ' . $roomLabel . ' created';
                                    ?>
                                    <span
                                        class="admin-teacher-activity-cell admin-teacher-activity-cell--<?= (int) $activityDay['level'] ?>"
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
                            <span class="admin-teacher-activity-cell admin-teacher-activity-cell--0"></span>
                            <span class="admin-teacher-activity-cell admin-teacher-activity-cell--1"></span>
                            <span class="admin-teacher-activity-cell admin-teacher-activity-cell--3"></span>
                            <span class="admin-teacher-activity-cell admin-teacher-activity-cell--5"></span>
                            <span>More</span>
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
.admin-teacher-activity-grid {
    display: grid;
    grid-auto-flow: column;
    grid-template-rows: repeat(7, 0.82rem);
    grid-auto-columns: 0.82rem;
    gap: 0.28rem;
    min-width: max-content;
    padding: 0.2rem;
}

.admin-teacher-activity-scroll {
    margin-inline: -0.25rem;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    padding: 2.2rem 0.25rem 1rem;
    scrollbar-width: thin;
}

.admin-teacher-activity-cell {
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

.admin-teacher-activity-cell--1 {
    background: rgba(37, 99, 235, 0.18);
}

.admin-teacher-activity-cell--2 {
    background: rgba(37, 99, 235, 0.34);
}

.admin-teacher-activity-cell--3 {
    background: rgba(14, 165, 233, 0.46);
}

.admin-teacher-activity-cell--4 {
    background: rgba(14, 165, 233, 0.66);
}

.admin-teacher-activity-cell--5 {
    background: #2563eb;
}

.admin-teacher-activity-cell:hover,
.admin-teacher-activity-cell:focus {
    z-index: 5;
    border-color: #111827;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
    transform: scale(1.18);
}

.admin-teacher-chart-tooltip {
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

body.pixelwar-dark-mode .admin-teacher-activity-cell {
    border-color: rgba(148, 163, 184, 0.22);
    background: rgba(148, 163, 184, 0.08);
}

body.pixelwar-dark-mode .admin-teacher-activity-cell--1 {
    background: rgba(96, 165, 250, 0.2);
}

body.pixelwar-dark-mode .admin-teacher-activity-cell--2 {
    background: rgba(96, 165, 250, 0.34);
}

body.pixelwar-dark-mode .admin-teacher-activity-cell--3 {
    background: rgba(56, 189, 248, 0.42);
}

body.pixelwar-dark-mode .admin-teacher-activity-cell--4 {
    background: rgba(56, 189, 248, 0.62);
}

body.pixelwar-dark-mode .admin-teacher-activity-cell--5 {
    background: #60a5fa;
}

body.pixelwar-dark-mode .admin-teacher-chart-tooltip {
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

    const cells = document.querySelectorAll('.admin-teacher-activity-cell[data-tooltip]');
    if (cells.length === 0) {
        return;
    }

    const tooltip = document.createElement('div');
    tooltip.className = 'admin-teacher-chart-tooltip';
    tooltip.hidden = true;
    document.body.appendChild(tooltip);

    const positionTooltip = (cell) => {
        const rect = cell.getBoundingClientRect();
        const text = cell.getAttribute('data-tooltip') || '';
        tooltip.textContent = text;
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
        cell.addEventListener('mousemove', () => positionTooltip(cell));
        cell.addEventListener('focus', () => positionTooltip(cell));
        cell.addEventListener('mouseleave', hideTooltip);
        cell.addEventListener('blur', hideTooltip);
    });

    window.addEventListener('scroll', hideTooltip, { passive: true });
    window.addEventListener('resize', hideTooltip);
})();
</script>
