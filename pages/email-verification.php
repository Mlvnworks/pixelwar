<?php
$verificationErrors = $_SESSION['verification_errors'] ?? [];
$verificationNotices = $_SESSION['verification_notices'] ?? [];
$pendingEmail = (string) ($_SESSION['pending_verification_email'] ?? '');
$mailWasSent = !empty($_SESSION['pending_verification_mail_sent']);
$maskedEmail = $pendingEmail !== '' && isset($tools) ? $tools->maskEmail($pendingEmail) : $pendingEmail;
unset($_SESSION['verification_errors'], $_SESSION['verification_notices']);
?>
<main class="auth-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-4 text-arcade-ink">
    <div class="auth-bg absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(76,201,240,0.2),transparent_24%),radial-gradient(circle_at_80%_80%,rgba(255,209,102,0.3),transparent_26%)]"></div>
    <div class="auth-grid absolute inset-0"></div>
    <div class="auth-token auth-token--one">OTP</div>
    <div class="auth-token auth-token--two">OK</div>
    <div class="auth-token auth-token--three">@</div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <form class="auth-card w-full max-w-[23rem] rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[8px_8px_0_#26190f] md:p-5" action="./?c=email-verification" method="post" novalidate>
            <?= pixelwarCsrfField() ?>
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Verify Email</p>
            <h1 class="mt-2 text-[1.35rem] font-bold leading-tight">Enter your code.</h1>
            <p class="mt-1 text-sm leading-5 text-arcade-ink/68">
                <?php if ($pendingEmail !== '' && $mailWasSent) : ?>
                    We sent a six-digit code to <strong><?= htmlspecialchars((string) $maskedEmail, ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php elseif ($pendingEmail !== '') : ?>
                    We could not send a code yet. Check your email address, then request another code for <strong><?= htmlspecialchars((string) $maskedEmail, ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php else : ?>
                    Start by creating your player account first.
                <?php endif; ?>
            </p>

            <?php if ($verificationErrors !== []) : ?>
                <div class="mt-3 rounded-2xl border-2 border-arcade-coral bg-arcade-coral/10 px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="alert">
                    <?php foreach ($verificationErrors as $error) : ?>
                        <p class="mb-1 last:mb-0"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($verificationNotices !== []) : ?>
                <div class="mt-3 rounded-2xl border-2 border-arcade-mint bg-arcade-mint/20 px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="status">
                    <?php foreach ($verificationNotices as $notice) : ?>
                        <p class="mb-1 last:mb-0"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label class="mt-4 block text-sm font-bold" for="verification-token">Verification Code</label>
            <input id="verification-token" name="token" type="text" inputmode="numeric" autocomplete="one-time-code" required minlength="6" maxlength="6" pattern="[0-9]{6}" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-3 text-center font-arcade text-base tracking-[0.35em] outline-none transition focus:border-arcade-orange" placeholder="000000">

            <button type="submit" class="mt-4 w-full rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-6 py-2.5 text-sm font-bold shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                Confirm Email
            </button>

            <p class="mt-3 text-center text-sm text-arcade-ink/68">
                Wrong account?
                <a href="./?c=signup" class="font-bold text-arcade-orange no-underline hover:text-arcade-coral">Create again</a>
            </p>

            <?php if ($pendingEmail !== '') : ?>
                <details class="verification-email-change mt-4 rounded-2xl border-2 border-arcade-ink/10 bg-white/70 p-3">
                    <summary class="cursor-pointer text-center text-xs font-extrabold text-arcade-orange transition hover:text-arcade-coral">
                        Need to change email?
                    </summary>
                    <div class="mt-3">
                        <label class="block text-sm font-bold" for="verification-new-email">New Email</label>
                        <input id="verification-new-email" name="new_email" form="verification-email-change-form" type="email" autocomplete="email" required class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 text-sm outline-none transition focus:border-arcade-orange" placeholder="player@example.com">
                        <small id="verification-new-email-message" class="verification-field-message" aria-live="polite"></small>
                        <button type="submit" form="verification-email-change-form" name="change_verification_email" value="1" class="mt-3 w-full rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                            Update Email & Send Code
                        </button>
                    </div>
                </details>
            <?php endif; ?>
        </form>

        <?php if ($pendingEmail !== '') : ?>
            <form id="verification-email-change-form" class="hidden" action="./?c=email-verification" method="post">
                <?= pixelwarCsrfField() ?>
                <input type="hidden" name="change_verification_email" value="1">
            </form>
            <form class="absolute bottom-5 left-1/2 w-full max-w-[23rem] -translate-x-1/2 px-4 text-center sm:px-0" action="./?c=email-verification" method="post">
                <?= pixelwarCsrfField() ?>
                <button type="submit" name="resend_verification" value="1" class="text-xs font-extrabold text-arcade-orange underline decoration-2 underline-offset-4 transition hover:text-arcade-coral">
                    Send another code
                </button>
            </form>
        <?php endif; ?>
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

.verification-field-message {
    display: block;
    min-height: 1rem;
    margin-top: 0.25rem;
    color: #f97373;
    font-size: 0.74rem;
    font-weight: 900;
}

.verification-field-message.is-valid {
    color: #247c6c;
}

.verification-email-change input.is-invalid {
    border-color: #f97373;
    background: rgba(249, 115, 115, 0.08);
}

.verification-email-change input.is-valid {
    border-color: #8bd3c7;
    background: rgba(139, 211, 199, 0.14);
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
    const form = document.querySelector('#verification-email-change-form');
    const input = document.querySelector('#verification-new-email');
    const message = document.querySelector('#verification-new-email-message');
    let emailIsAvailable = false;

    if (!form || !input || !message) {
        return;
    }

    const setState = (text, isValid = false) => {
        message.textContent = text;
        message.classList.toggle('is-valid', isValid);
        input.classList.toggle('is-invalid', text !== '' && !isValid);
        input.classList.toggle('is-valid', text !== '' && isValid);
    };

    const debounce = (callback, delay = 350) => {
        let timeoutId;

        return (...args) => {
            window.clearTimeout(timeoutId);
            timeoutId = window.setTimeout(() => callback(...args), delay);
        };
    };

    const checkEmail = async () => {
        const email = input.value.trim();

        if (email === '') {
            emailIsAvailable = false;
            setState('');
            return false;
        }

        if (!input.validity.valid) {
            emailIsAvailable = false;
            setState('Enter a valid email address.');
            return false;
        }

        setState('Checking email...', true);

        try {
            const response = await fetch(`./?c=email-verification&check_email=1&email=${encodeURIComponent(email)}`, {
                headers: {
                    Accept: 'application/json',
                },
            });
            const result = await response.json();

            emailIsAvailable = Boolean(result.available);
            setState(result.message || '', emailIsAvailable);

            return emailIsAvailable;
        } catch (error) {
            emailIsAvailable = false;
            setState('Unable to check email right now.');
            return false;
        }
    };

    input.addEventListener('input', debounce(checkEmail));
    input.addEventListener('blur', checkEmail);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const canUseEmail = await checkEmail();

        if (!canUseEmail || !emailIsAvailable) {
            input.focus();
            return;
        }

        form.submit();
    });
})();
</script>
