<?php
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
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4 xl:min-w-[34rem]">
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
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Rejected</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $studentRejectedCount ?></strong>
                </article>
            </div>
        </section>

        <section class="teacher-panel p-5 md:p-6">
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
                                    <th class="px-4 py-3 font-semibold">Actions</th>
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
                                    $isEmailVerified = (int) ($student['is_verified'] ?? 0) === 1;
                                    $activeState = (int) ($student['is_active'] ?? 0);
                                    $statusLabel = $activeState === 1 ? 'Verified' : ($activeState === -1 ? 'Rejected' : 'Pending');
                                    $statusClass = $activeState === 1 ? 'bg-arcade-mint' : ($activeState === -1 ? 'bg-arcade-coral/30' : 'bg-arcade-yellow/40');
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
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    class="teacher-button teacher-button--light gap-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#student-edit-modal"
                                                    data-student-id="<?= (int) ($student['user_id'] ?? 0) ?>"
                                                    data-student-username="<?= htmlspecialchars((string) ($student['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-student-email="<?= htmlspecialchars((string) ($student['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-student-firstname="<?= htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-student-lastname="<?= htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-student-number="<?= htmlspecialchars((string) ($student['student_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                >
                                                    <i data-lucide="square-pen" class="h-4 w-4" aria-hidden="true"></i>
                                                    <span>Edit</span>
                                                </button>

                                                <a
                                                    href="./?c=student-view&id=<?= (int) ($student['user_id'] ?? 0) ?>"
                                                    class="teacher-button teacher-button--light gap-2 no-underline"
                                                >
                                                    <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                                    <span>Open</span>
                                                </a>

                                                <?php if ($activeState === 1) : ?>
                                                    <button
                                                        type="button"
                                                        class="teacher-button teacher-button--light gap-2"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#student-action-modal"
                                                        data-student-action="unverify"
                                                        data-student-id="<?= (int) ($student['user_id'] ?? 0) ?>"
                                                        data-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        <i data-lucide="shield-alert" class="h-4 w-4" aria-hidden="true"></i>
                                                        <span>Unverify</span>
                                                    </button>
                                                <?php else : ?>
                                                    <button
                                                        type="button"
                                                        class="teacher-button teacher-button--primary gap-2"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#student-action-modal"
                                                        data-student-action="verify"
                                                        data-student-id="<?= (int) ($student['user_id'] ?? 0) ?>"
                                                        data-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                    >
                                                        <i data-lucide="badge-check" class="h-4 w-4" aria-hidden="true"></i>
                                                        <span>Verify</span>
                                                    </button>
                                                <?php endif; ?>

                                                <button
                                                    type="button"
                                                    class="teacher-button teacher-button--light gap-2 text-arcade-coral"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#student-action-modal"
                                                    data-student-action="delete"
                                                    data-student-id="<?= (int) ($student['user_id'] ?? 0) ?>"
                                                    data-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                                    <span>Delete</span>
                                                </button>
                                            </div>
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

<div class="modal fade" id="student-edit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.18)]">
            <form method="post" action="./?c=students">
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="student_action" value="edit">
                <input type="hidden" name="student_id" id="student-edit-id" value="">

                <div class="flex items-center justify-between gap-3 border-b border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Edit account</p>
                        <h2 id="student-edit-title" class="mt-1 text-lg font-bold">Student account</h2>
                    </div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink transition hover:bg-arcade-yellow/35" data-bs-dismiss="modal" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid gap-4 p-4 md:p-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Username</span>
                            <input type="text" name="username" id="student-edit-username" required maxlength="32" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Email</span>
                            <input type="email" name="email" id="student-edit-email" required maxlength="255" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">First name</span>
                            <input type="text" name="firstname" id="student-edit-firstname" required maxlength="80" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Last name</span>
                            <input type="text" name="lastname" id="student-edit-lastname" required maxlength="80" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                    </div>

                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student number</span>
                        <input type="text" name="student_number" id="student-edit-number" maxlength="40" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                    </label>

                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Admin password</span>
                        <input type="password" name="admin_password" id="student-edit-password" required autocomplete="current-password" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange" placeholder="Enter your password to save changes">
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-link-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Save changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="student-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.18)]">
            <form method="post" action="./?c=students">
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="student_action" id="student-action-input" value="">
                <input type="hidden" name="student_id" id="student-action-id" value="">

                <div class="flex items-center justify-between gap-3 border-b border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Confirm action</p>
                        <h2 id="student-action-title" class="mt-1 text-lg font-bold">Student action</h2>
                    </div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink transition hover:bg-arcade-yellow/35" data-bs-dismiss="modal" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid gap-4 p-4 md:p-5">
                    <p id="student-action-message" class="text-sm leading-7 text-arcade-ink/68"></p>
                    <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student</p>
                        <p id="student-action-name" class="mt-1 text-sm font-bold text-arcade-ink"></p>
                    </div>
                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Admin password</span>
                        <input type="password" name="admin_password" id="student-action-password" required autocomplete="current-password" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange" placeholder="Enter your password to continue">
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-link-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="student-action-submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="badge-check" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Confirm</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const editModal = document.getElementById('student-edit-modal');
    const editId = document.getElementById('student-edit-id');
    const editTitle = document.getElementById('student-edit-title');
    const editUsername = document.getElementById('student-edit-username');
    const editEmail = document.getElementById('student-edit-email');
    const editFirstname = document.getElementById('student-edit-firstname');
    const editLastname = document.getElementById('student-edit-lastname');
    const editNumber = document.getElementById('student-edit-number');
    const editPassword = document.getElementById('student-edit-password');

    if (editModal && editId && editTitle && editUsername && editEmail && editFirstname && editLastname && editNumber && editPassword) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            editId.value = trigger.getAttribute('data-student-id') || '';
            editTitle.textContent = trigger.getAttribute('data-student-name') || 'Student account';
            editUsername.value = trigger.getAttribute('data-student-username') || '';
            editEmail.value = trigger.getAttribute('data-student-email') || '';
            editFirstname.value = trigger.getAttribute('data-student-firstname') || '';
            editLastname.value = trigger.getAttribute('data-student-lastname') || '';
            editNumber.value = trigger.getAttribute('data-student-number') || '';
            editPassword.value = '';
        });
    }

    const actionModal = document.getElementById('student-action-modal');
    const actionInput = document.getElementById('student-action-input');
    const actionId = document.getElementById('student-action-id');
    const actionTitle = document.getElementById('student-action-title');
    const actionMessage = document.getElementById('student-action-message');
    const actionName = document.getElementById('student-action-name');
    const actionPassword = document.getElementById('student-action-password');
    const actionSubmit = document.getElementById('student-action-submit');
    const actionConfig = {
        verify: {
            title: 'Verify student account',
            message: 'This will mark the student as verified and restore access to the student panel.',
            buttonClass: 'teacher-button teacher-button--primary gap-2',
            icon: 'badge-check',
            label: 'Verify account',
        },
        unverify: {
            title: 'Move student to pending',
            message: 'This will remove current access and place the student back into pending review.',
            buttonClass: 'teacher-button teacher-button--light gap-2',
            icon: 'shield-alert',
            label: 'Unverify account',
        },
        delete: {
            title: 'Delete student account',
            message: 'This will soft-delete the student account and remove it from the active student list.',
            buttonClass: 'teacher-button teacher-button--light gap-2 text-arcade-coral',
            icon: 'trash-2',
            label: 'Delete account',
        },
    };

    if (actionModal && actionInput && actionId && actionTitle && actionMessage && actionName && actionPassword && actionSubmit) {
        actionModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            const action = trigger.getAttribute('data-student-action') || 'verify';
            const config = actionConfig[action] || actionConfig.verify;

            actionInput.value = action;
            actionId.value = trigger.getAttribute('data-student-id') || '';
            actionTitle.textContent = config.title;
            actionMessage.textContent = config.message;
            actionName.textContent = trigger.getAttribute('data-student-name') || 'Student';
            actionPassword.value = '';
            actionSubmit.className = config.buttonClass;
            actionSubmit.innerHTML = '<i data-lucide="' + config.icon + '" class="h-4 w-4" aria-hidden="true"></i><span>' + config.label + '</span>';
            window.lucide?.createIcons();
        });
    }
});
</script>
