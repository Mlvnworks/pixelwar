<?php
$teacherActivityId = max(0, (int) ($_GET['id'] ?? 0));
$teacherActivityProfile = $userRepository instanceof UserRepository
    ? $userRepository->findSessionUser($teacherActivityId)
    : null;
$teacherActivityPage = max(1, (int) ($_GET['page'] ?? 1));
$teacherActivityPerPage = 10;
$teacherChallenges = $challengeRepository instanceof ChallengeRepository && $teacherActivityId > 0
    ? $challengeRepository->searchCreatedChallenges('', '', $teacherActivityId, 100)
    : [];
$teacherChallengeTotal = count($teacherChallenges);
$teacherChallengePages = max(1, (int) ceil($teacherChallengeTotal / $teacherActivityPerPage));
$teacherActivityPage = min($teacherActivityPage, $teacherChallengePages);
$teacherChallengeOffset = ($teacherActivityPage - 1) * $teacherActivityPerPage;
$teacherChallengeRows = array_slice($teacherChallenges, $teacherChallengeOffset, $teacherActivityPerPage);

$teacherActivityName = trim((string) ($teacherActivityProfile['firstname'] ?? '') . ' ' . (string) ($teacherActivityProfile['lastname'] ?? ''));
$teacherActivityName = $teacherActivityName !== '' ? $teacherActivityName : trim((string) ($teacherActivityProfile['username'] ?? 'Teacher'));

$teacherActivityBuildQuery = static function (array $overrides = []) use ($teacherActivityId, $teacherActivityPage): string {
    $query = [
        'c' => 'teacher-activity',
        'id' => $teacherActivityId,
        'page' => $teacherActivityPage,
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }

        $query[$key] = $value;
    }

    return './?' . http_build_query($query);
};
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <div class="flex flex-wrap gap-2">
            <a href="./?c=teachers" class="teacher-button teacher-button--light gap-2 no-underline">
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                <span>Back to Teachers</span>
            </a>
            <?php if ($teacherActivityId > 0) : ?>
                <a href="./?c=teacher-view&id=<?= (int) $teacherActivityId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                    <i data-lucide="user-round-search" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Back to Teacher</span>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($teacherActivityProfile === null || (int) ($teacherActivityProfile['role_id'] ?? 0) !== 2) : ?>
            <section class="teacher-panel p-6">
                <h1 class="text-2xl font-bold">Teacher not found</h1>
                <p class="mt-2 text-sm font-medium leading-7 text-arcade-ink/62">The requested teacher account is unavailable or has been removed.</p>
            </section>
        <?php else : ?>
            <section class="grid gap-5">
                <section class="teacher-panel p-5 md:p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Teacher Activity</p>
                            <h1 class="mt-1 text-3xl font-bold leading-tight md:text-4xl"><?= htmlspecialchars($teacherActivityName, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/65 md:text-base">
                                Review created challenges and room records for this teacher.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="teacher-panel p-5 md:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Challenges</p>
                            <h2 class="mt-1 text-2xl font-bold">Created challenges</h2>
                        </div>
                        <span class="teacher-pill bg-arcade-peach/60"><?= (int) $teacherChallengeTotal ?> total</span>
                    </div>

                    <?php if ($teacherChallengeRows === []) : ?>
                        <div class="mt-5 rounded-2xl border border-dashed border-arcade-ink/14 bg-white/80 px-4 py-5 text-sm font-medium text-arcade-ink/55">
                            No created challenges found for this teacher.
                        </div>
                    <?php else : ?>
                        <div class="mt-5 grid gap-3 lg:grid-cols-2">
                            <?php foreach ($teacherChallengeRows as $challenge) : ?>
                                <?php
                                $difficulty = strtolower((string) ($challenge['difficulty_name'] ?? 'easy'));
                                $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', $difficulty);
                                $createdAt = (string) ($challenge['date_created'] ?? '');
                                ?>
                                <article class="rounded-2xl border border-arcade-ink/10 bg-white/80 p-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= (int) ($challenge['points'] ?? 0) ?> points</span>
                                    </div>
                                    <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars((string) ($challenge['name'] ?? 'Untitled Challenge'), ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="mt-2 text-sm leading-6 text-arcade-ink/68"><?= $tools->formatExcerpt((string) ($challenge['instruction'] ?? '')) ?></p>
                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs font-semibold text-arcade-ink/55">
                                        <span>Created <?= htmlspecialchars($createdAt !== '' ? date('M j, Y', strtotime($createdAt)) : 'Recently', ENT_QUOTES, 'UTF-8') ?></span>
                                        <a href="./?c=challenge-view&id=<?= (int) ($challenge['challenge_id'] ?? 0) ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                                            <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                            <span>Open</span>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 border-t border-arcade-ink/10 pt-4 md:flex-row md:items-center md:justify-between">
                            <p class="text-sm font-medium text-arcade-ink/55">
                                Page <?= (int) $teacherActivityPage ?> of <?= (int) $teacherChallengePages ?> · <?= (int) $teacherChallengeTotal ?> challenge<?= $teacherChallengeTotal === 1 ? '' : 's' ?>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars($teacherActivityBuildQuery(['page' => max(1, $teacherActivityPage - 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teacherActivityPage <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                                    <i data-lucide="chevron-left" class="h-4 w-4" aria-hidden="true"></i>
                                    <span>Previous</span>
                                </a>
                                <a href="<?= htmlspecialchars($teacherActivityBuildQuery(['page' => min($teacherChallengePages, $teacherActivityPage + 1)]), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--light gap-2 <?= $teacherActivityPage >= $teacherChallengePages ? 'pointer-events-none opacity-50' : '' ?>">
                                    <span>Next</span>
                                    <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="teacher-panel p-5 md:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Rooms</p>
                            <h2 class="mt-1 text-2xl font-bold">Created rooms</h2>
                        </div>
                        <span class="teacher-pill bg-white">No room data source</span>
                    </div>
                    <div class="mt-5 rounded-2xl border border-dashed border-arcade-ink/14 bg-white/80 px-4 py-5 text-sm font-medium leading-7 text-arcade-ink/55">
                        Room creation is not persisted in the current codebase yet. The teacher rooms page still uses sample data, so this admin view only shows real created challenges for now.
                    </div>
                </section>
            </section>
        <?php endif; ?>
    </section>
</main>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();
});
</script>
