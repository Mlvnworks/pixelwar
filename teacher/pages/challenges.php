<?php
$teacherChallenges = ChallengeCatalog::all();
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative">
        <div class="teacher-page-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Challenges</p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">Teacher Challenge Library</h1>
                    <p class="mt-2 max-w-2xl text-sm font-bold leading-7 text-arcade-ink/62">Use existing Pixelwar challenges as classroom material while the custom challenge builder is prepared.</p>
                </div>
                <button type="button" class="teacher-button teacher-button--primary">New Challenge</button>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-3">
                <?php foreach ($teacherChallenges as $challenge) : ?>
                    <article class="teacher-challenge-card rounded-[22px] border-2 border-arcade-ink/14 bg-white p-4">
                        <span class="challenge-difficulty <?= htmlspecialchars((string) $challenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-[0.14em]"><?= htmlspecialchars((string) $challenge['level'], ENT_QUOTES, 'UTF-8') ?></span>
                        <h2 class="mt-3 text-xl font-black"><?= htmlspecialchars((string) $challenge['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="mt-2 text-sm font-bold leading-6 text-arcade-ink/60"><?= htmlspecialchars((string) $challenge['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="teacher-pill bg-arcade-yellow"><?= htmlspecialchars((string) $challenge['estimate'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="teacher-pill bg-arcade-cyan"><?= htmlspecialchars((string) $challenge['reward'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="../?c=challenge&slug=<?= urlencode((string) $challenge['slug']) ?>" class="teacher-small-button">Preview</a>
                            <a href="../?c=pixelwar&slug=<?= urlencode((string) $challenge['slug']) ?>" class="teacher-small-button teacher-small-button--light">Play Test</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
