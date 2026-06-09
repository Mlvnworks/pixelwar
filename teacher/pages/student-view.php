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

$analyticsTrackedDays = 30;
$currentYear = (int) date('Y');
$analyticsEndDate = new DateTimeImmutable('today');
$yearStart = $analyticsEndDate->modify('-' . ($analyticsTrackedDays - 1) . ' days');
$completedCountsByDate = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->completedCountsByDate($studentViewId, $yearStart, $analyticsEndDate)
    : [];
$totalSolveCount = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->countCompletedForUser($studentViewId)
    : 0;
$totalPoints = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->totalCompletedPointsForUser($studentViewId)
    : 0;
if ($userRepository instanceof UserRepository) {
    $totalPoints = $userRepository->totalPlayerProgressPointsForUser($studentViewId);
}
$rankProgress = $rankRepository instanceof RankRepository
    ? $rankRepository->progressForPoints($totalPoints)
    : [
        'current_name' => 'Beginner',
        'display_requirement' => 500,
        'progress_percent' => min(100, (int) round(($totalPoints / 500) * 100)),
        'next_name' => 'Next Rank',
        'is_max_rank' => false,
    ];
$rankRequirementPoints = (int) ($rankProgress['display_requirement'] ?? 500);
$rankProgressPercent = (int) ($rankProgress['progress_percent'] ?? 0);
$currentRankName = (string) ($rankProgress['current_name'] ?? 'Beginner');
$nextRankName = (string) ($rankProgress['next_name'] ?? '');
$isMaxRank = (bool) ($rankProgress['is_max_rank'] ?? false);
$activityDays = [];
$studentAnalyticsChartLabels = [];
$studentAnalyticsChartValues = [];

for ($dayIndex = 0; $dayIndex < $analyticsTrackedDays; $dayIndex++) {
    $date = $yearStart->modify('+' . $dayIndex . ' days');
    $dateKey = $date->format('Y-m-d');
    $solves = (int) ($completedCountsByDate[$dateKey] ?? 0);
    $activityDays[] = [
        'date' => $date,
        'solves' => $solves,
        'level' => min($solves, 5),
    ];
    $studentAnalyticsChartLabels[] = $date->format('M j');
    $studentAnalyticsChartValues[] = $solves;
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

<main class="teacher-shell teacher-student-view-page relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
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
                                <h2 class="mt-1 text-xl font-bold"><?= htmlspecialchars($currentRankName, ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <span class="teacher-pill student-view-pill student-view-pill--points"><?= (int) $totalPoints ?> pts</span>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full border border-arcade-ink/10 bg-arcade-cream">
                            <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $rankProgressPercent ?>%;"></span>
                        </div>
                        <p class="student-view-label mt-2 text-xs font-semibold uppercase tracking-[0.08em]">
                            <?= (int) $totalPoints ?><?= $isMaxRank ? ' points' : ' / ' . (int) $rankRequirementPoints . ' points' ?>
                            <?php if (!$isMaxRank && $nextRankName !== '') : ?>
                                · Next: <?= htmlspecialchars($nextRankName, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
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
                                <h2 class="mt-1 text-2xl font-bold">Last 30 Days</h2>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="student-view-muted text-sm font-medium"><?= (int) $totalSolveCount ?> total solve<?= $totalSolveCount === 1 ? '' : 's' ?></p>
                                <a href="./?c=student-submissions&id=<?= (int) $studentViewId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                    <i data-lucide="list-checks" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>View Submissions</span>
                                </a>
                            </div>
                        </div>

                        <div class="student-view-chart-shell mt-4" aria-label="Student solving analytics chart for the last <?= (int) $analyticsTrackedDays ?> days">
                            <div class="student-view-chart-summary">
                                <p class="student-view-chart-summary__copy">Shows the student solve count day by day for the selected range.</p>
                            </div>
                            <div class="student-view-chart-stage">
                                <canvas id="student-view-analytics-chart" height="230"></canvas>
                            </div>
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

.student-view-chart-shell {
    border: 2px solid rgba(38, 25, 15, 0.12);
    border-radius: 1.25rem;
    background: linear-gradient(180deg, rgba(255, 253, 246, 0.92), rgba(255, 247, 232, 0.82));
    padding: 1rem;
}

.student-view-chart-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.student-view-chart-summary__copy {
    margin: 0;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.45;
    color: rgba(38, 25, 15, 0.58);
}

.student-view-chart-stage {
    position: relative;
    height: 13.2rem;
    width: 100%;
}

body.pixelwar-dark-mode .student-view-chart-shell {
    border-color: rgba(255, 247, 232, 0.12);
    background: #1f160f;
}

body.pixelwar-dark-mode .student-view-chart-summary__copy {
    color: rgba(255, 247, 232, 0.62);
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

@media (max-width: 640px) {
    .teacher-student-view-page {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .teacher-student-view-page .student-view-shell {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .teacher-student-view-page .student-view-shell > aside,
    .teacher-student-view-page .student-view-shell > section {
        width: min(95vw, 34rem) !important;
        max-width: min(95vw, 34rem) !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    .teacher-student-view-page .student-view-shell > section {
        margin-top: 1rem !important;
    }

    .teacher-student-view-page .student-view-shell > section > .student-view-panel,
    .teacher-student-view-page .student-view-shell aside .student-view-surface,
    .teacher-student-view-page .student-view-shell aside .student-view-panel {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .teacher-student-view-page .grid:has(> .student-view-surface),
    .teacher-student-view-page .grid:has(> .student-view-panel) {
        display: block !important;
        width: 100% !important;
    }

    .teacher-student-view-page .grid:has(> .student-view-surface) > .student-view-surface + .student-view-surface,
    .teacher-student-view-page .grid:has(> .student-view-panel) > .student-view-panel + .student-view-panel {
        margin-top: 0.75rem !important;
    }

    .teacher-student-view-page .student-view-shell > aside,
    .teacher-student-view-page .student-view-shell > section > .student-view-panel {
        padding: 1rem !important;
    }

    .teacher-student-view-page .student-view-chart-shell {
        width: 100% !important;
        max-width: 100% !important;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script>
(() => {
    const canvas = document.getElementById('student-view-analytics-chart');

    if (!canvas || typeof window.Chart === 'undefined') {
        return;
    }

    const context = canvas.getContext('2d');
    if (!context) {
        return;
    }

    const labels = <?= json_encode($studentAnalyticsChartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const values = <?= json_encode($studentAnalyticsChartValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
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
})();
</script>
