<footer class="relative border-t-4 border-arcade-ink/10 bg-arcade-panel/90 py-8">
    <div class="container">
        <div class="flex flex-col items-center justify-center gap-4 text-center md:flex-row md:items-center md:justify-between md:text-left">
            <p class="text-sm font-bold text-arcade-ink/60">
                &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <a href="#" class="inline-flex self-center rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-4 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white md:self-auto">
                Back to Top
            </a>
        </div>
    </div>
</footer>
