<?php
$profileName = trim((string) ($_SESSION['username'] ?? 'Pixel Rookie'));
$profileEmail = trim((string) ($_SESSION['email'] ?? 'player@example.com'));
$profileAvatarInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $profileName)) ?: 'PR', 0, 2));
$profileAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$profileFirstname = trim((string) ($_SESSION['firstname'] ?? ''));
$profileLastname = trim((string) ($_SESSION['lastname'] ?? ''));
$profileStudentNumber = '';
$settingsPasswordResetAvailableAt = function_exists('pixelwarForgotPasswordCooldownAvailableAt')
    ? pixelwarForgotPasswordCooldownAvailableAt()
    : 0;
$settingsPasswordResetSecondsLeft = max(0, $settingsPasswordResetAvailableAt - time());

if (isset($connection) && $connection instanceof mysqli && isset($_SESSION['user_id'])) {
    $settingsUserId = (int) $_SESSION['user_id'];
    $settingsProfile = $connection->prepare(
        'SELECT users.username, users.email, user_details.firstname, user_details.lastname, user_details.student_number, images.source AS avatar_url
         FROM users
         LEFT JOIN user_details ON user_details.user_id = users.user_id
         LEFT JOIN images ON images.img_id = user_details.image_id
         WHERE users.user_id = ? AND users.date_deleted IS NULL
         LIMIT 1'
    );
    $settingsProfile->bind_param('i', $settingsUserId);
    $settingsProfile->execute();
    $settingsProfileRow = $settingsProfile->get_result()->fetch_assoc();
    $settingsProfile->close();

    if ($settingsProfileRow) {
        $settingsFirstname = trim((string) ($settingsProfileRow['firstname'] ?? ''));
        $settingsLastname = trim((string) ($settingsProfileRow['lastname'] ?? ''));
        $settingsFullName = trim($settingsFirstname . ' ' . $settingsLastname);
        $profileFirstname = $settingsFirstname;
        $profileLastname = $settingsLastname;
        $profileName = $settingsFullName !== '' ? $settingsFullName : trim((string) $settingsProfileRow['username']);
        $profileEmail = trim((string) $settingsProfileRow['email']);
        $profileStudentNumber = trim((string) ($settingsProfileRow['student_number'] ?? ''));
        $profileAvatarUrl = trim((string) ($settingsProfileRow['avatar_url'] ?? ''));
        $profileAvatarInitials = strtoupper(substr($settingsFirstname, 0, 1) . substr($settingsLastname, 0, 1)) ?: $profileAvatarInitials;
    }
}
?>

<main class="settings-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div
        class="settings-page__glow absolute inset-0 bg-[radial-gradient(circle_at_14%_12%,rgba(255,209,102,0.28),transparent_22%),radial-gradient(circle_at_88%_20%,rgba(76,201,240,0.22),transparent_24%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_38%)]">
    </div>
    <div class="settings-page__grid absolute inset-0"></div>

    <section class="container relative">
        <a href="./?c=home"
            class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_rgba(38,25,15,0.22)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
            <span aria-hidden="true">&larr;</span>
            Back Home
        </a>

        <div class="mt-5 grid gap-5 lg:grid-cols-[0.74fr_1.26fr]">
            <aside
                class="settings-card rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Player Settings</p>
                <div class="mt-5 flex flex-col items-center text-center">
                    <div class="settings-avatar grid h-32 w-32 place-items-center overflow-hidden rounded-[32px] border-4 border-arcade-ink bg-arcade-yellow shadow-[7px_7px_0_rgba(38,25,15,0.24)]"
                        aria-label="Current avatar preview">
                        <?php if ($profileAvatarUrl !== ''): ?>
                            <img id="settings-current-avatar"
                                src="<?= htmlspecialchars($profileAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?> avatar"
                                class="h-full w-full object-cover">
                        <?php else: ?>
                            <span id="settings-current-avatar-initials"
                                class="font-arcade text-3xl text-arcade-ink"><?= htmlspecialchars($profileAvatarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="mt-5 text-3xl font-bold leading-tight">
                        <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mt-2 break-all text-sm font-bold text-arcade-ink/60">
                        <?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </aside>

            <form id="settings-password-reset-form" action="./?c=settings" method="post" class="hidden">
                <?= pixelwarCsrfField() ?>
                <input type="hidden" name="settings_action" value="password_reset">
            </form>

            <form
                class="settings-form rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-6"
                action="./?c=settings" method="post" enctype="multipart/form-data">
                <?= pixelwarCsrfField() ?>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Edit Profile</p>
                        <h2 class="mt-3 text-2xl font-bold">Account details</h2>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <?php if (isset($_GET['updated'])): ?>
                            <span
                                class="inline-flex rounded-full border-2 border-arcade-ink bg-arcade-mint px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em] text-arcade-ink">Saved</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <label class="settings-upload sm:col-span-2" for="settings-avatar-file">
                        <span class="settings-upload__eyebrow">Profile Image</span>
                        <div class="settings-upload__body">
                            <div class="settings-upload__preview">
                                <?php if ($profileAvatarUrl !== ''): ?>
                                    <img id="settings-avatar-preview"
                                        src="<?= htmlspecialchars($profileAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt=""
                                        class="h-full w-full object-cover">
                                <?php else: ?>
                                    <span id="settings-avatar-preview-initials"
                                        class="font-arcade text-sm"><?= htmlspecialchars($profileAvatarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <img id="settings-avatar-preview" src="" alt=""
                                        class="hidden h-full w-full object-cover">
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-black text-arcade-ink">Upload new avatar</p>
                                <p id="settings-avatar-file-name"
                                    class="mt-1 truncate text-xs font-bold text-arcade-ink/58">PNG, JPG, WEBP, or GIF.
                                    Max 2MB.</p>
                            </div>
                            <strong class="settings-upload__button">Choose File</strong>
                        </div>
                        <input id="settings-avatar-file" name="profile_image" type="file"
                            accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only">
                    </label>

                    <label class="settings-field" for="settings-firstname">
                        <span>First Name</span>
                        <input id="settings-firstname" name="firstname" type="text" autocomplete="given-name"
                            maxlength="80" value="<?= htmlspecialchars($profileFirstname, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Melvin" required>
                    </label>

                    <label class="settings-field" for="settings-lastname">
                        <span>Last Name</span>
                        <input id="settings-lastname" name="lastname" type="text" autocomplete="family-name"
                            maxlength="80" value="<?= htmlspecialchars($profileLastname, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Agustin" required>
                    </label>

                    <label class="settings-field sm:col-span-2" for="settings-email">
                        <span>Email</span>
                        <input id="settings-email" name="email" type="email" autocomplete="email"
                            value="<?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?>"
                            data-current-email="<?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="player@example.com" required>
                        <small id="settings-email-message" class="settings-field-message" aria-live="polite"></small>
                    </label>

                    <label class="settings-field sm:col-span-2" for="settings-student-number">
                        <span>Student ID</span>
                        <input id="settings-student-number" type="text"
                            value="<?= htmlspecialchars($profileStudentNumber !== '' ? $profileStudentNumber : 'Not assigned yet', ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            class="cursor-not-allowed bg-black/[0.03] text-arcade-ink/72">
                    </label>
                </div>

                <section class="mt-6 rounded-2xl border-2 border-arcade-ink/10 bg-white/75 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-arcade-orange">Security</p>
                            <h3 class="mt-1 text-lg font-bold text-arcade-ink">Password access</h3>
                            <p class="mt-1 text-sm font-bold leading-6 text-arcade-ink/58">
                                Update your password through a secure reset link sent to your registered email.
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                            <button
                                type="submit"
                                form="settings-password-reset-form"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-4 py-2.5 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-cyan disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:bg-white"
                                data-settings-password-reset-button
                                data-settings-password-reset-available-at="<?= (int) $settingsPasswordResetAvailableAt ?>"
                                <?= $settingsPasswordResetSecondsLeft > 0 ? 'disabled' : '' ?>
                            >
                                <span class="settings-password-reset-button__spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                                <svg class="settings-password-reset-button__icon h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                    <path fill="currentColor" d="M8 1a4 4 0 0 1 4 4v2h1v8H3V7h1V5a4 4 0 0 1 4-4Zm2 6V5a2 2 0 0 0-4 0v2h4Zm-5 2v4h6V9H5Z" />
                                </svg>
                                <span class="settings-password-reset-button__text">Send Reset Link</span>
                            </button>
                            <p
                                class="<?= $settingsPasswordResetSecondsLeft > 0 ? '' : 'hidden' ?> text-xs font-bold text-arcade-ink/55"
                                data-settings-password-reset-countdown
                            >
                                Resend available in <?= (int) $settingsPasswordResetSecondsLeft ?>s.
                            </p>
                        </div>
                    </div>
                </section>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-bold leading-6 text-arcade-ink/62">Leave the file empty if you want to keep
                        your current avatar.</p>
                    <button type="submit"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2.5 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:bg-arcade-yellow disabled:hover:text-arcade-ink"
                        data-settings-save-button>
                        <span class="settings-save-button__content inline-flex items-center gap-2">
                            <span class="settings-save-button__spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                            <svg class="settings-save-button__icon h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M3 2h8l2 2v10H3V2Zm2 2v3h5V4H5Zm0 6v2h6v-2H5Z" />
                            </svg>
                            <span class="settings-save-button__text">Save Changes</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
    (() => {
        const input = document.querySelector('#settings-avatar-file');
        const preview = document.querySelector('#settings-avatar-preview');
        const initials = document.querySelector('#settings-avatar-preview-initials');
        const fileName = document.querySelector('#settings-avatar-file-name');
        const form = document.querySelector('.settings-form');
        const emailInput = document.querySelector('#settings-email');
        const emailMessage = document.querySelector('#settings-email-message');
        const saveButton = document.querySelector('[data-settings-save-button]');
        const saveButtonSpinner = saveButton?.querySelector('.settings-save-button__spinner');
        const saveButtonText = saveButton?.querySelector('.settings-save-button__text');
        const saveButtonIcon = saveButton?.querySelector('.settings-save-button__icon');
        const passwordResetForm = document.querySelector('#settings-password-reset-form');
        const passwordResetButton = document.querySelector('[data-settings-password-reset-button]');
        const passwordResetSpinner = passwordResetButton?.querySelector('.settings-password-reset-button__spinner');
        const passwordResetIcon = passwordResetButton?.querySelector('.settings-password-reset-button__icon');
        const passwordResetText = passwordResetButton?.querySelector('.settings-password-reset-button__text');
        const passwordResetCountdown = document.querySelector('[data-settings-password-reset-countdown]');
        let emailIsAvailable = true;

        if (!input || !preview || !fileName || !form || !emailInput || !emailMessage) {
            return;
        }

        const allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
        const maxSize = 2 * 1024 * 1024;

        const updatePasswordResetCountdown = () => {
            if (!passwordResetButton || !passwordResetCountdown || !passwordResetText) {
                return;
            }

            const availableAt = Number(passwordResetButton.dataset.settingsPasswordResetAvailableAt || 0);
            const secondsLeft = Math.max(0, Math.ceil(availableAt - (Date.now() / 1000)));

            if (secondsLeft <= 0) {
                passwordResetButton.disabled = false;
                passwordResetCountdown.classList.add('hidden');
                passwordResetText.textContent = 'Send Reset Link';
                return;
            }

            passwordResetButton.disabled = true;
            passwordResetCountdown.classList.remove('hidden');
            passwordResetCountdown.textContent = `Resend available in ${secondsLeft}s.`;
            passwordResetText.textContent = 'Reset Link Sent';
        };

        updatePasswordResetCountdown();
        window.setInterval(updatePasswordResetCountdown, 1000);

        passwordResetForm?.addEventListener('submit', () => {
            if (!passwordResetButton || !passwordResetSpinner || !passwordResetIcon || !passwordResetText) {
                return;
            }

            passwordResetButton.disabled = true;
            passwordResetSpinner.classList.remove('hidden');
            passwordResetIcon.classList.add('hidden');
            passwordResetText.textContent = 'Sending...';
            passwordResetButton.setAttribute('aria-busy', 'true');
        });

        input.addEventListener('change', () => {
            const file = input.files && input.files.length > 0 ? input.files[0] : null;

            if (!file) {
                return;
            }

            if (!allowedTypes.includes(file.type)) {
                fileName.textContent = 'Profile image must be JPG, PNG, WEBP, or GIF.';
                input.value = '';
                return;
            }

            if (file.size > maxSize) {
                fileName.textContent = 'Profile image must be 2MB or smaller.';
                input.value = '';
                return;
            }

            fileName.textContent = `${file.name} - ${(file.size / 1024).toFixed(0)}KB`;
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');

            if (initials) {
                initials.classList.add('hidden');
            }
        });

        const setEmailState = (message, isValid = false) => {
            emailMessage.textContent = message;
            emailMessage.classList.toggle('is-valid', isValid);
            emailInput.classList.toggle('is-invalid', message !== '' && !isValid);
            emailInput.classList.toggle('is-valid', message !== '' && isValid);
        };

        const debounce = (callback, delay = 350) => {
            let timeoutId;

            return (...args) => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(() => callback(...args), delay);
            };
        };

        const checkEmail = async () => {
            const email = emailInput.value.trim();
            const currentEmail = emailInput.dataset.currentEmail || '';

            if (email === '') {
                emailIsAvailable = false;
                setEmailState('');
                return false;
            }

            if (!emailInput.validity.valid) {
                emailIsAvailable = false;
                setEmailState('Enter a valid email address.');
                return false;
            }

            if (email.toLowerCase() === currentEmail.toLowerCase()) {
                emailIsAvailable = true;
                setEmailState('Current email.', true);
                return true;
            }

            setEmailState('Checking email...', true);

            try {
                const response = await fetch(`./?c=settings&check_email=1&email=${encodeURIComponent(email)}`, {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                const result = await response.json();

                emailIsAvailable = Boolean(result.available);
                setEmailState(result.message || '', emailIsAvailable);

                return emailIsAvailable;
            } catch (error) {
                emailIsAvailable = false;
                setEmailState('Unable to check email right now.');
                return false;
            }
        };

        emailInput.addEventListener('input', debounce(checkEmail));
        emailInput.addEventListener('blur', checkEmail);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const canUseEmail = await checkEmail();

            if (!canUseEmail || !emailIsAvailable) {
                emailInput.focus();
                return;
            }

            if (saveButton && saveButtonSpinner && saveButtonText && saveButtonIcon) {
                saveButton.disabled = true;
                saveButtonSpinner.classList.remove('hidden');
                saveButtonIcon.classList.add('hidden');
                saveButtonText.textContent = 'Saving...';
                saveButton.setAttribute('aria-busy', 'true');
            }

            form.submit();
        });
    })();
</script>
