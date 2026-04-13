<footer class="relative border-t-4 border-arcade-ink/10 bg-arcade-panel/90 py-10">
    <div class="container">
        <div class="flex flex-col gap-4 text-center md:flex-row md:items-center md:justify-between md:text-left">
            <div>
                <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">
                    <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="mt-3 text-sm text-arcade-ink/70">
                    Gamified CSS game for students.
                </p>
            </div>
            <p class="text-sm text-arcade-ink/60">
                &copy; <?= date('Y') ?> Build, play, and learn through tiny browser experiments.
            </p>
        </div>
    </div>
</footer>
