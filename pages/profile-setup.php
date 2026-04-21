<?php
$profileSetupErrors = $_SESSION['profile_setup_errors'] ?? [];
$profileSetupOld = $_SESSION['profile_setup_old'] ?? [];
$setupUsername = (string) ($_SESSION['username'] ?? 'Player');
$setupRoleId = (int) ($_SESSION['role_id'] ?? 0);
$isTeacherSetup = $setupRoleId === 2;
$profileTitle = $isTeacherSetup ? 'Finish your teacher setup.' : 'Finish your profile.';
$profileEyebrow = $isTeacherSetup ? 'Teacher Setup' : 'Player Setup';
$profileDescription = $isTeacherSetup
    ? 'Welcome ' . htmlspecialchars($setupUsername, ENT_QUOTES, 'UTF-8') . '. Set your final access credentials and profile before entering the teacher panel.'
    : 'Welcome ' . htmlspecialchars($setupUsername, ENT_QUOTES, 'UTF-8') . '. Add your details before entering the arena.';
$submitLabel = $isTeacherSetup ? 'Finish Teacher Setup' : 'Enter Pixelwar';
unset($_SESSION['profile_setup_errors'], $_SESSION['profile_setup_old']);
?>
<main class="auth-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-4 text-arcade-ink">
    <div class="auth-bg absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(76,201,240,0.2),transparent_24%),radial-gradient(circle_at_80%_80%,rgba(255,209,102,0.3),transparent_26%)]"></div>
    <div class="auth-grid absolute inset-0"></div>
    <div class="auth-token auth-token--one">ID</div>
    <div class="auth-token auth-token--two">IMG</div>
    <div class="auth-token auth-token--three">OK</div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <form id="profile-setup-form" class="auth-card w-full max-w-[25rem] rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[8px_8px_0_#26190f] md:p-5" action="./?c=profile-setup" method="post" enctype="multipart/form-data" novalidate>
            <?= pixelwarCsrfField() ?>
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange"><?= htmlspecialchars($profileEyebrow, ENT_QUOTES, 'UTF-8') ?></p>
            <h1 class="mt-2 text-[1.35rem] font-bold leading-tight"><?= htmlspecialchars($profileTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-1 text-sm leading-5 text-arcade-ink/68"><?= $profileDescription ?></p>

            <?php if ($profileSetupErrors !== []) : ?>
                <div class="mt-3 rounded-2xl border-2 border-arcade-coral bg-arcade-coral/10 px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="alert">
                    <?php foreach ($profileSetupErrors as $error) : ?>
                        <p class="mb-1 last:mb-0"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($isTeacherSetup) : ?>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="block text-sm font-bold sm:col-span-2" for="profile-username">
                        Final Username
                        <input id="profile-username" name="username" type="text" autocomplete="username" required maxlength="32" value="<?= htmlspecialchars((string) ($profileSetupOld['username'] ?? $setupUsername), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="teacher_username" data-teacher-setup-username>
                        <span id="profile-username-feedback" class="mt-1 block min-h-5 text-xs font-bold leading-5 text-arcade-ink/55"></span>
                    </label>

                    <label class="block text-sm font-bold" for="profile-password">
                        Final Password
                        <input id="profile-password" name="password" type="password" autocomplete="new-password" required minlength="8" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="Minimum 8 characters">
                    </label>

                    <label class="block text-sm font-bold" for="profile-confirm-password">
                        Confirm Password
                        <input id="profile-confirm-password" name="confirm_password" type="password" autocomplete="new-password" required minlength="8" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="Repeat password">
                    </label>
                </div>
            <?php endif; ?>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="block text-sm font-bold" for="profile-firstname">
                    First Name
                    <input id="profile-firstname" name="firstname" type="text" autocomplete="given-name" required maxlength="80" value="<?= htmlspecialchars((string) ($profileSetupOld['firstname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="Mika">
                </label>

                <label class="block text-sm font-bold" for="profile-lastname">
                    Last Name
                    <input id="profile-lastname" name="lastname" type="text" autocomplete="family-name" required maxlength="80" value="<?= htmlspecialchars((string) ($profileSetupOld['lastname'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="Reyes">
                </label>
            </div>

            <label class="mt-3 block text-sm font-bold" for="profile-image">Profile Image</label>
            <div id="profile-upload-dropzone" class="profile-upload-dropzone mt-1 rounded-[22px] border-2 border-dashed border-arcade-ink/25 bg-white/75 p-3 transition">
                <div class="flex items-center gap-3">
                    <div class="profile-upload-preview grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow text-arcade-ink">
                        <span id="profile-upload-initials" class="font-arcade text-sm"><?= htmlspecialchars(strtoupper(substr($setupUsername, 0, 2)) ?: 'PW', ENT_QUOTES, 'UTF-8') ?></span>
                        <img id="profile-upload-preview-image" src="" alt="Profile image preview" class="hidden h-full w-full object-cover">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-extrabold text-arcade-ink">Drop your avatar here</p>
                        <p id="profile-upload-file-name" class="mt-1 truncate text-xs font-bold text-arcade-ink/55">PNG, JPG, WEBP, or GIF. Max 2MB.</p>
                    </div>
                    <span class="inline-flex shrink-0 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-3 py-2 text-xs font-extrabold shadow-[0_3px_0_#26190f]">Browse</span>
                </div>
                <input id="profile-image" name="profile_image" type="file" accept="image/png,image/jpeg,image/webp,image/gif" required class="sr-only">
            </div>
            <p id="profile-upload-message" class="mt-1 min-h-4 text-xs font-bold leading-5 text-arcade-coral" aria-live="polite"></p>

            <div id="profile-upload-progress" class="profile-upload-progress mt-4 hidden" aria-live="polite">
                <div class="flex items-center justify-between gap-3 text-xs font-extrabold uppercase tracking-[0.16em] text-arcade-ink/60">
                    <span id="profile-upload-progress-label">Preparing upload</span>
                    <span id="profile-upload-progress-value">0%</span>
                </div>
                <div class="mt-2 h-4 overflow-hidden rounded-full border-2 border-arcade-ink bg-white">
                    <span id="profile-upload-progress-bar" class="block h-full w-0 rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan transition-[width]"></span>
                </div>
            </div>

            <button id="profile-submit-button" type="submit" class="profile-submit-button mt-4 inline-flex w-full items-center justify-center gap-3 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-6 py-2.5 text-sm font-bold shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                <span class="profile-submit-spinner hidden h-4 w-4 rounded-full border-2 border-arcade-ink/40 border-t-arcade-ink" aria-hidden="true"></span>
                <span id="profile-submit-label"><?= htmlspecialchars($submitLabel, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
        </form>
    </section>
</main>

<style>
.auth-page {
    min-height: calc(100vh - 4.25rem);
}

.auth-bg {
    animation: authGlow 8s ease-in-out infinite alternate;
}

.auth-grid {
    background-image: linear-gradient(rgba(38, 25, 15, 0.055) 1px, transparent 1px), linear-gradient(90deg, rgba(38, 25, 15, 0.055) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.78), transparent 74%);
}

.auth-card {
    animation: authCardIn 650ms ease both;
}

.auth-card input,
.auth-card button,
.auth-card a {
    transition: transform 180ms ease, border-color 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
}

.auth-card input:focus {
    box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.16);
}

.profile-upload-dropzone {
    cursor: pointer;
}

.profile-upload-dropzone:hover,
.profile-upload-dropzone.is-dragging {
    border-color: #ff8c42;
    background: rgba(255, 209, 102, 0.2);
    transform: translateY(-1px);
}

.profile-upload-dropzone.is-invalid {
    border-color: #f97373;
    background: rgba(249, 115, 115, 0.1);
}

.profile-upload-preview {
    box-shadow: 4px 4px 0 rgba(38, 25, 15, 0.18);
}

.profile-submit-button.is-loading {
    pointer-events: none;
    transform: translateY(1px);
    opacity: 0.88;
}

.profile-submit-button.is-loading .profile-submit-spinner {
    display: inline-block;
    animation: profileSubmitSpin 800ms linear infinite;
}

@keyframes profileSubmitSpin {
    to {
        transform: rotate(360deg);
    }
}

.auth-token {
    position: absolute;
    z-index: 1;
    display: grid;
    place-items: center;
    border: 3px solid #26190f;
    border-radius: 18px;
    color: #26190f;
    font-weight: 800;
    box-shadow: 7px 7px 0 rgba(38, 25, 15, 0.18);
    animation: authTokenFloat 4.5s ease-in-out infinite;
}

.auth-token--one {
    left: 12%;
    top: 20%;
    height: 4.5rem;
    width: 5.5rem;
    background: #ffd166;
    transform: rotate(-8deg);
}

.auth-token--two {
    right: 14%;
    top: 16%;
    height: 4.2rem;
    width: 4.2rem;
    border-radius: 999px;
    background: #4cc9f0;
    animation-delay: 700ms;
}

.auth-token--three {
    bottom: 14%;
    right: 18%;
    height: 4.25rem;
    width: 5rem;
    background: #fffdf6;
    transform: rotate(7deg);
    animation-delay: 1.1s;
}

@keyframes authGlow {
    from {
        transform: scale(1);
        filter: saturate(1);
    }
    to {
        transform: scale(1.05);
        filter: saturate(1.18);
    }
}

@keyframes authCardIn {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes authTokenFloat {
    0%,
    100% {
        translate: 0 0;
    }
    50% {
        translate: 0 -12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .auth-bg,
    .auth-card,
    .auth-token {
        animation: none;
    }

    .auth-card input,
    .auth-card button,
    .auth-card a {
        transition: none;
    }
}

@media (max-width: 640px) {
    .auth-page {
        padding-bottom: 1rem;
        padding-top: 1rem;
    }

    .auth-page .container {
        min-height: calc(100vh - 6.25rem);
    }

    .auth-token {
        opacity: 0.42;
        transform: scale(0.72);
    }

    .auth-token--one {
        left: -1.25rem;
        top: 1.5rem;
    }

    .auth-token--two {
        right: -1rem;
        top: 4rem;
    }

    .auth-token--three {
        bottom: 1.2rem;
        right: 0.5rem;
    }

    .auth-card {
        box-shadow: 8px 8px 0 #26190f;
    }
}
</style>

<script>
(() => {
    const form = document.querySelector('#profile-setup-form');
    const dropzone = document.querySelector('#profile-upload-dropzone');
    const input = document.querySelector('#profile-image');
    const preview = document.querySelector('#profile-upload-preview-image');
    const initials = document.querySelector('#profile-upload-initials');
    const fileName = document.querySelector('#profile-upload-file-name');
    const message = document.querySelector('#profile-upload-message');
    const progress = document.querySelector('#profile-upload-progress');
    const progressLabel = document.querySelector('#profile-upload-progress-label');
    const progressValue = document.querySelector('#profile-upload-progress-value');
    const progressBar = document.querySelector('#profile-upload-progress-bar');
    const submitButton = document.querySelector('#profile-submit-button');
    const submitLabel = document.querySelector('#profile-submit-label');
    const usernameInput = document.querySelector('#profile-username');
    const usernameFeedback = document.querySelector('#profile-username-feedback');
    const defaultSubmitLabel = submitLabel ? submitLabel.textContent : 'Continue';

    if (!form || !dropzone || !input || !preview || !initials || !fileName || !message || !progress || !progressLabel || !progressValue || !progressBar || !submitButton || !submitLabel) {
        return;
    }

    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    const maxSize = 2 * 1024 * 1024;
    const requiresTeacherUsernameCheck = Boolean(usernameInput && usernameFeedback);
    const teacherUsernameState = {
        valid: !requiresTeacherUsernameCheck,
        available: !requiresTeacherUsernameCheck,
        pending: false,
    };

    const setError = (text) => {
        message.textContent = text;
        dropzone.classList.toggle('is-invalid', text !== '');
    };

    const setProgress = (value, label = 'Uploading avatar') => {
        const safeValue = Math.max(0, Math.min(100, Math.round(value)));
        progress.classList.remove('hidden');
        progressLabel.textContent = label;
        progressValue.textContent = `${safeValue}%`;
        progressBar.style.width = `${safeValue}%`;
    };

    const setLoading = (isLoading, label = 'Uploading avatar') => {
        submitButton.disabled = isLoading;
        submitButton.classList.toggle('is-loading', isLoading);
        submitLabel.textContent = isLoading ? label : defaultSubmitLabel;
    };

    const updateSubmitAvailability = () => {
        if (!requiresTeacherUsernameCheck) {
            return;
        }

        if (submitButton.classList.contains('is-loading')) {
            return;
        }

        submitButton.disabled = !teacherUsernameState.valid || !teacherUsernameState.available || teacherUsernameState.pending;
    };

    const applyUsernameFeedback = (messageText, tone) => {
        if (!usernameInput || !usernameFeedback) {
            return;
        }

        usernameInput.classList.remove('border-arcade-coral', 'border-arcade-mint', 'border-arcade-yellow');
        usernameFeedback.classList.remove('text-arcade-coral', 'text-arcade-mint', 'text-arcade-orange', 'text-arcade-ink/55');

        if (tone === 'invalid') {
            usernameInput.classList.add('border-arcade-coral');
            usernameFeedback.classList.add('text-arcade-coral');
        } else if (tone === 'valid') {
            usernameInput.classList.add('border-arcade-mint');
            usernameFeedback.classList.add('text-arcade-mint');
        } else if (tone === 'loading') {
            usernameInput.classList.add('border-arcade-yellow');
            usernameFeedback.classList.add('text-arcade-orange');
        } else {
            usernameFeedback.classList.add('text-arcade-ink/55');
        }

        usernameFeedback.textContent = messageText;
    };

    let usernameCheckTimer = null;

    const runTeacherUsernameCheck = () => {
        if (!usernameInput) {
            return;
        }

        const value = usernameInput.value.trim();

        if (value === '') {
            teacherUsernameState.valid = false;
            teacherUsernameState.available = false;
            teacherUsernameState.pending = false;
            applyUsernameFeedback('', 'idle');
            updateSubmitAvailability();
            return;
        }

        teacherUsernameState.pending = true;
        updateSubmitAvailability();
        applyUsernameFeedback('Checking username...', 'loading');

        const url = new URL('./?c=profile-setup', window.location.href);
        url.searchParams.set('check_username', '1');
        url.searchParams.set('username', value);

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.json())
            .then((payload) => {
                teacherUsernameState.valid = Boolean(payload.valid);
                teacherUsernameState.available = Boolean(payload.available);
                teacherUsernameState.pending = false;

                const tone = payload.valid && payload.available ? 'valid' : 'invalid';
                applyUsernameFeedback(payload.message || '', tone);
                updateSubmitAvailability();
            })
            .catch(() => {
                teacherUsernameState.valid = false;
                teacherUsernameState.available = false;
                teacherUsernameState.pending = false;
                applyUsernameFeedback('Unable to check username right now.', 'invalid');
                updateSubmitAvailability();
            });
    };

    const showPreview = (file) => {
        if (!allowedTypes.includes(file.type)) {
            setError('Profile image must be JPG, PNG, WEBP, or GIF.');
            input.value = '';
            return false;
        }

        if (file.size > maxSize) {
            setError('Profile image must be 2MB or smaller.');
            input.value = '';
            return false;
        }

        setError('');
        fileName.textContent = `${file.name} - ${(file.size / 1024).toFixed(0)}KB`;
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        initials.classList.add('hidden');
        return true;
    };

    dropzone.addEventListener('click', () => input.click());
    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');

        if (event.dataTransfer.files.length === 0) {
            return;
        }

        input.files = event.dataTransfer.files;
        showPreview(event.dataTransfer.files[0]);
    });
    input.addEventListener('change', () => {
        if (input.files.length > 0) {
            showPreview(input.files[0]);
        }
    });

    if (requiresTeacherUsernameCheck && usernameInput) {
        usernameInput.addEventListener('input', () => {
            window.clearTimeout(usernameCheckTimer);
            usernameCheckTimer = window.setTimeout(runTeacherUsernameCheck, 320);
        });

        usernameInput.addEventListener('blur', () => {
            window.clearTimeout(usernameCheckTimer);
            runTeacherUsernameCheck();
        });

        if (usernameInput.value.trim() !== '') {
            runTeacherUsernameCheck();
        } else {
            updateSubmitAvailability();
        }
    }

    const submitWithProgress = () => {
        const request = new XMLHttpRequest();
        const formData = new FormData(form);

        setError('');
        setLoading(true);
        setProgress(0, 'Preparing upload');

        request.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                setProgress(12, 'Uploading avatar');
                return;
            }

            setProgress((event.loaded / event.total) * 90, 'Uploading avatar');
        });

        request.addEventListener('load', () => {
            let response = null;

            try {
                response = JSON.parse(request.responseText);
            } catch (error) {
                response = null;
            }

            if (request.status >= 200 && request.status < 300 && response && response.success) {
                setProgress(100, 'Upload complete');
                submitLabel.textContent = response.redirect && response.redirect.includes('teacher/') ? 'Opening teacher panel...' : 'Entering Pixelwar...';
                window.location.href = response.redirect || './?c=home';
                return;
            }

            setLoading(false);
            setProgress(0, 'Upload stopped');
            progress.classList.add('hidden');
            setError(response && response.message ? response.message : 'Profile setup failed. Please try again.');
        });

        request.addEventListener('error', () => {
            setLoading(false);
            progress.classList.add('hidden');
            setError('Upload failed. Check your connection and try again.');
        });

        request.open('POST', form.action);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.send(formData);
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (requiresTeacherUsernameCheck && (!teacherUsernameState.valid || !teacherUsernameState.available || teacherUsernameState.pending)) {
            applyUsernameFeedback('Use a valid and available username before continuing.', 'invalid');
            updateSubmitAvailability();
            return;
        }

        if (input.files.length === 0) {
            setError('Upload a profile image before continuing.');
            return;
        }

        if (!showPreview(input.files[0])) {
            return;
        }

        submitWithProgress();
    });
})();
</script>
