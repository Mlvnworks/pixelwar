<?php
$seasonId = (int) ($_GET['id'] ?? 0);
$season = $seasonRepository instanceof SeasonRepository ? $seasonRepository->findById($seasonId) : null;
$leaderboardRows = $userRepository instanceof UserRepository && $season !== null
    ? $userRepository->listLeaderboardPlayersForSeason($seasonId, 100)
    : [];
$topPlayers = array_slice($leaderboardRows, 0, 3);
$restPlayers = array_slice($leaderboardRows, 3);
$podiumOrder = [1, 0, 2];
$seasonName = (string) ($season['name'] ?? 'Season');
$seasonStatus = (string) ($season['season_status'] ?? '');

$leaderboardInitials = static function (string $username): string {
    return strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $username) ?: 'PW', 0, 2));
};

$formatSeasonDate = static function (?string $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return 'Not set';
    }

    try {
        return (new DateTimeImmutable($raw))->format('M j, Y g:i A');
    } catch (Throwable) {
        return $raw;
    }
};
?>

<main class="leaderboards-page teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="leaderboards-page__grid absolute inset-0"></div>
    <div class="leaderboards-glow leaderboards-glow--one"></div>
    <div class="leaderboards-glow leaderboards-glow--two"></div>

    <section class="container relative">
        <a href="./?c=season-management" class="leaderboards-back-button inline-flex items-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
            <span aria-hidden="true">&larr;</span>
            Seasons
        </a>

        <?php if ($season === null) : ?>
            <section class="leaderboards-empty mt-6 rounded-[28px] border-4 border-dashed border-arcade-ink/25 bg-white/80 p-8 text-center shadow-[7px_7px_0_rgba(38,25,15,0.16)]">
                <h1 class="text-2xl font-black">Season not found</h1>
                <p class="mt-2 text-sm font-bold leading-6 text-arcade-ink/62">Choose a valid season to view its leaderboard.</p>
            </section>
        <?php else : ?>
            <section class="leaderboards-hero mt-5 overflow-hidden rounded-[30px] border-4 border-arcade-ink bg-arcade-panel shadow-[10px_10px_0_#26190f]">
                <div class="leaderboards-hero__inner relative p-5 md:p-7">
                    <div class="leaderboards-hero__spark leaderboards-hero__spark--one"></div>
                    <div class="leaderboards-hero__spark leaderboards-hero__spark--two"></div>
                    <div class="relative grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(16rem,0.36fr)] lg:items-end">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-cyan">Season Leaderboard</p>
                            <h1 class="mt-3 text-4xl font-black leading-tight md:text-6xl"><?= htmlspecialchars($seasonName, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="mt-3 max-w-2xl text-sm font-bold leading-7 text-arcade-ink/68 md:text-base">
                                Rankings are based on player progress points earned during this season.
                            </p>
                        </div>
                        <article class="leaderboards-rank-card rounded-[24px] border-4 border-arcade-ink bg-white/85 p-4 shadow-[7px_7px_0_rgba(38,25,15,0.24)]">
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-arcade-orange">Season Status</p>
                            <strong class="mt-2 block text-4xl font-black capitalize"><?= htmlspecialchars($seasonStatus, ENT_QUOTES, 'UTF-8') ?></strong>
                            <p class="mt-1 text-sm font-bold text-arcade-ink/62"><?= count($leaderboardRows) ?> ranked player<?= count($leaderboardRows) === 1 ? '' : 's' ?></p>
                        </article>
                    </div>
                    <div class="relative mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border-2 border-arcade-ink/10 bg-white/60 px-4 py-3">
                            <p class="text-xs font-black uppercase tracking-[0.1em] text-arcade-ink/45">Starts</p>
                            <p class="mt-1 text-sm font-bold"><?= htmlspecialchars($formatSeasonDate((string) ($season['start_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-2xl border-2 border-arcade-ink/10 bg-white/60 px-4 py-3">
                            <p class="text-xs font-black uppercase tracking-[0.1em] text-arcade-ink/45">Ends</p>
                            <p class="mt-1 text-sm font-bold"><?= htmlspecialchars($formatSeasonDate((string) ($season['end_date'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <?php if ($leaderboardRows === []) : ?>
                <section class="leaderboards-empty mt-6 rounded-[28px] border-4 border-dashed border-arcade-ink/25 bg-white/80 p-8 text-center shadow-[7px_7px_0_rgba(38,25,15,0.16)]">
                    <h2 class="text-2xl font-black">No ranked players yet</h2>
                    <p class="mt-2 text-sm font-bold leading-6 text-arcade-ink/62">This season has no progress points recorded yet.</p>
                </section>
            <?php else : ?>
                <section class="leaderboards-podium mt-7" aria-label="Top three season leaderboard players">
                    <?php foreach ($podiumOrder as $podiumPositionIndex) : ?>
                        <?php if (!isset($topPlayers[$podiumPositionIndex])) {
                            continue;
                        } ?>
                        <?php
                        $player = $topPlayers[$podiumPositionIndex];
                        $rankNumber = $podiumPositionIndex + 1;
                        $username = (string) ($player['username'] ?? 'player');
                        $avatarUrl = trim((string) ($player['avatar_url'] ?? ''));
                        ?>
                        <article class="leaderboards-podium-card leaderboards-podium-card--<?= (int) $rankNumber ?>" style="--podium-delay: <?= (int) $podiumPositionIndex * 90 ?>ms;">
                            <span class="leaderboards-podium-card__rank">#<?= (int) $rankNumber ?></span>
                            <span class="leaderboards-avatar leaderboards-avatar--large">
                                <?php if ($avatarUrl !== '') : ?>
                                    <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                <?php else : ?>
                                    <?= htmlspecialchars($leaderboardInitials($username), ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </span>
                            <h2><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= (int) ($player['points'] ?? 0) ?> pts</p>
                            <div class="leaderboards-podium-card__base"></div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="leaderboards-list mt-7 rounded-[28px] border-4 border-arcade-ink bg-white/90 p-4 shadow-[8px_8px_0_#26190f] md:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Ranked List</p>
                            <h2 class="mt-2 text-2xl font-black">All contenders</h2>
                        </div>
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-arcade-ink/50">Top 100 players</p>
                    </div>

                    <div class="mt-4 grid gap-3">
                        <?php foreach ($restPlayers as $restIndex => $player) : ?>
                            <?php
                            $rankNumber = $restIndex + 4;
                            $username = (string) ($player['username'] ?? 'player');
                            $avatarUrl = trim((string) ($player['avatar_url'] ?? ''));
                            ?>
                            <article class="leaderboards-list-row" style="--row-delay: <?= (int) min($restIndex, 18) * 35 ?>ms;">
                                <span class="leaderboards-list-row__rank">#<?= (int) $rankNumber ?></span>
                                <span class="leaderboards-avatar">
                                    <?php if ($avatarUrl !== '') : ?>
                                        <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                    <?php else : ?>
                                        <?= htmlspecialchars($leaderboardInitials($username), ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </span>
                                <h3><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h3>
                                <span class="leaderboards-list-row__points"><?= (int) ($player['points'] ?? 0) ?> pts</span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>
