<?php
$teacherSearch = trim((string) ($_GET['q'] ?? ''));
$teacherStatus = strtolower(trim((string) ($_GET['status'] ?? 'all')));
$teacherStatusMap = [
    'all' => null,
    'ready' => 1,
    'pending' => 0,
];
$teacherVerifiedFilter = $teacherStatusMap[$teacherStatus] ?? null;
$teachersPerPage = 25;
$requestedTeachersPage = max(1, (int) ($_GET['page'] ?? 1));
$teacherTotalCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleAndVerificationFiltered(2, $teacherSearch, $teacherVerifiedFilter)
    : 0;
$teacherTotalPages = max(1, (int) ceil($teacherTotalCount / $teachersPerPage));
$teachersPage = min($requestedTeachersPage, $teacherTotalPages);
$teachersOffset = ($teachersPage - 1) * $teachersPerPage;
$teachers = $userRepository instanceof UserRepository
    ? $userRepository->listUsersByRoleAndVerificationFiltered(2, $teacherSearch, $teacherVerifiedFilter, $teachersPerPage, $teachersOffset)
    : [];
$teacherVerifiedCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleAndVerificationFiltered(2, '', 1)
    : 0;
$teacherPendingCount = $userRepository instanceof UserRepository
    ? $userRepository->countUsersByRoleAndVerificationFiltered(2, '', 0)
    : 0;
$teacherCreateErrors = $_SESSION['admin_teacher_create_errors'] ?? [];
$teacherCreateOld = $_SESSION['admin_teacher_create_old'] ?? [];
unset($_SESSION['admin_teacher_create_errors'], $_SESSION['admin_teacher_create_old']);

$teacherBuildQuery = static function (array $overrides = []) use ($teacherSearch, $teacherStatus, $teachersPage): string {
    $query = [
        'c' => 'teachers',
        'q' => $teacherSearch,
        'status' => $teacherStatus,
        'page' => $teachersPage,
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
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Teachers</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Create teacher accounts, review setup status, and manage access from one place.
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-stretch sm:justify-end xl:min-w-[22rem]">
                <button type="button" class="teacher-button teacher-button--primary gap-2 self-start sm:self-auto" data-bs-toggle="modal" data-bs-target="#admin-create-teacher-modal">
                    <i data-lucide="user-plus" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Add teacher</span>
                </button>
            </div>
        </section>

        <section class="teacher-panel p-5 md:p-6">
            <form class="flex flex-col gap-3 xl:flex-row xl:items-end" method="get" action="./">
                <input type="hidden" name="c" value="teachers">

                <label class="grid gap-2 xl:min-w-0 xl:flex-[1.45]">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Search</span>
                    <input
                        type="search"
                        name="q"
                        value="<?= htmlspecialchars($teacherSearch, ENT_QUOTES, 'UTF-8') ?>"
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
                        <option value="all" <?= $teacherStatus === 'all' ? 'selected' : '' ?>>All teachers</option>
                        <option value="ready" <?= $teacherStatus === 'ready' ? 'selected' : '' ?>>Profile ready</option>
                        <option value="pending" <?= $teacherStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                </label>

                <div class="flex flex-wrap items-center gap-2 xl:pb-[1px]">
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Apply</span>
                    </button>
                    <a href="./?c=teachers" class="teacher-link-button text-center">Reset</a>
                </div>
            </form>

            <div class="mt-5 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-white/85">
                <?php if ($teachers === []) : ?>
                    <div class="px-5 py-6 text-sm font-medium text-arcade-ink/55">
                        No teacher accounts matched the current filters.
                    </div>
                <?php else : ?>
                    <div class="max-h-[42rem] overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="sticky top-0 z-[1] bg-white/95">
                                <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                    <th class="px-4 py-3 font-semibold">Teacher</th>
                                    <th class="px-4 py-3 font-semibold">Status</th>
                                    <th class="px-4 py-3 font-semibold">Joined</th>
                                    <th class="px-4 py-3 font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher) : ?>
                                    <?php
                                    $firstname = trim((string) ($teacher['firstname'] ?? ''));
                                    $lastname = trim((string) ($teacher['lastname'] ?? ''));
                                    $displayName = trim($firstname . ' ' . $lastname) ?: (string) ($teacher['username'] ?? 'Teacher');
                                    $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'TR', 0, 2));
                                    $hasProfile = (int) ($teacher['user_details_id'] ?? 0) > 0;
                                    $isVerified = (int) ($teacher['is_verified'] ?? 0) === 1;
                                    $statusLabel = $isVerified ? 'Profile ready' : ($hasProfile ? 'Not set' : 'Pending setup');
                                    $statusClass = $isVerified ? 'bg-arcade-mint' : ($hasProfile ? 'bg-arcade-cyan/30' : 'bg-arcade-yellow/40');
                                    ?>
                                    <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-bold text-arcade-ink">
                                                    <?php if (trim((string) ($teacher['avatar_url'] ?? '')) !== '') : ?>
                                                        <img src="<?= htmlspecialchars((string) $teacher['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                                    <?php else : ?>
                                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="truncate font-semibold text-arcade-ink"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs text-arcade-ink/55">@<?= htmlspecialchars((string) ($teacher['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs text-arcade-ink/55"><?= htmlspecialchars((string) ($teacher['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <span class="teacher-pill <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <span class="teacher-pill <?= $hasProfile ? 'bg-arcade-cyan' : 'bg-white' ?>">
                                                    <?= $hasProfile ? 'Profile ready' : 'No profile' ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-arcade-ink/65 whitespace-nowrap">
                                            <?= htmlspecialchars(date('M j, Y', strtotime((string) ($teacher['registration_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    class="teacher-button teacher-button--light gap-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#teacher-edit-modal"
                                                    data-teacher-id="<?= (int) ($teacher['user_id'] ?? 0) ?>"
                                                    data-teacher-username="<?= htmlspecialchars((string) ($teacher['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-teacher-email="<?= htmlspecialchars((string) ($teacher['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-teacher-firstname="<?= htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-teacher-lastname="<?= htmlspecialchars($lastname, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-teacher-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                >
                                                    <i data-lucide="square-pen" class="h-4 w-4" aria-hidden="true"></i>
                                                    <span>Edit</span>
                                                </button>

                                                <a
                                                    href="./?c=teacher-view&id=<?= (int) ($teacher['user_id'] ?? 0) ?>"
                                                    class="teacher-button teacher-button--light gap-2 no-underline"
                                                >
                                                    <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                                    <span>Open</span>
                                                </a>
                                                <button
                                                    type="button"
                                                    class="teacher-button teacher-button--light gap-2 text-arcade-coral"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#teacher-action-modal"
                                                    data-teacher-action="delete"
                                                    data-teacher-id="<?= (int) ($teacher['user_id'] ?? 0) ?>"
                                                    data-teacher-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
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
                    Page <?= (int) $teachersPage ?> of <?= (int) $teacherTotalPages ?> · <?= (int) $teacherTotalCount ?> teacher<?= $teacherTotalCount === 1 ? '' : 's' ?>
                </p>
                <div class="flex flex-wrap gap-2">
                    <a href="<?= htmlspecialchars($teacherBuildQuery(['page' => max(1, $teachersPage - 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teachersPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                        <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Previous</span>
                    </a>
                    <a href="<?= htmlspecialchars($teacherBuildQuery(['page' => min($teacherTotalPages, $teachersPage + 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teachersPage >= $teacherTotalPages ? 'pointer-events-none opacity-50' : '' ?>">
                        <span>Next</span>
                        <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </section>
</main>

<div class="modal fade" id="admin-create-teacher-modal" tabindex="-1" aria-labelledby="admin-create-teacher-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <form id="admin-create-teacher-form" class="teacher-page-card rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5" method="post" action="./?c=teachers" novalidate>
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="admin_action" value="create_teacher">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">New Teacher</p>
                        <h2 id="admin-create-teacher-modal-label" class="mt-2 text-2xl font-black">Create teacher account</h2>
                        <p class="mt-1 text-sm font-bold leading-6 text-arcade-ink/62">Temporary access credentials will be sent to the teacher email.</p>
                    </div>
                    <button type="button" class="rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-sm font-black text-arcade-ink" data-bs-dismiss="modal" aria-label="Close">Close</button>
                </div>

                <?php if ($teacherCreateErrors !== []) : ?>
                    <div class="mt-4 rounded-2xl border-2 border-arcade-coral bg-arcade-coral/10 px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="alert">
                        <?php foreach ($teacherCreateErrors as $error) : ?>
                            <p class="mb-1 last:mb-0"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 grid gap-3">
                    <label class="block text-sm font-bold" for="teacher-create-username">
                        Username
                        <input id="teacher-create-username" name="username" type="text" required maxlength="32" value="<?= htmlspecialchars((string) ($teacherCreateOld['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="teacher_username" data-availability-field="username" autocomplete="off">
                        <span id="teacher-create-username-feedback" class="mt-1 block min-h-5 text-xs font-bold leading-5 text-arcade-ink/55"></span>
                    </label>

                    <label class="block text-sm font-bold" for="teacher-create-email">
                        Teacher Email
                        <input id="teacher-create-email" name="email" type="email" required maxlength="190" value="<?= htmlspecialchars((string) ($teacherCreateOld['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="teacher@example.com" data-availability-field="email" autocomplete="off">
                        <span id="teacher-create-email-feedback" class="mt-1 block min-h-5 text-xs font-bold leading-5 text-arcade-ink/55"></span>
                    </label>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-bold" for="teacher-create-password">
                            Password
                            <input id="teacher-create-password" name="password" type="password" required minlength="8" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="Minimum 8 characters">
                        </label>

                        <label class="block text-sm font-bold" for="teacher-create-confirm-password">
                            Confirm Password
                            <input id="teacher-create-confirm-password" name="confirm_password" type="password" required minlength="8" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="Repeat password">
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="teacher-button teacher-button--light" data-bs-dismiss="modal">Cancel</button>
                    <button id="teacher-create-submit" type="submit" class="teacher-button teacher-button--primary gap-2" data-loading-label="Creating teacher...">
                        <i data-lucide="mail-plus" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Create teacher</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="teacher-edit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.18)]">
            <form id="teacher-edit-form" method="post" action="./?c=teachers">
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="teacher_action" value="edit">
                <input type="hidden" name="teacher_id" id="teacher-edit-id" value="">

                <div class="flex items-center justify-between gap-3 border-b border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Edit account</p>
                        <h2 id="teacher-edit-title" class="mt-1 text-lg font-bold">Teacher account</h2>
                    </div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink transition hover:bg-arcade-yellow/35" data-bs-dismiss="modal" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid gap-4 p-4 md:p-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Username</span>
                            <input type="text" name="username" id="teacher-edit-username" required maxlength="32" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Email</span>
                            <input type="email" name="email" id="teacher-edit-email" required maxlength="190" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">First name</span>
                            <input type="text" name="firstname" id="teacher-edit-firstname" required maxlength="80" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Last name</span>
                            <input type="text" name="lastname" id="teacher-edit-lastname" required maxlength="80" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                        </label>
                    </div>

                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Admin password</span>
                        <input type="password" name="admin_password" id="teacher-edit-password" required autocomplete="current-password" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange" placeholder="Enter your password to save changes">
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-link-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2" data-loading-label="Saving changes...">
                        <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Save changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="teacher-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.18)]">
            <form id="teacher-action-form" method="post" action="./?c=teachers">
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="teacher_action" id="teacher-action-input" value="">
                <input type="hidden" name="teacher_id" id="teacher-action-id" value="">

                <div class="flex items-center justify-between gap-3 border-b border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Confirm action</p>
                        <h2 id="teacher-action-title" class="mt-1 text-lg font-bold">Teacher action</h2>
                    </div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink transition hover:bg-arcade-yellow/35" data-bs-dismiss="modal" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid gap-4 p-4 md:p-5">
                    <p id="teacher-action-message" class="text-sm leading-7 text-arcade-ink/68"></p>
                    <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Teacher</p>
                        <p id="teacher-action-name" class="mt-1 text-sm font-bold text-arcade-ink"></p>
                    </div>
                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Admin password</span>
                        <input type="password" name="admin_password" id="teacher-action-password" required autocomplete="current-password" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange" placeholder="Enter your password to continue">
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-link-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="teacher-action-submit" class="teacher-button teacher-button--primary gap-2" data-loading-label="Processing action...">
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

    const setSubmitting = (form) => {
        const submitButton = form.querySelector('button[type="submit"]');
        if (!(submitButton instanceof HTMLButtonElement) || submitButton.dataset.loading === '1') {
            return false;
        }

        submitButton.dataset.loading = '1';
        submitButton.disabled = true;
        submitButton.classList.add('opacity-80', 'pointer-events-none');
        const label = submitButton.querySelector('span');
        const loadingLabel = submitButton.dataset.loadingLabel || 'Processing...';
        if (label) {
            label.textContent = loadingLabel;
        } else {
            submitButton.textContent = loadingLabel;
        }

        return true;
    };

    const createForm = document.getElementById('admin-create-teacher-form');
    const usernameInput = document.getElementById('teacher-create-username');
    const emailInput = document.getElementById('teacher-create-email');
    const usernameFeedback = document.getElementById('teacher-create-username-feedback');
    const emailFeedback = document.getElementById('teacher-create-email-feedback');
    const submitButton = document.getElementById('teacher-create-submit');

    if (createForm && usernameInput && emailInput && usernameFeedback && emailFeedback && submitButton) {
        const fieldState = {
            username: { valid: false, available: false, pending: false },
            email: { valid: false, available: false, pending: false },
        };

        const updateSubmitState = () => {
            if (submitButton.dataset.loading === '1') {
                return;
            }

            const canSubmit = fieldState.username.valid
                && fieldState.username.available
                && fieldState.email.valid
                && fieldState.email.available
                && !fieldState.username.pending
                && !fieldState.email.pending;

            submitButton.disabled = !canSubmit;
        };

        const applyFeedback = (input, feedback, message, tone) => {
            input.classList.remove('border-arcade-coral', 'border-arcade-mint', 'border-arcade-yellow');
            feedback.classList.remove('text-arcade-coral', 'text-arcade-mint', 'text-arcade-ink/55', 'text-arcade-orange');

            if (tone === 'invalid') {
                input.classList.add('border-arcade-coral');
                feedback.classList.add('text-arcade-coral');
            } else if (tone === 'valid') {
                input.classList.add('border-arcade-mint');
                feedback.classList.add('text-arcade-mint');
            } else if (tone === 'loading') {
                input.classList.add('border-arcade-yellow');
                feedback.classList.add('text-arcade-orange');
            } else {
                feedback.classList.add('text-arcade-ink/55');
            }

            feedback.textContent = message;
        };

        const debouncedTimers = {};

        const runAvailabilityCheck = (field, input, feedback) => {
            const value = input.value.trim();

            if (value === '') {
                fieldState[field] = { valid: false, available: false, pending: false };
                applyFeedback(input, feedback, '', 'idle');
                updateSubmitState();
                return;
            }

            fieldState[field].pending = true;
            updateSubmitState();
            applyFeedback(input, feedback, 'Checking...', 'loading');

            const url = new URL('./?c=teachers', window.location.href);
            url.searchParams.set('check_teacher_field', '1');
            url.searchParams.set('field', field);
            url.searchParams.set('value', value);

            fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then((response) => response.json())
                .then((payload) => {
                    fieldState[field] = {
                        valid: Boolean(payload.valid),
                        available: Boolean(payload.available),
                        pending: false,
                    };

                    const tone = payload.valid && payload.available ? 'valid' : 'invalid';
                    applyFeedback(input, feedback, payload.message || '', tone);
                    updateSubmitState();
                })
                .catch(() => {
                    fieldState[field] = { valid: false, available: false, pending: false };
                    applyFeedback(input, feedback, 'Unable to check availability right now.', 'invalid');
                    updateSubmitState();
                });
        };

        const bindField = (field, input, feedback) => {
            input.addEventListener('input', () => {
                window.clearTimeout(debouncedTimers[field]);
                debouncedTimers[field] = window.setTimeout(() => runAvailabilityCheck(field, input, feedback), 320);
            });

            input.addEventListener('blur', () => {
                window.clearTimeout(debouncedTimers[field]);
                runAvailabilityCheck(field, input, feedback);
            });
        };

        bindField('username', usernameInput, usernameFeedback);
        bindField('email', emailInput, emailFeedback);

        if (usernameInput.value.trim() !== '') {
            runAvailabilityCheck('username', usernameInput, usernameFeedback);
        }

        if (emailInput.value.trim() !== '') {
            runAvailabilityCheck('email', emailInput, emailFeedback);
        }

        createForm.addEventListener('submit', (event) => {
            if (submitButton.disabled || !setSubmitting(createForm)) {
                event.preventDefault();
            }
        });

        updateSubmitState();
    }

    const editModal = document.getElementById('teacher-edit-modal');
    const editId = document.getElementById('teacher-edit-id');
    const editTitle = document.getElementById('teacher-edit-title');
    const editUsername = document.getElementById('teacher-edit-username');
    const editEmail = document.getElementById('teacher-edit-email');
    const editFirstname = document.getElementById('teacher-edit-firstname');
    const editLastname = document.getElementById('teacher-edit-lastname');
    const editPassword = document.getElementById('teacher-edit-password');
    const editForm = document.getElementById('teacher-edit-form');

    if (editModal && editId && editTitle && editUsername && editEmail && editFirstname && editLastname && editPassword && editForm) {
        editModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            editId.value = trigger.getAttribute('data-teacher-id') || '';
            editTitle.textContent = trigger.getAttribute('data-teacher-name') || 'Teacher account';
            editUsername.value = trigger.getAttribute('data-teacher-username') || '';
            editEmail.value = trigger.getAttribute('data-teacher-email') || '';
            editFirstname.value = trigger.getAttribute('data-teacher-firstname') || '';
            editLastname.value = trigger.getAttribute('data-teacher-lastname') || '';
            editPassword.value = '';
        });

        editForm.addEventListener('submit', (event) => {
            if (!setSubmitting(editForm)) {
                event.preventDefault();
            }
        });
    }

    const actionModal = document.getElementById('teacher-action-modal');
    const actionInput = document.getElementById('teacher-action-input');
    const actionId = document.getElementById('teacher-action-id');
    const actionTitle = document.getElementById('teacher-action-title');
    const actionMessage = document.getElementById('teacher-action-message');
    const actionName = document.getElementById('teacher-action-name');
    const actionPassword = document.getElementById('teacher-action-password');
    const actionSubmit = document.getElementById('teacher-action-submit');
    const actionForm = document.getElementById('teacher-action-form');
    const actionConfig = {
        delete: {
            title: 'Delete teacher account',
            message: 'This will soft-delete the teacher account from the active list.',
            buttonClass: 'teacher-button teacher-button--light gap-2 text-arcade-coral',
            icon: 'trash-2',
            label: 'Delete account',
        },
    };

    if (actionModal && actionInput && actionId && actionTitle && actionMessage && actionName && actionPassword && actionSubmit && actionForm) {
        actionModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            const action = trigger.getAttribute('data-teacher-action') || 'delete';
            const config = actionConfig[action] || actionConfig.delete;

            actionInput.value = action;
            actionId.value = trigger.getAttribute('data-teacher-id') || '';
            actionTitle.textContent = config.title;
            actionMessage.textContent = config.message;
            actionName.textContent = trigger.getAttribute('data-teacher-name') || 'Teacher';
            actionPassword.value = '';
            actionSubmit.dataset.loading = '';
            actionSubmit.disabled = false;
            actionSubmit.className = config.buttonClass;
            actionSubmit.innerHTML = '<i data-lucide="' + config.icon + '" class="h-4 w-4" aria-hidden="true"></i><span>' + config.label + '</span>';
            window.lucide?.createIcons();
        });

        actionForm.addEventListener('submit', (event) => {
            if (!setSubmitting(actionForm)) {
                event.preventDefault();
            }
        });
    }

    <?php if ($teacherCreateErrors !== []) : ?>
    const modalElement = document.getElementById('admin-create-teacher-modal');
    if (modalElement && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
    <?php endif; ?>
});
</script>
