<?php
require_once __DIR__ . '/../classes/challenge-catalog.php';

$createdChallengeRows = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->searchCreatedChallenges('', '', null, 100)
    : [];
$ongoingChallengeLookup = $userChallengeRepository instanceof UserChallengeRepository && isset($_SESSION['user_id'])
    ? $userChallengeRepository->ongoingChallengeIdLookup((int) $_SESSION['user_id'])
    : [];
$challenges = [];

foreach ($createdChallengeRows as $challengeRow) {
    $difficulty = ucfirst(strtolower((string) ($challengeRow['difficulty_name'] ?? 'Easy')));
    $challengeId = (int) $challengeRow['challenge_id'];
    $completionCount = $userChallengeRepository instanceof UserChallengeRepository
        ? $userChallengeRepository->countCompletedByChallenge($challengeId)
        : 0;
    $challenges[] = [
        'key' => 'db-' . $challengeId,
        'title' => (string) $challengeRow['name'],
        'level' => $difficulty,
        'levelClass' => 'challenge-difficulty--' . strtolower($difficulty),
        'reward' => '+' . (int) ($challengeRow['points'] ?? 0) . ' pts',
        'author' => (string) ($challengeRow['author'] ?? 'Teacher'),
        'description' => (string) $challengeRow['instruction'],
        'href' => './?c=challenge&id=' . $challengeId,
        'isOngoing' => isset($ongoingChallengeLookup[$challengeId]),
        'completionCount' => $completionCount,
    ];
}

if ($challenges === []) {
    foreach (ChallengeCatalog::all() as $catalogChallenge) {
        $challenges[] = [
            'key' => (string) $catalogChallenge['slug'],
            'title' => $catalogChallenge['title'],
            'level' => $catalogChallenge['level'],
            'levelClass' => $catalogChallenge['levelClass'],
            'reward' => $catalogChallenge['reward'],
            'author' => $catalogChallenge['author'],
            'description' => $catalogChallenge['description'],
            'href' => './?c=challenge&slug=' . urlencode((string) $catalogChallenge['slug']),
            'isOngoing' => false,
            'completionCount' => 0,
        ];
    }
}

$difficulties = array_values(array_unique(array_map(static fn (array $challenge): string => $challenge['level'], $challenges)));
?>

<main class="challenges-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_16%_14%,rgba(255,209,102,0.28),transparent_24%),radial-gradient(circle_at_86%_10%,rgba(76,201,240,0.22),transparent_25%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_42%)]"></div>
    <div class="challenges-page__grid absolute inset-0"></div>

    <section class="container relative">
        <a href="./?c=home" class="challenges-back-button mb-4 inline-flex items-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
            <span aria-hidden="true">&larr;</span>
            Back Home
        </a>

        <div class="challenges-hero rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-4xl font-bold leading-tight md:text-6xl">Challenges</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-arcade-ink/70">Search and filter CSS design-matching challenges before starting a run.</p>
                </div>
            </div>

            <div class="challenges-controls mt-6 grid gap-3 lg:grid-cols-[1fr_auto] lg:items-center">
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Search</span>
                    <input id="challenge-search" type="search" class="mt-2 w-full rounded-2xl border-2 border-arcade-ink/15 bg-white px-4 py-3 text-sm outline-none transition focus:border-arcade-orange" placeholder="Search by title, author, or description">
                </label>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Difficulty</p>
                    <div class="challenges-filter-list mt-2 flex flex-wrap gap-2" role="group" aria-label="Filter challenges by difficulty">
                        <button type="button" class="challenge-filter is-active rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-3 py-2 text-xs font-bold text-arcade-ink shadow-[0_3px_0_#26190f]" data-filter="all">All</button>
                        <?php foreach ($difficulties as $difficulty) : ?>
                            <button type="button" class="challenge-filter rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-xs font-bold text-arcade-ink shadow-[0_3px_0_#26190f]" data-filter="<?= htmlspecialchars(strtolower($difficulty), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8') ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="challenges-toolbar mt-5 flex flex-wrap items-center justify-between gap-3">
            <p id="challenge-count" class="text-sm font-bold text-arcade-ink/60"></p>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/45">Open a card to view details before starting</p>
        </div>

        <section class="challenges-results mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Challenge results">
            <?php foreach ($challenges as $challenge) : ?>
                <article class="challenge-library-card relative rounded-[24px] border-2 border-arcade-ink/15 bg-arcade-panel p-4 shadow-[5px_5px_0_rgba(38,25,15,0.18)]" data-challenge-card data-difficulty="<?= htmlspecialchars(strtolower($challenge['level']), ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower($challenge['title'] . ' ' . $challenge['author'] . ' ' . $challenge['description']), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="challenge-difficulty <?= htmlspecialchars($challenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['level'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['reward'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($challenge['isOngoing'])) : ?>
                            <span class="rounded-full border-2 border-arcade-ink bg-arcade-cyan px-3 py-1 text-xs font-bold text-arcade-ink">Ongoing</span>
                        <?php endif; ?>
                    </div>
                    <h2 class="mt-3 text-xl font-bold"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="mt-2 text-sm leading-6 text-arcade-ink/70"><?= htmlspecialchars($challenge['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="challenge-library-card__footer mt-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-arcade-ink/50">By <?= htmlspecialchars($challenge['author'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="challenge-library-card__action text-right">
                            <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.14em] text-arcade-ink/45"><?= (int) ($challenge['completionCount'] ?? 0) ?> completed</p>
                            <a href="<?= htmlspecialchars($challenge['href'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">Train</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <p id="challenge-empty" class="mt-5 hidden rounded-2xl border-2 border-arcade-ink/15 bg-white p-5 text-center text-sm font-bold text-arcade-ink/60">No challenges match your search.</p>

        <div class="challenges-pagination mt-5 flex items-center justify-between gap-3">
            <button id="challenges-prev" type="button" class="rounded-xl bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Prev</button>
            <span id="challenges-page-status" class="text-xs font-bold text-arcade-ink/60"></span>
            <button id="challenges-next" type="button" class="rounded-xl bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Next</button>
        </div>
    </section>
</main>

<script>
(() => {
    const searchInput = document.getElementById('challenge-search');
    const filters = Array.from(document.querySelectorAll('[data-filter]'));
    const cards = Array.from(document.querySelectorAll('[data-challenge-card]'));
    const countLabel = document.getElementById('challenge-count');
    const emptyState = document.getElementById('challenge-empty');
    const previousButton = document.getElementById('challenges-prev');
    const nextButton = document.getElementById('challenges-next');
    const pageStatus = document.getElementById('challenges-page-status');
    const mobileQuery = window.matchMedia('(max-width: 768px)');
    const pageSize = 25;
    let activeFilter = 'all';
    let currentPage = 1;

    const renderFilterState = () => {
        filters.forEach((button) => {
            const isActive = (button.dataset.filter || 'all') === activeFilter;
            button.classList.toggle('is-active', isActive);
            button.classList.toggle('bg-arcade-yellow', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    const renderChallenges = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        const matchedCards = cards.filter((card) => {
            const matchesFilter = activeFilter === 'all' || card.dataset.difficulty === activeFilter;
            const matchesSearch = !query || (card.dataset.search || '').includes(query);
            return matchesFilter && matchesSearch;
        });
        const totalPages = Math.max(1, Math.ceil(matchedCards.length / pageSize));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        cards.forEach((card) => {
            const cardIndex = matchedCards.indexOf(card);
            const isVisible = cardIndex >= start && cardIndex < end;
            card.hidden = !isVisible;
        });

        if (countLabel) {
            countLabel.textContent = `${matchedCards.length} challenge${matchedCards.length === 1 ? '' : 's'} found`;
        }

        if (emptyState) {
            emptyState.classList.toggle('hidden', matchedCards.length !== 0);
        }

        if (pageStatus) {
            pageStatus.textContent = mobileQuery.matches ? `Page ${currentPage}/${totalPages}` : `Page ${currentPage} of ${totalPages} - 25 per page`;
        }

        if (previousButton) {
            previousButton.disabled = currentPage === 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage === totalPages;
        }
    };

    filters.forEach((filterButton) => {
        filterButton.addEventListener('click', () => {
            activeFilter = filterButton.dataset.filter || 'all';
            currentPage = 1;
            renderFilterState();
            renderChallenges();
        });
    });

    previousButton?.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        renderChallenges();
    });

    nextButton?.addEventListener('click', () => {
        currentPage += 1;
        renderChallenges();
    });

    searchInput?.addEventListener('input', () => {
        currentPage = 1;
        renderChallenges();
    });
    mobileQuery.addEventListener('change', renderChallenges);
    renderFilterState();
    renderChallenges();
})();
</script>
