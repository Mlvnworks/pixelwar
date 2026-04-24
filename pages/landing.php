<main class="landing-page relative overflow-hidden bg-arcade-cream text-arcade-ink">
    <div class="landing-bg absolute inset-0 bg-[radial-gradient(circle_at_14%_18%,rgba(255,209,102,0.34),transparent_24%),radial-gradient(circle_at_84%_12%,rgba(76,201,240,0.22),transparent_25%),radial-gradient(circle_at_58%_92%,rgba(249,115,115,0.18),transparent_28%)]"></div>
    <div class="landing-grid absolute inset-0"></div>

    <section class="container relative grid min-h-[calc(100vh-120px)] items-center gap-10 px-4 py-16 lg:grid-cols-[1.05fr_0.95fr]">
        <div class="landing-copy">
            <p class="landing-kicker font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Pixelwar</p>
            <h1 class="mt-6 max-w-4xl text-5xl font-bold leading-[0.95] tracking-tight md:text-7xl">
                Learn CSS by matching what you see.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-9 text-arcade-ink/72">
                Pixelwar makes CSS practice feel like a quick little game: look at the design, grab the styles that fit, and rebuild it piece by piece at your own pace.
            </p>

            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="./?c=login" class="landing-button landing-button--primary inline-flex min-w-[15rem] justify-center rounded-2xl border-2 border-arcade-ink bg-arcade-yellow px-8 py-4 text-sm font-bold text-arcade-ink no-underline shadow-[0_7px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                    Start Learning
                </a>
            </div>

            <div class="landing-stats mt-10 grid max-w-xl gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border-2 border-arcade-ink/10 bg-white/70 p-4">
                    <p class="font-arcade text-[9px] uppercase tracking-[0.2em] text-arcade-orange">Solo</p>
                    <p class="mt-2 text-lg font-bold">Solo Solving</p>
                </div>
                <div class="rounded-2xl border-2 border-arcade-ink/10 bg-white/70 p-4">
                    <p class="font-arcade text-[9px] uppercase tracking-[0.2em] text-arcade-cyan">Versus</p>
                    <p class="mt-2 text-lg font-bold">1v1 Match</p>
                </div>
                <div class="rounded-2xl border-2 border-arcade-ink/10 bg-white/70 p-4">
                    <p class="font-arcade text-[9px] uppercase tracking-[0.2em] text-arcade-coral">Party</p>
                    <p class="mt-2 text-lg font-bold">Room Match</p>
                </div>
            </div>
        </div>

        <div class="landing-preview relative">
            <div class="landing-orb landing-orb--yellow absolute -right-4 -top-4 h-24 w-24 rounded-[28px] bg-arcade-yellow"></div>
            <div class="landing-orb landing-orb--cyan absolute -bottom-5 -left-4 h-20 w-20 rounded-full bg-arcade-cyan/70"></div>
            <article class="landing-machine relative rounded-[38px] border-4 border-arcade-ink bg-arcade-panel p-6 shadow-[14px_14px_0_#26190f] md:p-8">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex gap-2">
                        <span class="h-3 w-3 rounded-full bg-arcade-coral"></span>
                        <span class="h-3 w-3 rounded-full bg-arcade-yellow"></span>
                        <span class="h-3 w-3 rounded-full bg-arcade-cyan"></span>
                    </div>
                    <span class="rounded-full border border-arcade-ink/15 bg-white px-3 py-1 text-xs font-bold">Live Build</span>
                </div>
                <div class="rounded-[28px] border-2 border-dashed border-arcade-ink/20 bg-[#f7efe1] p-6">
                    <div class="landing-target mx-auto w-full max-w-sm rounded-[24px] border-4 border-arcade-ink bg-white p-6 text-center shadow-[0_12px_0_#26190f]">
                        <span class="inline-block rounded-full bg-arcade-yellow px-3 py-2 text-xs font-bold">Target Design</span>
                        <h2 class="mt-5 text-4xl font-bold text-arcade-orange">Pixelwar</h2>
                        <p class="mt-3 text-sm leading-7 text-arcade-ink/70">Match the CSS properties to complete the challenge.</p>
                        <div class="mt-5 inline-flex rounded-xl bg-arcade-cyan px-5 py-3 text-sm font-bold">Launch Run</div>
                    </div>
                </div>
                <div class="landing-property property-one rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-xs font-bold">border-radius: 24px;</div>
                <div class="landing-property property-two rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-3 py-2 text-xs font-bold">color: #ff8c42;</div>
            </article>
        </div>
    </section>
</main>

<style>
.landing-page {
    min-height: 100vh;
}

.landing-bg {
    animation: landingGlow 10s ease-in-out infinite alternate;
}

.landing-grid {
    background-image: linear-gradient(rgba(38, 25, 15, 0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(38, 25, 15, 0.06) 1px, transparent 1px);
    background-size: 42px 42px;
    mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.8), transparent 78%);
}

.landing-copy,
.landing-preview {
    animation: landingRise 700ms ease both;
}

.landing-preview {
    animation-delay: 120ms;
}

.landing-kicker,
.landing-stats > div,
.landing-button,
.landing-machine {
    transition: transform 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
}

.landing-button:hover,
.landing-stats > div:hover {
    transform: translateY(-3px);
}

.landing-machine:hover {
    transform: translate(-2px, -2px);
    box-shadow: 18px 18px 0 #26190f;
}

.landing-target {
    animation: landingFloat 3.8s ease-in-out infinite;
}

.landing-orb {
    animation: landingBob 4.5s ease-in-out infinite;
}

.landing-orb--cyan {
    animation-delay: 800ms;
}

.landing-property {
    position: absolute;
    box-shadow: 5px 5px 0 rgba(38, 25, 15, 0.22);
    animation: landingChip 4s ease-in-out infinite;
}

.property-one {
    left: -18px;
    top: 36%;
}

.property-two {
    bottom: 16%;
    right: -12px;
    animation-delay: 650ms;
}

@keyframes landingRise {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes landingGlow {
    from {
        transform: scale(1);
        filter: saturate(1);
    }
    to {
        transform: scale(1.04);
        filter: saturate(1.18);
    }
}

@keyframes landingFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes landingBob {
    0%,
    100% {
        transform: translate(0, 0) rotate(0deg);
    }
    50% {
        transform: translate(6px, -10px) rotate(5deg);
    }
}

@keyframes landingChip {
    0%,
    100% {
        transform: translateY(0) rotate(-2deg);
    }
    50% {
        transform: translateY(-10px) rotate(2deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .landing-bg,
    .landing-copy,
    .landing-preview,
    .landing-target,
    .landing-orb,
    .landing-property {
        animation: none;
    }

    .landing-kicker,
    .landing-stats > div,
    .landing-button,
    .landing-machine {
        transition: none;
    }
}

@media (max-width: 640px) {
    .landing-page .container {
        min-height: auto;
        gap: 2rem;
        padding-bottom: 3rem;
        padding-top: 3rem;
    }

    .landing-copy {
        order: 1;
        position: relative;
        text-align: center;
    }

    .landing-copy::before,
    .landing-copy::after {
        content: "";
        position: absolute;
        z-index: -1;
        border: 3px solid rgba(38, 25, 15, 0.12);
        background: rgba(255, 253, 246, 0.48);
    }

    .landing-copy::before {
        top: -1.4rem;
        right: 0.4rem;
        width: 5.25rem;
        height: 5.25rem;
        border-radius: 1.4rem;
        transform: rotate(10deg);
    }

    .landing-copy::after {
        bottom: -1.2rem;
        left: 0.2rem;
        width: 4.4rem;
        height: 4.4rem;
        border-radius: 999px;
        background: rgba(76, 201, 240, 0.22);
    }

    .landing-preview {
        display: none;
    }

    .landing-copy h1,
    .landing-copy p {
        margin-left: auto;
        margin-right: auto;
    }

    .landing-copy h1 {
        font-size: clamp(2.65rem, 14vw, 3.6rem);
        line-height: 0.95;
    }

    .landing-copy p:not(.landing-kicker) {
        font-size: 1rem;
        line-height: 1.85;
    }

    .landing-stats {
        margin-left: auto;
        margin-right: auto;
        max-width: none;
    }

    .landing-preview {
        margin-inline: auto;
        max-width: 100%;
    }

    .landing-machine {
        border-radius: 28px;
        box-shadow: 8px 8px 0 #26190f;
        padding: 1rem;
    }

    .landing-machine:hover {
        transform: none;
        box-shadow: 8px 8px 0 #26190f;
    }

    .landing-target {
        padding: 1.1rem;
        box-shadow: 0 8px 0 #26190f;
    }

    .landing-target h2 {
        font-size: 2rem;
    }

    .landing-target p {
        line-height: 1.65;
    }

    .landing-orb {
        opacity: 0.55;
        transform: scale(0.75);
    }

    .landing-property {
        display: none;
    }
}
</style>
