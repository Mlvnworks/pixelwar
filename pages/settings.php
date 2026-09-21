<?php
$profileName = trim((string) ($_SESSION['username'] ?? 'Pixel Rookie'));
$profileUsername = trim((string) ($_SESSION['username'] ?? ''));
$profileEmail = trim((string) ($_SESSION['email'] ?? 'player@example.com'));
$profileAvatarInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $profileName)) ?: 'PR', 0, 2));
$profileAvatarUrl = function_exists('pixelwarAvatarUrl') ? pixelwarAvatarUrl(trim((string) ($_SESSION['avatar_url'] ?? '')), 160) : trim((string) ($_SESSION['avatar_url'] ?? ''));
$profileFirstname = trim((string) ($_SESSION['firstname'] ?? ''));
$profileLastname = trim((string) ($_SESSION['lastname'] ?? ''));
$profileStudentNumber = '';
$profileSection = '';
$accountUsernameChangeAvailableAt = 0;
$settingsPasswordResetAvailableAt = function_exists('pixelwarForgotPasswordCooldownAvailableAt')
    ? pixelwarForgotPasswordCooldownAvailableAt()
    : 0;
$settingsPasswordResetSecondsLeft = max(0, $settingsPasswordResetAvailableAt - time());

if (isset($connection) && $connection instanceof mysqli && isset($_SESSION['user_id'])) {
    $settingsUserId = (int) $_SESSION['user_id'];
    if (isset($userRepository) && $userRepository instanceof UserRepository) {
        $accountUsernameChangeAvailableAt = $userRepository->accountChangeAvailableAt($settingsUserId, 'username');
    }
    $settingsProfile = $connection->prepare(
        'SELECT users.username, users.email, user_details.firstname, user_details.lastname, user_details.student_number, user_details.section, images.source AS avatar_url
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
        $profileUsername = trim((string) ($settingsProfileRow['username'] ?? ''));
        $profileName = $settingsFullName !== '' ? $settingsFullName : trim((string) $settingsProfileRow['username']);
        $profileEmail = trim((string) $settingsProfileRow['email']);
        $profileStudentNumber = trim((string) ($settingsProfileRow['student_number'] ?? ''));
        $profileSection = trim((string) ($settingsProfileRow['section'] ?? ''));
        $profileAvatarUrl = function_exists('pixelwarAvatarUrl') ? pixelwarAvatarUrl(trim((string) ($settingsProfileRow['avatar_url'] ?? '')), 160) : trim((string) ($settingsProfileRow['avatar_url'] ?? ''));
        $profileAvatarInitials = strtoupper(substr($settingsFirstname, 0, 1) . substr($settingsLastname, 0, 1)) ?: $profileAvatarInitials;
    }
}
$accountUsernameChangeLocked = $accountUsernameChangeAvailableAt > time();
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
                        <input id="settings-firstname" type="text"
                            value="<?= htmlspecialchars($profileFirstname, ENT_QUOTES, 'UTF-8') ?>" readonly
                            class="cursor-not-allowed bg-black/[0.03] text-arcade-ink/72">
                    </label>

                    <label class="settings-field" for="settings-lastname">
                        <span>Last Name</span>
                        <input id="settings-lastname" type="text"
                            value="<?= htmlspecialchars($profileLastname, ENT_QUOTES, 'UTF-8') ?>" readonly
                            class="cursor-not-allowed bg-black/[0.03] text-arcade-ink/72">
                    </label>

                    <p class="-mt-2 text-xs font-bold text-arcade-ink/55 sm:col-span-2">Your registered name cannot be changed.</p>

                    <label class="settings-field sm:col-span-2" for="settings-username">
                        <span>Username</span>
                        <input id="settings-username" name="username" type="text" autocomplete="username"
                            minlength="3" maxlength="32" pattern="[A-Za-z0-9_]{3,32}"
                            value="<?= htmlspecialchars($profileUsername, ENT_QUOTES, 'UTF-8') ?>"
                            data-current-username="<?= htmlspecialchars($profileUsername, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="pixel_player" required
                            <?= $accountUsernameChangeLocked ? 'readonly aria-readonly="true" class="cursor-not-allowed bg-black/[0.03] text-arcade-ink/72"' : '' ?>>
                        <small id="settings-username-message" class="settings-field-message" aria-live="polite"></small>
                    </label>

                    <p class="-mt-2 text-xs font-bold text-arcade-ink/55 sm:col-span-2">
                        <?= $accountUsernameChangeLocked
                            ? 'Username changes are available again on ' . htmlspecialchars(date('M j, Y g:i A', $accountUsernameChangeAvailableAt), ENT_QUOTES, 'UTF-8') . '.'
                            : 'Username can be changed once every 15 days.' ?>
                    </p>

                    <label class="settings-field sm:col-span-2" for="settings-email">
                        <span>Email</span>
                        <input id="settings-email" type="email" autocomplete="email"
                            value="<?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?>"
                            readonly aria-readonly="true"
                            class="cursor-not-allowed bg-black/[0.03] text-arcade-ink/72">
                    </label>

                    <p class="-mt-2 text-xs font-bold text-arcade-ink/55 sm:col-span-2">Your registered email cannot be changed.</p>

                    <label class="settings-field sm:col-span-2" for="settings-student-number">
                        <span>Student ID</span>
                        <input id="settings-student-number" type="text"
                            value="<?= htmlspecialchars($profileStudentNumber !== '' ? $profileStudentNumber : 'Not assigned yet', ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            class="cursor-not-allowed bg-black/[0.03] text-arcade-ink/72">
                    </label>

                    <label class="settings-field sm:col-span-2" for="settings-section">
                        <span>Section</span>
                        <input id="settings-section" type="text"
                            value="<?= htmlspecialchars($profileSection !== '' ? $profileSection : 'Not assigned yet', ENT_QUOTES, 'UTF-8') ?>"
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

                <div class="mt-6 flex justify-end">
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
        const usernameInput = document.querySelector('#settings-username');
        const usernameMessage = document.querySelector('#settings-username-message');
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
        let usernameIsAvailable = true;

        if (!input || !preview || !fileName || !form || !usernameInput || !usernameMessage) {
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

        const setUsernameState = (message, isValid = false) => {
            usernameMessage.textContent = message;
            usernameMessage.classList.toggle('is-valid', isValid);
            usernameInput.classList.toggle('is-invalid', message !== '' && !isValid);
            usernameInput.classList.toggle('is-valid', message !== '' && isValid);
        };

        const debounce = (callback, delay = 350) => {
            let timeoutId;

            return (...args) => {
                window.clearTimeout(timeoutId);
                timeoutId = window.setTimeout(() => callback(...args), delay);
            };
        };

        const checkUsername = async () => {
            const username = usernameInput.value.trim();
            const currentUsername = usernameInput.dataset.currentUsername || '';

            if (username === '') {
                usernameIsAvailable = false;
                setUsernameState('');
                return false;
            }

            if (!/^[A-Za-z0-9_]{3,32}$/.test(username)) {
                usernameIsAvailable = false;
                setUsernameState('Use 3-32 letters, numbers, or underscores.');
                return false;
            }

            if (username.toLowerCase() === currentUsername.toLowerCase()) {
                usernameIsAvailable = true;
                setUsernameState('Current username.', true);
                return true;
            }

            setUsernameState('Checking username...', true);

            try {
                const response = await fetch(`./?c=settings&check_username=1&username=${encodeURIComponent(username)}`, {
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                usernameIsAvailable = Boolean(result.available);
                setUsernameState(result.message || '', usernameIsAvailable);
                return usernameIsAvailable;
            } catch (error) {
                usernameIsAvailable = false;
                setUsernameState('Unable to check username right now.');
                return false;
            }
        };

        usernameInput.addEventListener('input', debounce(checkUsername));
        usernameInput.addEventListener('blur', checkUsername);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const canUseUsername = await checkUsername();

            if (!canUseUsername || !usernameIsAvailable) {
                usernameInput.focus();
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
