<?php
$teachers = $userRepository instanceof UserRepository ? $userRepository->listUsersByRole(2, 50) : [];
$teacherCreateErrors = $_SESSION['admin_teacher_create_errors'] ?? [];
$teacherCreateOld = $_SESSION['admin_teacher_create_old'] ?? [];
unset($_SESSION['admin_teacher_create_errors'], $_SESSION['admin_teacher_create_old']);
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative">
        <div class="teacher-page-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Management</p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">Teachers</h1>
                    <p class="mt-2 max-w-2xl text-sm font-bold leading-7 text-arcade-ink/62">
                        Create teacher accounts, monitor setup status, and review access details.
                    </p>
                </div>
                <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#admin-create-teacher-modal">
                    <i data-lucide="user-plus" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Add teacher</span>
                </button>
            </div>

            <div class="mt-5 grid gap-3">
                <?php if ($teachers === []) : ?>
                    <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-5 text-sm font-black text-arcade-ink/55">
                        No teacher accounts found yet.
                    </div>
                <?php endif; ?>

                <?php foreach ($teachers as $teacher) : ?>
                    <?php
                    $firstname = trim((string) ($teacher['firstname'] ?? ''));
                    $lastname = trim((string) ($teacher['lastname'] ?? ''));
                    $displayName = trim($firstname . ' ' . $lastname) ?: (string) $teacher['username'];
                    $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'TR', 0, 2));
                    $hasProfile = (int) ($teacher['user_details_id'] ?? 0) > 0;
                    $isSetupComplete = (int) ($teacher['is_verified'] ?? 0) === 1 && $hasProfile;
                    ?>
                    <article class="teacher-student-card rounded-2xl border-2 border-arcade-ink/14 bg-white p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-arcade text-[10px] text-arcade-ink">
                                    <?php if (trim((string) ($teacher['avatar_url'] ?? '')) !== '') : ?>
                                        <img src="<?= htmlspecialchars((string) $teacher['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                    <?php else : ?>
                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <h2 class="text-lg font-black"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="text-sm font-bold text-arcade-ink/58">@<?= htmlspecialchars((string) $teacher['username'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $teacher['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="teacher-pill <?= $isSetupComplete ? 'bg-arcade-mint' : 'bg-arcade-coral/30' ?>"><?= $isSetupComplete ? 'Verified' : 'Pending setup' ?></span>
                                <span class="teacher-pill bg-arcade-cyan">Joined <?= htmlspecialchars(date('M j, Y', strtotime((string) $teacher['registration_date'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <button type="button" class="teacher-small-button teacher-small-button--light" disabled>Edit</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
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
                    <button id="teacher-create-submit" type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="mail-plus" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Create teacher</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const form = document.getElementById('admin-create-teacher-form');
    const usernameInput = document.getElementById('teacher-create-username');
    const emailInput = document.getElementById('teacher-create-email');
    const usernameFeedback = document.getElementById('teacher-create-username-feedback');
    const emailFeedback = document.getElementById('teacher-create-email-feedback');
    const submitButton = document.getElementById('teacher-create-submit');

    if (form && usernameInput && emailInput && usernameFeedback && emailFeedback && submitButton) {
        const fieldState = {
            username: { valid: false, available: false, pending: false },
            email: { valid: false, available: false, pending: false },
        };

        const updateSubmitState = () => {
            const canSubmit = fieldState.username.valid
                && fieldState.username.available
                && fieldState.email.valid
                && fieldState.email.available
                && !fieldState.username.pending
                && !fieldState.email.pending;

            submitButton.disabled = !canSubmit;
        };

        const applyFeedback = (input, feedback, state, message, tone) => {
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
                applyFeedback(input, feedback, fieldState[field], '', 'idle');
                updateSubmitState();
                return;
            }

            fieldState[field].pending = true;
            updateSubmitState();
            applyFeedback(input, feedback, fieldState[field], 'Checking...', 'loading');

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

                    const tone = payload.valid && payload.available
                        ? 'valid'
                        : 'invalid';

                    applyFeedback(input, feedback, fieldState[field], payload.message || '', tone);
                    updateSubmitState();
                })
                .catch(() => {
                    fieldState[field] = { valid: false, available: false, pending: false };
                    applyFeedback(input, feedback, fieldState[field], 'Unable to check availability right now.', 'invalid');
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

        form.addEventListener('submit', (event) => {
            if (submitButton.disabled) {
                event.preventDefault();
            }
        });

        updateSubmitState();
    }

    <?php if ($teacherCreateErrors !== []) : ?>
    const modalElement = document.getElementById('admin-create-teacher-modal');
    if (modalElement && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
    <?php endif; ?>
});
</script>
