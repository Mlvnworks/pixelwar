<?php
$reviewSearch = trim((string) ($_GET['q'] ?? ''));
$reviewStatus = strtolower(trim((string) ($_GET['status'] ?? 'pending')));
$reviewStatusMap = [
    'all' => null,
    'pending' => 0,
    'approved' => 1,
    'rejected' => -1,
];
$reviewActiveFilter = array_key_exists($reviewStatus, $reviewStatusMap) ? $reviewStatusMap[$reviewStatus] : null;
$reviewPerPage = 12;
$requestedReviewPage = max(1, (int) ($_GET['page'] ?? 1));
$reviewTotalCount = $userRepository instanceof UserRepository
    ? $userRepository->countPendingStudentReviews($reviewSearch, $reviewActiveFilter)
    : 0;
$reviewTotalPages = max(1, (int) ceil($reviewTotalCount / $reviewPerPage));
$reviewPage = min($requestedReviewPage, $reviewTotalPages);
$reviewOffset = ($reviewPage - 1) * $reviewPerPage;
$reviewStudents = $userRepository instanceof UserRepository
    ? $userRepository->listPendingStudentReviews($reviewSearch, $reviewActiveFilter, $reviewPerPage, $reviewOffset)
    : [];
$pendingReviewCount = $userRepository instanceof UserRepository ? $userRepository->countPendingStudentReviews('', 0) : 0;
$approvedReviewCount = $userRepository instanceof UserRepository ? $userRepository->countPendingStudentReviews('', 1) : 0;
$rejectedReviewCount = $userRepository instanceof UserRepository ? $userRepository->countPendingStudentReviews('', -1) : 0;

$reviewBuildQuery = static function (array $overrides = []) use ($reviewSearch, $reviewStatus, $reviewPage): string {
    $query = [
        'c' => 'student-verification',
        'q' => $reviewSearch,
        'status' => $reviewStatus,
        'page' => $reviewPage,
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
$reviewActionIntent = [
    'approve' => [
        'title' => 'Approve student access',
        'message' => 'This will unlock the student dashboard and send an approval email.',
        'button' => 'Confirm approval',
        'buttonClass' => 'teacher-button teacher-button--primary gap-2',
        'icon' => 'badge-check',
    ],
    'reject' => [
        'title' => 'Reject student verification',
        'message' => 'This will keep the account locked and send a rejection email to the student.',
        'button' => 'Confirm rejection',
        'buttonClass' => 'teacher-button teacher-button--light gap-2 text-arcade-coral',
        'icon' => 'shield-x',
    ],
];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Verification Queue</p>
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Student verification</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Search, filter, and review student submissions before access is unlocked.
                </p>
            </div>
            <div class="grid gap-2 sm:grid-cols-3 lg:min-w-[26rem]">
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Pending</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $pendingReviewCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Approved</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $approvedReviewCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Rejected</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $rejectedReviewCount ?></strong>
                </article>
            </div>
        </section>

        <section class="teacher-panel p-5 md:p-6">
            <form class="flex flex-col gap-3 xl:flex-row xl:items-end" method="get" action="./">
                <input type="hidden" name="c" value="student-verification">

                <label class="grid gap-2 xl:min-w-0 xl:flex-[1.45]">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Search</span>
                    <input
                        type="search"
                        name="q"
                        value="<?= htmlspecialchars($reviewSearch, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Search student name, username, email, or student number"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                </label>

                <label class="grid gap-2 xl:w-[12rem]">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Filter</span>
                    <select
                        name="status"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                        <option value="all" <?= $reviewStatus === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="pending" <?= $reviewStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $reviewStatus === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $reviewStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </label>

                <div class="flex flex-wrap items-center gap-2 xl:pb-[1px]">
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Apply</span>
                    </button>
                    <a href="./?c=student-verification" class="teacher-link-button text-center">Reset</a>
                </div>
            </form>

            <?php if ($reviewStudents === []) : ?>
                <div class="mt-5 rounded-2xl border border-arcade-ink/10 bg-white/80 px-5 py-6 text-sm font-medium text-arcade-ink/55">
                    No student records matched the current search and filter.
                </div>
            <?php else : ?>
                <div class="mt-5 grid gap-4 xl:grid-cols-2">
                    <?php foreach ($reviewStudents as $student) : ?>
                        <?php
                        $firstname = trim((string) ($student['firstname'] ?? ''));
                        $lastname = trim((string) ($student['lastname'] ?? ''));
                        $displayName = trim($firstname . ' ' . $lastname) ?: (string) ($student['username'] ?? 'Student');
                        $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2));
                        $avatarUrl = trim((string) ($student['avatar_url'] ?? ''));
                        $idPictureUrl = trim((string) ($student['id_picture_url'] ?? ''));
                        $studentNumber = trim((string) ($student['student_number'] ?? ''));
                        $activeState = (int) ($student['is_active'] ?? 0);
                        $statusLabel = $activeState === 1 ? 'Approved' : ($activeState === -1 ? 'Rejected' : 'Pending');
                        $statusPillClass = $activeState === 1
                            ? 'bg-arcade-mint/35'
                            : ($activeState === -1 ? 'bg-arcade-coral/35' : 'bg-arcade-yellow/35');
                        ?>
                        <article class="teacher-panel overflow-hidden p-4">
                            <div class="flex flex-col gap-4 lg:flex-row">
                                <div class="flex min-w-0 flex-1 items-start gap-3">
                                    <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-bold text-arcade-ink">
                                        <?php if ($avatarUrl !== '') : ?>
                                            <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                        <?php else : ?>
                                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-lg font-bold text-arcade-ink"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="truncate text-sm text-arcade-ink/58">@<?= htmlspecialchars((string) ($student['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="truncate text-sm text-arcade-ink/58"><?= htmlspecialchars((string) ($student['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="teacher-pill <?= htmlspecialchars($statusPillClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="teacher-pill bg-arcade-cyan/25">Verified email</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="min-w-0 rounded-2xl border border-arcade-ink/10 bg-white/80 p-3 lg:w-[12.5rem]">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student ID Preview</p>
                                        <?php if ($idPictureUrl !== '') : ?>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-arcade-ink/10 bg-white px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-arcade-ink transition hover:bg-arcade-yellow/35"
                                                data-bs-toggle="modal"
                                                data-bs-target="#student-id-preview-modal"
                                                data-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                data-student-id-url="<?= htmlspecialchars($idPictureUrl, ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                                View
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2 overflow-hidden rounded-xl border border-arcade-ink/10 bg-arcade-cream/80">
                                        <div class="relative grid min-h-[10rem] place-items-center p-2">
                                            <?php if ($idPictureUrl !== '') : ?>
                                                <div class="admin-image-loader" data-image-loader>
                                                    <span class="admin-image-loader__spinner" aria-hidden="true"></span>
                                                    <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/50">Loading</span>
                                                </div>
                                                <img
                                                    src=""
                                                    data-src="<?= htmlspecialchars($idPictureUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                    alt="Submitted student ID"
                                                    class="admin-lazy-image admin-lazy-image--hidden max-h-[13rem] w-full cursor-zoom-in object-contain"
                                                    decoding="async"
                                                    data-image-target
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#student-id-preview-modal"
                                                    data-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-student-id-url="<?= htmlspecialchars($idPictureUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                >
                                            <?php else : ?>
                                                <span class="px-3 text-center text-xs font-semibold leading-5 text-arcade-ink/45">No ID picture uploaded.</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-3 py-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student Number</p>
                                    <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= htmlspecialchars($studentNumber !== '' ? $studentNumber : 'Not provided', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="rounded-2xl border border-arcade-ink/10 bg-white/80 px-3 py-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Joined</p>
                                    <p class="mt-1 text-sm font-semibold text-arcade-ink"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($student['registration_date'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm font-medium text-arcade-ink/60">
                                    <?= $activeState === 1
                                        ? 'This student already has access to the student resources.'
                                        : ($activeState === -1
                                            ? 'This student was previously rejected and still has no access.'
                                            : 'Review the submitted ID and choose whether to unlock student access.'); ?>
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <?php if ($activeState !== 1) : ?>
                                        <button
                                            type="button"
                                            class="teacher-button teacher-button--primary gap-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#student-review-action-modal"
                                            data-review-action="approve"
                                            data-review-student-id="<?= (int) ($student['user_id'] ?? 0) ?>"
                                            data-review-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="badge-check" class="h-4 w-4" aria-hidden="true"></i>
                                            <span>Approve</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($activeState !== -1) : ?>
                                        <button
                                            type="button"
                                            class="teacher-button teacher-button--light gap-2 text-arcade-coral"
                                            data-bs-toggle="modal"
                                            data-bs-target="#student-review-action-modal"
                                            data-review-action="reject"
                                            data-review-student-id="<?= (int) ($student['user_id'] ?? 0) ?>"
                                            data-review-student-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="shield-x" class="h-4 w-4" aria-hidden="true"></i>
                                            <span>Reject</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-5 flex flex-col gap-3 border-t border-arcade-ink/10 pt-4 md:flex-row md:items-center md:justify-between">
                <p class="text-sm font-medium text-arcade-ink/55">
                    Page <?= (int) $reviewPage ?> of <?= (int) $reviewTotalPages ?> · <?= (int) $reviewTotalCount ?> result<?= $reviewTotalCount === 1 ? '' : 's' ?>
                </p>
                <div class="flex flex-wrap gap-2">
                    <a
                        href="<?= htmlspecialchars($reviewBuildQuery(['page' => max(1, $reviewPage - 1)]), ENT_QUOTES, 'UTF-8') ?>"
                        class="teacher-button teacher-button--light gap-2 <?= $reviewPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>"
                    >
                        <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Previous</span>
                    </a>
                    <a
                        href="<?= htmlspecialchars($reviewBuildQuery(['page' => min($reviewTotalPages, $reviewPage + 1)]), ENT_QUOTES, 'UTF-8') ?>"
                        class="teacher-button teacher-button--light gap-2 <?= $reviewPage >= $reviewTotalPages ? 'pointer-events-none opacity-50' : '' ?>"
                    >
                        <span>Next</span>
                        <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
    </section>
</main>

<div class="modal fade" id="student-review-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.18)]">
            <form method="post" action="./?c=student-verification">
                <?= adminPanelCsrfField() ?>
                <input type="hidden" name="action" id="student-review-action-input" value="">
                <input type="hidden" name="student_id" id="student-review-student-id-input" value="">

                <div class="flex items-center justify-between gap-3 border-b border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Confirm action</p>
                        <h2 id="student-review-action-title" class="mt-1 text-lg font-bold">Review action</h2>
                    </div>
                    <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink transition hover:bg-arcade-yellow/35" data-bs-dismiss="modal" aria-label="Close">
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="p-4 md:p-5">
                    <p id="student-review-action-message" class="text-sm leading-7 text-arcade-ink/68"></p>

                    <div class="mt-4 rounded-2xl border border-arcade-ink/10 bg-white/80 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student</p>
                        <p id="student-review-student-name" class="mt-1 text-sm font-bold text-arcade-ink"></p>
                    </div>

                    <label class="mt-4 grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Admin password</span>
                        <input
                            type="password"
                            name="admin_password"
                            id="student-review-admin-password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                            placeholder="Enter your password to continue"
                        >
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-link-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="student-review-action-submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="badge-check" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Confirm</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="student-id-preview-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.18)]">
            <div class="flex items-center justify-between gap-3 border-b border-arcade-ink/10 px-4 py-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Student ID Preview</p>
                    <h2 id="student-id-preview-title" class="mt-1 text-lg font-bold">Student ID</h2>
                </div>
                <button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-white text-arcade-ink transition hover:bg-arcade-yellow/35" data-bs-dismiss="modal" aria-label="Close">
                    <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                </button>
            </div>
            <div class="p-4 md:p-5">
                <div class="relative overflow-hidden rounded-[22px] border border-arcade-ink/10 bg-white">
                    <div class="admin-image-loader" id="student-id-modal-loader">
                        <span class="admin-image-loader__spinner" aria-hidden="true"></span>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.08em] text-arcade-ink/50">Loading</span>
                    </div>
                    <img id="student-id-modal-image" src="" alt="Student ID preview" class="hidden max-h-[70vh] w-full object-contain p-3">
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-image-loader {
    display: grid;
    place-items: center;
    gap: 0.55rem;
    min-height: 10rem;
    width: 100%;
    color: rgba(38, 25, 15, 0.62);
}

.admin-image-loader.is-hidden {
    display: none;
}

.admin-image-loader__spinner {
    width: 1.35rem;
    height: 1.35rem;
    border-radius: 999px;
    border: 2px solid rgba(38, 25, 15, 0.18);
    border-top-color: #ff8c42;
    animation: adminImageSpin 800ms linear infinite;
}

.admin-lazy-image--hidden {
    opacity: 0;
    pointer-events: none;
}

@keyframes adminImageSpin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const attachImageLoaders = (root = document) => {
        const images = [...root.querySelectorAll('[data-image-target]')];
        const revealImage = (image, loader) => {
            image.classList.remove('admin-lazy-image--hidden');
            loader?.classList.add('is-hidden');
        };

        const failImage = (loader) => {
            loader?.classList.add('is-hidden');
        };

        const startImageLoad = (image) => {
            if (!(image instanceof HTMLImageElement) || image.dataset.loaded === '1') {
                return;
            }

            const loader = image.parentElement?.querySelector('[data-image-loader]');
            const source = image.dataset.src || '';

            if (source === '') {
                failImage(loader);
                image.dataset.loaded = '1';
                return;
            }

            image.dataset.loaded = '1';
            image.addEventListener('load', () => revealImage(image, loader), { once: true });
            image.addEventListener('error', () => failImage(loader), { once: true });
            image.src = source;

            if (image.complete && image.naturalWidth > 0) {
                revealImage(image, loader);
            }
        };

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    startImageLoad(entry.target);
                    observer.unobserve(entry.target);
                });
            }, {
                rootMargin: '180px 0px',
            });

            images.forEach((image) => observer.observe(image));
            return;
        }

        images.forEach((image) => startImageLoad(image));
    };

    attachImageLoaders();

    const previewModal = document.getElementById('student-id-preview-modal');
    const modalTitle = document.getElementById('student-id-preview-title');
    const modalImage = document.getElementById('student-id-modal-image');
    const modalLoader = document.getElementById('student-id-modal-loader');

    if (previewModal && modalTitle && modalImage && modalLoader) {
        previewModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            const imageUrl = trigger.getAttribute('data-student-id-url') || '';
            const studentName = trigger.getAttribute('data-student-name') || 'Student ID';

            modalTitle.textContent = studentName;
            modalImage.classList.add('hidden');
            modalLoader.classList.remove('is-hidden');

            const finalize = () => {
                modalLoader.classList.add('is-hidden');
                modalImage.classList.remove('hidden');
            };

            const fail = () => {
                modalLoader.classList.add('is-hidden');
                modalImage.classList.add('hidden');
            };

            modalImage.onload = finalize;
            modalImage.onerror = fail;
            modalImage.src = imageUrl;

            if (modalImage.complete && modalImage.naturalWidth > 0) {
                finalize();
            }
        });
    }

    const actionModal = document.getElementById('student-review-action-modal');
    const actionTitle = document.getElementById('student-review-action-title');
    const actionMessage = document.getElementById('student-review-action-message');
    const actionStudentName = document.getElementById('student-review-student-name');
    const actionField = document.getElementById('student-review-action-input');
    const studentIdField = document.getElementById('student-review-student-id-input');
    const passwordField = document.getElementById('student-review-admin-password');
    const submitButton = document.getElementById('student-review-action-submit');
    const actionIntent = <?= json_encode($reviewActionIntent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    if (actionModal && actionTitle && actionMessage && actionStudentName && actionField && studentIdField && passwordField && submitButton) {
        actionModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            const action = trigger.getAttribute('data-review-action') || 'approve';
            const studentId = trigger.getAttribute('data-review-student-id') || '';
            const studentName = trigger.getAttribute('data-review-student-name') || 'Student';
            const intent = actionIntent[action] || actionIntent.approve;

            actionTitle.textContent = intent.title || 'Review action';
            actionMessage.textContent = intent.message || '';
            actionStudentName.textContent = studentName;
            actionField.value = action;
            studentIdField.value = studentId;
            passwordField.value = '';
            submitButton.className = intent.buttonClass || 'teacher-button teacher-button--primary gap-2';
            submitButton.innerHTML = '<i data-lucide="' + (intent.icon || 'badge-check') + '" class="h-4 w-4" aria-hidden="true"></i><span>' + (intent.button || 'Confirm') + '</span>';
            window.lucide?.createIcons();
        });
    }
});
</script>
