<?php
$signupErrors = $_SESSION['signup_errors'] ?? [];
$signupOld = $_SESSION['signup_old'] ?? [];
unset($_SESSION['signup_errors'], $_SESSION['signup_old']);
?>
<main class="auth-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-4 text-arcade-ink">
    <div class="auth-bg absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(76,201,240,0.2),transparent_24%),radial-gradient(circle_at_80%_80%,rgba(255,209,102,0.3),transparent_26%)]"></div>
    <div class="auth-grid absolute inset-0"></div>
    <div class="auth-token auth-token--one">CSS</div>
    <div class="auth-token auth-token--two">1v1</div>
    <div class="auth-token auth-token--three">{ }</div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <form id="signup-form" class="auth-card w-full max-w-[23rem] rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-3.5 shadow-[8px_8px_0_#26190f] md:p-4" action="./?c=signup" method="post" novalidate>
            <?= pixelwarCsrfField() ?>
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Sign Up</p>
            <h1 class="mt-2 text-[1.35rem] font-bold leading-tight">Create your player.</h1>
            <p class="mt-1 text-sm leading-5 text-arcade-ink/68">Make a Pixelwar account and start matching CSS designs.</p>

            <?php if ($signupErrors !== []) : ?>
                <div class="mt-3 rounded-2xl border-2 border-arcade-coral bg-arcade-coral/10 px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="alert">
                    <?php foreach ($signupErrors as $error) : ?>
                        <p class="mb-1 last:mb-0"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label class="mt-3 block text-sm font-bold" for="signup-username">Username</label>
            <input id="signup-username" name="username" type="text" autocomplete="username" required minlength="3" maxlength="32" pattern="[A-Za-z0-9_]{3,32}" value="<?= htmlspecialchars((string) ($signupOld['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="signup-input mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="pixelrookie">
            <p id="signup-username-message" class="signup-field-message mt-1 text-xs font-bold" aria-live="polite"></p>

            <label class="mt-2.5 block text-sm font-bold" for="signup-email">Email</label>
            <input id="signup-email" name="email" type="email" autocomplete="email" required value="<?= htmlspecialchars((string) ($signupOld['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="signup-input mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="player@example.com">
            <p id="signup-email-message" class="signup-field-message mt-1 text-xs font-bold" aria-live="polite"></p>

            <label class="mt-2.5 block text-sm font-bold" for="signup-password">Password</label>
            <input id="signup-password" name="password" type="password" autocomplete="new-password" required minlength="8" class="signup-input mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="********">
            <p id="signup-password-message" class="signup-field-message mt-1 text-xs font-bold" aria-live="polite"></p>

            <label class="mt-2.5 block text-sm font-bold" for="signup-confirm-password">Confirm Password</label>
            <input id="signup-confirm-password" name="confirm_password" type="password" autocomplete="new-password" required minlength="8" class="signup-input mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="********">
            <p id="signup-confirm-password-message" class="signup-field-message mt-1 text-xs font-bold" aria-live="polite"></p>

            <button id="signup-submit-button" type="submit" class="auth-submit-button mt-3.5 inline-flex w-full items-center justify-center gap-3 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-6 py-2 text-sm font-bold shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                <span class="auth-submit-spinner hidden h-4 w-4 rounded-full border-2 border-arcade-ink/40 border-t-arcade-ink" aria-hidden="true"></span>
                <span id="signup-submit-label">Create Account</span>
            </button>

            <div class="my-2.5 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.2em] text-arcade-ink/45">
                <span class="h-px flex-1 bg-arcade-ink/15"></span>
                or
                <span class="h-px flex-1 bg-arcade-ink/15"></span>
            </div>

            <button type="button" class="auth-google-button flex w-full items-center justify-center gap-3 rounded-xl border-2 border-arcade-ink/15 bg-white px-6 py-2 text-sm font-bold text-arcade-ink transition hover:-translate-y-0.5 hover:border-arcade-ink/30 hover:bg-arcade-peach/40">
                <span class="grid h-6 w-6 place-items-center rounded-full border border-arcade-ink/10 bg-white" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" role="img" focusable="false">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.15v2.84C3.96 20.53 7.68 23 12 23z" />
                        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.15C1.41 8.53 1 10.22 1 12s.41 3.47 1.15 4.94l3.69-2.84z" />
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.68 1 3.96 3.47 2.15 7.06l3.69 2.84C6.71 7.3 9.14 5.38 12 5.38z" />
                    </svg>
                </span>
                Continue with Google
            </button>

            <p class="mt-2.5 text-center text-sm text-arcade-ink/68">
                Already registered?
                <a href="./?c=login" class="font-bold text-arcade-orange no-underline hover:text-arcade-coral">Login instead</a>
            </p>
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

.signup-input.is-invalid {
    border-color: #f97373;
    background: rgba(249, 115, 115, 0.08);
}

.signup-input.is-valid {
    border-color: #8bd3c7;
    background: rgba(139, 211, 199, 0.14);
}

.signup-field-message {
    min-height: 1rem;
    color: #f97373;
}

.signup-field-message.is-valid {
    color: #247c6c;
}

.auth-google-button:hover {
    box-shadow: 0 6px 0 rgba(38, 25, 15, 0.16);
}

.auth-submit-button.is-loading {
    pointer-events: none;
    transform: translateY(1px);
    opacity: 0.88;
}

.auth-submit-button.is-loading .auth-submit-spinner {
    display: inline-block;
    animation: authSubmitSpin 800ms linear infinite;
}

@keyframes authSubmitSpin {
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

@media (max-height: 720px) {
    .auth-page {
        padding-bottom: 0.75rem;
        padding-top: 0.75rem;
    }

    .auth-page .container {
        min-height: calc(100vh - 5.75rem);
    }

    .auth-card {
        padding: 0.85rem;
    }

    .auth-card h1 {
        font-size: 1.35rem;
        margin-top: 0.55rem;
    }

    .auth-card p,
    .auth-card label,
    .auth-card button,
    .auth-card input {
        font-size: 0.84rem;
    }
}
</style>

<script>
(() => {
    const form = document.querySelector('#signup-form');

    if (!form) {
        return;
    }

    const fields = {
        username: document.querySelector('#signup-username'),
        email: document.querySelector('#signup-email'),
        password: document.querySelector('#signup-password'),
        confirmPassword: document.querySelector('#signup-confirm-password'),
    };
    const messages = {
        username: document.querySelector('#signup-username-message'),
        email: document.querySelector('#signup-email-message'),
        password: document.querySelector('#signup-password-message'),
        confirmPassword: document.querySelector('#signup-confirm-password-message'),
    };
    const availability = {
        username: null,
        email: null,
    };
    const submitButton = document.querySelector('#signup-submit-button');
    const submitLabel = document.querySelector('#signup-submit-label');
    let allowSubmit = false;

    const setLoading = (isLoading) => {
        if (!submitButton || !submitLabel) {
            return;
        }

        submitButton.disabled = isLoading;
        submitButton.classList.toggle('is-loading', isLoading);
        submitLabel.textContent = isLoading ? 'Creating account...' : 'Create Account';
    };

    const debounce = (callback, delay = 350) => {
        let timeoutId;

        return (...args) => {
            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(() => callback(...args), delay);
        };
    };

    const setFieldState = (field, message, isValid = false) => {
        const input = fields[field];
        const messageBox = messages[field];

        if (!input || !messageBox) {
            return;
        }

        input.classList.toggle('is-invalid', message !== '' && !isValid);
        input.classList.toggle('is-valid', message !== '' && isValid);
        messageBox.textContent = message;
        messageBox.classList.toggle('is-valid', isValid);
    };

    const validatePassword = () => {
        const password = fields.password.value;
        const confirmPassword = fields.confirmPassword.value;
        let isValid = true;

        if (password !== '' && password.length < 8) {
            setFieldState('password', 'Password must be at least 8 characters.');
            isValid = false;
        } else {
            setFieldState('password', password === '' ? '' : 'Password length looks good.', password !== '');
        }

        if (confirmPassword !== '' && password !== confirmPassword) {
            setFieldState('confirmPassword', 'Password confirmation does not match.');
            isValid = false;
        } else {
            setFieldState('confirmPassword', confirmPassword === '' ? '' : 'Passwords match.', confirmPassword !== '');
        }

        return isValid;
    };

    const validateFormat = (field) => {
        const input = fields[field];

        if (!input.value.trim()) {
            setFieldState(field, '');
            availability[field] = null;
            return false;
        }

        if (field === 'username' && !/^[A-Za-z0-9_]{3,32}$/.test(input.value.trim())) {
            setFieldState(field, 'Username must be 3-32 characters and only use letters, numbers, or underscores.');
            availability[field] = false;
            return false;
        }

        if (field === 'email' && !input.validity.valid) {
            setFieldState(field, 'Enter a valid email address.');
            availability[field] = false;
            return false;
        }

        return true;
    };

    const checkAvailability = async (field) => {
        if (!validateFormat(field)) {
            return false;
        }

        const input = fields[field];
        const value = input.value.trim();
        const requestUrl = `./?c=signup&check_signup=1&field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`;

        setFieldState(field, 'Checking availability...', true);

        try {
            const response = await fetch(requestUrl, {
                headers: {
                    Accept: 'application/json',
                },
            });
            const result = await response.json();

            availability[field] = Boolean(result.available);
            setFieldState(
                field,
                result.available ? `${field === 'username' ? 'Username' : 'Email'} is available.` : result.message,
                result.available
            );

            return result.available;
        } catch (error) {
            availability[field] = false;
            setFieldState(field, 'Unable to check availability right now.');
            return false;
        }
    };

    fields.password.addEventListener('input', validatePassword);
    fields.confirmPassword.addEventListener('input', validatePassword);
    fields.username.addEventListener('input', debounce(() => checkAvailability('username')));
    fields.email.addEventListener('input', debounce(() => checkAvailability('email')));
    fields.username.addEventListener('blur', () => checkAvailability('username'));
    fields.email.addEventListener('blur', () => checkAvailability('email'));

    form.addEventListener('submit', async (event) => {
        if (allowSubmit) {
            return;
        }

        event.preventDefault();

        const passwordIsValid = validatePassword();
        const usernameIsAvailable = await checkAvailability('username');
        const emailIsAvailable = await checkAvailability('email');

        if (passwordIsValid && usernameIsAvailable && emailIsAvailable) {
            allowSubmit = true;
            setLoading(true);
            form.submit();
        }
    });
})();
</script>
