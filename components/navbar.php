<?php
$navLinks = [
    ['label' => 'Home', 'href' => './'],
    ['label' => 'Game Test', 'href' => './#game-test'],
    ['label' => 'Guide', 'href' => './development-guide/00_START_HERE.md'],
];
?>
<nav class="relative z-50">
    <div class="container pt-4">
        <div class="flex items-center justify-between rounded-[28px] border-4 border-arcade-ink/10 bg-arcade-panel/90 px-4 py-3 shadow-arcade backdrop-blur md:px-6">
            <a class="font-arcade text-[11px] uppercase tracking-[0.22em] text-arcade-orange no-underline md:text-xs" href="./">
                <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
            </a>

            <button
                class="navbar-toggler rounded-2xl border-2 border-arcade-ink/15 bg-arcade-yellow/70 px-3 py-2 text-arcade-ink md:hidden"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navMenu"
                aria-controls="navMenu"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="flex flex-col gap-1.5" aria-hidden="true">
                    <span class="block h-0.5 w-5 rounded-full bg-arcade-ink"></span>
                    <span class="block h-0.5 w-5 rounded-full bg-arcade-ink"></span>
                    <span class="block h-0.5 w-5 rounded-full bg-arcade-ink"></span>
                </span>
            </button>

            <div class="collapse navbar-collapse md:!block md:!basis-auto" id="navMenu">
                <ul class="mt-4 flex flex-col gap-2 md:mt-0 md:flex-row md:items-center md:gap-2">
                    <?php foreach ($navLinks as $navLink) : ?>
                        <li>
                            <a
                                class="block rounded-2xl px-4 py-2 text-sm font-semibold text-arcade-ink no-underline transition hover:bg-arcade-yellow/60 hover:text-arcade-ink"
                                href="<?= htmlspecialchars($navLink['href'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($navLink['label'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>
