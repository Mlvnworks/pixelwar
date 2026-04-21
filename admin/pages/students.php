<?php
$studentSearch = trim((string) ($_GET['q'] ?? ''));
$studentStatus = strtolower(trim((string) ($_GET['status'] ?? 'all')));
$studentStatusMap = [
    'all' => null,
    'verified' => 1,
    'pending' => 0,
];
$studentVerifiedFilter = $studentStatusMap[$studentStatus] ?? null;
$studentsPerPage = 25;
$requestedStudentsPage = max(1, (int) ($_GET['page'] ?? 1));
$studentTotalCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleFiltered(3, $studentSearch, $studentVerifiedFilter)
    : 0;
$studentTotalPages = max(1, (int) ceil($studentTotalCount / $studentsPerPage));
$studentsPage = min($requestedStudentsPage, $studentTotalPages);
$studentsOffset = ($studentsPage - 1) * $studentsPerPage;
$students = $userRepository instanceof UserRepository
    ? $userRepository->listUsersByRoleFiltered(3, $studentSearch, $studentVerifiedFilter, $studentsPerPage, $studentsOffset)
    : [];
$studentVerifiedCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleFiltered(3, '', 1)
    : 0;
$studentPendingCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleFiltered(3, '', 0)
    : 0;

$studentBuildQuery = static function (array $overrides = []) use ($studentSearch, $studentStatus, $studentsPage): string {
    $query = [
        'c' => 'students',
        'q' => $studentSearch,
        'status' => $studentStatus,
        'page' => $studentsPage,
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
        <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Management</p>
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Students</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Review student accounts, search by name or email, and filter setup status.
                </p>
            </div>
            <div class="grid gap-2 sm:grid-cols-3 lg:min-w-[26rem]">
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Total</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $studentTotalCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Verified</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $studentVerifiedCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Pending</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $studentPendingCount ?></strong>
                </article>
            </div>
        </section>

        <section class="teacher-panel p-5 md:p-6">
            <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_12rem_auto]" method="get" action="./">
                <input type="hidden" name="c" value="students">

                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Search</span>
                    <input
                        type="search"
                        name="q"
                        value="<?= htmlspecialchars($studentSearch, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Search username, email, or full name"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                </label>

                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Status</span>
                    <select
                        name="status"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                        <option value="all" <?= $studentStatus === 'all' ? 'selected' : '' ?>>All students</option>
                        <option value="verified" <?= $studentStatus === 'verified' ? 'selected' : '' ?>>Verified</option>
                        <option value="pending" <?= $studentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </label>

                <div class="flex flex-col gap-2 lg:justify-end">
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Apply</span>
                    </button>
                    <a href="./?c=students" class="teacher-link-button text-center">Reset</a>
                </div>
            </form>

            <div class="mt-5 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-white/85">
                <?php if ($students === []) : ?>
                    <div class="px-5 py-6 text-sm font-medium text-arcade-ink/55">
                        No student accounts matched the current filters.
                    </div>
                <?php else : ?>
                    <div class="max-h-[42rem] overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="sticky top-0 z-[1] bg-white/95">
                                <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                    <th class="px-4 py-3 font-semibold">Student</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                    <th class="px-4 py-3 font-semibold">Joined</th>
                                    <th class="px-4 py-3 font-semibold">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student) : ?>
                                    <?php
                                    $firstname = trim((string) ($student['firstname'] ?? ''));
                                    $lastname = trim((string) ($student['lastname'] ?? ''));
                                    $displayName = trim($firstname . ' ' . $lastname) ?: (string) $student['username'];
                                    $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2));
                                    $hasProfile = (int) ($student['user_details_id'] ?? 0) > 0;
                                    $isVerified = (int) ($student['is_verified'] ?? 0) === 1;
                                    ?>
                                    <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-bold text-arcade-ink">
                                                    <?php if (trim((string) ($student['avatar_url'] ?? '')) !== '') : ?>
                                                        <img src="<?= htmlspecialchars((string) $student['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                                    <?php else : ?>
                                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="truncate font-semibold text-arcade-ink"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs text-arcade-ink/55">@<?= htmlspecialchars((string) $student['username'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs text-arcade-ink/55"><?= htmlspecialchars((string) $student['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <span class="teacher-pill <?= $isVerified ? 'bg-arcade-mint' : 'bg-arcade-coral/30' ?>">
                                                    <?= $isVerified ? 'Verified' : 'Pending' ?>
                                                </span>
                                                <span class="teacher-pill <?= $hasProfile ? 'bg-arcade-cyan' : 'bg-white' ?>">
                                                    <?= $hasProfile ? 'Profile ready' : 'No profile' ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-arcade-ink/65 whitespace-nowrap">
                                            <?= htmlspecialchars(date('M j, Y', strtotime((string) $student['registration_date'])), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="px-4 py-3 text-arcade-ink/65">
                                            User ID #<?= (int) $student['user_id'] ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-5 flex flex-col gap-3 border-t border-arcade-ink/10 pt-4 md:flex-row md:items-center md:justify-between">
                <p class="text-sm font-medium text-arcade-ink/55">
                    Page <?= (int) $studentsPage ?> of <?= (int) $studentTotalPages ?> · <?= (int) $studentTotalCount ?> result<?= $studentTotalCount === 1 ? '' : 's' ?>
                </p>
                <div class="flex flex-wrap gap-2">
                    <a
                        href="<?= htmlspecialchars($studentBuildQuery(['page' => max(1, $studentsPage - 1)]), ENT_QUOTES, 'UTF-8') ?>"
                        class="teacher-button teacher-button--light gap-2 <?= $studentsPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>"
                    >
                        <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Previous</span>
                    </a>
                    <a
                        href="<?= htmlspecialchars($studentBuildQuery(['page' => min($studentTotalPages, $studentsPage + 1)]), ENT_QUOTES, 'UTF-8') ?>"
                        class="teacher-button teacher-button--light gap-2 <?= $studentsPage >= $studentTotalPages ? 'pointer-events-none opacity-50' : '' ?>"
                    >
                        <span>Next</span>
                        <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </section>
</main>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();
});
</script>
