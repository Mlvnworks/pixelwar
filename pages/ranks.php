<?php
$rankRows = $rankRepository instanceof RankRepository ? $rankRepository->listAll() : [];
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
$currentPoints = $userRepository instanceof UserRepository
    ? $userRepository->totalPlayerProgressPointsForUser($currentUserId)
    : 0;
$rankProgress = $rankRepository instanceof RankRepository
    ? $rankRepository->progressForPoints($currentPoints)
    : [
        'current_name' => 'Beginner',
        'next_name' => 'Next Rank',
        'display_requirement' => 500,
        'progress_percent' => min(100, (int) round(($currentPoints / 500) * 100)),
        'is_max_rank' => false,
    ];
$currentRankName = (string) ($rankProgress['current_name'] ?? 'Beginner');
$nextRankName = (string) ($rankProgress['next_name'] ?? '');
$rankProgressPercent = (int) ($rankProgress['progress_percent'] ?? 0);
$displayRequirement = (int) ($rankProgress['display_requirement'] ?? 500);
$isMaxRank = (bool) ($rankProgress['is_max_rank'] ?? false);
$highestRequirement = 0;

foreach ($rankRows as $rankRow) {
    $highestRequirement = max($highestRequirement, (int) ($rankRow['points_requirements'] ?? 0));
}
?>

<main class="ranks-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="ranks-page__glow ranks-page__glow--left"></div>
    <div class="ranks-page__glow ranks-page__glow--right"></div>
    <div class="ranks-page__grid absolute inset-0"></div>

    <section class="container relative">
        <a href="./?c=home" class="ranks-back-button inline-flex items-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
            <span aria-hidden="true">&larr;</span>
            Back Home
        </a>

        <section class="ranks-hero mt-5 overflow-hidden rounded-[30px] border-4 border-arcade-ink bg-arcade-panel shadow-[10px_10px_0_#26190f]">
            <div class="ranks-hero__top relative p-5 md:p-7">
                <div class="ranks-hero__coin ranks-hero__coin--one"></div>
                <div class="ranks-hero__coin ranks-hero__coin--two"></div>
                <div class="relative grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.45fr)] lg:items-end">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-cyan">Rank Ladder</p>
                        <h1 class="mt-3 text-4xl font-black leading-tight md:text-6xl">Climb the Pixelwar ranks.</h1>
                        <p class="mt-3 max-w-2xl text-sm font-bold leading-7 text-arcade-ink/68 md:text-base">
                            Every point from your challenge wins feeds your player progress. Hit the minimum requirement to unlock the next rank.
                        </p>
                    </div>
                    <div class="ranks-current-card rounded-[24px] border-4 border-arcade-ink bg-white/85 p-4 shadow-[7px_7px_0_rgba(38,25,15,0.24)]">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-arcade-orange">Your Rank</p>
                        <h2 class="mt-2 text-3xl font-black"><?= htmlspecialchars($currentRankName, ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="mt-1 text-sm font-bold text-arcade-ink/62"><?= (int) $currentPoints ?><?= $isMaxRank ? ' points earned' : ' / ' . (int) $displayRequirement . ' points' ?></p>
                        <div class="mt-3 h-4 overflow-hidden rounded-full border-2 border-arcade-ink bg-arcade-cream">
                            <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $rankProgressPercent ?>%;"></span>
                        </div>
                        <?php if (!$isMaxRank && $nextRankName !== '') : ?>
                            <p class="mt-2 text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/55">Next: <?= htmlspecialchars($nextRankName, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php else : ?>
                            <p class="mt-2 text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/55">Top rank reached</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4">
            <?php if ($rankRows === []) : ?>
                <article class="ranks-empty rounded-[28px] border-4 border-dashed border-arcade-ink/25 bg-white/80 p-8 text-center shadow-[7px_7px_0_rgba(38,25,15,0.16)]">
                    <h2 class="text-2xl font-black">No ranks configured yet</h2>
                    <p class="mt-2 text-sm font-bold leading-6 text-arcade-ink/62">Rank requirements will appear here after an admin creates them.</p>
                </article>
            <?php else : ?>
                <div class="ranks-ladder grid gap-4">
                    <?php foreach ($rankRows as $rankIndex => $rankRow) : ?>
                        <?php
                        $rankName = (string) ($rankRow['name'] ?? 'Rank');
                        $rankRequirement = (int) ($rankRow['points_requirements'] ?? 0);
                        $rankInitial = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $rankName) ?: 'R', 0, 1));
                        $isCurrentRank = strcasecmp($rankName, $currentRankName) === 0;
                        $isUnlocked = $currentPoints >= $rankRequirement;
                        $ladderPercent = $highestRequirement > 0 ? min(100, (int) round(($rankRequirement / $highestRequirement) * 100)) : 0;
                        ?>
                        <article class="ranks-ladder-card <?= $isCurrentRank ? 'ranks-ladder-card--current' : '' ?> <?= $isUnlocked ? 'ranks-ladder-card--unlocked' : 'ranks-ladder-card--locked' ?>" style="--rank-delay: <?= (int) $rankIndex * 70 ?>ms;">
                            <div class="ranks-ladder-card__marker">
                                <span><?= htmlspecialchars($rankInitial, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-arcade-orange">Level <?= (int) ($rankIndex + 1) ?></p>
                                        <h2 class="mt-1 break-words text-2xl font-black text-arcade-ink"><?= htmlspecialchars($rankName, ENT_QUOTES, 'UTF-8') ?></h2>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <?php if ($isCurrentRank) : ?>
                                            <span class="ranks-pill ranks-pill--current">Current</span>
                                        <?php elseif ($isUnlocked) : ?>
                                            <span class="ranks-pill ranks-pill--unlocked">Unlocked</span>
                                        <?php else : ?>
                                            <span class="ranks-pill ranks-pill--locked">Locked</span>
                                        <?php endif; ?>
                                        <span class="ranks-pill"><?= (int) $rankRequirement ?> pts minimum</span>
                                    </div>
                                </div>
                                <div class="mt-4 h-3 overflow-hidden rounded-full border-2 border-arcade-ink bg-white">
                                    <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $ladderPercent ?>%;"></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>
