<main class="forgot-page relative min-h-[calc(100vh-4.25rem)] overflow-hidden bg-arcade-cream px-4 py-4 text-arcade-ink">
    <div class="forgot-bg absolute inset-0 bg-[radial-gradient(circle_at_18%_20%,rgba(76,201,240,0.2),transparent_24%),radial-gradient(circle_at_82%_78%,rgba(255,209,102,0.32),transparent_26%)]"></div>
    <div class="forgot-grid absolute inset-0"></div>
    <div class="forgot-token forgot-token--one">?</div>
    <div class="forgot-token forgot-token--two">CSS</div>
    <div class="forgot-token forgot-token--three">ID</div>

    <section class="container relative flex min-h-[calc(100vh-7.25rem)] items-center justify-center">
        <form class="forgot-card w-full max-w-[23rem] rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[8px_8px_0_#26190f] md:p-5" action="./?c=forgot-password" method="get">
            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Find Account</p>
            <h1 class="mt-2 text-[1.45rem] font-bold leading-tight">Recover your player.</h1>
            <p class="mt-2 text-sm leading-6 text-arcade-ink/68">Enter your username or email so we can find your Pixelwar account.</p>

            <label class="mt-5 block text-sm font-bold" for="forgot-identity">Username or Email</label>
            <input id="forgot-identity" name="identity" type="text" autocomplete="username" class="mt-1 w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2.5 outline-none transition focus:border-arcade-orange" placeholder="pixelrookie or player@example.com">

            <button type="button" class="mt-4 w-full rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-6 py-2.5 text-sm font-bold shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white" aria-disabled="true">
                Find Account
            </button>
            <p class="mt-2 text-center text-xs font-bold leading-5 text-arcade-ink/55">Account recovery flow is not enabled yet.</p>

            <p class="mt-4 text-center text-sm text-arcade-ink/68">
                Remembered it?
                <a href="./?c=login" class="font-bold text-arcade-orange no-underline hover:text-arcade-coral">Back to login</a>
            </p>
        </form>
    </section>
</main>

<style>
.forgot-page {
    min-height: calc(100vh - 4.25rem);
}

.forgot-bg {
    animation: forgotGlow 8s ease-in-out infinite alternate;
}

.forgot-grid {
    background-image: linear-gradient(rgba(38, 25, 15, 0.055) 1px, transparent 1px), linear-gradient(90deg, rgba(38, 25, 15, 0.055) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.78), transparent 74%);
}

.forgot-card {
    animation: forgotCardIn 650ms ease both;
}

.forgot-card input,
.forgot-card button,
.forgot-card a {
    transition: transform 180ms ease, border-color 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
}

.forgot-card input:focus {
    box-shadow: 0 0 0 4px rgba(255, 140, 66, 0.16);
}

.forgot-token {
    position: absolute;
    z-index: 1;
    display: grid;
    place-items: center;
    border: 3px solid #26190f;
    border-radius: 18px;
    color: #26190f;
    font-weight: 800;
    box-shadow: 7px 7px 0 rgba(38, 25, 15, 0.18);
    animation: forgotTokenFloat 4.5s ease-in-out infinite;
}

.forgot-token--one {
    left: 13%;
    top: 22%;
    height: 4.4rem;
    width: 4.4rem;
    border-radius: 999px;
    background: #ffd166;
    transform: rotate(-8deg);
}

.forgot-token--two {
    right: 14%;
    top: 16%;
    height: 4.25rem;
    width: 5rem;
    background: #4cc9f0;
    animation-delay: 700ms;
}

.forgot-token--three {
    bottom: 14%;
    right: 18%;
    height: 4rem;
    width: 4.75rem;
    background: #fffdf6;
    transform: rotate(7deg);
    animation-delay: 1.1s;
}

@keyframes forgotGlow {
    from {
        transform: scale(1);
        filter: saturate(1);
    }
    to {
        transform: scale(1.05);
        filter: saturate(1.18);
    }
}

@keyframes forgotCardIn {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes forgotTokenFloat {
    0%,
    100% {
        translate: 0 0;
    }
    50% {
        translate: 0 -12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .forgot-bg,
    .forgot-card,
    .forgot-token {
        animation: none;
    }

    .forgot-card input,
    .forgot-card button,
    .forgot-card a {
        transition: none;
    }
}

@media (max-width: 640px) {
    .forgot-page {
        padding-bottom: 1rem;
        padding-top: 1rem;
    }

    .forgot-page .container {
        min-height: calc(100vh - 6.25rem);
    }

    .forgot-token {
        opacity: 0.42;
        transform: scale(0.72);
    }

    .forgot-token--one {
        left: -1.25rem;
        top: 1.5rem;
    }

    .forgot-token--two {
        right: -1rem;
        top: 4rem;
    }

    .forgot-token--three {
        bottom: 1.2rem;
        right: 0.5rem;
    }

    .forgot-card {
        box-shadow: 8px 8px 0 #26190f;
    }
}

@media (max-height: 720px) {
    .forgot-page {
        padding-bottom: 0.75rem;
        padding-top: 0.75rem;
    }

    .forgot-page .container {
        min-height: calc(100vh - 5.75rem);
    }

    .forgot-card {
        padding: 0.85rem;
    }
}
</style>
