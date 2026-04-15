<?php
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
$teacherChallenges = ChallengeCatalog::all();
$recentStudents = [
    ['name' => 'Pixel Rookie', 'status' => 'Completed Button Border Basics', 'points' => 340],
    ['name' => 'CSSRunner', 'status' => 'Started Card Shadow Match', 'points' => 420],
    ['name' => 'TinyCascade', 'status' => 'Commented on Hero Text Alignment', 'points' => 290],
];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Teacher Dashboard</p>
            <div class="mt-3 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <h1 class="text-3xl font-black leading-tight md:text-5xl">Welcome <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?>,</h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Manage classroom activity, review challenge progress, and keep students moving through focused CSS design-matching drills.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="./?c=challenges" class="teacher-button teacher-button--primary">Create Challenge</a>
                    <a href="./?c=students" class="teacher-button teacher-button--light">View Students</a>
                </div>
            </div>
        </article>

        <div class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Challenge Queue</p>
                        <h2 class="mt-2 text-2xl font-black">Active Challenge Templates</h2>
                    </div>
                    <a href="./?c=challenges" class="teacher-link-button">Manage All</a>
                </div>
                <div class="mt-4 grid gap-3">
                    <?php foreach (array_slice($teacherChallenges, 0, 3) as $challenge) : ?>
                        <article class="teacher-list-card rounded-2xl border-2 border-arcade-ink/12 bg-white/82 p-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <span class="challenge-difficulty <?= htmlspecialchars((string) $challenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-[0.14em]"><?= htmlspecialchars((string) $challenge['level'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <h3 class="mt-2 text-lg font-black"><?= htmlspecialchars((string) $challenge['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="mt-1 text-sm font-bold text-arcade-ink/58">Focus: <?= htmlspecialchars((string) $challenge['focus'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <a href="../?c=challenge&slug=<?= urlencode((string) $challenge['slug']) ?>" class="teacher-small-button">Preview</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-white p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Recent Activity</p>
                <h2 class="mt-2 text-2xl font-black">Student Signals</h2>
                <div class="mt-4 grid gap-3">
                    <?php foreach ($recentStudents as $student) : ?>
                        <article class="teacher-activity-card rounded-2xl border-2 border-arcade-ink/12 bg-arcade-cream/72 p-3">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-arcade text-[10px] text-arcade-ink"><?= htmlspecialchars(substr($student['name'], 0, 2), ENT_QUOTES, 'UTF-8') ?></span>
                                <div>
                                    <p class="font-black"><?= htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-1 text-sm font-bold leading-6 text-arcade-ink/58"><?= htmlspecialchars($student['status'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-1 text-xs font-black uppercase tracking-[0.14em] text-arcade-orange"><?= (int) $student['points'] ?> points</p>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</main>
