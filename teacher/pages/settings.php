<?php
$profileName = trim((string) ($_SESSION['username'] ?? 'Teacher'));
$profileEmail = trim((string) ($_SESSION['email'] ?? 'teacher@example.com'));
$profileAvatarInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $profileName)) ?: 'TR', 0, 2));
$profileAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$profileFirstname = trim((string) ($_SESSION['firstname'] ?? ''));
$profileLastname = trim((string) ($_SESSION['lastname'] ?? ''));

if (isset($connection) && $connection instanceof mysqli && isset($_SESSION['user_id'])) {
    $settingsUserId = (int) $_SESSION['user_id'];
    $settingsProfile = $connection->prepare(
        'SELECT users.username, users.email, user_details.firstname, user_details.lastname, images.source AS avatar_url
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
        $profileAvatarUrl = trim((string) ($settingsProfileRow['avatar_url'] ?? ''));
        $profileAvatarInitials = strtoupper(substr($settingsFirstname, 0, 1) . substr($settingsLastname, 0, 1)) ?: $profileAvatarInitials;
    }
}
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative">
        <a href="./?c=dashboard"
            class="teacher-button teacher-button--light gap-2 w-fit no-underline">
            <span aria-hidden="true">&larr;</span>
            Back to Dashboard
        </a>

        <div class="mt-5 grid gap-5 lg:grid-cols-[0.74fr_1.26fr]">
            <aside class="teacher-panel rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Teacher Settings</p>
                <div class="mt-5 flex flex-col items-center text-center">
                    <div class="grid h-32 w-32 place-items-center overflow-hidden rounded-[32px] border-4 border-arcade-ink bg-arcade-yellow shadow-[7px_7px_0_rgba(38,25,15,0.24)]"
                        aria-label="Current avatar preview">
                        <?php if ($profileAvatarUrl !== ''): ?>
                            <img id="teacher-settings-current-avatar"
                                src="<?= htmlspecialchars($profileAvatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?> avatar"
                                class="h-full w-full object-cover">
                        <?php else: ?>
                            <span id="teacher-settings-current-avatar-initials"
                                class="font-arcade text-3xl text-arcade-ink"><?= htmlspecialchars($profileAvatarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="mt-5 text-3xl font-bold leading-tight">
                        <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mt-2 break-all text-sm font-bold text-arcade-ink/60">
                        <?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </aside>

            <form
                class="teacher-panel rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-6 teacher-settings-form"
                action="./?c=settings" method="post" enctype="multipart/form-data">
                <?= teacherPanelCsrfField() ?>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Edit Profile</p>
                        <h2 class="mt-3 text-2xl font-bold">Account details</h2>
                    </div>
                    <?php if (isset($_GET['updated'])): ?>
                        <span class="inline-flex rounded-full border-2 border-arcade-ink bg-arcade-mint px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em] text-arcade-ink">Saved</span>
                    <?php endif; ?>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <label class="teacher-settings-upload sm:col-span-2" for="teacher-settings-avatar-file">
                        <span class="teacher-settings-upload__eyebrow">Profile Image</span>
                        <div class="teacher-settings-upload__body">
                            <div class="teacher-settings-upload__preview">
                                <?php if ($profileAvatarUrl !== ''): ?>
                                    <img id="teacher-settings-avatar-preview"
                                        src="<?= htmlspecialchars($profileAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt=""
                                        class="h-full w-full object-cover">
                                <?php else: ?>
                                    <span id="teacher-settings-avatar-preview-initials"
                                        class="font-arcade text-sm"><?= htmlspecialchars($profileAvatarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <img id="teacher-settings-avatar-preview" src="" alt=""
                                        class="hidden h-full w-full object-cover">
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-black text-arcade-ink">Upload new avatar</p>
                                <p id="teacher-settings-avatar-file-name"
                                    class="mt-1 truncate text-xs font-bold text-arcade-ink/58">PNG, JPG, WEBP, or GIF. Max 2MB.</p>
                            </div>
                            <strong class="teacher-settings-upload__button">Choose File</strong>
                        </div>
                        <input id="teacher-settings-avatar-file" name="profile_image" type="file"
                            accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only">
                    </label>

                    <label class="teacher-settings-field" for="teacher-settings-firstname">
                        <span>First Name</span>
                        <input id="teacher-settings-firstname" name="firstname" type="text" autocomplete="given-name"
                            maxlength="80" value="<?= htmlspecialchars($profileFirstname, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Jane" required>
                    </label>

                    <label class="teacher-settings-field" for="teacher-settings-lastname">
                        <span>Last Name</span>
                        <input id="teacher-settings-lastname" name="lastname" type="text" autocomplete="family-name"
                            maxlength="80" value="<?= htmlspecialchars($profileLastname, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Doe" required>
                    </label>

                    <label class="teacher-settings-field sm:col-span-2" for="teacher-settings-email">
                        <span>Email</span>
                        <input id="teacher-settings-email" name="email" type="email" autocomplete="email"
                            value="<?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?>"
                            data-current-email="<?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="teacher@example.com" required>
                        <small id="teacher-settings-email-message" class="teacher-settings-field-message" aria-live="polite"></small>
                    </label>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-bold leading-6 text-arcade-ink/62">Leave the file empty if you want to keep your current avatar.</p>
                    <button type="submit"
                        class="teacher-button teacher-button--primary gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M3 2h8l2 2v10H3V2Zm2 2v3h5V4H5Zm0 6v2h6v-2H5Z" />
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

<style>
.teacher-settings-upload {
    display: grid;
    gap: 0.75rem;
}

.teacher-settings-upload__eyebrow {
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.6);
}

.teacher-settings-upload__body {
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 2px solid rgba(38, 25, 15, 0.12);
    border-radius: 1.4rem;
    background: rgba(255, 255, 255, 0.78);
    padding: 1rem;
    cursor: pointer;
    transition: transform 160ms ease, border-color 160ms ease, background-color 160ms ease;
}

.teacher-settings-upload__body:hover {
    transform: translateY(-1px);
    border-color: #26190f;
    background: rgba(255, 209, 102, 0.2);
}

.teacher-settings-upload__preview {
    display: grid;
    width: 4.75rem;
    height: 4.75rem;
    place-items: center;
    overflow: hidden;
    border: 2px solid #26190f;
    border-radius: 1.25rem;
    background: #fff7e8;
    color: #26190f;
}

.teacher-settings-upload__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.55rem;
    border: 2px solid #26190f;
    border-radius: 0.95rem;
    background: #ffd166;
    padding: 0.7rem 1rem;
    color: #26190f;
    font-size: 0.82rem;
    font-weight: 900;
    box-shadow: 0 4px 0 #26190f;
}

.teacher-settings-field {
    display: grid;
    gap: 0.65rem;
}

.teacher-settings-field > span {
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.6);
}

.teacher-settings-field input {
    width: 100%;
    border: 2px solid rgba(38, 25, 15, 0.12);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.86);
    padding: 0.9rem 1rem;
    color: #26190f;
    font-size: 0.95rem;
    font-weight: 700;
    outline: none;
    transition: border-color 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
}

.teacher-settings-field input:focus {
    border-color: #ff8c42;
    box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.14);
}

.teacher-settings-field input.is-invalid {
    border-color: #f97373;
    box-shadow: 0 0 0 4px rgba(249, 115, 115, 0.14);
}

.teacher-settings-field input.is-valid {
    border-color: #4cc9f0;
    box-shadow: 0 0 0 4px rgba(76, 201, 240, 0.14);
}

.teacher-settings-field-message {
    min-height: 1rem;
    color: #d94c3f;
    font-size: 0.75rem;
    font-weight: 800;
}

.teacher-settings-field-message.is-valid {
    color: #0c8a74;
}

body.pixelwar-dark-mode .teacher-settings-upload__body,
body.pixelwar-dark-mode .teacher-settings-field input {
    border-color: rgba(255, 247, 232, 0.18);
    background: #1f160f;
    color: #fff7e8;
}

body.pixelwar-dark-mode .teacher-settings-upload__preview {
    border-color: rgba(255, 247, 232, 0.72);
    background: #2a1d13;
    color: #fff7e8;
}

body.pixelwar-dark-mode .teacher-settings-upload__body:hover {
    background: rgba(255, 140, 66, 0.14);
}

body.pixelwar-dark-mode .teacher-settings-field > span,
body.pixelwar-dark-mode .teacher-settings-upload__eyebrow {
    color: rgba(255, 247, 232, 0.72);
}

body.pixelwar-dark-mode .teacher-settings-field-message {
    color: #ff9a8f;
}

body.pixelwar-dark-mode .teacher-settings-field-message.is-valid {
    color: #7ce3d4;
}
</style>

<script>
(() => {
    const input = document.querySelector('#teacher-settings-avatar-file');
    const preview = document.querySelector('#teacher-settings-avatar-preview');
    const initials = document.querySelector('#teacher-settings-avatar-preview-initials');
    const fileName = document.querySelector('#teacher-settings-avatar-file-name');
    const form = document.querySelector('.teacher-settings-form');
    const emailInput = document.querySelector('#teacher-settings-email');
    const emailMessage = document.querySelector('#teacher-settings-email-message');
    let emailIsAvailable = true;

    if (!input || !preview || !fileName || !form || !emailInput || !emailMessage) {
        return;
    }

    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    const maxSize = 2 * 1024 * 1024;

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

        form.submit();
    });
})();
</script>
