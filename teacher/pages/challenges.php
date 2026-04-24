<?php
$challengeSearch = trim((string) ($_GET['search'] ?? ''));
$challengeDifficulty = strtolower(trim((string) ($_GET['difficulty'] ?? '')));
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$createdChallenges = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->searchCreatedChallenges($challengeSearch, $challengeDifficulty, $teacherId, 60)
    : [];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative">
        <div class="teacher-page-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Challenges</p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">Teacher Challenge Library</h1>
                    <p class="mt-2 max-w-2xl text-sm font-bold leading-7 text-arcade-ink/62">Search, filter, and review your teacher-created Pixelwar challenges.</p>
                </div>
                <a href="./?c=create-challenge" class="teacher-button teacher-button--primary">New Challenge</a>
            </div>

            <section class="mt-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Created</p>
                        <h2 class="mt-2 text-2xl font-black">Custom Challenges</h2>
                    </div>
                    <span class="teacher-pill bg-arcade-yellow"><?= count($createdChallenges) ?> shown</span>
                </div>

                <form class="teacher-filterbar mt-4" method="get" action="./">
                    <input type="hidden" name="c" value="challenges">
                    <label>
                        <span>Search</span>
                        <input type="search" name="search" value="<?= htmlspecialchars($challengeSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by name, instruction, or author">
                    </label>
                    <label>
                        <span>Difficulty</span>
                        <select name="difficulty">
                            <option value="" <?= $challengeDifficulty === '' ? 'selected' : '' ?>>All difficulties</option>
                            <option value="easy" <?= $challengeDifficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                            <option value="medium" <?= $challengeDifficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="hard" <?= $challengeDifficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                        </select>
                    </label>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Filter</span>
                    </button>
                    <a href="./?c=challenges" class="teacher-button teacher-button--light">Reset</a>
                </form>

                <div class="teacher-created-grid teacher-created-grid--compact mt-4">
                    <?php if ($createdChallenges === []) : ?>
                        <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-5">
                            <p class="text-sm font-black text-arcade-ink/58">No custom challenges match the current filters.</p>
                            <a href="./?c=create-challenge" class="teacher-link-button mt-3">Create Challenge</a>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($createdChallenges as $challenge) : ?>
                        <?php
                        $difficulty = strtolower((string) $challenge['difficulty_name']);
                        $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', $difficulty);
                        ?>
                        <article class="teacher-created-challenge teacher-created-challenge--library rounded-[18px] border-2 border-arcade-ink/12 bg-white p-4 transition hover:-translate-y-1 hover:border-arcade-orange hover:shadow-[0_6px_0_rgba(38,25,15,0.18)]">
                            <div class="flex h-full flex-col gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="teacher-pill bg-arcade-yellow"><?= (int) $challenge['points'] ?> points</span>
                                    </div>
                                    <h3 class="mt-3 text-xl font-black"><?= htmlspecialchars((string) $challenge['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="teacher-card-description mt-2 text-sm font-bold leading-6 text-arcade-ink/60"><?= $tools->formatExcerpt((string) $challenge['instruction']) ?></p>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-black text-arcade-ink/52">
                                        <span>By <?= htmlspecialchars((string) $challenge['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span><?= htmlspecialchars(date('M j, Y', strtotime((string) $challenge['date_created'])), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                                <a href="./?c=challenge-view&id=<?= (int) $challenge['challenge_id'] ?>" class="teacher-small-button mt-auto">Open</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</main>
