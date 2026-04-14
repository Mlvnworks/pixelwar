<?php
$onlinePlayers = [];
$playerNames = [
    'PixelRookie', 'CSSRunner', 'BorderBuddy', 'TinyCascade', 'BoxShadowFan', 'LayoutKid', 'PanelPilot', 'ArcadeBox',
    'HeroFixer', 'TypeTuner', 'SelectorMage', 'PixelProof', 'GridPilot', 'FlexNinja', 'CascadeRay', 'StyleSprinter',
    'RadiusRider', 'ShadowScout', 'ButtonBee', 'MarginMage', 'PaddingPunk', 'DesignDuelist', 'SyntaxFox', 'HoverHawk',
    'ClipPathKai', 'MediaQueryMia', 'TokenTuner', 'PanelPacer', 'BadgeBrawler', 'CSSCobra', 'PixelMika', 'RuleRanger',
    'StackSage', 'LayerLeo', 'ColorCraft', 'UnitUma', 'AlignAce', 'GapGhost', 'FontFighter', 'CardKai',
];
$rankLabels = ['Beginner', 'Builder', 'Matcher', 'Stylist', 'Arcade Pro'];

foreach ($playerNames as $playerIndex => $playerName) {
    $onlinePlayers[] = [
        'name' => $playerName,
        'initials' => strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $playerName), 0, 2)),
        'rank' => $rankLabels[$playerIndex % count($rankLabels)],
        'status' => 'Online',
        'solves' => 12 + (($playerIndex * 7) % 96),
        'streak' => 1 + ($playerIndex % 14),
        'points' => 180 + ($playerIndex * 45),
        'accent' => ['yellow', 'cyan', 'orange', 'mint'][$playerIndex % 4],
    ];
}
?>

<main class="versus-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_14%,rgba(76,201,240,0.22),transparent_24%),radial-gradient(circle_at_86%_12%,rgba(255,209,102,0.3),transparent_24%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_38%)]"></div>
    <div class="versus-page__grid absolute inset-0"></div>

    <section class="container relative">
        <a href="./?c=home" class="versus-back-button inline-flex items-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_rgba(38,25,15,0.22)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
            <span aria-hidden="true">&larr;</span>
            Back Home
        </a>

        <div class="versus-hero mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-cyan">1v1 Arena</p>
                    <h1 class="mt-3 text-3xl font-bold leading-tight md:text-5xl">Find an online player.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-arcade-ink/68">Search active players, check their cards, then send a duel invite when you are ready.</p>
                </div>
            </div>

            <label class="versus-search-control mt-6 block" for="versus-search">
                <span class="text-xs font-black uppercase tracking-[0.18em] text-arcade-ink/55">Search player</span>
                <div class="mt-2 flex items-center gap-3 rounded-2xl border-2 border-arcade-ink/15 bg-white px-4 py-3 transition focus-within:border-arcade-orange focus-within:shadow-[0_0_0_4px_rgba(255,140,66,0.14)]">
                    <svg class="h-5 w-5 shrink-0 text-arcade-orange" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M7 2a5 5 0 0 1 3.9 8.1l2.5 2.5-1.1 1.1-2.5-2.5A5 5 0 1 1 7 2Zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z" />
                    </svg>
                    <input id="versus-search" type="search" class="w-full bg-transparent text-sm font-bold text-arcade-ink outline-none" placeholder="Search by player name or rank" autocomplete="off">
                </div>
            </label>
        </div>

        <div id="versus-player-grid" class="versus-player-grid mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-live="polite"></div>
        <p id="versus-empty" class="mt-5 hidden rounded-2xl border-2 border-arcade-ink/15 bg-white p-5 text-center text-sm font-bold text-arcade-ink/60">No online players match your search.</p>
        <div id="versus-scroll-sentinel" class="h-12" aria-hidden="true"></div>
    </section>
</main>

<script>
(() => {
    const players = <?= json_encode($onlinePlayers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const grid = document.getElementById('versus-player-grid');
    const searchInput = document.getElementById('versus-search');
    const emptyState = document.getElementById('versus-empty');
    const sentinel = document.getElementById('versus-scroll-sentinel');
    const pageSize = 9;
    let filteredPlayers = [...players];
    let renderedCount = 0;

    const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;',
    }[character]));

    const playerCard = (player) => `
        <article class="versus-player-card versus-player-card--${escapeHtml(player.accent)} rounded-[24px] border-4 border-arcade-ink bg-white p-4 shadow-[7px_7px_0_rgba(38,25,15,0.22)] transition hover:-translate-y-1 hover:shadow-[9px_9px_0_rgba(38,25,15,0.26)]">
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="versus-player-avatar grid h-14 w-14 shrink-0 place-items-center rounded-2xl border-4 border-arcade-ink font-arcade text-sm text-arcade-ink">${escapeHtml(player.initials)}</span>
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-black">${escapeHtml(player.name)}</h2>
                        <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-arcade-ink/52">${escapeHtml(player.rank)}</p>
                    </div>
                </div>
                <span class="versus-status rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em]">${escapeHtml(player.status)}</span>
            </div>
            <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-2xl bg-arcade-cream p-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.12em] text-arcade-ink/48">Solved</p>
                    <p class="mt-1 text-lg font-black">${Number(player.solves)}</p>
                </div>
                <div class="rounded-2xl bg-arcade-cream p-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.12em] text-arcade-ink/48">Streak</p>
                    <p class="mt-1 text-lg font-black">${Number(player.streak)}d</p>
                </div>
                <div class="rounded-2xl bg-arcade-cream p-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.12em] text-arcade-ink/48">Points</p>
                    <p class="mt-1 text-lg font-black">${Number(player.points)}</p>
                </div>
            </div>
            <button type="button" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-arcade-orange px-4 py-2 text-sm font-bold text-white shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">
                <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm6 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2 13c0-2.2 1.4-4 3-4s3 1.8 3 4H2Zm6 0c0-2.2 1.4-4 3-4s3 1.8 3 4H8Z" /></svg>
                Invite Player
            </button>
        </article>`;

    const renderNextPlayers = () => {
        if (!grid) {
            return;
        }

        const nextPlayers = filteredPlayers.slice(renderedCount, renderedCount + pageSize);
        grid.insertAdjacentHTML('beforeend', nextPlayers.map(playerCard).join(''));
        renderedCount += nextPlayers.length;

        if (emptyState) {
            emptyState.classList.toggle('hidden', filteredPlayers.length > 0);
        }

        if (sentinel) {
            sentinel.hidden = renderedCount >= filteredPlayers.length;
        }
    };

    const applySearch = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        filteredPlayers = players.filter((player) => [player.name, player.rank, player.status].some((value) => String(value).toLowerCase().includes(query)));
        renderedCount = 0;
        if (grid) {
            grid.innerHTML = '';
        }
        renderNextPlayers();
    };

    searchInput?.addEventListener('input', applySearch);

    if ('IntersectionObserver' in window && sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                renderNextPlayers();
            }
        }, { rootMargin: '320px' });
        observer.observe(sentinel);
    } else {
        window.addEventListener('scroll', () => {
            if (!sentinel || sentinel.hidden) {
                return;
            }
            if (sentinel.getBoundingClientRect().top < window.innerHeight + 320) {
                renderNextPlayers();
            }
        });
    }

    renderNextPlayers();
})();
</script>
