<?php
$allLogs = $activityLogRepository instanceof ActivityLogRepository
    ? $activityLogRepository->listLatestOverall(500)
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
        <section class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Logs</p>
                <h1 class="text-3xl font-bold leading-tight md:text-4xl">Activity Logs</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/65 md:text-base">
                    Latest platform actions from admins, teachers, and students.
                </p>
            </div>
            <a href="./?c=dashboard" class="teacher-button teacher-button--light gap-2">
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                <span>Back to Dashboard</span>
            </a>
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

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();
});
</script>
