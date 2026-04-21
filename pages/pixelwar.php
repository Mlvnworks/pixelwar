<?php
$gameChallengeId = (int) ($_GET['challenge_id'] ?? 0);
$gameChallenge = null;
$gameUserChallenge = null;
$gameChallengeError = '';

if ($gameChallengeId > 0) {
    try {
        if (!$challengeRepository instanceof ChallengeRepository || !$userChallengeRepository instanceof UserChallengeRepository) {
            throw new RuntimeException('Challenge progress is not available.');
        }

        $gameChallenge = $challengeRepository->findCreatedChallenge($gameChallengeId);

        if ($gameChallenge === null) {
            throw new RuntimeException('Challenge not found.');
        }

        $gameUserId = (int) ($_SESSION['user_id'] ?? 0);

        if ($gameUserId <= 0) {
            throw new RuntimeException('Login required to start this challenge.');
        }

        $gameUserChallenge = $userChallengeRepository->startOrFindOngoing($gameUserId, $gameChallengeId);

        if (!empty($gameUserChallenge['was_created']) && $activityLogRepository instanceof ActivityLogRepository) {
            $activityLogRepository->create(
                $gameUserId,
                'challenge',
                'Started challenge "' . (string) ($gameChallenge['name'] ?? 'Challenge') . '".'
            );
        }
    } catch (Throwable $err) {
        error_log('Pixelwar gameplay challenge start error: ' . $err->getMessage());
        $gameChallengeError = APP_DEBUG ? $err->getMessage() : 'Unable to start this challenge. Please try another one.';
    }
}

$gameChallengeTitle = $gameChallenge !== null ? (string) $gameChallenge['name'] : 'Pixelwar';
$gameChallengeInstruction = $gameChallenge !== null
    ? (string) $gameChallenge['instruction']
    : 'Drag CSS properties to match the target design.';
$gameChallengeHtmlSource = $gameChallenge !== null ? (string) $gameChallenge['html_source'] : '';
$gameChallengeCssSource = $gameChallenge !== null ? (string) $gameChallenge['css_source'] : '';
$gameStartedAtIso = $gameUserChallenge !== null
    ? date(DATE_ATOM, strtotime((string) $gameUserChallenge['started_at']))
    : '';
$gameUserChallengeId = $gameUserChallenge !== null ? (int) $gameUserChallenge['uc_id'] : 0;
?>

<section id="game-test" class="p-0">
        <?php if ($gameChallengeError !== '') : ?>
            <div class="fixed inset-x-4 top-4 z-[80] mx-auto max-w-xl rounded-2xl border-4 border-arcade-ink bg-arcade-coral px-4 py-3 text-sm font-black text-white shadow-[6px_6px_0_#26190f]">
                <?= htmlspecialchars($gameChallengeError, ENT_QUOTES, 'UTF-8') ?>
                <a href="./?c=challenges" class="ml-2 text-white underline">Back to challenges</a>
            </div>
        <?php endif; ?>

        <div id="game-opening-effect" class="game-opening-effect" aria-hidden="true">
            <div class="game-opening-effect__panel">
                <span class="game-opening-effect__eyebrow">Loading Arena</span>
                <strong>Pixelwar</strong>
                <span class="game-opening-effect__bar"></span>
            </div>
        </div>

        <div id="completion-confetti" class="completion-confetti" aria-hidden="true"></div>

        <div class="challenge-shell border-4 border-arcade-ink/10 bg-arcade-panel/80 p-2">
            <aside class="floating-hud" aria-live="polite">
                <div class="hud-pill gameplay-status-pill">
                    <span id="game-status">Waiting for your first move.</span>
                    <?php if ($gameUserChallenge !== null) : ?>
                        <span class="gameplay-time" id="gameplay-time" data-started-at="<?= htmlspecialchars($gameStartedAtIso, ENT_QUOTES, 'UTF-8') ?>">00:00</span>
                        <form id="give-up-form" class="give-up-form" action="./?c=pixelwar&challenge_id=<?= (int) $gameChallengeId ?>" method="post">
                            <?= pixelwarCsrfField() ?>
                            <input type="hidden" name="gameplay_action" value="give_up">
                            <input type="hidden" name="challenge_id" value="<?= (int) $gameChallengeId ?>">
                            <input type="hidden" name="user_challenge_id" value="<?= (int) $gameUserChallenge['uc_id'] ?>">
                            <button type="submit" class="give-up-button">Give Up</button>
                        </form>
                    <?php endif; ?>
                </div>
            </aside>

            <div class="challenge-grid" id="challenge-grid">
                <section class="builder-pane rounded-[26px] border-4 border-arcade-ink/10 bg-white/70 p-4 md:p-5">
                    <section class="panel-card panel-card--preview rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                        <div class="preview-card-header mb-3">
                            <h2 class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">1. Live Preview</h2>
                            <button type="button" class="mobile-preview-toggle rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-3 py-1.5 text-[11px] font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" data-bs-toggle="modal" data-bs-target="#mobile-preview-modal">
                                View Target
                            </button>
                        </div>
                        <div class="preview-frame rounded-[20px] border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-4">
                            <iframe class="game-source-preview" title="Live challenge preview" sandbox="allow-same-origin" data-live-preview></iframe>
                        </div>
                    </section>

                    <section class="panel-card panel-card--identifiers rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                        <div class="identifiers-header mb-3">
                            <h2 class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">2. Identifier Containers</h2>
                            <div class="progress-inline" aria-label="Challenge progress">
                                <span class="progress-inline__track">
                                    <span id="progress-bar-fill" class="progress-inline__fill"></span>
                                </span>
                            </div>
                        </div>
                        <div class="identifiers-scroll">
                            <div id="selector-card-grid" class="grid gap-3 md:grid-cols-2"></div>
                        </div>
                    </section>

                    <section class="panel-card panel-card--properties rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                        <h2 class="mb-2 font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">3. Properties Panel</h2>
                        <div class="property-controls mb-2">
                            <div class="property-search-wrap">
                                <input
                                    id="property-search"
                                    type="search"
                                    autocomplete="off"
                                    spellcheck="false"
                                    placeholder="Search properties..."
                                    class="w-full rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-2 text-sm text-arcade-ink outline-none transition focus:border-arcade-orange">
                            </div>
                            <button id="reset-layout-btn" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-arcade-peach/60 px-3 py-2 text-xs font-semibold text-arcade-ink transition hover:bg-arcade-yellow/70">
                                Reset Placements
                            </button>
                        </div>
                        <div class="drop-zone property-zone" data-drop-key="pool">
                            <div class="chip-list chip-list--horizontal" data-property-list="pool"></div>
                        </div>
                    </section>
                </section>

                <div id="split-handle" class="split-handle" role="separator" aria-orientation="vertical" aria-label="Resize target panel"></div>

                <section class="target-pane rounded-[26px] border-4 border-arcade-ink/10 bg-white/80 p-4 md:p-5" id="target-pane">
                    <header class="mb-4 rounded-[18px] border-2 border-arcade-ink/10 bg-arcade-cream px-3 py-3">
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Target Design</p>
                    </header>

                    <div class="target-panel-body">
                        <div
                            class="target-frame rounded-[20px] border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-4"
                            data-source-target-frame
                            data-html-source="<?= htmlspecialchars($gameChallengeHtmlSource, ENT_QUOTES, 'UTF-8') ?>"
                            data-css-source="<?= htmlspecialchars($gameChallengeCssSource, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="game-source-loader" data-source-loader <?= $gameChallenge === null ? 'hidden' : '' ?>>
                                <span class="game-source-loader__spinner" aria-hidden="true"></span>
                                <strong>Loading target...</strong>
                            </div>
                            <iframe class="game-source-preview" title="Challenge target preview" sandbox="allow-same-origin" data-source-preview hidden></iframe>
                        </div>
                    </div>
                </section>
            </div>
        </div>
</section>

<div class="modal fade gameplay-exit-modal" id="gameplay-exit-modal" tabindex="-1" aria-labelledby="gameplay-exit-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-coral">Leave Challenge</p>
                    <h2 id="gameplay-exit-modal-title" class="modal-title mt-2 text-xl font-bold">Give up this run?</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <p class="text-sm font-semibold leading-7 text-arcade-ink/70">
                    Your current solving progress will reset when you exit this page or reload it. If you give up now, this ongoing run will be removed.
                </p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Keep Solving</button>
                    <button type="button" id="confirm-give-up-button" class="rounded-xl border-2 border-arcade-ink bg-arcade-coral px-5 py-2 text-sm font-bold text-white shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-red-600">Give Up</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mobile-preview-modal" id="mobile-preview-modal" tabindex="-1" aria-labelledby="mobile-preview-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Target Design</p>
                    <h2 id="mobile-preview-modal-title" class="modal-title mt-2 text-xl font-bold"><?= htmlspecialchars($gameChallengeTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <div
                    class="mobile-preview-frame rounded-[20px] border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-4"
                    data-source-target-frame
                    data-html-source="<?= htmlspecialchars($gameChallengeHtmlSource, ENT_QUOTES, 'UTF-8') ?>"
                    data-css-source="<?= htmlspecialchars($gameChallengeCssSource, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="game-source-loader" data-source-loader <?= $gameChallenge === null ? 'hidden' : '' ?>>
                        <span class="game-source-loader__spinner" aria-hidden="true"></span>
                        <strong>Loading target...</strong>
                    </div>
                    <iframe class="game-source-preview" title="Challenge target preview" sandbox="allow-same-origin" data-source-preview hidden></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade gameplay-complete-modal" id="gameplay-complete-modal" tabindex="-1" aria-labelledby="gameplay-complete-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Challenge Complete</p>
                    <h2 id="gameplay-complete-modal-title" class="modal-title mt-2 text-xl font-bold">Nice work.</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <div class="gameplay-complete-summary">
                    <div class="gameplay-complete-card">
                        <span class="gameplay-complete-card__label">Challenge</span>
                        <strong id="gameplay-complete-name">Pixelwar</strong>
                    </div>
                    <div class="gameplay-complete-grid">
                        <div class="gameplay-complete-card">
                            <span class="gameplay-complete-card__label">Duration</span>
                            <strong id="gameplay-complete-duration">00:00</strong>
                        </div>
                        <div class="gameplay-complete-card">
                            <span class="gameplay-complete-card__label">Points</span>
                            <strong id="gameplay-complete-points">0</strong>
                        </div>
                        <div class="gameplay-complete-card">
                            <span class="gameplay-complete-card__label">Selectors</span>
                            <strong id="gameplay-complete-selectors">0</strong>
                        </div>
                        <div class="gameplay-complete-card">
                            <span class="gameplay-complete-card__label">Properties</span>
                            <strong id="gameplay-complete-properties">0</strong>
                        </div>
                    </div>
                    <div class="gameplay-complete-card">
                        <span class="gameplay-complete-card__label">Completed At</span>
                        <strong id="gameplay-complete-finished-at">-</strong>
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <a href="./?c=challenge&id=<?= (int) $gameChallengeId ?>" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-center text-sm font-bold text-arcade-ink no-underline transition hover:bg-arcade-peach/60">Back to Challenge</a>
                    <a href="./?c=home" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-center text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Go Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const challengeConfig = {
        htmlSource: <?= json_encode($gameChallengeHtmlSource, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        cssSource: <?= json_encode($gameChallengeCssSource, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        hasChallenge: <?= $gameChallenge !== null ? 'true' : 'false' ?>,
        challengeId: <?= (int) $gameChallengeId ?>,
        userChallengeId: <?= (int) $gameUserChallengeId ?>,
        challengeTitle: <?= json_encode($gameChallengeTitle, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        csrfToken: <?= json_encode(pixelwarCsrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
    };

    const selectorGrid = document.getElementById('selector-card-grid');
    const livePreview = document.querySelector('[data-live-preview]');
    const targetPreviews = Array.from(document.querySelectorAll('[data-source-preview]'));
    const allPreviewFrames = [livePreview, ...targetPreviews].filter((frame) => frame instanceof HTMLIFrameElement);
    const targetFrames = Array.from(document.querySelectorAll('[data-source-target-frame]'));
    const statusLabel = document.getElementById('game-status');
    const gameplayTime = document.getElementById('gameplay-time');
    const giveUpForm = document.getElementById('give-up-form');
    const progressBarFill = document.getElementById('progress-bar-fill');
    const resetButton = document.getElementById('reset-layout-btn');
    const propertySearchInput = document.getElementById('property-search');
    const openingEffect = document.getElementById('game-opening-effect');
    const completionConfetti = document.getElementById('completion-confetti');
    const completionModalElement = document.getElementById('gameplay-complete-modal');
    const completionModal = completionModalElement ? new bootstrap.Modal(completionModalElement) : null;
    const exitModalElement = document.getElementById('gameplay-exit-modal');
    const exitModal = exitModalElement ? new bootstrap.Modal(exitModalElement) : null;
    const confirmGiveUpButton = document.getElementById('confirm-give-up-button');
    const targetGrid = document.getElementById('challenge-grid');
    const splitHandle = document.getElementById('split-handle');
    const identifiersScrollContainer = document.querySelector('.identifiers-scroll');
    const previewModal = document.getElementById('mobile-preview-modal');

    const state = {
        html: '',
        css: '',
        selectorDefinitions: [],
        propertyOccurrences: [],
        selectorKeys: [],
        placements: { pool: {} },
        requiredBySelector: {},
        propertyCatalog: {},
        totalRequiredByProperty: {},
        poolOrder: [],
        selectorLookup: {},
        selectorCardLookup: {},
        selectorMetaLookup: {},
        listNodes: {},
        totalCount: 0,
        selectedPayload: null,
        draggedPayload: null,
        isResizing: false,
        pinnedSelectorKey: null,
        hoveredSelectorKey: null,
        lastHighlightedSelectorKey: null,
        isCompletionSubmitting: false,
        isCompleted: false,
        skipUnloadWarning: false,
    };

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const buildPreviewDocument = (html, css) => `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { box-sizing: border-box; }
html, body { width: 100%; min-height: 100%; margin: 0; }
body { display: grid; min-height: 100vh; place-items: center; background: #f7efe1; font-family: Arial, sans-serif; padding: 24px; overflow: auto; }
${css}
</style>
</head>
<body>${html}</body>
</html>`;

    const fitPreviewFrame = (frame) => {
        if (!(frame instanceof HTMLIFrameElement)) {
            return;
        }

        const doc = frame.contentDocument;
        const body = doc?.body;
        const html = doc?.documentElement;
        if (!doc || !body || !html) {
            return;
        }

        const shell = frame.parentElement;
        if (!(shell instanceof HTMLElement)) {
            return;
        }

        const naturalWidth = Math.max(
            body.scrollWidth,
            body.offsetWidth,
            html.scrollWidth,
            html.offsetWidth,
            1
        );
        const naturalHeight = Math.max(
            body.scrollHeight,
            body.offsetHeight,
            html.scrollHeight,
            html.offsetHeight,
            1
        );

        const shellWidth = shell.clientWidth;
        const shellHeight = shell.clientHeight;
        if (shellWidth <= 0 || shellHeight <= 0) {
            return;
        }

        const scale = Math.min(1, shellWidth / naturalWidth, shellHeight / naturalHeight);
        frame.style.width = `${naturalWidth}px`;
        frame.style.height = `${naturalHeight}px`;
        frame.style.maxWidth = 'none';
        frame.style.maxHeight = 'none';
        frame.style.transform = `scale(${scale})`;
        frame.style.transformOrigin = 'top left';
    };

    const runOpeningEffect = () => {
        if (!openingEffect) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        if (params.get('intro') !== '1') {
            openingEffect.remove();
            return;
        }

        openingEffect.classList.add('is-playing');
        window.setTimeout(() => openingEffect.remove(), 1500);
    };

    const setStatus = (message, isSuccess = false) => {
        if (!statusLabel) {
            return;
        }

        statusLabel.textContent = message;
        statusLabel.closest('.hud-pill')?.classList.toggle('is-success', isSuccess);
    };

    const formatElapsedTime = (totalSeconds) => {
        const safeSeconds = Math.max(0, totalSeconds);
        const hours = Math.floor(safeSeconds / 3600);
        const minutes = Math.floor((safeSeconds % 3600) / 60);
        const seconds = safeSeconds % 60;
        const two = (value) => String(value).padStart(2, '0');

        return hours > 0
            ? `${hours}:${two(minutes)}:${two(seconds)}`
            : `${two(minutes)}:${two(seconds)}`;
    };

    const startGameplayTimer = () => {
        if (!gameplayTime?.dataset.startedAt) {
            return;
        }

        const startedAt = Date.parse(gameplayTime.dataset.startedAt);
        if (Number.isNaN(startedAt)) {
            return;
        }

        const renderTime = () => {
            gameplayTime.textContent = formatElapsedTime(Math.floor((Date.now() - startedAt) / 1000));
        };

        renderTime();
        const intervalId = window.setInterval(() => {
            if (state.isCompleted) {
                window.clearInterval(intervalId);
                return;
            }

            renderTime();
        }, 1000);
    };

    const formatDateTime = (isoValue) => {
        if (!isoValue) {
            return '-';
        }

        const parsed = new Date(isoValue);
        if (Number.isNaN(parsed.getTime())) {
            return '-';
        }

        return parsed.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    };

    const launchConfetti = () => {
        if (!completionConfetti) {
            return;
        }

        completionConfetti.innerHTML = '';
        completionConfetti.classList.add('is-active');
        const colors = ['#ff8c42', '#ffd166', '#4cc9f0', '#8bd3c7', '#f97373'];

        for (let index = 0; index < 42; index += 1) {
            const piece = document.createElement('span');
            piece.className = 'confetti-piece';
            piece.style.left = `${Math.random() * 100}%`;
            piece.style.background = colors[index % colors.length];
            piece.style.animationDelay = `${Math.random() * 0.35}s`;
            piece.style.animationDuration = `${2.2 + Math.random() * 1.2}s`;
            piece.style.transform = `translateY(-16px) rotate(${Math.random() * 180}deg)`;
            completionConfetti.appendChild(piece);
        }

        window.setTimeout(() => {
            completionConfetti.classList.remove('is-active');
            completionConfetti.innerHTML = '';
        }, 3400);
    };

    const populateCompletionModal = (completionData) => {
        const setText = (id, value) => {
            const node = document.getElementById(id);
            if (node) {
                node.textContent = value;
            }
        };

        setText('gameplay-complete-name', completionData.challenge_name || challengeConfig.challengeTitle || 'Pixelwar');
        setText('gameplay-complete-duration', formatElapsedTime(Number(completionData.duration_seconds || 0)));
        setText('gameplay-complete-points', `${Number(completionData.points || 0)} pts`);
        setText('gameplay-complete-selectors', String(state.selectorKeys.length));
        setText('gameplay-complete-properties', String(state.totalCount));
        setText('gameplay-complete-finished-at', formatDateTime(completionData.completed_at || ''));
    };

    const submitCompletion = async () => {
        if (state.isCompletionSubmitting || state.isCompleted || !challengeConfig.challengeId || !challengeConfig.userChallengeId) {
            return;
        }

        state.isCompletionSubmitting = true;
        setStatus('Completing challenge...', true);

        try {
            const body = new URLSearchParams({
                _csrf_token: challengeConfig.csrfToken,
                gameplay_action: 'complete',
                challenge_id: String(challengeConfig.challengeId),
                user_challenge_id: String(challengeConfig.userChallengeId),
            });

            const response = await fetch(`./?c=pixelwar&challenge_id=${encodeURIComponent(challengeConfig.challengeId)}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            });

            const payload = await response.json();

            if (!response.ok || !payload?.success) {
                throw new Error(payload?.message || 'Unable to complete this challenge right now.');
            }

            state.isCompleted = true;
            state.skipUnloadWarning = true;
            if (gameplayTime && payload.data?.completed_at) {
                gameplayTime.dataset.startedAt = '';
                gameplayTime.textContent = formatElapsedTime(Number(payload.data?.duration_seconds || 0));
            }
            setStatus('Complete', true);
            launchConfetti();
            populateCompletionModal(payload.data || {});
            completionModal?.show();
        } catch (error) {
            console.error(error);
            state.isCompletionSubmitting = false;
            setStatus(error instanceof Error ? error.message : 'Unable to complete this challenge right now.');
        }
    };

    const shouldWarnBeforeExit = () => {
        if (!challengeConfig.hasChallenge || !challengeConfig.userChallengeId) {
            return false;
        }

        if (state.skipUnloadWarning || state.isCompleted || state.isCompletionSubmitting) {
            return false;
        }

        return true;
    };

    const beforeUnloadHandler = (event) => {
        if (!shouldWarnBeforeExit()) {
            return;
        }

        const message = 'Your current solving progress will reset when you exit this page or reload it.';
        event.preventDefault();
        event.returnValue = message;
        return message;
    };

    const fetchText = async (url) => {
        const response = await fetch(url, { cache: 'no-store' });
        if (!response.ok) {
            throw new Error(`Unable to load ${url}`);
        }
        return response.text();
    };

    const parseCss = (css) => {
        const cleaned = css.replace(/\/\*[\s\S]*?\*\//g, '');
        const rules = [];
        const rulePattern = /([^{}@]+)\{([^{}]+)\}/g;
        let match;

        while ((match = rulePattern.exec(cleaned)) !== null) {
            const selectors = match[1]
                .split(',')
                .map((selector) => selector.trim())
                .filter(Boolean);
            const declarations = match[2]
                .split(';')
                .map((declaration) => declaration.trim())
                .filter(Boolean)
                .map((declaration) => declaration.endsWith(';') ? declaration : `${declaration};`);

            selectors.forEach((selector) => {
                if (declarations.length > 0) {
                    rules.push({ selector, declarations });
                }
            });
        }

        return rules;
    };

    const selectorKeyFor = (selector, index) => `s${index + 1}`;

    const sanitizeSelectorForMatch = (selector) => selector
        .replace(/::?[a-zA-Z-]+(?:\([^)]*\))?/g, '')
        .replace(/\s+/g, ' ')
        .trim();

    const selectorSpecificity = (selector) => {
        const idCount = (selector.match(/#[\w-]+/g) || []).length;
        const classLikeCount = (selector.match(/(?:\.[\w-]+|\[[^\]]+\]|:[\w-]+)/g) || []).length;
        const elementCount = (selector.replace(/#[\w-]+|(?:\.[\w-]+|\[[^\]]+\]|:[\w-]+)/g, '').match(/\b[a-zA-Z][\w-]*\b/g) || []).length;

        return (idCount * 100) + (classLikeCount * 10) + elementCount;
    };

    const initializeChallengeData = (css) => {
        const parsedRules = parseCss(css);
        const selectorKeyBySelector = new Map();
        const selectorDefinitions = [];
        const propertyOccurrences = [];

        parsedRules.forEach((rule) => {
            if (!selectorKeyBySelector.has(rule.selector)) {
                const key = selectorKeyFor(rule.selector, selectorDefinitions.length);
                selectorKeyBySelector.set(rule.selector, key);
                selectorDefinitions.push({
                    key,
                    selector: rule.selector,
                    matchSelector: sanitizeSelectorForMatch(rule.selector),
                    specificity: selectorSpecificity(rule.selector),
                });
            }

            const target = selectorKeyBySelector.get(rule.selector);
            rule.declarations.forEach((declaration) => {
                propertyOccurrences.push({ rule: declaration, target });
            });
        });

        state.selectorDefinitions = selectorDefinitions;
        state.propertyOccurrences = propertyOccurrences;
        state.selectorKeys = selectorDefinitions.map((selector) => selector.key);
        state.placements = { pool: {} };
        state.requiredBySelector = {};
        state.propertyCatalog = {};
        state.totalRequiredByProperty = {};
        state.selectorLookup = Object.fromEntries(selectorDefinitions.map((selector) => [selector.key, selector.selector]));
        state.selectorKeys.forEach((key) => {
            state.placements[key] = {};
            state.requiredBySelector[key] = {};
        });

        const keyByRule = new Map();
        let propertyCounter = 1;

        propertyOccurrences.forEach((occurrence) => {
            if (!keyByRule.has(occurrence.rule)) {
                const generatedKey = `p${propertyCounter}`;
                propertyCounter += 1;
                keyByRule.set(occurrence.rule, generatedKey);
                state.propertyCatalog[generatedKey] = { rule: occurrence.rule };
            }

            const propertyKey = keyByRule.get(occurrence.rule);
            state.requiredBySelector[occurrence.target][propertyKey] = (state.requiredBySelector[occurrence.target][propertyKey] || 0) + 1;
            state.totalRequiredByProperty[propertyKey] = (state.totalRequiredByProperty[propertyKey] || 0) + 1;
        });

        Object.keys(state.totalRequiredByProperty).forEach((propertyKey) => {
            state.placements.pool[propertyKey] = state.totalRequiredByProperty[propertyKey];
        });

        state.poolOrder = Object.keys(state.propertyCatalog);
        for (let index = state.poolOrder.length - 1; index > 0; index -= 1) {
            const randomIndex = Math.floor(Math.random() * (index + 1));
            [state.poolOrder[index], state.poolOrder[randomIndex]] = [state.poolOrder[randomIndex], state.poolOrder[index]];
        }

        state.totalCount = propertyOccurrences.length;
    };

    const renderSelectorCards = () => {
        if (!selectorGrid) {
            return;
        }

        selectorGrid.innerHTML = '';
        state.selectorCardLookup = {};
        state.selectorMetaLookup = {};
        state.listNodes = { pool: document.querySelector('[data-property-list="pool"]') };

        if (state.selectorDefinitions.length === 0) {
            selectorGrid.innerHTML = '<p class="empty-zone md:col-span-2">No CSS identifiers were found in this challenge.</p>';
            return;
        }

        state.selectorDefinitions.forEach((selectorDefinition) => {
            const card = document.createElement('article');
            card.className = 'selector-card rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/60 p-3';
            card.dataset.selectorCard = selectorDefinition.key;
            card.innerHTML = `
                <div class="selector-head">
                    <p class="mb-2 font-mono text-xs font-semibold text-arcade-ink/80">${escapeHtml(selectorDefinition.selector)}</p>
                    <span class="selector-meta" data-selector-meta="${selectorDefinition.key}"></span>
                </div>
                <div class="drop-zone selector-zone" data-drop-key="${selectorDefinition.key}">
                    <div class="chip-list" data-property-list="${selectorDefinition.key}"></div>
                </div>
            `;
            selectorGrid.appendChild(card);
            state.selectorCardLookup[selectorDefinition.key] = card;
            state.selectorMetaLookup[selectorDefinition.key] = card.querySelector('[data-selector-meta]');
            state.listNodes[selectorDefinition.key] = card.querySelector('[data-property-list]');
        });
    };

    const extractColorPreview = (rule) => {
        const hexMatch = rule.match(/#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/);
        if (hexMatch) {
            return hexMatch[0];
        }

        const fnColorMatch = rule.match(/\b(?:rgb|rgba|hsl|hsla)\([^)]+\)/i);
        return fnColorMatch ? fnColorMatch[0] : null;
    };

    const getCount = (locationKey, propertyKey) => state.placements[locationKey]?.[propertyKey] || 0;

    const setCount = (locationKey, propertyKey, nextValue) => {
        if (!state.placements[locationKey]) {
            return;
        }

        if (nextValue <= 0) {
            delete state.placements[locationKey][propertyKey];
            return;
        }

        state.placements[locationKey][propertyKey] = nextValue;
    };

    const moveOne = (propertyKey, sourceKey, destinationKey) => {
        if (!(sourceKey in state.placements) || !(destinationKey in state.placements) || sourceKey === destinationKey) {
            return false;
        }

        const sourceCount = getCount(sourceKey, propertyKey);
        if (sourceCount <= 0) {
            return false;
        }

        setCount(sourceKey, propertyKey, sourceCount - 1);
        setCount(destinationKey, propertyKey, getCount(destinationKey, propertyKey) + 1);
        return true;
    };

    const isSamePayload = (left, right) => Boolean(left && right && left.propertyKey === right.propertyKey && left.sourceKey === right.sourceKey);

    const clearSelectedPayload = () => {
        state.selectedPayload = null;
    };

    const clearSelectorCardHighlight = (resetTrackedKey = true) => {
        Object.values(state.selectorCardLookup).forEach((card) => card.classList.remove('is-target-active'));
        if (resetTrackedKey) {
            state.lastHighlightedSelectorKey = null;
        }
    };

    const movePayloadTo = (payload, destination) => {
        if (state.isCompleted || state.isCompletionSubmitting) {
            return false;
        }

        if (!payload || !payload.sourceKey || !payload.propertyKey) {
            return false;
        }

        if (moveOne(payload.propertyKey, payload.sourceKey, destination)) {
            clearSelectedPayload();
            state.pinnedSelectorKey = null;
            state.hoveredSelectorKey = null;
            clearSelectorCardHighlight();
            render();
            return true;
        }

        return false;
    };

    const createChip = (propertyKey, sourceKey) => {
        const count = getCount(sourceKey, propertyKey);
        if (count <= 0) {
            return null;
        }

        const rule = state.propertyCatalog[propertyKey].rule;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'property-chip';
        chip.draggable = true;
        chip.dataset.propertyKey = propertyKey;
        chip.dataset.sourceKey = sourceKey;
        chip.setAttribute('aria-pressed', isSamePayload(state.selectedPayload, { propertyKey, sourceKey }) ? 'true' : 'false');

        if (isSamePayload(state.selectedPayload, { propertyKey, sourceKey })) {
            chip.classList.add('is-selected');
        }

        const colorPreviewValue = extractColorPreview(rule);
        if (colorPreviewValue) {
            const swatch = document.createElement('span');
            swatch.className = 'property-chip__swatch';
            swatch.style.backgroundColor = colorPreviewValue;
            swatch.title = colorPreviewValue;
            chip.append(swatch);
        }

        const label = document.createElement('span');
        label.className = 'property-chip__label';
        label.textContent = rule;
        chip.append(label);

        if (count > 1) {
            const countBadge = document.createElement('span');
            countBadge.className = 'property-chip__count';
            countBadge.textContent = String(count);
            chip.append(countBadge);
        }

        chip.addEventListener('dragstart', (event) => {
            if (state.isCompleted || state.isCompletionSubmitting) {
                event.preventDefault();
                return;
            }
            state.draggedPayload = { propertyKey, sourceKey };
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('application/json', JSON.stringify(state.draggedPayload));
            event.dataTransfer.setData('text/plain', `${sourceKey}|${propertyKey}`);
        });

        chip.addEventListener('dragend', () => {
            state.draggedPayload = null;
            document.querySelectorAll('.drop-zone').forEach((zone) => zone.classList.remove('is-over'));
        });

        chip.addEventListener('click', (event) => {
            event.stopPropagation();
            if (state.isCompleted || state.isCompletionSubmitting) {
                return;
            }
            state.selectedPayload = isSamePayload(state.selectedPayload, { propertyKey, sourceKey }) ? null : { propertyKey, sourceKey };
            renderLists();
        });

        chip.addEventListener('dblclick', (event) => {
            event.stopPropagation();
            if (state.isCompleted || state.isCompletionSubmitting) {
                return;
            }
            if (sourceKey !== 'pool' && moveOne(propertyKey, sourceKey, 'pool')) {
                clearSelectedPayload();
                render();
            }
        });

        return chip;
    };

    const renderLocationList = (locationKey, searchQuery) => {
        const listNode = state.listNodes[locationKey];
        if (!listNode) {
            return;
        }

        listNode.innerHTML = '';
        const keysToRender = locationKey === 'pool'
            ? state.poolOrder.filter((propertyKey) => getCount('pool', propertyKey) > 0 && (!searchQuery || state.propertyCatalog[propertyKey].rule.toLowerCase().includes(searchQuery)))
            : Object.keys(state.placements[locationKey] || {}).sort((leftKey, rightKey) => state.propertyCatalog[leftKey].rule.localeCompare(state.propertyCatalog[rightKey].rule));

        if (keysToRender.length === 0) {
            const emptyState = document.createElement('p');
            emptyState.className = 'empty-zone';
            emptyState.textContent = locationKey === 'pool'
                ? (searchQuery ? 'No properties match this search.' : 'Drag or select properties from here.')
                : 'Drop or tap selected properties here.';
            listNode.appendChild(emptyState);
            return;
        }

        keysToRender.forEach((propertyKey) => {
            const chip = createChip(propertyKey, locationKey);
            if (chip) {
                listNode.appendChild(chip);
            }
        });
    };

    const renderLists = () => {
        if (state.selectedPayload && getCount(state.selectedPayload.sourceKey, state.selectedPayload.propertyKey) <= 0) {
            clearSelectedPayload();
        }

        const searchQuery = (propertySearchInput?.value || '').trim().toLowerCase();
        renderLocationList('pool', searchQuery);
        state.selectorKeys.forEach((selectorKey) => renderLocationList(selectorKey, searchQuery));
    };

    const currentPlayerCss = () => state.selectorDefinitions.map((selectorDefinition) => {
        const rules = Object.keys(state.placements[selectorDefinition.key] || {})
            .map((propertyKey) => Array.from({ length: getCount(selectorDefinition.key, propertyKey) }, () => state.propertyCatalog[propertyKey].rule).join(' '))
            .join(' ');
        return `${state.selectorLookup[selectorDefinition.key]} { ${rules} }`;
    }).join('\n');

    const renderPreviewStyles = () => {
        if (livePreview instanceof HTMLIFrameElement) {
            livePreview.srcdoc = buildPreviewDocument(state.html, currentPlayerCss());
        }
    };

    const selectorState = (selectorKey) => {
        const requiredMap = state.requiredBySelector[selectorKey] || {};
        const placedMap = state.placements[selectorKey] || {};
        let mismatch = false;
        let missing = false;

        Object.keys(placedMap).forEach((propertyKey) => {
            const placedCount = getCount(selectorKey, propertyKey);
            const requiredCount = requiredMap[propertyKey] || 0;
            if (requiredCount === 0 || placedCount > requiredCount) {
                mismatch = true;
            }
        });

        Object.keys(requiredMap).forEach((propertyKey) => {
            if (getCount(selectorKey, propertyKey) < requiredMap[propertyKey]) {
                missing = true;
            }
        });

        return { mismatch, complete: !mismatch && !missing };
    };

    const requiredTotalBySelector = (selectorKey) => Object.values(state.requiredBySelector[selectorKey] || {}).reduce((sum, value) => sum + value, 0);

    const renderSelectorStates = () => {
        state.selectorKeys.forEach((selectorKey) => {
            const card = state.selectorCardLookup[selectorKey];
            if (!card) {
                return;
            }

            const selectorStatus = selectorState(selectorKey);
            const placedTotal = Object.values(state.placements[selectorKey] || {}).reduce((sum, value) => sum + value, 0);
            const requiredTotal = requiredTotalBySelector(selectorKey);
            const metaNode = state.selectorMetaLookup[selectorKey];
            card.classList.remove('is-target-danger', 'is-target-complete');

            if (selectorStatus.complete) {
                card.classList.add('is-target-complete');
            } else if (selectorStatus.mismatch) {
                card.classList.add('is-target-danger');
            }

            if (metaNode) {
                metaNode.textContent = `${placedTotal}/${requiredTotal} props`;
            }
        });
    };

    const renderProgress = () => {
        let correctCount = 0;
        let hasMismatch = false;
        let allComplete = state.selectorKeys.length > 0;

        state.selectorKeys.forEach((selectorKey) => {
            const requiredMap = state.requiredBySelector[selectorKey] || {};
            Object.keys(requiredMap).forEach((propertyKey) => {
                correctCount += Math.min(getCount(selectorKey, propertyKey), requiredMap[propertyKey]);
            });

            const selectorStatus = selectorState(selectorKey);
            hasMismatch = hasMismatch || selectorStatus.mismatch;
            allComplete = allComplete && selectorStatus.complete;
        });

        const progressPercent = state.totalCount > 0 ? Math.round((correctCount / state.totalCount) * 100) : 0;
        if (progressBarFill) {
            progressBarFill.style.width = `${progressPercent}%`;
        }

        if (allComplete && correctCount === state.totalCount && state.totalCount > 0) {
            setStatus('Complete', true);
            submitCompletion();
        } else {
            setStatus(hasMismatch ? 'Mismatch detected' : 'In progress');
        }
    };

    const render = () => {
        renderLists();
        renderPreviewStyles();
        renderSelectorStates();
        renderProgress();
    };

    const scrollSelectorCardIntoView = (key) => {
        if (!identifiersScrollContainer || !key || !state.selectorCardLookup[key]) {
            return;
        }
        state.selectorCardLookup[key].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    };

    const highlightSelectorCard = (key, shouldAutoScroll = false) => {
        const previousKey = state.lastHighlightedSelectorKey;
        clearSelectorCardHighlight(false);
        if (!key || !state.selectorCardLookup[key]) {
            state.lastHighlightedSelectorKey = null;
            return;
        }

        state.selectorCardLookup[key].classList.add('is-target-active');
        if (shouldAutoScroll && key !== previousKey) {
            scrollSelectorCardIntoView(key);
        }
        state.lastHighlightedSelectorKey = key;
    };

    const elementMatchesSelector = (element, selector) => {
        if (!selector) {
            return false;
        }

        try {
            return element.matches(selector);
        } catch (error) {
            return false;
        }
    };

    const resolveSelectorKeyFromTarget = (element) => {
        if (!element || element.nodeType !== 1) {
            return null;
        }

        let currentElement = element;
        let depth = 0;
        let bestMatch = null;

        while (currentElement && currentElement.nodeType === 1 && currentElement.tagName !== 'HTML') {
            state.selectorDefinitions.forEach((selectorDefinition, index) => {
                const selector = selectorDefinition.matchSelector || selectorDefinition.selector;
                if (!elementMatchesSelector(currentElement, selector)) {
                    return;
                }

                const candidate = {
                    key: selectorDefinition.key,
                    depth,
                    specificity: selectorDefinition.specificity || 0,
                    order: index,
                };

                if (
                    bestMatch === null
                    || candidate.depth < bestMatch.depth
                    || (candidate.depth === bestMatch.depth && candidate.specificity > bestMatch.specificity)
                    || (candidate.depth === bestMatch.depth && candidate.specificity === bestMatch.specificity && candidate.order > bestMatch.order)
                ) {
                    bestMatch = candidate;
                }
            });

            if (bestMatch !== null && bestMatch.depth === depth) {
                return bestMatch.key;
            }

            currentElement = currentElement.parentElement;
            depth += 1;
        }

        return bestMatch?.key || null;
    };

    const attachTargetInspectorHandlers = (frame) => {
        if (frame.dataset.inspectorAttached === '1') {
            return;
        }

        const doc = frame.contentDocument;
        if (!doc?.body) {
            return;
        }

        frame.dataset.inspectorAttached = '1';

        doc.body.querySelectorAll('a,button,input,select,textarea,[role="button"]').forEach((node) => {
            node.setAttribute('tabindex', '-1');
            node.setAttribute('aria-disabled', 'true');
        });

        doc.body.addEventListener('click', (event) => {
            event.preventDefault();
            const key = resolveSelectorKeyFromTarget(event.target);
            state.pinnedSelectorKey = key;
            highlightSelectorCard(key, true);
        });

        const handleTargetMove = (event) => {
            if (state.pinnedSelectorKey !== null) {
                return;
            }
            state.hoveredSelectorKey = resolveSelectorKeyFromTarget(event.target);
            highlightSelectorCard(state.hoveredSelectorKey, true);
        };

        doc.body.addEventListener('mousemove', handleTargetMove);
        doc.body.addEventListener('mouseover', handleTargetMove);

        doc.addEventListener('mouseleave', () => {
            state.hoveredSelectorKey = null;
            if (state.pinnedSelectorKey === null) {
                clearSelectorCardHighlight();
            }
        });
    };

    const attachDropHandlers = () => {
        document.querySelectorAll('.drop-zone').forEach((zone) => {
            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                zone.classList.add('is-over');
            });

            zone.addEventListener('dragleave', () => zone.classList.remove('is-over'));

            zone.addEventListener('drop', (event) => {
                event.preventDefault();
                zone.classList.remove('is-over');
                const destination = zone.dataset.dropKey || 'pool';
                const rawJson = event.dataTransfer.getData('application/json');
                const rawText = event.dataTransfer.getData('text/plain');
                let payload = null;

                if (rawJson) {
                    try {
                        payload = JSON.parse(rawJson);
                    } catch (error) {
                        payload = null;
                    }
                }

                if (!payload && rawText.includes('|')) {
                    const [sourceKey, propertyKey] = rawText.split('|');
                    payload = { sourceKey, propertyKey };
                }

                movePayloadTo(payload || state.draggedPayload, destination);
            });

            zone.addEventListener('click', (event) => {
                if (!event.target.closest('.property-chip')) {
                    movePayloadTo(state.selectedPayload, zone.dataset.dropKey || 'pool');
                }
            });
        });
    };

    const loadTargetPreviews = () => {
        const targetDocument = buildPreviewDocument(state.html, state.css);
        targetPreviews.forEach((preview) => {
            if (!(preview instanceof HTMLIFrameElement)) {
                return;
            }

            preview.addEventListener('load', () => {
                fitPreviewFrame(preview);
                attachTargetInspectorHandlers(preview);
            }, { once: false });
            preview.srcdoc = targetDocument;
            preview.hidden = false;
        });

        targetFrames.forEach((frame) => {
            const loader = frame.querySelector('[data-source-loader]');
            if (loader instanceof HTMLElement) {
                loader.hidden = true;
            }
        });
    };

    allPreviewFrames.forEach((frame) => {
        frame.addEventListener('load', () => fitPreviewFrame(frame), { once: false });
    });

    const resetGame = () => {
        if (state.isCompleted || state.isCompletionSubmitting) {
            return;
        }

        state.selectorKeys.forEach((selectorKey) => {
            state.placements[selectorKey] = {};
        });
        state.placements.pool = { ...state.totalRequiredByProperty };
        state.hoveredSelectorKey = null;
        state.pinnedSelectorKey = null;
        clearSelectorCardHighlight();
        clearSelectedPayload();
        render();
    };

    const clampTargetWidth = (value) => {
        const gridRect = targetGrid.getBoundingClientRect();
        const minTargetWidth = 360;
        const maxTargetWidth = Math.max(420, gridRect.width - 520);
        return Math.max(minTargetWidth, Math.min(maxTargetWidth, value));
    };

    const setTargetWidth = (value) => {
        if (!targetGrid) {
            return;
        }
        targetGrid.style.setProperty('--target-width', `${clampTargetWidth(value)}px`);
    };

    const initResizeHandle = () => {
        if (!splitHandle || !targetGrid) {
            return;
        }

        splitHandle.addEventListener('mousedown', () => {
            if (window.matchMedia('(max-width: 1220px)').matches) {
                return;
            }
            state.isResizing = true;
            document.body.classList.add('is-resizing-split');
        });

        window.addEventListener('mousemove', (event) => {
            if (!state.isResizing) {
                return;
            }
            const gridRect = targetGrid.getBoundingClientRect();
            setTargetWidth(gridRect.right - event.clientX);
        });

        window.addEventListener('mouseup', () => {
            state.isResizing = false;
            document.body.classList.remove('is-resizing-split');
        });

        window.addEventListener('resize', () => {
            const current = parseInt(getComputedStyle(targetGrid).getPropertyValue('--target-width'), 10);
            if (!Number.isNaN(current)) {
                setTargetWidth(current);
            }
        });
    };

    const boot = async () => {
        runOpeningEffect();

        if (!challengeConfig.hasChallenge || !challengeConfig.htmlSource || !challengeConfig.cssSource) {
            setStatus('Open a challenge first');
            if (livePreview instanceof HTMLIFrameElement) {
                livePreview.srcdoc = buildPreviewDocument('<div class="game-source-error">Choose a challenge from the challenge page.</div>', '.game-source-error { max-width: 320px; border: 3px solid #26190f; border-radius: 18px; background: #ffd166; padding: 18px; color: #26190f; font-weight: 900; text-align: center; box-shadow: 6px 6px 0 #26190f; }');
            }
            return;
        }

        try {
            setStatus('Loading challenge');
            const [html, css] = await Promise.all([
                fetchText(challengeConfig.htmlSource),
                fetchText(challengeConfig.cssSource),
            ]);
            state.html = html;
            state.css = css;
            initializeChallengeData(css);
            renderSelectorCards();
            attachDropHandlers();
            loadTargetPreviews();
            render();
            setStatus('In progress');
        } catch (error) {
            console.error(error);
            setStatus('Challenge load failed');
            const errorDocument = buildPreviewDocument(
                '<div class="game-source-error">Target source could not be loaded.</div>',
                '.game-source-error { max-width: 320px; border: 3px solid #26190f; border-radius: 18px; background: #ffd166; padding: 18px; color: #26190f; font-weight: 900; text-align: center; box-shadow: 6px 6px 0 #26190f; }'
            );
            if (livePreview instanceof HTMLIFrameElement) {
                livePreview.srcdoc = errorDocument;
            }
            targetPreviews.forEach((preview) => {
                if (preview instanceof HTMLIFrameElement) {
                    preview.srcdoc = errorDocument;
                    preview.hidden = false;
                }
            });
            targetFrames.forEach((frame) => {
                const loader = frame.querySelector('[data-source-loader]');
                if (loader instanceof HTMLElement) {
                    loader.hidden = true;
                }
            });
        }
    };

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-source-preview]')) {
            state.pinnedSelectorKey = null;
        }
    });

    resetButton?.addEventListener('click', resetGame);
    propertySearchInput?.addEventListener('input', renderLists);
    giveUpForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (state.isCompleted || state.isCompletionSubmitting) {
            return;
        }

        exitModal?.show();
    });
    confirmGiveUpButton?.addEventListener('click', () => {
        if (!giveUpForm || state.isCompleted || state.isCompletionSubmitting) {
            return;
        }

        state.skipUnloadWarning = true;
        exitModal?.hide();
        giveUpForm.submit();
    });
    window.addEventListener('beforeunload', beforeUnloadHandler);
    previewModal?.addEventListener('shown.bs.modal', () => requestAnimationFrame(() => targetPreviews.forEach((frame) => fitPreviewFrame(frame))));
    if (allPreviewFrames.length > 0 && 'ResizeObserver' in window) {
        const previewObserver = new ResizeObserver(() => allPreviewFrames.forEach((frame) => fitPreviewFrame(frame)));
        allPreviewFrames.forEach((frame) => frame.parentElement && previewObserver.observe(frame.parentElement));
    }
    window.addEventListener('resize', () => allPreviewFrames.forEach((frame) => fitPreviewFrame(frame)));
    initResizeHandle();
    startGameplayTimer();
    boot();
})();
</script>
