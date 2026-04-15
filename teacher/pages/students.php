<?php
$students = [
    ['name' => 'Pixel Rookie', 'email' => 'rookie@example.test', 'rank' => 'Beginner', 'solved' => 34, 'status' => 'Active'],
    ['name' => 'CSSRunner', 'email' => 'runner@example.test', 'rank' => 'Apprentice', 'solved' => 42, 'status' => 'Active'],
    ['name' => 'TinyCascade', 'email' => 'cascade@example.test', 'rank' => 'Beginner', 'solved' => 29, 'status' => 'Needs Review'],
    ['name' => 'BoxShadowFan', 'email' => 'shadow@example.test', 'rank' => 'Skilled', 'solved' => 58, 'status' => 'Active'],
];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative">
        <div class="teacher-page-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Students</p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">Class Progress</h1>
                    <p class="mt-2 max-w-2xl text-sm font-bold leading-7 text-arcade-ink/62">Review student activity, ranks, and challenge progress from one teacher view.</p>
                </div>
                <form class="teacher-search" role="search">
                    <input type="search" name="q" placeholder="Search student" aria-label="Search student">
                    <button type="submit">Search</button>
                </form>
            </div>

            <div class="mt-5 grid gap-3">
                <?php foreach ($students as $student) : ?>
                    <article class="teacher-student-card rounded-2xl border-2 border-arcade-ink/14 bg-white p-3">
                        <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-center">
                            <div class="flex items-start gap-3">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl border-2 border-arcade-ink bg-arcade-cyan font-arcade text-[10px]"><?= htmlspecialchars(substr($student['name'], 0, 2), ENT_QUOTES, 'UTF-8') ?></span>
                                <div>
                                    <h2 class="text-lg font-black"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="text-sm font-bold text-arcade-ink/56"><?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="teacher-pill bg-arcade-yellow">Rank: <?= htmlspecialchars($student['rank'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="teacher-pill bg-arcade-mint"><?= (int) $student['solved'] ?> solved</span>
                                <span class="teacher-pill bg-white"><?= htmlspecialchars($student['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                <a href="../?c=player-analytics" class="teacher-small-button">Analytics</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
