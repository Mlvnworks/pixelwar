<?php
$studentPresenceThreshold = time() - 90;
$studentSearch = trim((string) ($_GET['q'] ?? ''));
$studentStatus = strtolower(trim((string) ($_GET['status'] ?? 'all')));
$studentStatusMap = [
    'all' => null,
    'verified' => 1,
    'pending' => 0,
    'rejected' => -1,
];
$studentActiveFilter = $studentStatusMap[$studentStatus] ?? null;
$studentsPerPage = 25;
$requestedStudentsPage = max(1, (int) ($_GET['page'] ?? 1));
$studentTotalCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleFiltered(3, $studentSearch, $studentActiveFilter)
    : 0;
$studentTotalPages = max(1, (int) ceil($studentTotalCount / $studentsPerPage));
$studentsPage = min($requestedStudentsPage, $studentTotalPages);
$studentsOffset = ($studentsPage - 1) * $studentsPerPage;
$students = $userRepository instanceof UserRepository
    ? $userRepository->listUsersByRoleFiltered(3, $studentSearch, $studentActiveFilter, $studentsPerPage, $studentsOffset)
    : [];
$studentVerifiedCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleFiltered(3, '', 1)
    : 0;
$studentPendingCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleFiltered(3, '', 0)
    : 0;
$studentRejectedCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleFiltered(3, '', -1)
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

<main class="teacher-shell teacher-students-page relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Management</p>
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Students</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Review student accounts, see who is online right now, and open individual progress details.
                </p>
            </div>
            <div class="teacher-students-summary-grid grid gap-2 sm:grid-cols-2 xl:grid-cols-4 xl:min-w-[34rem]">
                <article class="teacher-panel teacher-students-summary-card px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Total</p>
                    <strong id="teacher-students-total-count" class="mt-1 block text-2xl font-bold"><?= (int) $studentTotalCount ?></strong>
                </article>
                <article class="teacher-panel teacher-students-summary-card px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Verified</p>
                    <strong id="teacher-students-verified-count" class="mt-1 block text-2xl font-bold"><?= (int) $studentVerifiedCount ?></strong>
                </article>
                <article class="teacher-panel teacher-students-summary-card px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Pending</p>
                    <strong id="teacher-students-pending-count" class="mt-1 block text-2xl font-bold"><?= (int) $studentPendingCount ?></strong>
                </article>
                <article class="teacher-panel teacher-students-summary-card px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Rejected</p>
                    <strong id="teacher-students-rejected-count" class="mt-1 block text-2xl font-bold"><?= (int) $studentRejectedCount ?></strong>
                </article>
            </div>
        </section>

        <section class="teacher-panel teacher-students-list-panel p-5 md:p-6">
            <form class="flex flex-col gap-3 xl:flex-row xl:items-end" method="get" action="./">
                <input type="hidden" name="c" value="students">

                <label class="grid gap-2 xl:min-w-0 xl:flex-[1.45]">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Search</span>
                    <input
                        type="search"
                        name="q"
                        value="<?= htmlspecialchars($studentSearch, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Search username, email, or full name"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                </label>

                <label class="grid gap-2 xl:w-[12rem]">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Status</span>
                    <select
                        name="status"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                        <option value="all" <?= $studentStatus === 'all' ? 'selected' : '' ?>>All students</option>
                        <option value="verified" <?= $studentStatus === 'verified' ? 'selected' : '' ?>>Verified</option>
                        <option value="pending" <?= $studentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="rejected" <?= $studentStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </label>

                <div class="flex flex-wrap items-center gap-2 xl:pb-[1px]">
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Apply</span>
                    </button>
                    <a href="./?c=students" class="teacher-link-button text-center">Reset</a>
                </div>
            </form>

            <div class="teacher-students-table-shell mt-5 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-white/85">
                <div id="teacher-students-empty-state" class="px-5 py-6 text-sm font-medium text-arcade-ink/55<?= $students === [] ? '' : ' hidden' ?>">
                    No students matched the current filters.
                </div>
                <div id="teacher-students-table-wrap" class="teacher-students-table-wrap max-h-[42rem] overflow-auto<?= $students === [] ? ' hidden' : '' ?>">
                    <table class="teacher-students-table min-w-full text-left text-sm">
                        <thead class="sticky top-0 z-[1] bg-white/95">
                            <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                <th class="px-4 py-3 font-semibold">Student</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Joined</th>
                                <th class="px-4 py-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teacher-students-tbody">
                            <?php foreach ($students as $student) : ?>
                                    <?php
                                    $firstname = trim((string) ($student['firstname'] ?? ''));
                                    $lastname = trim((string) ($student['lastname'] ?? ''));
                                    $displayName = trim($firstname . ' ' . $lastname) ?: (string) $student['username'];
                                    $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2));
                                    $hasProfile = (int) ($student['user_details_id'] ?? 0) > 0;
                                    $isEmailVerified = (int) ($student['is_verified'] ?? 0) === 1;
                                    $activeState = (int) ($student['is_active'] ?? 0);
                                    $statusLabel = $activeState === 1 ? 'Verified' : ($activeState === -1 ? 'Rejected' : 'Pending');
                                    $statusClass = $activeState === 1 ? 'bg-arcade-mint' : ($activeState === -1 ? 'bg-arcade-coral/30' : 'bg-arcade-yellow/40');
                                    $isOnline = !empty($student['last_seen_at']) && strtotime((string) $student['last_seen_at']) >= $studentPresenceThreshold;
                                    $presenceLabel = $isOnline ? 'Online' : 'Offline';
                                    $presenceClass = $isOnline ? 'bg-arcade-mint/35' : 'bg-white';
                                    ?>
                                    <tr class="teacher-student-row border-b border-arcade-ink/10 align-top last:border-b-0">
                                        <td class="px-4 py-3">
                                            <div class="teacher-student-identity flex items-center gap-3">
                                                <span class="relative grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-bold text-arcade-ink">
                                                    <?php if (trim((string) ($student['avatar_url'] ?? '')) !== '') : ?>
                                                        <img src="<?= htmlspecialchars((string) $student['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                                    <?php else : ?>
                                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                    <span class="absolute bottom-0.5 right-0.5 h-3 w-3 rounded-full border-2 border-white <?= $isOnline ? 'bg-emerald-500' : 'bg-slate-300' ?>" aria-hidden="true"></span>
                                                </span>
                                                <div class="teacher-student-copy min-w-0">
                                                    <div class="truncate font-semibold text-arcade-ink"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs text-arcade-ink/55">@<?= htmlspecialchars((string) $student['username'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs text-arcade-ink/55"><?= htmlspecialchars((string) $student['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <span class="teacher-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <span class="teacher-pill <?= $isEmailVerified ? 'bg-arcade-cyan/25' : 'bg-white' ?>">
                                                    <?= $isEmailVerified ? 'Email verified' : 'Email pending' ?>
                                                </span>
                                                <span class="teacher-pill <?= $hasProfile ? 'bg-arcade-cyan' : 'bg-white' ?>">
                                                    <?= $hasProfile ? 'Profile ready' : 'No profile' ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-arcade-ink/65 whitespace-nowrap">
                                            <?= htmlspecialchars(date('M j, Y', strtotime((string) $student['registration_date'])), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a
                                                href="./?c=student-view&id=<?= (int) ($student['user_id'] ?? 0) ?>"
                                                class="teacher-button teacher-button--light gap-2 no-underline"
                                            >
                                                <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                                <span>Open</span>
                                            </a>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-3 border-t border-arcade-ink/10 pt-4 md:flex-row md:items-center md:justify-between">
                <p id="teacher-students-page-status" class="text-sm font-medium text-arcade-ink/55">
                    Page <?= (int) $studentsPage ?> of <?= (int) $studentTotalPages ?> · <?= (int) $studentTotalCount ?> result<?= $studentTotalCount === 1 ? '' : 's' ?>
                </p>
                <div class="flex flex-wrap gap-2">
                    <a
                        id="teacher-students-prev"
                        href="<?= htmlspecialchars($studentBuildQuery(['page' => max(1, $studentsPage - 1)]), ENT_QUOTES, 'UTF-8') ?>"
                        class="teacher-button teacher-button--light gap-2 <?= $studentsPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>"
                    >
                        <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Previous</span>
                    </a>
                    <a
                        id="teacher-students-next"
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
(() => {
    const tbody = document.getElementById('teacher-students-tbody');
    const emptyState = document.getElementById('teacher-students-empty-state');
    const tableWrap = document.getElementById('teacher-students-table-wrap');
    const totalNode = document.getElementById('teacher-students-total-count');
    const verifiedNode = document.getElementById('teacher-students-verified-count');
    const pendingNode = document.getElementById('teacher-students-pending-count');
    const rejectedNode = document.getElementById('teacher-students-rejected-count');
    const pageStatus = document.getElementById('teacher-students-page-status');
    const prevLink = document.getElementById('teacher-students-prev');
    const nextLink = document.getElementById('teacher-students-next');
    const csrfToken = <?= json_encode(teacherPanelCsrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const currentQuery = {
        q: <?= json_encode($studentSearch, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        status: <?= json_encode($studentStatus, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        page: <?= (int) $studentsPage ?>,
    };
    const onlineThresholdSeconds = 90;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;',
    }[character]));

    const initialsFor = (label) => {
        const normalized = String(label || 'ST').replace(/[^a-z0-9]+/ig, '');
        return (normalized.slice(0, 2) || 'ST').toUpperCase();
    };

    const isOnline = (lastSeenAt) => {
        if (!lastSeenAt) {
            return false;
        }

        const timestamp = Date.parse(lastSeenAt);
        if (Number.isNaN(timestamp)) {
            return false;
        }

        return timestamp >= (Date.now() - (onlineThresholdSeconds * 1000));
    };

    const studentRow = (student) => {
        const firstname = String(student.firstname || '').trim();
        const lastname = String(student.lastname || '').trim();
        const displayName = `${firstname} ${lastname}`.trim() || String(student.username || 'Student');
        const activeState = Number(student.is_active || 0);
        const isEmailVerified = Number(student.is_verified || 0) === 1;
        const hasProfile = Number(student.user_details_id || 0) > 0;
        const statusLabel = activeState === 1 ? 'Verified' : (activeState === -1 ? 'Rejected' : 'Pending');
        const statusClass = activeState === 1 ? 'bg-arcade-mint' : (activeState === -1 ? 'bg-arcade-coral/30' : 'bg-arcade-yellow/40');
        const onlineNow = isOnline(student.last_seen_at);
        const joinedLabel = student.registration_date
            ? new Date(student.registration_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
            : 'Not set';

        return `
            <tr class="teacher-student-row border-b border-arcade-ink/10 align-top last:border-b-0">
                <td class="px-4 py-3">
                    <div class="teacher-student-identity flex items-center gap-3">
                        <span class="relative grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-bold text-arcade-ink">
                            ${student.avatar_url ? `<img src="${escapeHtml(student.avatar_url)}" alt="" class="h-full w-full object-cover">` : escapeHtml(initialsFor(displayName))}
                            <span class="absolute bottom-0.5 right-0.5 h-3 w-3 rounded-full border-2 border-white ${onlineNow ? 'bg-emerald-500' : 'bg-slate-300'}" aria-hidden="true"></span>
                        </span>
                        <div class="teacher-student-copy min-w-0">
                            <div class="truncate font-semibold text-arcade-ink">${escapeHtml(displayName)}</div>
                            <div class="truncate text-xs text-arcade-ink/55">@${escapeHtml(student.username || '')}</div>
                            <div class="truncate text-xs text-arcade-ink/55">${escapeHtml(student.email || '')}</div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-2">
                        <span class="teacher-pill ${statusClass}">${escapeHtml(statusLabel)}</span>
                        <span class="teacher-pill ${isEmailVerified ? 'bg-arcade-cyan/25' : 'bg-white'}">${isEmailVerified ? 'Email verified' : 'Email pending'}</span>
                        <span class="teacher-pill ${hasProfile ? 'bg-arcade-cyan' : 'bg-white'}">${hasProfile ? 'Profile ready' : 'No profile'}</span>
                    </div>
                </td>
                <td class="px-4 py-3 font-medium text-arcade-ink/65 whitespace-nowrap">${escapeHtml(joinedLabel)}</td>
                <td class="px-4 py-3">
                    <a href="./?c=student-view&id=${Number(student.user_id || 0)}" class="teacher-button teacher-button--light gap-2 no-underline">
                        <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Open</span>
                    </a>
                </td>
            </tr>
        `;
    };

    const applyPaginationState = (pagination) => {
        if (!pageStatus) {
            return;
        }

        const page = Number(pagination.page || 1);
        const totalPages = Number(pagination.total_pages || 1);
        const totalCount = Number(pagination.total_count || 0);
        currentQuery.page = page;
        pageStatus.textContent = `Page ${page} of ${totalPages} · ${totalCount} result${totalCount === 1 ? '' : 's'}`;

        if (prevLink) {
            const prevPage = Math.max(1, page - 1);
            prevLink.href = `./?c=students&q=${encodeURIComponent(currentQuery.q || '')}&status=${encodeURIComponent(currentQuery.status || 'all')}&page=${prevPage}`;
            prevLink.classList.toggle('pointer-events-none', page <= 1);
            prevLink.classList.toggle('opacity-50', page <= 1);
        }

        if (nextLink) {
            const nextPage = Math.min(totalPages, page + 1);
            nextLink.href = `./?c=students&q=${encodeURIComponent(currentQuery.q || '')}&status=${encodeURIComponent(currentQuery.status || 'all')}&page=${nextPage}`;
            nextLink.classList.toggle('pointer-events-none', page >= totalPages);
            nextLink.classList.toggle('opacity-50', page >= totalPages);
        }
    };

    const syncStudents = () => {
        const body = new URLSearchParams({
            students_action: 'snapshot',
            _csrf_token: csrfToken,
            q: currentQuery.q || '',
            status: currentQuery.status || 'all',
            page: String(currentQuery.page || 1),
        });

        fetch('./?c=students', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error('Snapshot failed')))
            .then((payload) => {
                if (!payload?.success) {
                    return;
                }

                if (totalNode) totalNode.textContent = String(payload.counts?.total || 0);
                if (verifiedNode) verifiedNode.textContent = String(payload.counts?.verified || 0);
                if (pendingNode) pendingNode.textContent = String(payload.counts?.pending || 0);
                if (rejectedNode) rejectedNode.textContent = String(payload.counts?.rejected || 0);

                if (tbody) {
                    const students = Array.isArray(payload.students) ? payload.students : [];
                    tbody.innerHTML = students.map(studentRow).join('');
                    if (window.lucide?.createIcons) {
                        window.lucide.createIcons();
                    }
                }

                const hasRows = Array.isArray(payload.students) && payload.students.length > 0;
                if (emptyState) {
                    emptyState.classList.toggle('hidden', hasRows);
                }
                if (tableWrap) {
                    tableWrap.classList.toggle('hidden', !hasRows);
                }

                applyPaginationState(payload.pagination || {});
            })
            .catch(() => {});
    };

    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            syncStudents();
        }
    }, 15000);
})();
</script>
