<?php
$matchingCurrentUser = trim((string) ($_SESSION['firstname'] ?? '') . ' ' . (string) ($_SESSION['lastname'] ?? ''));
$matchingCurrentUser = $matchingCurrentUser !== '' ? $matchingCurrentUser : (string) ($_SESSION['username'] ?? 'Player One');
$matchingCurrentUsername = trim((string) ($_SESSION['username'] ?? 'player_one'));
$matchingCurrentAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$matchingOpponent = trim((string) ($_GET['opponent'] ?? 'Arcade Rival'));
$matchingOpponent = $matchingOpponent !== '' ? $matchingOpponent : 'Arcade Rival';
$matchingOpponentUsername = trim((string) ($_GET['opponent_username'] ?? 'arcade_rival'));
$matchingOpponentAvatarUrl = trim((string) ($_GET['opponent_avatar_url'] ?? ''));
$matchingUserInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $matchingCurrentUser) ?: 'P1', 0, 2));
$matchingOpponentInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $matchingOpponent) ?: 'P2', 0, 2));
$matchingPvpId = max(0, (int) ($_GET['pvp_id'] ?? 0));
$matchingChallengeId = max(0, (int) ($_GET['challenge_id'] ?? 0));
$matchingGameStartAtMs = max(0, (int) ($_GET['game_start_at_ms'] ?? 0));
$matchingChallenges = $challengeRepository instanceof ChallengeRepository
    ? array_map(
        static fn (array $challenge): array => [
            'challenge_id' => (int) ($challenge['challenge_id'] ?? 0),
            'name' => (string) ($challenge['name'] ?? ''),
        ],
        array_filter(
            $challengeRepository->listLatestPublicCreated(18),
            static fn (array $challenge): bool => trim((string) ($challenge['name'] ?? '')) !== '' && (int) ($challenge['challenge_id'] ?? 0) > 0
        )
    )
    : [];

if ($matchingChallengeId > 0 && $challengeRepository instanceof ChallengeRepository) {
    $matchingHasSelectedChallenge = false;
    foreach ($matchingChallenges as $matchingChallenge) {
        if ((int) ($matchingChallenge['challenge_id'] ?? 0) === $matchingChallengeId) {
            $matchingHasSelectedChallenge = true;
            break;
        }
    }

    if (!$matchingHasSelectedChallenge) {
        $matchingSelectedChallenge = $challengeRepository->findPublicCreatedChallenge($matchingChallengeId);
        if ($matchingSelectedChallenge) {
            $matchingChallenges[] = [
                'challenge_id' => (int) ($matchingSelectedChallenge['challenge_id'] ?? 0),
                'name' => (string) ($matchingSelectedChallenge['name'] ?? ''),
            ];
        }
    }
}

if ($matchingChallenges === []) {
    $matchingChallenges = [
        ['challenge_id' => 0, 'name' => 'Flex Landing Hero'],
        ['challenge_id' => 0, 'name' => 'Product Card Showdown'],
        ['challenge_id' => 0, 'name' => 'Leaderboard Tiles'],
        ['challenge_id' => 0, 'name' => 'Neon Pricing Board'],
        ['challenge_id' => 0, 'name' => 'Arcade Stats Ribbon'],
        ['challenge_id' => 0, 'name' => 'Battle Room Header'],
        ['challenge_id' => 0, 'name' => 'Profile Duel Panel'],
        ['challenge_id' => 0, 'name' => 'Split Banner Match'],
    ];
}
$matchingSelectedChallengeIndex = 0;
foreach ($matchingChallenges as $matchingChallengeIndex => $matchingChallenge) {
    if ((int) ($matchingChallenge['challenge_id'] ?? 0) === $matchingChallengeId && $matchingChallengeId > 0) {
        $matchingSelectedChallengeIndex = $matchingChallengeIndex;
        break;
    }
}
$matchingSelectedChallengeName = (string) ($matchingChallenges[$matchingSelectedChallengeIndex]['name'] ?? 'Challenge Locked');
$matchingSelectedChallengeId = (int) ($matchingChallenges[$matchingSelectedChallengeIndex]['challenge_id'] ?? 0);
$matchingReelLoopCount = 20;
$matchingTargetLoopIndex = 14;
$matchingRankForPoints = static function (int $points) use ($rankRepository): string {
    if ($rankRepository instanceof RankRepository) {
        $rankProgress = $rankRepository->progressForPoints($points);

        return (string) ($rankProgress['current_name'] ?? 'Beginner');
    }

    return 'Beginner';
};
$matchingCurrentRank = $matchingRankForPoints($userRepository instanceof UserRepository
    ? $userRepository->totalPlayerProgressPointsForUser((int) ($_SESSION['user_id'] ?? 0))
    : 0);
$matchingOpponentRank = trim((string) ($_GET['opponent_rank'] ?? 'Arcade Rival'));
$matchingOpponentRank = $matchingOpponentRank !== '' ? $matchingOpponentRank : 'Arcade Rival';
?>

<main class="matching-page relative overflow-hidden px-4 py-8 text-arcade-ink md:py-10">
    <div class="matching-page__noise absolute inset-0"></div>
    <div class="matching-page__glow matching-page__glow--left absolute"></div>
    <div class="matching-page__glow matching-page__glow--right absolute"></div>

    <section class="container relative">
        <section class="matching-board">
            <article class="matching-player matching-player--self">
                <div class="matching-player__signal">
                    <span class="matching-player__signal-dot"></span>
                    Ready
                </div>
                <div class="matching-player__avatar matching-player__avatar--self">
                    <?php if ($matchingCurrentAvatarUrl !== '') : ?>
                        <img src="<?= htmlspecialchars($matchingCurrentAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                    <?php else : ?>
                        <span><?= htmlspecialchars($matchingUserInitials, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <p class="matching-player__label">Player 1</p>
                <h2 class="matching-player__name"><?= htmlspecialchars($matchingCurrentUsername, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="matching-player__rank"><?= htmlspecialchars($matchingCurrentRank, ENT_QUOTES, 'UTF-8') ?></p>
            </article>

            <div class="matching-center">
                <div class="matching-center__versus-wrap">
                    <div class="matching-center__pulse matching-center__pulse--one"></div>
                    <div class="matching-center__pulse matching-center__pulse--two"></div>
                    <div class="matching-center__versus">VS</div>
                </div>

                <div class="matching-reel-shell">
                    <p class="matching-reel-shell__label" id="matching-reel-label">Selecting challenge...</p>
                    <div class="matching-reel-window">
                        <div class="matching-reel" id="matching-reel">
                            <?php for ($loop = 0; $loop < $matchingReelLoopCount; $loop++) : ?>
                                <?php foreach ($matchingChallenges as $challengeIndex => $challengeData) : ?>
                                    <div class="matching-reel__item" data-base-index="<?= (int) $challengeIndex ?>" data-loop-index="<?= (int) $loop ?>">
                                        <span class="matching-reel__spark"></span>
                                        <?= htmlspecialchars((string) ($challengeData['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <article class="matching-player matching-player--opponent">
                <div class="matching-player__signal">
                    <span class="matching-player__signal-dot"></span>
                    Ready
                </div>
                <div class="matching-player__avatar matching-player__avatar--opponent">
                    <?php if ($matchingOpponentAvatarUrl !== '') : ?>
                        <img src="<?= htmlspecialchars($matchingOpponentAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                    <?php else : ?>
                        <span><?= htmlspecialchars($matchingOpponentInitials, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <p class="matching-player__label">Player 2</p>
                <h2 class="matching-player__name"><?= htmlspecialchars($matchingOpponentUsername, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="matching-player__rank"><?= htmlspecialchars($matchingOpponentRank, ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        </section>
    </section>
</main>

<script>
(() => {
    const reel = document.getElementById('matching-reel');
    const reelWindow = document.querySelector('.matching-reel-window');
    const reelLabel = document.getElementById('matching-reel-label');
    const minimumRollDuration = 5200;
    const selectedBaseIndex = <?= (int) $matchingSelectedChallengeIndex ?>;
    const targetLoopIndex = <?= (int) $matchingTargetLoopIndex ?>;
    const scrollSpeed = 430;
    const gameStartAtMs = <?= (int) $matchingGameStartAtMs ?>;
    const redirectUrl = <?= json_encode($matchingSelectedChallengeId > 0 ? './?c=pixelwar&challenge_id=' . $matchingSelectedChallengeId . ($matchingPvpId > 0 ? '&pvp_id=' . $matchingPvpId : '') : './?c=home', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    if (!reel || !reelWindow) {
        return;
    }

    const items = Array.from(reel.querySelectorAll('.matching-reel__item'));
    if (items.length < 2) {
        return;
    }

    const windowCenter = reelWindow.clientHeight / 2;
    const targetItem = items.find((item) =>
        Number(item.getAttribute('data-base-index') || -1) === selectedBaseIndex
        && Number(item.getAttribute('data-loop-index') || -1) === targetLoopIndex
    ) || null;

    if (!(targetItem instanceof HTMLElement)) {
        return;
    }

    const targetAbsolutePosition = targetItem.offsetTop + (targetItem.offsetHeight / 2) - windowCenter;
    const plannedTravelDistance = scrollSpeed * (minimumRollDuration / 1000);
    const startAbsolutePosition = Math.max(0, targetAbsolutePosition - plannedTravelDistance);
    const travelDistance = Math.max(1, targetAbsolutePosition - startAbsolutePosition);

    if (targetAbsolutePosition <= startAbsolutePosition) {
        return;
    }

    const applyPosition = (position) => {
        reel.style.transform = `translateY(-${position}px)`;
    };

    const lockSelectedChallenge = () => {
        reel.classList.add('matching-reel--settled');
        items.forEach((item) => {
            item.classList.toggle('matching-reel__item--selected', item === targetItem);
        });
        if (reelLabel) {
            reelLabel.textContent = 'Game starting...';
        }
        if (redirectUrl) {
            const delay = Math.max(0, gameStartAtMs > 0 ? gameStartAtMs - Date.now() : 3000);
            window.setTimeout(() => {
                window.location.href = redirectUrl;
            }, delay);
        }
    };

    const easeInOutCubic = (progress) => (
        progress < 0.5
            ? 4 * progress * progress * progress
            : 1 - Math.pow(-2 * progress + 2, 3) / 2
    );

    const startTimestamp = performance.now();
    const tick = (timestamp) => {
        const elapsed = timestamp - startTimestamp;
        const progress = Math.min(1, elapsed / minimumRollDuration);
        const easedProgress = easeInOutCubic(progress);
        const absolutePosition = startAbsolutePosition + (travelDistance * easedProgress);
        applyPosition(absolutePosition);

        if (progress >= 1) {
            applyPosition(targetAbsolutePosition);
            lockSelectedChallenge();
            return;
        }

        window.requestAnimationFrame(tick);
    };

    reel.style.transition = 'none';
    applyPosition(startAbsolutePosition);
    window.requestAnimationFrame(tick);
})();
</script>
