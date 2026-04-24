<?php
$reviewUsername = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Player'));
?>
<main class="auth-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-6 text-arcade-ink">
    <div class="auth-bg absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(249,115,115,0.16),transparent_24%),radial-gradient(circle_at_82%_22%,rgba(255,209,102,0.18),transparent_24%)]"></div>
    <div class="auth-grid absolute inset-0"></div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <div class="w-full max-w-[31rem] rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-6">
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-coral">Review Update</p>
            <h1 class="mt-3 text-3xl font-bold leading-tight md:text-4xl">We could not approve the details yet.</h1>
            <p class="mt-3 text-sm leading-7 text-arcade-ink/72">
                Hi <?= htmlspecialchars($reviewUsername, ENT_QUOTES, 'UTF-8') ?>, we checked the submitted information but could not approve the account at this time.
            </p>

            <div class="mt-5 rounded-[22px] border-2 border-arcade-ink bg-white/80 p-4">
                <div class="flex items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border-2 border-arcade-ink bg-arcade-coral text-white shadow-[4px_4px_0_rgba(38,25,15,0.15)]">
                        <svg class="h-5 w-5" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M8 1.5A6.5 6.5 0 1 0 14.5 8 6.5 6.5 0 0 0 8 1.5Zm2 8.6L8 8.1l-2 2-1.1-1.1 2-2-2-2L6 3.9l2 2 2-2 1.1 1.1-2 2 2 2L10 10.1Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-extrabold text-arcade-ink">Your access is still locked</p>
                        <p class="mt-1 text-sm leading-6 text-arcade-ink/68">
                            Please contact your instructor or admin so they can review the submission with you and let you know what needs to be corrected.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5 rounded-[22px] border-2 border-arcade-ink/10 bg-white/70 p-4">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-arcade-ink/58">Next step</p>
                <p class="mt-2 text-sm leading-7 text-arcade-ink/68">
                    We also sent an email update to your registered address so you have a written copy of the decision.
                </p>
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
