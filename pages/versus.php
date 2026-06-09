<?php
$versusCurrentUserId = (int) ($_SESSION['user_id'] ?? 0);
$rankForPoints = static function (int $points) use ($rankRepository): string {
    if ($rankRepository instanceof RankRepository) {
        $rankProgress = $rankRepository->progressForPoints($points);

        return (string) ($rankProgress['current_name'] ?? 'Beginner');
    }

    return 'Beginner';
};
$onlinePlayers = [];

if ($userRepository instanceof UserRepository) {
    $onlinePlayers = array_map(static function (array $player) use ($rankForPoints): array {
        $displayName = trim((string) ($player['username'] ?? 'Student')) ?: 'Student';
        $accentOptions = ['yellow', 'cyan', 'orange', 'mint'];
        $accent = $accentOptions[((int) ($player['user_id'] ?? 0)) % count($accentOptions)];
        $points = (int) ($player['points'] ?? 0);
        $solves = (int) ($player['solves'] ?? 0);

        return [
            'user_id' => (int) ($player['user_id'] ?? 0),
            'name' => $displayName,
            'initials' => strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2)),
            'avatar_url' => (string) ($player['avatar_url'] ?? ''),
            'rank' => $rankForPoints($points),
            'status' => 'Online',
            'solves' => $solves,
            'streak' => 1 + ($solves % 14),
            'points' => $points,
            'accent' => $accent,
        ];
    }, $userRepository->listOnlineStudentsForVersus($versusCurrentUserId, 120));
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
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-arcade-ink/68">Search online students, check their cards, then send a duel invite when you are ready.</p>
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
    let players = <?= json_encode($onlinePlayers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const grid = document.getElementById('versus-player-grid');
    const searchInput = document.getElementById('versus-search');
    const emptyState = document.getElementById('versus-empty');
    const sentinel = document.getElementById('versus-scroll-sentinel');
    const csrfToken = <?= json_encode(pixelwarCsrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
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

    const normalizePlayers = (nextPlayers) => {
        players = Array.isArray(nextPlayers) ? nextPlayers : [];
        filteredPlayers = [...players];
        renderedCount = 0;
        if (grid) {
            grid.innerHTML = '';
        }
        renderNextPlayers();
        applySearch();
    };

    const playerCard = (player) => `
        <article class="versus-player-card versus-player-card--${escapeHtml(player.accent)} rounded-[24px] border-4 border-arcade-ink bg-white p-4 shadow-[7px_7px_0_rgba(38,25,15,0.22)] transition hover:-translate-y-1 hover:shadow-[9px_9px_0_rgba(38,25,15,0.26)]">
            <div class="versus-player-card__profile">
                <span class="versus-player-avatar grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-2xl border-4 border-arcade-ink bg-arcade-yellow font-arcade text-sm text-arcade-ink">
                    ${player.avatar_url
                        ? `<img src="${escapeHtml(player.avatar_url)}" alt="" class="h-full w-full object-cover">`
                        : escapeHtml(player.initials)}
                </span>
                <div class="versus-player-card__identity">
                    <div class="versus-player-card__title-row">
                        <h2>${escapeHtml(player.name)}</h2>
                        <span class="versus-status">${escapeHtml(player.status)}</span>
                    </div>
                    <div class="versus-player-card__meta">
                        <span class="versus-rank-chip">${escapeHtml(player.rank)}</span>
                        <span class="versus-points-chip">${Number(player.points || 0)} pts</span>
                    </div>
                </div>
            </div>
            <button type="button" data-versus-invite-button data-player-id="${Number(player.user_id || 0)}" class="versus-invite-button">
                <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm6 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM2 13c0-2.2 1.4-4 3-4s3 1.8 3 4H2Zm6 0c0-2.2 1.4-4 3-4s3 1.8 3 4H8Z" /></svg>
                Invite Player
            </button>
        </article>`;

    const setInviteButtonState = (button, label, disabled) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.disabled = disabled;
        button.dataset.originalLabel = button.dataset.originalLabel || button.innerHTML;
        button.innerHTML = label;
    };

    const sendInvite = (targetUserId, button) => {
        if (!targetUserId || !csrfToken) {
            return;
        }

        setInviteButtonState(button, 'Sending...', true);
        const body = new URLSearchParams({
            versus_action: 'invite',
            target_user_id: String(targetUserId),
            _csrf_token: csrfToken,
        });

        fetch('./?c=versus', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error('Invite failed')))
            .then((payload) => {
                if (!payload?.success) {
                    throw new Error(payload?.message || 'Invite failed');
                }

                setInviteButtonState(button, 'Invite Sent', true);
                window.setTimeout(() => {
                    if (!(button instanceof HTMLButtonElement)) {
                        return;
                    }
                    button.innerHTML = button.dataset.originalLabel || 'Invite Player';
                    button.disabled = false;
                }, 2200);
            })
            .catch(() => {
                if (!(button instanceof HTMLButtonElement)) {
                    return;
                }
                button.innerHTML = button.dataset.originalLabel || 'Invite Player';
                button.disabled = false;
            });
    };

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
    grid?.addEventListener('click', (event) => {
        const button = event.target instanceof Element ? event.target.closest('[data-versus-invite-button]') : null;
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        const targetUserId = Number(button.getAttribute('data-player-id') || 0);
        if (targetUserId > 0) {
            sendInvite(targetUserId, button);
        }
    });

    const syncOnlinePlayers = () => {
        const body = new URLSearchParams({
            versus_action: 'snapshot',
            _csrf_token: csrfToken,
        });

        fetch('./?c=versus', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error('Snapshot failed')))
            .then((payload) => {
                if (payload?.success) {
                    normalizePlayers(payload.players || []);
                }
            })
            .catch(() => {});
    };

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
    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            syncOnlinePlayers();
        }
    }, 15000);
})();
</script>
