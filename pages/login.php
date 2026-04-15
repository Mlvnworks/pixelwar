<?php
$loginErrors = $_SESSION['login_errors'] ?? [];
$loginOld = $_SESSION['login_old'] ?? [];
unset($_SESSION['login_errors'], $_SESSION['login_old']);
?>
<main class="login-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-4 text-arcade-ink">
    <div class="login-bg absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(76,201,240,0.2),transparent_24%),radial-gradient(circle_at_80%_80%,rgba(255,209,102,0.3),transparent_26%)]"></div>
    <div class="login-grid absolute inset-0"></div>
    <div class="login-token login-token--one">CSS</div>
    <div class="login-token login-token--two">1v1</div>
    <div class="login-token login-token--three">{ }</div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <form id="login-form" class="login-card w-full max-w-[23.5rem] rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[8px_8px_0_#26190f] md:p-5" action="./?c=login" method="post" novalidate>
            <?= pixelwarCsrfField() ?>
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Login</p>
            <h1 class="mt-2 text-[1.35rem] font-bold leading-tight">Back to the arena.</h1>
            <p class="mt-1.5 text-sm leading-5 text-arcade-ink/68">Jump back in with your Pixelwar account or continue with Google.</p>

            <?php if ($loginErrors !== []) : ?>
                <div class="mt-3 rounded-2xl border-2 border-arcade-coral bg-arcade-coral/10 px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="alert">
                    <?php foreach ($loginErrors as $error) : ?>
                        <p class="mb-1 last:mb-0"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label class="mt-4 block text-sm font-bold" for="login-identity">Username or Email</label>
            <input id="login-identity" name="identity" type="text" autocomplete="username" required value="<?= htmlspecialchars((string) ($loginOld['identity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="pixelrookie or player@example.com">

            <label class="mt-3 block text-sm font-bold" for="login-password">Password</label>
            <input id="login-password" name="password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 outline-none transition focus:border-arcade-orange" placeholder="********">
            <div class="mt-2 text-right">
                <a href="./?c=forgot-password" class="text-xs font-bold text-arcade-orange no-underline hover:text-arcade-coral">Forgot password?</a>
            </div>

            <button id="login-submit-button" type="submit" class="auth-submit-button mt-4 inline-flex w-full items-center justify-center gap-3 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-6 py-2 text-sm font-bold shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                <span class="auth-submit-spinner hidden h-4 w-4 rounded-full border-2 border-arcade-ink/40 border-t-arcade-ink" aria-hidden="true"></span>
                <span class="auth-submit-label">Login</span>
            </button>

            <div class="my-3 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.2em] text-arcade-ink/45">
                <span class="h-px flex-1 bg-arcade-ink/15"></span>
                or
                <span class="h-px flex-1 bg-arcade-ink/15"></span>
            </div>

            <button type="button" class="login-google-button flex w-full items-center justify-center gap-3 rounded-xl border-2 border-arcade-ink/15 bg-white px-6 py-2 text-sm font-bold text-arcade-ink transition hover:-translate-y-0.5 hover:border-arcade-ink/30 hover:bg-arcade-peach/40">
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

            <p class="mt-3 text-center text-sm text-arcade-ink/68">
                New player?
                <a href="./?c=signup" class="font-bold text-arcade-orange no-underline hover:text-arcade-coral">Create an account</a>
            </p>
        </form>
    </section>
</main>

<style>
.login-page {
    min-height: calc(100vh - 4.25rem);
}

.login-bg {
    animation: loginGlow 8s ease-in-out infinite alternate;
}

.login-grid {
    background-image: linear-gradient(rgba(38, 25, 15, 0.055) 1px, transparent 1px), linear-gradient(90deg, rgba(38, 25, 15, 0.055) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.78), transparent 74%);
}

.login-card {
    max-height: calc(100vh - 6rem);
    animation: loginCardIn 650ms ease both;
    overflow-y: auto;
}

.login-card input,
.login-card button,
.login-card a {
    transition: transform 180ms ease, border-color 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
}

.login-card input:focus {
    box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.16);
}

.login-google-button:hover {
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

.login-token {
    position: absolute;
    z-index: 1;
    display: grid;
    place-items: center;
    border: 3px solid #26190f;
    border-radius: 18px;
    color: #26190f;
    font-weight: 800;
    box-shadow: 7px 7px 0 rgba(38, 25, 15, 0.18);
    animation: loginTokenFloat 4.5s ease-in-out infinite;
}

.login-token--one {
    left: 12%;
    top: 20%;
    height: 4.5rem;
    width: 5.5rem;
    background: #ffd166;
    transform: rotate(-8deg);
}

.login-token--two {
    right: 14%;
    top: 16%;
    height: 4.2rem;
    width: 4.2rem;
    border-radius: 999px;
    background: #4cc9f0;
    animation-delay: 700ms;
}

.login-token--three {
    bottom: 14%;
    right: 18%;
    height: 4.25rem;
    width: 5rem;
    background: #fffdf6;
    transform: rotate(7deg);
    animation-delay: 1.1s;
}

@keyframes loginGlow {
    from {
        transform: scale(1);
        filter: saturate(1);
    }
    to {
        transform: scale(1.05);
        filter: saturate(1.18);
    }
}

@keyframes loginCardIn {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes loginTokenFloat {
    0%,
    100% {
        translate: 0 0;
    }
    50% {
        translate: 0 -12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .login-bg,
    .login-card,
    .login-token {
        animation: none;
    }

    .login-card input,
    .login-card button,
    .login-card a {
        transition: none;
    }
}

@media (max-width: 640px) {
    .login-page {
        padding-bottom: 1rem;
        padding-top: 1rem;
    }

    .login-page .container {
        min-height: calc(100vh - 6.25rem);
    }

    .login-token {
        opacity: 0.42;
        transform: scale(0.72);
    }

    .login-token--one {
        left: -1.25rem;
        top: 1.5rem;
    }

    .login-token--two {
        right: -1rem;
        top: 4rem;
    }

    .login-token--three {
        bottom: 1.2rem;
        right: 0.5rem;
    }

    .login-card {
        max-height: calc(100vh - 6.75rem);
        box-shadow: 8px 8px 0 #26190f;
    }
}

@media (max-height: 720px) {
    .login-page {
        padding-bottom: 0.75rem;
        padding-top: 0.75rem;
    }

    .login-page .container {
        min-height: calc(100vh - 5.75rem);
    }

    .login-card {
        max-height: calc(100vh - 6rem);
        padding: 0.85rem;
    }

    .login-card h1 {
        font-size: 1.35rem;
        margin-top: 0.55rem;
    }

    .login-card p,
    .login-card label,
    .login-card button,
    .login-card input {
        font-size: 0.84rem;
    }
}
</style>

<script>
(() => {
    const form = document.querySelector('#login-form');
    const button = document.querySelector('#login-submit-button');
    const label = button ? button.querySelector('.auth-submit-label') : null;

    if (!form || !button || !label) {
        return;
    }

    form.addEventListener('submit', () => {
        button.disabled = true;
        button.classList.add('is-loading');
        label.textContent = 'Logging in...';
    });
})();
</script>
