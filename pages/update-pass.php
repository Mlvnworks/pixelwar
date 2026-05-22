<?php
$resetErrors = $_SESSION['reset_password_errors'] ?? [];
$resetOld = $_SESSION['reset_password_old'] ?? [];
unset($_SESSION['reset_password_errors'], $_SESSION['reset_password_old']);

$resetUserId = max(0, (int) ($_GET['uid'] ?? ($resetOld['uid'] ?? 0)));
$resetToken = trim((string) ($_GET['token'] ?? ($resetOld['token'] ?? '')));
$resetLinkMessage = '';
$resetLinkValid = false;

if ($resetUserId > 0 && $resetToken !== '' && $userRepository instanceof UserRepository && $verificationRepository instanceof VerificationRepository) {
    $resetUser = $userRepository->findAuthUserById($resetUserId);
    $resetVerification = $resetUser ? $verificationRepository->findLatest($resetUserId, 'password change') : null;

    if (!$resetUser || !$resetVerification) {
        $resetLinkMessage = 'This password reset link is invalid. Request a new one.';
    } else {
        $resetStatus = (int) ($resetVerification['status'] ?? 0);
        $requestedAt = strtotime((string) ($resetVerification['request_timestamp'] ?? ''));
        $expiresAt = $requestedAt === false ? 0 : $requestedAt + (20 * 60);

        if ($resetStatus === 1) {
            $resetLinkMessage = 'This password reset link was already used.';
        } elseif ($resetStatus === -1 || $expiresAt < time()) {
            if ($resetStatus === 0) {
                $verificationRepository->updateStatus((int) $resetVerification['ev_id'], -1);
            }
            $resetLinkMessage = 'This password reset link has expired. Request a new one.';
        } elseif (!pixelwarVerificationTokenMatches((string) ($resetVerification['token'] ?? ''), $resetToken)) {
            $resetLinkMessage = 'This password reset link is invalid. Request a new one.';
        } else {
            $resetLinkValid = true;
        }
    }
} elseif ($resetErrors === []) {
    $resetLinkMessage = 'Open this page using the password reset link sent to your email.';
}
?>
<main class="auth-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-4 text-arcade-ink">
    <div class="auth-bg absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(76,201,240,0.2),transparent_24%),radial-gradient(circle_at_80%_80%,rgba(255,209,102,0.3),transparent_26%)]"></div>
    <div class="auth-grid absolute inset-0"></div>
    <div class="auth-token auth-token--one">KEY</div>
    <div class="auth-token auth-token--two">OK</div>
    <div class="auth-token auth-token--three">PW</div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <form class="auth-card w-full max-w-[23rem] rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[8px_8px_0_#26190f] md:p-5" action="./?c=update-pass" method="post" novalidate>
            <?= pixelwarCsrfField() ?>
            <input type="hidden" name="uid" value="<?= (int) $resetUserId ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8') ?>">

            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Reset Password</p>
            <h1 class="mt-2 text-[1.35rem] font-bold leading-tight">Choose a new password.</h1>
            <p class="mt-1 text-sm leading-5 text-arcade-ink/68">Use a password with at least 8 characters. This reset link expires 20 minutes after it is issued.</p>

            <?php if ($resetErrors !== []) : ?>
                <div class="mt-3 rounded-2xl border-2 border-arcade-coral bg-arcade-coral/10 px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="alert">
                    <?php foreach ($resetErrors as $error) : ?>
                        <p class="mb-1 last:mb-0"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($resetLinkMessage !== '') : ?>
                <div class="mt-3 rounded-2xl border-2 <?= $resetLinkValid ? 'border-arcade-mint bg-arcade-mint/20' : 'border-arcade-coral bg-arcade-coral/10' ?> px-3 py-2 text-sm font-bold leading-5 text-arcade-ink" role="<?= $resetLinkValid ? 'status' : 'alert' ?>">
                    <?= htmlspecialchars($resetLinkMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <label class="mt-4 block text-sm font-bold" for="reset-password">New Password</label>
            <input id="reset-password" name="password" type="password" autocomplete="new-password" required minlength="8" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2.5 outline-none transition focus:border-arcade-orange" placeholder="Minimum 8 characters" <?= $resetLinkValid ? '' : 'disabled' ?>>

            <label class="mt-3 block text-sm font-bold" for="reset-confirm-password">Confirm Password</label>
            <input id="reset-confirm-password" name="confirm_password" type="password" autocomplete="new-password" required minlength="8" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2.5 outline-none transition focus:border-arcade-orange" placeholder="Repeat password" <?= $resetLinkValid ? '' : 'disabled' ?>>

            <button type="submit" class="mt-4 w-full rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-6 py-2.5 text-sm font-bold shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:bg-arcade-yellow disabled:hover:text-arcade-ink" <?= $resetLinkValid ? '' : 'disabled' ?>>
                Update Password
            </button>

            <p class="mt-4 text-center text-sm text-arcade-ink/68">
                Need another link?
                <a href="./?c=forgot-password" class="font-bold text-arcade-orange no-underline hover:text-arcade-coral">Request a new reset email</a>
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
