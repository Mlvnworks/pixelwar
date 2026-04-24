<?php
$reviewUsername = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Player'));
?>
<main class="auth-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-6 text-arcade-ink">
    <div class="auth-bg absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(255,209,102,0.24),transparent_24%),radial-gradient(circle_at_82%_22%,rgba(76,201,240,0.18),transparent_24%)]"></div>
    <div class="auth-grid absolute inset-0"></div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <div class="w-full max-w-[30rem] rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-6">
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Review Pending</p>
            <h1 class="mt-3 text-3xl font-bold leading-tight md:text-4xl">We&apos;re checking your details.</h1>
            <p class="mt-3 text-sm leading-7 text-arcade-ink/72">
                Hi <?= htmlspecialchars($reviewUsername, ENT_QUOTES, 'UTF-8') ?>, our team is reviewing your submitted details so we can unlock the rest of Pixelwar resources for you soon.
            </p>

            <div class="mt-5 rounded-[22px] border-2 border-arcade-ink bg-white/80 p-4">
                <div class="flex items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border-2 border-arcade-ink bg-arcade-yellow text-arcade-ink shadow-[4px_4px_0_rgba(38,25,15,0.15)]">
                        <svg class="h-5 w-5" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M8 1.5A6.5 6.5 0 1 0 14.5 8 6.5 6.5 0 0 0 8 1.5Zm0 2a1 1 0 0 1 1 1V8a1 1 0 1 1-2 0V4.5a1 1 0 0 1 1-1Zm0 8.2a1.1 1.1 0 1 1 0-2.2 1.1 1.1 0 0 1 0 2.2Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-extrabold text-arcade-ink">Access is temporarily limited</p>
                        <p class="mt-1 text-sm leading-6 text-arcade-ink/68">
                            You can stay signed in, but the dashboard and other student pages will open only after your profile review is approved.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <form action="./?c=logout" method="post" class="m-0">
                    <?= pixelwarCsrfField() ?>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_rgba(38,25,15,0.18)] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<style>
.auth-grid {
    background-image: linear-gradient(rgba(38, 25, 15, 0.055) 1px, transparent 1px), linear-gradient(90deg, rgba(38, 25, 15, 0.055) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.78), transparent 74%);
}
</style>
