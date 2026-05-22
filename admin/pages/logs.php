<?php
$allLogs = $activityLogRepository instanceof ActivityLogRepository
    ? $activityLogRepository->listLatestOverall(500)
    : [];
$selectedCategory = strtolower(trim((string) ($_GET['category'] ?? 'all')));
$roleLabels = [
    1 => 'Admin',
    2 => 'Teacher',
    3 => 'Student',
];
$categoryCounts = [];
$allLogsTotal = count($allLogs);

foreach ($allLogs as $log) {
    $categoryKey = strtolower(trim((string) ($log['category'] ?? 'general')));
    if ($categoryKey === '') {
        $categoryKey = 'general';
    }

    $categoryCounts[$categoryKey] = (int) ($categoryCounts[$categoryKey] ?? 0) + 1;
}

ksort($categoryCounts);

if ($selectedCategory !== 'all' && isset($categoryCounts[$selectedCategory])) {
    $allLogs = array_values(array_filter(
        $allLogs,
        static fn(array $log): bool => strtolower(trim((string) ($log['category'] ?? 'general'))) === $selectedCategory
    ));
}

$logBuildQuery = static function (string $category): string {
    $query = ['c' => 'logs'];
    if ($category !== 'all') {
        $query['category'] = $category;
    }

    return './?' . http_build_query($query);
};
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Logs</p>
                <h1 class="text-3xl font-bold leading-tight md:text-4xl">Activity Logs</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/65 md:text-base">
                    Latest platform actions from admins, teachers, and students.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#admin-logs-export-modal">
                    <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Export CSV</span>
                </button>
                <a href="./?c=dashboard" class="teacher-button teacher-button--light gap-2">
                    <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </section>

        <section class="teacher-panel p-5 md:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Categorization</p>
                    <h2 class="mt-1 text-xl font-bold">Filter by category</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="<?= htmlspecialchars($logBuildQuery('all'), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button gap-2 <?= $selectedCategory === 'all' ? 'teacher-button--primary' : 'teacher-button--light' ?>">
                        <span>All</span>
                        <span class="teacher-pill bg-white/70"><?= (int) $allLogsTotal ?></span>
                    </a>
                    <?php foreach ($categoryCounts as $categoryName => $categoryTotal) : ?>
                        <a href="<?= htmlspecialchars($logBuildQuery($categoryName), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button gap-2 <?= $selectedCategory === $categoryName ? 'teacher-button--primary' : 'teacher-button--light' ?>">
                            <span><?= htmlspecialchars(ucfirst($categoryName), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="teacher-pill bg-white/70"><?= (int) $categoryTotal ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="teacher-panel p-0 overflow-hidden">
            <?php if ($allLogs === []) : ?>
                <div class="px-5 py-5 text-sm font-medium text-arcade-ink/55">
                    No activity logs recorded yet.
                </div>
            <?php else : ?>
                <div class="max-h-[70vh] overflow-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="sticky top-0 z-[1] bg-white/95">
                            <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">User</th>
                                <th class="px-4 py-3 font-semibold">Role</th>
                                <th class="px-4 py-3 font-semibold">Category</th>
                                <th class="px-4 py-3 font-semibold">Log</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allLogs as $log) : ?>
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
        </section>
    </section>
</main>

<div class="modal fade" id="admin-logs-export-modal" tabindex="-1" aria-labelledby="admin-logs-export-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]" action="./" method="get">
            <input type="hidden" name="c" value="logs">
            <input type="hidden" name="export" value="csv">
            <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory, ENT_QUOTES, 'UTF-8') ?>">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Export Logs</p>
                    <h2 class="modal-title mt-2 text-2xl font-bold" id="admin-logs-export-modal-title">Choose date range</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-5 pb-5 pt-2">
                <p class="text-sm font-semibold leading-6 text-arcade-ink/65">Export the current activity log view as CSV for the exact date range you choose.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="admin-logs-export-date-field">
                        <span>Start Date</span>
                        <input type="date" name="export_start_date" max="<?= htmlspecialchars((new DateTimeImmutable('today'))->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                    <label class="admin-logs-export-date-field">
                        <span>End Date</span>
                        <input type="date" name="export_end_date" max="<?= htmlspecialchars((new DateTimeImmutable('today'))->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">Export CSV</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.admin-logs-export-date-field {
    display: grid;
    gap: 0.45rem;
}

.admin-logs-export-date-field span {
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.6);
}

.admin-logs-export-date-field input {
    min-height: 3rem;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.96);
    padding: 0.8rem 0.95rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
}

body.pixelwar-dark-mode .admin-logs-export-date-field span {
    color: rgba(226, 232, 240, 0.7);
}

body.pixelwar-dark-mode .admin-logs-export-date-field input {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(15, 23, 42, 0.92);
    color: #e2e8f0;
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();
});
</script>
