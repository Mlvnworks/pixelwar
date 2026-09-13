<?php
$gameChallengeId = (int) ($_GET['challenge_id'] ?? 0);
$gameRoomId = (int) ($_GET['room_id'] ?? 0);
$gamePvpId = (int) ($_GET['pvp_id'] ?? 0);
$gameChallenge = null;
$gameUserChallenge = null;
$gameChallengeError = '';
$gameRoom = null;
$gameRoomDeadlineIso = '';
$gameRoomTimerLimit = 0;
$gameRoomStrictMode = false;
$gamePusherEnabled = isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured();
$gameShouldPlayOpening = (string) ($_GET['intro'] ?? '') === '1' || $gameRoomId > 0 || $gamePvpId > 0;

if (
    $gameChallengeId <= 0
    && $gameRoomId <= 0
    && $gamePvpId <= 0
    && $userChallengeRepository instanceof UserChallengeRepository
) {
    $latestSoloRun = $userChallengeRepository->findLatestOngoingSolo((int) ($_SESSION['user_id'] ?? 0));
    if ($latestSoloRun !== null) {
        $gameChallengeId = (int) ($latestSoloRun['challenge_id'] ?? 0);
    }
}

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

        if ($gameRoomId > 0) {
            if (!$roomRepository instanceof RoomRepository || !$roomPlayerRepository instanceof RoomPlayerRepository) {
                throw new RuntimeException('Room progress is not available.');
            }

            $gameRoom = $roomRepository->findById($gameRoomId);

            if ($gameRoom === null) {
                throw new RuntimeException('Room not found.');
            }

            if ((int) ($gameRoom['challenge_id'] ?? 0) !== $gameChallengeId) {
                throw new RuntimeException('This room is not linked to the selected challenge.');
            }

            if ((int) ($gameRoom['status'] ?? 1) !== 1) {
                throw new RuntimeException('This room is closed.');
            }

            if (trim((string) ($gameRoom['started_at'] ?? '')) === '') {
                throw new RuntimeException('This room session has not started yet.');
            }

            if (trim((string) ($gameRoom['ended_at'] ?? '')) !== '' || (int) ($gameRoom['status'] ?? 1) !== 1) {
                throw new RuntimeException('This room is already ended.');
            }

            $gameRoomPlayer = $roomPlayerRepository->findByUserAndRoom($gameUserId, $gameRoomId);
            if ($gameRoomPlayer === null) {
                throw new RuntimeException('You have not joined this room yet.');
            }

            if (
                (int) ($gameRoomPlayer['status'] ?? 0) === 3
                && trim((string) ($gameRoom['ended_at'] ?? '')) === ''
            ) {
                throw new RuntimeException('You already gave up this room and cannot re-enter while it is still ongoing.');
            }
        }

        if ($gamePvpId > 0) {
            if (!$pvpPlayerRepository instanceof PvpPlayerRepository || !$pvpMatchRepository instanceof PvpMatchRepository) {
                throw new RuntimeException('1v1 match progress is not available.');
            }

            $gamePvpMatch = $pvpMatchRepository->findById($gamePvpId);
            if ($gamePvpMatch === null) {
                throw new RuntimeException('This 1v1 match is no longer available.');
            }

            if ((int) ($gamePvpMatch['challenge_id'] ?? 0) !== $gameChallengeId) {
                throw new RuntimeException('This 1v1 match is not linked to the selected challenge.');
            }

            $gamePvpPlayer = $pvpPlayerRepository->findByMatchAndUser($gameUserId > 0 ? $gamePvpId : 0, $gameUserId);
            if ($gamePvpPlayer === null) {
                throw new RuntimeException('You are not part of this 1v1 match.');
            }

            if (in_array((int) ($gamePvpPlayer['status'] ?? 0), [2, 3], true)) {
                throw new RuntimeException('This 1v1 match is already ended.');
            }
        }

        $gameChallengeIsPublic = (int) ($gameChallenge['status'] ?? 0) === 1;
        $gameIsRoomChallengeAccess = $gameRoomId > 0 && $gameRoom !== null;
        if (!$gameChallengeIsPublic && !$gameIsRoomChallengeAccess) {
            throw new RuntimeException('This challenge is not available publicly right now.');
        }

        $gameUserChallenge = $userChallengeRepository->startOrFindOngoing($gameUserId, $gameChallengeId, $gameRoomId, $gamePvpId);

        if (!empty($gameUserChallenge['was_created']) && $activityLogRepository instanceof ActivityLogRepository) {
            $activityLogRepository->create(
                $gameUserId,
                'challenge',
                'Started challenge "' . (string) ($gameChallenge['name'] ?? 'Challenge') . '".'
            );
        }

        if ($gameRoomId > 0 && $roomPlayerRepository instanceof RoomPlayerRepository) {
            $roomPlayerRepository->markSolving($gameUserId, $gameRoomId);

            if ($gameRoom !== null) {
                $startedAtTs = strtotime((string) ($gameRoom['started_at'] ?? ''));
                $gameRoomTimerLimit = max(0, (int) ($gameRoom['timer_limit'] ?? 0));
                $gameRoomStrictMode = (int) ($gameRoom['strict_mode'] ?? 0) === 1;
                if ($startedAtTs !== false && $gameRoomTimerLimit > 0) {
                    $gameRoomDeadlineIso = date(DATE_ATOM, $startedAtTs + ($gameRoomTimerLimit * 60));
                }
            }

            if (isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured()) {
                try {
                    $pusherService->trigger(
                        'room-' . $gameRoomId,
                        'player-status',
                        [
                            'user_id' => $gameUserId,
                            'status_label' => 'solving',
                            'started_at' => date(DATE_ATOM),
                            'completed_at' => '',
                        ]
                    );
                } catch (Throwable $pusherError) {
                    error_log('Pixelwar room solving pusher error: ' . $pusherError->getMessage());
                }
            }
        }
    } catch (Throwable $err) {
        error_log('Pixelwar gameplay challenge start error: ' . $err->getMessage());
        $message = $err->getMessage();

        if ($message === 'Challenge not found.') {
            $gameChallengeError = 'This challenge is no longer available. Please choose another one from the library.';
        } elseif ($message === 'This challenge is not available publicly right now.') {
            $gameChallengeError = $message;
        } elseif (in_array($message, ['Room not found.', 'This room is closed.', 'This room session has not started yet.', 'This room is already ended.', 'This room is not linked to the selected challenge.', 'You have not joined this room yet.', 'You already gave up this room and cannot re-enter while it is still ongoing.'], true)) {
            $gameChallengeError = $message;
        } else {
            $gameChallengeError = APP_DEBUG ? $message : 'Unable to start this challenge. Please try another one.';
        }
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

        <div id="game-opening-effect" class="game-opening-effect<?= $gameShouldPlayOpening ? ' is-playing' : '' ?>" aria-hidden="true">
            <div class="game-opening-effect__panel">
                <span class="game-opening-effect__eyebrow">Loading Arena</span>
                <strong>Pixelwar</strong>
                <span class="game-opening-effect__bar"></span>
            </div>
        </div>

        <div id="completion-confetti" class="completion-confetti" aria-hidden="true"></div>
        <div id="gameplay-streak-pop" class="gameplay-streak-pop" aria-live="polite"></div>
        <div id="identifier-complete-pop" class="identifier-complete-pop" aria-live="polite"></div>
        <div id="rocket-warning-flash" class="rocket-warning-flash" aria-hidden="true"></div>
        <div id="rocket-warning-pop" class="rocket-warning-pop" aria-live="polite">Rocket incoming</div>
        <div id="rocket-layer" class="rocket-layer" aria-hidden="true"></div>

        <div class="challenge-shell border-4 border-arcade-ink/10 bg-arcade-panel/80 p-2">
            <aside class="floating-hud" aria-live="polite">
                <div class="hud-pill gameplay-status-pill">
                    <span id="game-status">Waiting for your first move.</span>
                    <?php if ($gameUserChallenge !== null) : ?>
                        <span class="gameplay-time" id="gameplay-time" data-started-at="<?= htmlspecialchars($gameStartedAtIso, ENT_QUOTES, 'UTF-8') ?>">00:00</span>
                        <?php if ($gameRoomDeadlineIso !== '') : ?>
                            <span class="gameplay-time" id="room-session-timer" data-deadline-at="<?= htmlspecialchars($gameRoomDeadlineIso, ENT_QUOTES, 'UTF-8') ?>">00:00</span>
                        <?php endif; ?>
                        <?php if ($gameRoomStrictMode && $gameRoomId > 0) : ?>
                            <button type="button" id="strict-mode-submit-button" class="give-up-button give-up-button--submit">Submit</button>
                        <?php endif; ?>
                        <form id="give-up-form" class="give-up-form" action="./?c=pixelwar" method="post">
                            <?= pixelwarCsrfField() ?>
                            <input type="hidden" name="gameplay_action" value="give_up">
                            <input type="hidden" name="challenge_id" value="<?= (int) $gameChallengeId ?>">
                            <input type="hidden" name="room_id" value="<?= (int) $gameRoomId ?>">
                            <input type="hidden" name="pvp_id" value="<?= (int) $gamePvpId ?>">
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
                            <div class="preview-card-actions">
                                <button type="button" class="preview-expand-button" aria-label="Expand live preview" title="Expand live preview">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M15 3h6v6" />
                                        <path d="m21 3-7 7" />
                                        <path d="M9 21H3v-6" />
                                        <path d="m3 21 7-7" />
                                    </svg>
                                </button>
                                <button type="button" class="mobile-preview-toggle rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-3 py-1.5 text-[11px] font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" data-bs-toggle="modal" data-bs-target="#mobile-preview-modal">
                                    View Target
                                </button>
                            </div>
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
                            <button id="reset-layout-btn" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-arcade-peach/60 px-3 py-2 text-xs font-semibold text-arcade-ink transition hover:bg-arcade-yellow/70" aria-label="Reset placements" title="Reset placements">
                                <svg class="reset-layout-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M3 12a9 9 0 1 0 3-6.7" />
                                    <path d="M3 3v6h6" />
                                </svg>
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

<div class="modal fade gameplay-reset-modal" id="gameplay-reset-modal" tabindex="-1" aria-labelledby="gameplay-reset-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Reset Placement</p>
                    <h2 id="gameplay-reset-modal-title" class="modal-title mt-2 text-xl font-bold">Reset all placed properties?</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <p class="text-sm font-semibold leading-7 text-arcade-ink/70">
                    This will return every placed property to the pool. Your active run and timer will continue.
                </p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirm-reset-layout-button" class="rounded-xl border-2 border-arcade-ink bg-arcade-orange px-5 py-2 text-sm font-bold text-white shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-coral">Reset Placement</button>
                </div>
            </div>
        </div>
    </div>
</div>

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

<div class="modal fade live-compare-modal" id="live-compare-modal" tabindex="-1" aria-labelledby="live-compare-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Design Compare</p>
                    <h2 id="live-compare-modal-title" class="modal-title mt-2 text-xl font-bold"><?= htmlspecialchars($gameChallengeTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="live-compare-grid">
                    <section class="live-compare-panel">
                        <div class="live-compare-panel__header">
                            <span>Current Design</span>
                        </div>
                        <div class="live-compare-frame">
                            <iframe class="game-source-preview" title="Expanded live challenge preview" sandbox="allow-same-origin" data-live-compare-preview></iframe>
                        </div>
                    </section>
                    <section class="live-compare-panel">
                        <div class="live-compare-panel__header">
                            <span>Target Design</span>
                        </div>
                        <div
                            class="live-compare-frame"
                            data-source-target-frame
                            data-html-source="<?= htmlspecialchars($gameChallengeHtmlSource, ENT_QUOTES, 'UTF-8') ?>"
                            data-css-source="<?= htmlspecialchars($gameChallengeCssSource, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="game-source-loader" data-source-loader <?= $gameChallenge === null ? 'hidden' : '' ?>>
                                <span class="game-source-loader__spinner" aria-hidden="true"></span>
                                <strong>Loading target...</strong>
                            </div>
                            <iframe class="game-source-preview" title="Expanded challenge target preview" sandbox="allow-same-origin" data-source-preview hidden></iframe>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade gameplay-complete-modal" id="gameplay-complete-modal" tabindex="-1" aria-labelledby="gameplay-complete-modal-title" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Challenge Complete</p>
                    <h2 id="gameplay-complete-modal-title" class="modal-title mt-2 text-xl font-bold">Nice work.</h2>
                </div>
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

<div class="modal fade gameplay-strict-result-modal" id="gameplay-strict-result-modal" tabindex="-1" aria-labelledby="gameplay-strict-result-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Strict Mode Result</p>
                    <h2 id="gameplay-strict-result-modal-title" class="modal-title mt-2 text-xl font-bold">Run submitted.</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <div class="gameplay-complete-summary">
                    <div class="gameplay-complete-card">
                        <span class="gameplay-complete-card__label">Challenge</span>
                        <strong id="gameplay-strict-result-name">Pixelwar</strong>
                    </div>
                    <div class="gameplay-complete-grid">
                        <div class="gameplay-complete-card">
                            <span class="gameplay-complete-card__label">Match Score</span>
                            <strong id="gameplay-strict-result-score">0%</strong>
                        </div>
                        <div class="gameplay-complete-card">
                            <span class="gameplay-complete-card__label">Selectors</span>
                            <strong id="gameplay-strict-result-selectors">0</strong>
                        </div>
                        <div class="gameplay-complete-card">
                            <span class="gameplay-complete-card__label">Properties</span>
                            <strong id="gameplay-strict-result-properties">0</strong>
                        </div>
                    </div>
                    <div class="gameplay-complete-card">
                        <span class="gameplay-complete-card__label">Result</span>
                        <strong id="gameplay-strict-result-message">Your run has ended.</strong>
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <a href="./?c=home" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-center text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Back Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (($gameRoomId > 0 || $gamePvpId > 0) && $gamePusherEnabled) : ?>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<?php endif; ?>
<script>
(() => {
    if (window.history?.replaceState) {
        try {
            const sanitizedUrl = new URL(window.location.href);
            if (sanitizedUrl.searchParams.has('challenge_id')) {
                sanitizedUrl.searchParams.delete('challenge_id');
                window.history.replaceState({}, '', sanitizedUrl.href);
            }
        } catch (error) {
            console.warn('Unable to sanitize gameplay URL.', error);
        }
    }

    const challengeConfig = {
        htmlSource: <?= json_encode($gameChallengeHtmlSource, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        cssSource: <?= json_encode($gameChallengeCssSource, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        hasChallenge: <?= $gameChallenge !== null ? 'true' : 'false' ?>,
        challengeId: <?= (int) $gameChallengeId ?>,
        roomId: <?= (int) $gameRoomId ?>,
        pvpId: <?= (int) $gamePvpId ?>,
        currentUserId: <?= (int) ($_SESSION['user_id'] ?? 0) ?>,
        roomDeadlineAt: <?= json_encode($gameRoomDeadlineIso, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        roomTimerLimit: <?= (int) $gameRoomTimerLimit ?>,
        strictMode: <?= $gameRoomStrictMode ? 'true' : 'false' ?>,
        userChallengeId: <?= (int) $gameUserChallengeId ?>,
        challengeTitle: <?= json_encode($gameChallengeTitle, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        csrfToken: <?= json_encode(pixelwarCsrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        pusherKey: <?= json_encode($gamePusherEnabled ? (string) PUSHER_KEY : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        pusherCluster: <?= json_encode($gamePusherEnabled ? (string) PUSHER_CLUSTER : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        startSoundUrl: <?= json_encode('assets/sound effects/game_start.mp3', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        glassCrackSoundUrl: <?= json_encode('assets/sound effects/glass_crack.mp3', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        correctSoundUrl: <?= json_encode('assets/sound effects/correct.mp3', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        wrongSoundUrl: <?= json_encode('assets/sound effects/wrong.mp3', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        rocketLaunchSoundUrl: <?= json_encode('assets/sound effects/rocket-luanch.mp3', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        rocketExplosionSoundUrl: <?= json_encode('assets/sound effects/rocket-explosion.mp3', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        backgroundMusicUrl: <?= json_encode('assets/sound effects/bg_music.mp3', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        cheerSoundUrls: <?= json_encode([
            'assets/sound effects/Game voice cheer/Good Job!.mp3',
            'assets/sound effects/Game voice cheer/Awesome!.mp3',
            'assets/sound effects/Game voice cheer/great!.wav',
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '[]' ?>,
        endedRedirectUrl: './?c=home&room_notice=ended_incomplete',
    };

    const selectorGrid = document.getElementById('selector-card-grid');
    const livePreview = document.querySelector('[data-live-preview]');
    const liveComparePreview = document.querySelector('[data-live-compare-preview]');
    const targetPreviews = Array.from(document.querySelectorAll('[data-source-preview]'));
    const allPreviewFrames = [livePreview, liveComparePreview, ...targetPreviews].filter((frame) => frame instanceof HTMLIFrameElement);
    const targetFrames = Array.from(document.querySelectorAll('[data-source-target-frame]'));
    const statusLabel = document.getElementById('game-status');
    const gameplayTime = document.getElementById('gameplay-time');
    const roomSessionTimer = document.getElementById('room-session-timer');
    const giveUpForm = document.getElementById('give-up-form');
    const progressBarFill = document.getElementById('progress-bar-fill');
    const resetButton = document.getElementById('reset-layout-btn');
    const propertySearchInput = document.getElementById('property-search');
    const strictModeSubmitButton = document.getElementById('strict-mode-submit-button');
    const openingEffect = document.getElementById('game-opening-effect');
    const completionConfetti = document.getElementById('completion-confetti');
    const streakPop = document.getElementById('gameplay-streak-pop');
    const identifierCompletePop = document.getElementById('identifier-complete-pop');
    const rocketWarningFlash = document.getElementById('rocket-warning-flash');
    const rocketWarningPop = document.getElementById('rocket-warning-pop');
    const rocketLayer = document.getElementById('rocket-layer');
    const completionModalElement = document.getElementById('gameplay-complete-modal');
    const completionModal = completionModalElement ? new bootstrap.Modal(completionModalElement) : null;
    const strictResultModalElement = document.getElementById('gameplay-strict-result-modal');
    const strictResultModal = strictResultModalElement ? new bootstrap.Modal(strictResultModalElement) : null;
    const resetModalElement = document.getElementById('gameplay-reset-modal');
    const resetModal = resetModalElement ? new bootstrap.Modal(resetModalElement) : null;
    const exitModalElement = document.getElementById('gameplay-exit-modal');
    const exitModal = exitModalElement ? new bootstrap.Modal(exitModalElement) : null;
    const confirmResetButton = document.getElementById('confirm-reset-layout-button');
    const confirmGiveUpButton = document.getElementById('confirm-give-up-button');
    const targetGrid = document.getElementById('challenge-grid');
    const splitHandle = document.getElementById('split-handle');
    const identifiersScrollContainer = document.querySelector('.identifiers-scroll');
    const previewModal = document.getElementById('mobile-preview-modal');
    const liveCompareModalElement = document.getElementById('live-compare-modal');
    const liveCompareModal = liveCompareModalElement ? new bootstrap.Modal(liveCompareModalElement) : null;
    const liveCompareButton = document.querySelector('.preview-expand-button');

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
        isUnavailable: false,
        strictProgressPercent: null,
        streakCount: 0,
        streakTimerId: null,
        completedSelectorKeys: new Set(),
        identifierCompleteTimerId: null,
        identifierCompleteActiveUntil: 0,
        introFinished: false,
        challengeLoaded: false,
        musicStarted: false,
        rocketTimerId: null,
        rocketCountdownTimerId: null,
        rocketLaunchTimerId: null,
        rocketActive: false,
    };

    let gameplayAudioContext = null;
    let gameStartAudio = null;
    let gameEndAudio = null;
    let glassCrackAudio = null;
    let correctDropAudio = null;
    let wrongDropAudio = null;
    let rocketLaunchAudio = null;
    let rocketExplosionAudio = null;
    let backgroundMusicAudio = null;
    let cheerAudios = [];
    let hasPlayedGameStartSound = false;
    let isGameStartSoundAttempting = false;
    let gameStartSoundRetryDeadline = 0;
    let roomEndSubmitting = false;

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const gameplaySoundIsOn = () => {
        try {
            return localStorage.getItem('pixelwarSound') !== 'off';
        } catch (error) {
            return false;
        }
    };

    const playGameplaySound = (type) => {
        if (!gameplaySoundIsOn()) {
            return;
        }

        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                return;
            }

            gameplayAudioContext = gameplayAudioContext || new AudioContextClass();
            if (gameplayAudioContext.state === 'suspended') {
                gameplayAudioContext.resume();
            }

            const now = gameplayAudioContext.currentTime;
            const oscillator = gameplayAudioContext.createOscillator();
            const gain = gameplayAudioContext.createGain();

            oscillator.type = type === 'pickup' ? 'square' : 'triangle';
            oscillator.frequency.setValueAtTime(type === 'pickup' ? 420 : 540, now);
            oscillator.frequency.exponentialRampToValueAtTime(type === 'pickup' ? 620 : 820, now + (type === 'pickup' ? 0.03 : 0.04));
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(type === 'pickup' ? 0.08 : 0.1, now + 0.012);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + (type === 'pickup' ? 0.07 : 0.11));

            oscillator.connect(gain);
            gain.connect(gameplayAudioContext.destination);
            oscillator.start(now);
            oscillator.stop(now + (type === 'pickup' ? 0.08 : 0.12));
        } catch (error) {
            return;
        }
    };

    const preloadGameStartSound = () => {
        if (!challengeConfig.startSoundUrl) {
            return;
        }

        try {
            gameStartAudio = gameStartAudio || new Audio(challengeConfig.startSoundUrl);
            gameStartAudio.preload = 'auto';
            gameStartAudio.volume = 0.72;
            gameStartAudio.load();
        } catch (error) {
            return;
        }
    };

    const preloadGlassCrackSound = () => {
        if (!challengeConfig.glassCrackSoundUrl) {
            return;
        }

        try {
            glassCrackAudio = glassCrackAudio || new Audio(challengeConfig.glassCrackSoundUrl);
            glassCrackAudio.preload = 'auto';
            glassCrackAudio.volume = 0.82;
            glassCrackAudio.load();
        } catch (error) {
            return;
        }
    };

    const playGlassCrackSound = () => {
        if (!gameplaySoundIsOn() || !challengeConfig.glassCrackSoundUrl) {
            return;
        }

        try {
            preloadGlassCrackSound();
            if (!glassCrackAudio) {
                return;
            }

            glassCrackAudio.currentTime = 0;
            glassCrackAudio.volume = 0.82;
            const playRequest = glassCrackAudio.play();

            if (playRequest && typeof playRequest.catch === 'function') {
                playRequest.catch(() => {});
            }
        } catch (error) {
            return;
        }
    };

    const preloadPropertyDropSounds = () => {
        try {
            if (challengeConfig.correctSoundUrl) {
                correctDropAudio = correctDropAudio || new Audio(challengeConfig.correctSoundUrl);
                correctDropAudio.preload = 'auto';
                correctDropAudio.volume = 0.72;
                correctDropAudio.load();
            }

            if (challengeConfig.wrongSoundUrl) {
                wrongDropAudio = wrongDropAudio || new Audio(challengeConfig.wrongSoundUrl);
                wrongDropAudio.preload = 'auto';
                wrongDropAudio.volume = 0.72;
                wrongDropAudio.load();
            }
        } catch (error) {
            return;
        }
    };

    const playPropertyDropSound = (isCorrect) => {
        if (!gameplaySoundIsOn() || challengeConfig.strictMode) {
            return;
        }

        try {
            preloadPropertyDropSounds();
            const audio = isCorrect ? correctDropAudio : wrongDropAudio;
            if (!audio) {
                return;
            }

            audio.currentTime = 0;
            audio.volume = isCorrect ? 0.72 : 0.76;
            const playRequest = audio.play();

            if (playRequest && typeof playRequest.catch === 'function') {
                playRequest.catch(() => {});
            }
        } catch (error) {
            return;
        }
    };

    const preloadRocketSounds = () => {
        try {
            if (challengeConfig.rocketLaunchSoundUrl) {
                rocketLaunchAudio = rocketLaunchAudio || new Audio(challengeConfig.rocketLaunchSoundUrl);
                rocketLaunchAudio.preload = 'auto';
                rocketLaunchAudio.volume = 0.78;
                rocketLaunchAudio.load();
            }

            if (challengeConfig.rocketExplosionSoundUrl) {
                rocketExplosionAudio = rocketExplosionAudio || new Audio(challengeConfig.rocketExplosionSoundUrl);
                rocketExplosionAudio.preload = 'auto';
                rocketExplosionAudio.volume = 0.86;
                rocketExplosionAudio.load();
            }
        } catch (error) {
            return;
        }
    };

    const playRocketSound = (type) => {
        if (!gameplaySoundIsOn()) {
            return;
        }

        try {
            preloadRocketSounds();
            const audio = type === 'launch' ? rocketLaunchAudio : rocketExplosionAudio;
            if (!audio) {
                return;
            }

            audio.currentTime = 0;
            audio.volume = type === 'launch' ? 0.78 : 0.86;
            const playRequest = audio.play();

            if (playRequest && typeof playRequest.catch === 'function') {
                playRequest.catch(() => {});
            }
        } catch (error) {
            return;
        }
    };

    const stopBackgroundMusic = () => {
        if (!backgroundMusicAudio) {
            return;
        }

        try {
            backgroundMusicAudio.pause();
            backgroundMusicAudio.currentTime = 0;
        } catch (error) {
            return;
        }
    };

    const startBackgroundMusic = () => {
        if (state.musicStarted || !state.introFinished || !state.challengeLoaded || !gameplaySoundIsOn() || !challengeConfig.backgroundMusicUrl) {
            return;
        }

        try {
            backgroundMusicAudio = backgroundMusicAudio || new Audio();
            if (!backgroundMusicAudio.src) {
                backgroundMusicAudio.preload = 'none';
                backgroundMusicAudio.src = challengeConfig.backgroundMusicUrl;
            }

            backgroundMusicAudio.loop = true;
            backgroundMusicAudio.volume = 0.18;
            const playRequest = backgroundMusicAudio.play();
            state.musicStarted = true;

            if (playRequest && typeof playRequest.catch === 'function') {
                playRequest.catch(() => {
                    state.musicStarted = false;
                });
            }
        } catch (error) {
            state.musicStarted = false;
        }
    };

    const stopRocketHazard = () => {
        window.clearInterval(state.rocketTimerId);
        window.clearInterval(state.rocketCountdownTimerId);
        window.clearTimeout(state.rocketLaunchTimerId);
        state.rocketTimerId = null;
        state.rocketCountdownTimerId = null;
        state.rocketLaunchTimerId = null;
        state.rocketActive = false;
        rocketWarningFlash?.classList.remove('is-active');
        rocketWarningPop?.classList.remove('is-visible');
        document.querySelectorAll('.selector-card.is-rocket-target, .selector-card.is-rocket-hit').forEach((card) => {
            card.classList.remove('is-rocket-target', 'is-rocket-hit');
        });
        document.querySelectorAll('.selector-zone.is-rocket-target-zone, .selector-zone.is-rocket-hit-zone').forEach((zone) => {
            zone.classList.remove('is-rocket-target-zone', 'is-rocket-hit-zone');
        });
        if (rocketLayer) {
            rocketLayer.innerHTML = '';
        }
    };

    const scheduleRocketHazard = (delay = null) => {
        if (challengeConfig.strictMode || !rocketLayer || state.rocketActive || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable || !state.challengeLoaded) {
            return;
        }

        window.clearInterval(state.rocketTimerId);
        const rocketInterval = 1 * 60 * 1000;
        const nextRocketAt = Date.now() + (delay ?? rocketInterval);
        state.rocketTimerId = window.setInterval(() => {
            if (challengeConfig.strictMode || state.rocketActive || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable || !state.challengeLoaded) {
                return;
            }

            if (Date.now() >= nextRocketAt) {
                window.clearInterval(state.rocketTimerId);
                state.rocketTimerId = null;
                triggerRocketHazard();
            }
        }, 1000);
    };

    const shatterOnePropertyFromSelector = (selectorKey) => {
        if (!selectorKey || !(selectorKey in state.placements)) {
            return false;
        }

        const placedKeys = Object.keys(state.placements[selectorKey] || {}).filter((propertyKey) => getCount(selectorKey, propertyKey) > 0);
        if (placedKeys.length === 0) {
            return false;
        }

        const propertyKey = placedKeys[Math.floor(Math.random() * placedKeys.length)];
        const chip = Array.from(state.listNodes[selectorKey]?.querySelectorAll('.property-chip') || [])
            .find((node) => node.dataset.propertyKey === propertyKey);
        chip?.classList.add('is-shattering');

        window.setTimeout(() => {
            if (state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
                return;
            }
            moveOne(propertyKey, selectorKey, 'pool');
            if (state.selectedPayload?.sourceKey === selectorKey && state.selectedPayload?.propertyKey === propertyKey) {
                clearSelectedPayload();
            }
            render();
        }, chip ? 420 : 0);

        return true;
    };

    const centerRocketTargetCardForMobile = (targetCard) => {
        if (!(targetCard instanceof HTMLElement)) {
            return;
        }

        if (identifiersScrollContainer instanceof HTMLElement && identifiersScrollContainer.contains(targetCard)) {
            const cardRect = targetCard.getBoundingClientRect();
            const scrollRect = identifiersScrollContainer.getBoundingClientRect();
            const targetScrollTop = identifiersScrollContainer.scrollTop
                + (cardRect.top - scrollRect.top)
                - ((scrollRect.height - cardRect.height) / 2);
            identifiersScrollContainer.scrollTo({
                top: Math.max(0, targetScrollTop),
                behavior: 'auto',
            });
            return;
        }

        targetCard.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'nearest' });
    };

    const getRocketTargetPoint = (targetElement, isMobileLaunch) => {
        const targetRect = targetElement.getBoundingClientRect();
        if (!isMobileLaunch || !(identifiersScrollContainer instanceof HTMLElement) || !identifiersScrollContainer.contains(targetElement)) {
            return {
                x: targetRect.left + (targetRect.width / 2),
                y: targetRect.top + (targetRect.height / 2),
            };
        }

        const scrollRect = identifiersScrollContainer.getBoundingClientRect();
        const visibleTop = Math.max(targetRect.top, scrollRect.top);
        const visibleBottom = Math.min(targetRect.bottom, scrollRect.bottom);
        const visibleHeight = Math.max(0, visibleBottom - visibleTop);

        return {
            x: targetRect.left + (targetRect.width / 2),
            y: visibleHeight > 0
                ? visibleTop + (visibleHeight / 2)
                : targetRect.top + (targetRect.height / 2),
        };
    };

    const launchRocketElement = (targetPoint, launchDirection, onImpact) => {
        if (!rocketLayer) {
            onImpact();
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.className = 'rocket-bomb-canvas';
        rocketLayer.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            canvas.remove();
            onImpact();
            return;
        }

        const rocketLength = 54;
        const hitRadius = 12;
        const explosionDuration = 1.1;
        const thrustAccel = 980;
        const maxSpeed = 620;
        const isMobileRocket = launchDirection === 'top';
        const rocketScale = isMobileRocket ? 1.22 : 1;
        const particles = [];
        let width = 0;
        let height = 0;
        let dpr = 1;
        let animationFrameId = 0;
        let lastTime = null;
        let mode = 'flying';
        let explodeTimer = 0;
        let shake = 0;
        let flashAlpha = 0;
        let hasImpacted = false;
        let hasCleanedUp = false;

        const resizeCanvas = () => {
            dpr = Math.min(window.devicePixelRatio || 1, 2);
            const canvasRect = canvas.getBoundingClientRect();
            width = canvasRect.width || window.innerWidth;
            height = canvasRect.height || window.innerHeight;
            canvas.width = Math.max(1, Math.round(width * dpr));
            canvas.height = Math.max(1, Math.round(height * dpr));
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };
        resizeCanvas();

        const canvasRect = canvas.getBoundingClientRect();
        const localTargetPoint = {
            x: targetPoint.x - canvasRect.left,
            y: targetPoint.y - canvasRect.top,
        };
        const startX = launchDirection === 'top' ? localTargetPoint.x : width + 88;
        const startY = launchDirection === 'top' ? -88 : Math.max(72, Math.min(height - 72, localTargetPoint.y - 72));
        const dx = localTargetPoint.x - startX;
        const dy = localTargetPoint.y - startY;
        const distance = Math.hypot(dx, dy) || 1;
        const rocket = {
            x: startX,
            y: startY,
            targetX: localTargetPoint.x,
            targetY: localTargetPoint.y,
            dirX: dx / distance,
            dirY: dy / distance,
            angle: Math.atan2(dy, dx),
            speed: 0,
            vx: 0,
            vy: 0,
        };

        const drawRoundedRocketPath = (length, bodyWidth) => {
            ctx.beginPath();
            ctx.moveTo(-length * 0.30, -bodyWidth * 0.5);
            ctx.lineTo(length * 0.20, -bodyWidth * 0.5);
            ctx.quadraticCurveTo(length * 0.34, -bodyWidth * 0.5, length * 0.32, -bodyWidth * 0.42);
            ctx.lineTo(length * 0.32, bodyWidth * 0.42);
            ctx.quadraticCurveTo(length * 0.34, bodyWidth * 0.5, length * 0.20, bodyWidth * 0.5);
            ctx.lineTo(-length * 0.30, bodyWidth * 0.5);
            ctx.quadraticCurveTo(-length * 0.42, bodyWidth * 0.5, -length * 0.42, 0);
            ctx.quadraticCurveTo(-length * 0.42, -bodyWidth * 0.5, -length * 0.30, -bodyWidth * 0.5);
            ctx.closePath();
        };

        const spawnExhaust = () => {
            const tailX = rocket.x - Math.cos(rocket.angle) * (rocketLength * 0.55);
            const tailY = rocket.y - Math.sin(rocket.angle) * (rocketLength * 0.55);
            for (let index = 0; index < 3; index += 1) {
                const spread = (Math.random() - 0.5) * 0.6;
                const backAngle = rocket.angle + Math.PI + spread;
                const speed = 60 + Math.random() * 90;
                particles.push({
                    x: tailX,
                    y: tailY,
                    vx: Math.cos(backAngle) * speed + rocket.vx * 0.3,
                    vy: Math.sin(backAngle) * speed + rocket.vy * 0.3,
                    life: 0,
                    maxLife: 0.35 + Math.random() * 0.25,
                    size: 4 + Math.random() * 4,
                    kind: 'smoke',
                });
            }
            if (particles.length > 500) {
                particles.splice(0, particles.length - 500);
            }
        };

        const spawnExplosion = (x, y) => {
            for (let index = 0; index < 90; index += 1) {
                const angle = Math.random() * Math.PI * 2;
                const speed = 90 + Math.random() * 420;
                particles.push({
                    x,
                    y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed,
                    life: 0,
                    maxLife: 0.5 + Math.random() * 0.7,
                    size: 3 + Math.random() * 6,
                    kind: 'spark',
                });
            }

            for (let index = 0; index < 28; index += 1) {
                const angle = Math.random() * Math.PI * 2;
                const speed = 20 + Math.random() * 70;
                particles.push({
                    x,
                    y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - 30,
                    life: 0,
                    maxLife: 0.9 + Math.random() * 0.6,
                    size: 10 + Math.random() * 14,
                    kind: 'cloud',
                });
            }
        };

        const updateParticles = (dt) => {
            for (let index = particles.length - 1; index >= 0; index -= 1) {
                const particle = particles[index];
                particle.life += dt;
                if (particle.life >= particle.maxLife) {
                    particles.splice(index, 1);
                    continue;
                }

                particle.x += particle.vx * dt;
                particle.y += particle.vy * dt;
                const drag = particle.kind === 'cloud' ? 1.2 : 1.5;
                particle.vx *= (1 - drag * dt);
                particle.vy *= (1 - drag * dt);
                if (particle.kind === 'spark') {
                    particle.vy += 180 * dt;
                }
            }
        };

        const drawParticles = () => {
            particles.forEach((particle) => {
                const progress = particle.life / particle.maxLife;
                const alpha = Math.max(0, 1 - progress);
                const size = particle.size * (particle.kind === 'cloud' ? (1 + progress * 0.8) : (1 - progress * 0.6));
                const inner = particle.kind === 'cloud' ? '120,120,120' : '255,244,214';
                const outer = particle.kind === 'cloud' ? '40,40,40' : '255,120,0';
                const gradient = ctx.createRadialGradient(particle.x, particle.y, 0, particle.x, particle.y, size);
                gradient.addColorStop(0, `rgba(${inner},${alpha})`);
                gradient.addColorStop(1, `rgba(${outer},0)`);
                ctx.fillStyle = gradient;
                ctx.beginPath();
                ctx.arc(particle.x, particle.y, size, 0, Math.PI * 2);
                ctx.fill();
            });
        };

        const drawTargetMarker = (now) => {
            if (mode !== 'flying') {
                return;
            }

            const pulse = (isMobileRocket ? 15 : 10) + Math.sin(now / 300) * (isMobileRocket ? 5 : 3);
            ctx.save();
            ctx.shadowColor = isMobileRocket ? 'rgba(255,80,0,0.85)' : 'transparent';
            ctx.shadowBlur = isMobileRocket ? 18 : 0;
            ctx.strokeStyle = 'rgba(255,157,0,0.94)';
            ctx.lineWidth = isMobileRocket ? 3 : 2;
            ctx.beginPath();
            ctx.arc(rocket.targetX, rocket.targetY, pulse, 0, Math.PI * 2);
            ctx.stroke();
            if (isMobileRocket) {
                ctx.strokeStyle = 'rgba(255,255,255,0.82)';
                ctx.lineWidth = 1.5;
                ctx.beginPath();
                ctx.arc(rocket.targetX, rocket.targetY, pulse + 8, 0, Math.PI * 2);
                ctx.stroke();
            }
            ctx.beginPath();
            const crosshairSize = isMobileRocket ? 24 : 16;
            ctx.moveTo(rocket.targetX - crosshairSize, rocket.targetY);
            ctx.lineTo(rocket.targetX + crosshairSize, rocket.targetY);
            ctx.moveTo(rocket.targetX, rocket.targetY - crosshairSize);
            ctx.lineTo(rocket.targetX, rocket.targetY + crosshairSize);
            ctx.stroke();
            ctx.restore();
        };

        const drawRocket = () => {
            if (mode !== 'flying') {
                return;
            }

            ctx.save();
            ctx.translate(rocket.x, rocket.y);
            ctx.rotate(rocket.angle);
            ctx.scale(rocketScale, rocketScale);

            const length = rocketLength;
            const bodyWidth = length * 0.3;

            ctx.save();
            ctx.translate(isMobileRocket ? 5 : 3, isMobileRocket ? 8 : 5);
            ctx.globalAlpha = isMobileRocket ? 0.34 : 0.25;
            ctx.filter = isMobileRocket ? 'blur(1px)' : 'none';
            ctx.fillStyle = '#000';
            drawRoundedRocketPath(length, bodyWidth);
            ctx.fill();
            ctx.restore();

            ctx.fillStyle = '#34495e';
            ctx.beginPath();
            ctx.moveTo(-length * 0.28, -bodyWidth * 0.5);
            ctx.lineTo(-length * 0.55, -bodyWidth * 1.6);
            ctx.lineTo(-length * 0.12, -bodyWidth * 0.5);
            ctx.closePath();
            ctx.fill();
            ctx.beginPath();
            ctx.moveTo(-length * 0.28, bodyWidth * 0.5);
            ctx.lineTo(-length * 0.55, bodyWidth * 1.6);
            ctx.lineTo(-length * 0.12, bodyWidth * 0.5);
            ctx.closePath();
            ctx.fill();

            const bodyGradient = ctx.createLinearGradient(0, -bodyWidth / 2, 0, bodyWidth / 2);
            bodyGradient.addColorStop(0, isMobileRocket ? '#ff786a' : '#e0574a');
            bodyGradient.addColorStop(0.5, '#c0392b');
            bodyGradient.addColorStop(1, '#8e2a20');
            ctx.shadowColor = isMobileRocket ? 'rgba(255,157,0,0.42)' : 'transparent';
            ctx.shadowBlur = isMobileRocket ? 14 : 0;
            ctx.fillStyle = bodyGradient;
            drawRoundedRocketPath(length, bodyWidth);
            ctx.fill();
            ctx.shadowBlur = 0;
            ctx.strokeStyle = 'rgba(0,0,0,0.35)';
            ctx.lineWidth = 1;
            ctx.stroke();

            ctx.fillStyle = '#2c3e50';
            ctx.beginPath();
            ctx.moveTo(length * 0.32, -bodyWidth * 0.42);
            ctx.lineTo(length * 0.5, 0);
            ctx.lineTo(length * 0.32, bodyWidth * 0.42);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#a9d6ff';
            ctx.beginPath();
            ctx.arc(-length * 0.02, 0, bodyWidth * 0.22, 0, Math.PI * 2);
            ctx.fill();
            ctx.strokeStyle = 'rgba(0,0,0,0.4)';
            ctx.lineWidth = 1;
            ctx.stroke();

            ctx.strokeStyle = 'rgba(255,255,255,0.5)';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(-length * 0.30, -bodyWidth * 0.48);
            ctx.lineTo(length * 0.20, -bodyWidth * 0.48);
            ctx.stroke();

            const flicker = 0.75 + Math.random() * 0.5;
            const flameLength = length * 0.55 * flicker;
            const flameGradient = ctx.createLinearGradient(-length * 0.28, 0, -length * 0.28 - flameLength, 0);
            flameGradient.addColorStop(0, '#fff3b0');
            flameGradient.addColorStop(0.4, '#ff9d00');
            flameGradient.addColorStop(1, 'rgba(255,80,0,0)');
            ctx.shadowColor = isMobileRocket ? 'rgba(255,120,0,0.82)' : 'transparent';
            ctx.shadowBlur = isMobileRocket ? 18 : 0;
            ctx.fillStyle = flameGradient;
            ctx.beginPath();
            ctx.moveTo(-length * 0.28, -bodyWidth * 0.32);
            ctx.quadraticCurveTo(-length * 0.28 - flameLength * 0.6, 0, -length * 0.28 - flameLength, 0);
            ctx.quadraticCurveTo(-length * 0.28 - flameLength * 0.6, 0, -length * 0.28, bodyWidth * 0.32);
            ctx.closePath();
            ctx.fill();
            ctx.shadowBlur = 0;

            ctx.restore();
        };

        const drawExplosionGlow = () => {
            if (mode !== 'exploding') {
                return;
            }

            const progress = explodeTimer / explosionDuration;
            const radius = 20 + progress * 140;
            const alpha = Math.max(0, 1 - progress * 1.3);
            const gradient = ctx.createRadialGradient(rocket.targetX, rocket.targetY, 0, rocket.targetX, rocket.targetY, radius);
            gradient.addColorStop(0, `rgba(255,244,214,${alpha})`);
            gradient.addColorStop(0.35, `rgba(255,157,0,${alpha * 0.7})`);
            gradient.addColorStop(1, 'rgba(255,80,0,0)');
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(rocket.targetX, rocket.targetY, radius, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = `rgba(255,255,255,${alpha})`;
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.arc(rocket.targetX, rocket.targetY, 12 + progress * 90, 0, Math.PI * 2);
            ctx.stroke();
        };

        const updateFlying = (dt) => {
            const remainingDistance = Math.hypot(rocket.targetX - rocket.x, rocket.targetY - rocket.y);
            rocket.speed = Math.min(rocket.speed + thrustAccel * dt, maxSpeed);
            const step = Math.min(rocket.speed * dt, remainingDistance);

            rocket.x += rocket.dirX * step;
            rocket.y += rocket.dirY * step;
            rocket.vx = rocket.dirX * rocket.speed;
            rocket.vy = rocket.dirY * rocket.speed;

            spawnExhaust();

            const distanceToTarget = Math.hypot(rocket.targetX - rocket.x, rocket.targetY - rocket.y);
            if (distanceToTarget <= hitRadius) {
                mode = 'exploding';
                explodeTimer = 0;
                shake = 18;
                flashAlpha = 0.85;
                spawnExplosion(rocket.targetX, rocket.targetY);
                if (!hasImpacted) {
                    hasImpacted = true;
                    onImpact();
                }
            }
        };

        const cleanupRocketCanvas = () => {
            if (hasCleanedUp) {
                return;
            }
            hasCleanedUp = true;
            cancelAnimationFrame(animationFrameId);
            window.removeEventListener('resize', handleResize);
            canvas.remove();
        };

        const frame = (now) => {
            if (!canvas.isConnected) {
                return;
            }

            if (lastTime === null) {
                lastTime = now;
            }
            const dt = Math.min((now - lastTime) / 1000, 1 / 30);
            lastTime = now;

            if (mode === 'flying') {
                updateFlying(dt);
            } else if (mode === 'exploding') {
                explodeTimer += dt;
                shake = Math.max(0, shake - dt * 40);
                flashAlpha = Math.max(0, flashAlpha - dt * 1.7);
                if (explodeTimer >= explosionDuration) {
                    cleanupRocketCanvas();
                    return;
                }
            }
            updateParticles(dt);

            ctx.clearRect(0, 0, width, height);
            ctx.save();
            if (shake > 0) {
                ctx.translate((Math.random() - 0.5) * shake, (Math.random() - 0.5) * shake);
            }
            drawTargetMarker(now);
            drawParticles();
            drawExplosionGlow();
            drawRocket();
            if (flashAlpha > 0) {
                ctx.fillStyle = `rgba(255,255,255,${flashAlpha})`;
                ctx.fillRect(-20, -20, width + 40, height + 40);
            }
            ctx.restore();

            animationFrameId = requestAnimationFrame(frame);
        };

        const handleResize = () => resizeCanvas();
        window.addEventListener('resize', handleResize);
        animationFrameId = requestAnimationFrame(frame);

        window.setTimeout(() => {
            if (!canvas.isConnected || hasImpacted) {
                return;
            }

            cleanupRocketCanvas();
            onImpact();
        }, 5200);
    };

    function triggerRocketHazard() {
        if (challengeConfig.strictMode || !rocketLayer || state.rocketActive || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
            return;
        }

        const targetCards = Object.values(state.selectorCardLookup).filter((card) => card instanceof HTMLElement && card.isConnected);
        if (targetCards.length === 0) {
            scheduleRocketHazard();
            return;
        }

        state.rocketActive = true;
        const targetCard = targetCards[Math.floor(Math.random() * targetCards.length)];
        const targetSelectorKey = targetCard.dataset.selectorCard || '';
        const targetSelectorLabel = targetSelectorKey && state.selectorLookup[targetSelectorKey]
            ? state.selectorLookup[targetSelectorKey]
            : 'identifier';
        const targetZone = targetCard.querySelector(`[data-drop-key="${targetSelectorKey}"]`);
        const countdownSeconds = 5;
        let countdownLeft = countdownSeconds;
        let impactTriggered = false;

        const updateCountdownWarning = () => {
            if (rocketWarningPop) {
                rocketWarningPop.textContent = `Rocket incoming: ${countdownLeft}s - ${targetSelectorLabel}`;
            }
        };

        rocketLayer.innerHTML = '';
        preloadRocketSounds();
        updateCountdownWarning();
        rocketWarningPop?.classList.add('is-visible');
        targetCard.classList.add('is-rocket-target');
        if (targetZone instanceof HTMLElement) {
            targetZone.classList.add('is-rocket-target-zone');
        }
        if (!targetCard.hasAttribute('tabindex')) {
            targetCard.setAttribute('tabindex', '-1');
        }
        targetCard.focus({ preventScroll: true });
        if (window.matchMedia('(max-width: 1023px)').matches) {
            centerRocketTargetCardForMobile(targetCard);
        } else {
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }

        window.clearInterval(state.rocketCountdownTimerId);
        state.rocketCountdownTimerId = window.setInterval(() => {
            countdownLeft -= 1;
            if (countdownLeft <= 0) {
                window.clearInterval(state.rocketCountdownTimerId);
                state.rocketCountdownTimerId = null;
                return;
            }
            updateCountdownWarning();
        }, 1000);

        state.rocketLaunchTimerId = window.setTimeout(() => {
            state.rocketLaunchTimerId = null;
            window.clearInterval(state.rocketCountdownTimerId);
            state.rocketCountdownTimerId = null;

            if (!targetCard.isConnected || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
                stopRocketHazard();
                return;
            }

            if (rocketWarningPop) {
                rocketWarningPop.textContent = `Rocket locked: ${targetSelectorLabel}`;
            }
            const isMobileLaunch = window.matchMedia('(max-width: 1023px)').matches;
            if (isMobileLaunch) {
                centerRocketTargetCardForMobile(targetCard);
            } else {
                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
            }

            const launchAfterTargetSettles = () => {
                if (!targetCard.isConnected || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
                    stopRocketHazard();
                    return;
                }

                const freshTargetZone = targetCard.querySelector(`[data-drop-key="${targetSelectorKey}"]`);
                const targetElement = isMobileLaunch || !(freshTargetZone instanceof HTMLElement) ? targetCard : freshTargetZone;
                const targetPoint = getRocketTargetPoint(targetElement, isMobileLaunch);
                const launchDirection = isMobileLaunch ? 'top' : 'right';

                rocketWarningFlash?.classList.add('is-active');
                playRocketSound('launch');
                launchRocketElement(targetPoint, launchDirection, () => {
                    if (impactTriggered) {
                        return;
                    }
                    impactTriggered = true;
                    playRocketSound('explosion');
                    if (rocketWarningPop) {
                        rocketWarningPop.textContent = 'Impact!';
                    }

                    targetCard.classList.remove('is-rocket-target');
                    freshTargetZone?.classList.remove('is-rocket-target-zone');
                    freshTargetZone?.classList.add('is-rocket-hit-zone');
                    targetCard.classList.add('is-rocket-hit');
                    if (!shatterOnePropertyFromSelector(targetSelectorKey) && rocketWarningPop) {
                        rocketWarningPop.textContent = 'Impact! No property hit.';
                    }

                    window.setTimeout(() => {
                        rocketWarningPop?.classList.remove('is-visible');
                        rocketWarningFlash?.classList.remove('is-active');
                        targetCard.classList.remove('is-rocket-hit');
                        freshTargetZone?.classList.remove('is-rocket-hit-zone');
                        state.rocketActive = false;
                        scheduleRocketHazard();
                    }, 1050);
                });
            };

            window.setTimeout(() => {
                if (isMobileLaunch) {
                    requestAnimationFrame(() => requestAnimationFrame(launchAfterTargetSettles));
                    return;
                }

                launchAfterTargetSettles();
            }, isMobileLaunch ? 80 : 260);
        }, countdownSeconds * 1000);
    }

    const preloadCheerSounds = () => {
        if (!Array.isArray(challengeConfig.cheerSoundUrls) || challengeConfig.cheerSoundUrls.length === 0) {
            return;
        }

        try {
            cheerAudios = challengeConfig.cheerSoundUrls.map((url) => {
                const audio = new Audio(url);
                audio.preload = 'auto';
                audio.volume = 0.78;
                audio.load();
                return audio;
            });
        } catch (error) {
            cheerAudios = [];
        }
    };

    const playIdentifierCompleteCheer = () => {
        if (!gameplaySoundIsOn()) {
            return;
        }

        try {
            if (cheerAudios.length === 0) {
                preloadCheerSounds();
            }

            if (cheerAudios.length === 0) {
                return;
            }

            const audio = cheerAudios[Math.floor(Math.random() * cheerAudios.length)];
            audio.currentTime = 0;
            audio.volume = 0.78;
            const playRequest = audio.play();

            if (playRequest && typeof playRequest.catch === 'function') {
                playRequest.catch(() => {});
            }
        } catch (error) {
            return;
        }
    };

    const removeGameStartSoundRetry = () => {
        document.removeEventListener('pointerdown', retryGameStartSoundOnInteraction, true);
        document.removeEventListener('touchstart', retryGameStartSoundOnInteraction, true);
        document.removeEventListener('keydown', retryGameStartSoundOnInteraction, true);
    };

    function retryGameStartSoundOnInteraction() {
        if (Date.now() > gameStartSoundRetryDeadline) {
            removeGameStartSoundRetry();
            return;
        }

        playGameStartSound(false);
    }

    const queueGameStartSoundRetry = () => {
        gameStartSoundRetryDeadline = Date.now() + 5000;
        removeGameStartSoundRetry();
        document.addEventListener('pointerdown', retryGameStartSoundOnInteraction, { capture: true, once: true });
        document.addEventListener('touchstart', retryGameStartSoundOnInteraction, { capture: true, once: true });
        document.addEventListener('keydown', retryGameStartSoundOnInteraction, { capture: true, once: true });
    };

    const playGameStartSound = (allowRetry = true) => {
        if (hasPlayedGameStartSound || isGameStartSoundAttempting || !gameplaySoundIsOn() || !challengeConfig.startSoundUrl) {
            return;
        }

        try {
            preloadGameStartSound();
            if (!gameStartAudio) {
                return;
            }

            isGameStartSoundAttempting = true;
            gameStartAudio.currentTime = 0;
            gameStartAudio.volume = 0.72;
            const playRequest = gameStartAudio.play();

            if (playRequest && typeof playRequest.then === 'function') {
                playRequest
                    .then(() => {
                        hasPlayedGameStartSound = true;
                        isGameStartSoundAttempting = false;
                        removeGameStartSoundRetry();
                    })
                    .catch(() => {
                        isGameStartSoundAttempting = false;
                        if (allowRetry) {
                            queueGameStartSoundRetry();
                        }
                    });
                return;
            }

            hasPlayedGameStartSound = true;
            isGameStartSoundAttempting = false;
            removeGameStartSoundRetry();
        } catch (error) {
            isGameStartSoundAttempting = false;
            if (allowRetry) {
                queueGameStartSoundRetry();
            }
            return;
        }
    };

    const preloadGameEndSound = () => {
        if (!challengeConfig.startSoundUrl) {
            return;
        }

        try {
            gameEndAudio = gameEndAudio || new Audio(challengeConfig.startSoundUrl);
            gameEndAudio.preload = 'auto';
            gameEndAudio.volume = 0.72;
            gameEndAudio.load();
        } catch (error) {
            return;
        }
    };

    const playGameEndSoundAfterResult = () => {
        if (!gameplaySoundIsOn() || !challengeConfig.startSoundUrl) {
            return;
        }

        window.setTimeout(() => {
            try {
                preloadGameEndSound();
                if (!gameEndAudio) {
                    return;
                }

                gameEndAudio.currentTime = 0;
                gameEndAudio.volume = 0.72;
                const playRequest = gameEndAudio.play();

                if (playRequest && typeof playRequest.catch === 'function') {
                    playRequest.catch(() => {});
                }
            } catch (error) {
                return;
            }
        }, 90);
    };

    if (gameplaySoundIsOn() && challengeConfig.startSoundUrl) {
        preloadGameStartSound();
        preloadGameEndSound();
    }

    if (gameplaySoundIsOn() && challengeConfig.glassCrackSoundUrl) {
        preloadGlassCrackSound();
    }

    if (gameplaySoundIsOn() && (challengeConfig.correctSoundUrl || challengeConfig.wrongSoundUrl)) {
        preloadPropertyDropSounds();
    }

    if (gameplaySoundIsOn() && Array.isArray(challengeConfig.cheerSoundUrls)) {
        preloadCheerSounds();
    }

    const buildPreviewDocument = (html, css) => `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { box-sizing: border-box; }
html, body { width: 100%; min-height: 100%; margin: 0; }
body { display: grid; min-height: 100vh; place-items: center; background: #f7efe1; font-family: Arial, sans-serif; padding: 24px; overflow: auto; }
a, area { cursor: default !important; }
${css}
</style>
</head>
<body>${html}</body>
</html>`;

    const disablePreviewLinks = (frame) => {
        if (!(frame instanceof HTMLIFrameElement)) {
            return;
        }

        const doc = frame.contentDocument;
        if (!doc) {
            return;
        }

        if (!doc.getElementById('pixelwar-preview-link-guard')) {
            const style = doc.createElement('style');
            style.id = 'pixelwar-preview-link-guard';
            style.textContent = 'a, area, button, [role="button"], input, select, textarea { cursor: default !important; }';
            doc.head?.appendChild(style);
        }

        doc.querySelectorAll('a, area').forEach((link) => {
            if (link.hasAttribute('href')) {
                link.dataset.pixelwarDisabledHref = link.getAttribute('href') || '';
                link.removeAttribute('href');
            }
            link.removeAttribute('target');
            link.setAttribute('tabindex', '-1');
            link.setAttribute('aria-disabled', 'true');
        });

        if (doc.defaultView?.pixelwarPreviewLinksBlocked) {
            return;
        }

        doc.defaultView.pixelwarPreviewLinksBlocked = true;
        const blockPreviewActivation = (event) => {
            if (event.target?.closest?.('a, area, form, button[type="submit"], input[type="submit"], input[type="image"]')) {
                event.preventDefault();
            }
        };

        doc.addEventListener('click', blockPreviewActivation, true);
        doc.addEventListener('auxclick', blockPreviewActivation, true);
        doc.addEventListener('pointerup', blockPreviewActivation, true);
        doc.addEventListener('touchend', blockPreviewActivation, true);
        doc.addEventListener('submit', blockPreviewActivation, true);
        doc.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && event.target?.closest?.('a, area, button[type="submit"], input[type="submit"], input[type="image"]')) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);
    };

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

        const shellStyle = window.getComputedStyle(shell);
        const paddingLeft = parseFloat(shellStyle.paddingLeft) || 0;
        const paddingRight = parseFloat(shellStyle.paddingRight) || 0;
        const paddingTop = parseFloat(shellStyle.paddingTop) || 0;
        const paddingBottom = parseFloat(shellStyle.paddingBottom) || 0;
        const shellWidth = Math.max(1, shell.clientWidth - paddingLeft - paddingRight);
        const shellHeight = Math.max(1, shell.clientHeight - paddingTop - paddingBottom);

        frame.style.width = `${shellWidth}px`;
        frame.style.height = `${shellHeight}px`;
        frame.style.maxWidth = 'none';
        frame.style.maxHeight = 'none';
        frame.style.position = 'absolute';
        frame.style.left = `${paddingLeft}px`;
        frame.style.top = `${paddingTop}px`;
        frame.style.transform = 'none';
        frame.style.transformOrigin = 'top left';

        const viewportWidth = Math.max(frame.clientWidth, shellWidth, 1);
        const viewportHeight = Math.max(frame.clientHeight, shellHeight, 1);
        const naturalWidth = Math.max(
            body.scrollWidth,
            body.offsetWidth,
            html.scrollWidth,
            html.offsetWidth,
            viewportWidth,
            1
        );
        const naturalHeight = Math.max(
            body.scrollHeight,
            body.offsetHeight,
            html.scrollHeight,
            html.offsetHeight,
            viewportHeight,
            1
        );

        if (shellWidth <= 0 || shellHeight <= 0) {
            return;
        }

        const scale = Math.min(shellWidth / naturalWidth, shellHeight / naturalHeight);
        const scaledWidth = naturalWidth * scale;
        const scaledHeight = naturalHeight * scale;
        const centeredLeft = paddingLeft + Math.max(0, (shellWidth - scaledWidth) / 2);
        const centeredTop = paddingTop + Math.max(0, (shellHeight - scaledHeight) / 2);

        frame.style.width = `${naturalWidth}px`;
        frame.style.height = `${naturalHeight}px`;
        frame.style.maxWidth = 'none';
        frame.style.maxHeight = 'none';
        frame.style.position = 'absolute';
        frame.style.left = `${centeredLeft}px`;
        frame.style.top = `${centeredTop}px`;
        frame.style.transform = `scale(${scale})`;
        frame.style.transformOrigin = 'top left';
    };

    const runOpeningEffect = () => {
        if (!openingEffect) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const shouldPlayIntro = params.get('intro') === '1' || challengeConfig.roomId > 0 || challengeConfig.pvpId > 0;
        if (!shouldPlayIntro) {
            openingEffect.remove();
            state.introFinished = true;
            startBackgroundMusic();
            scheduleRocketHazard();
            return;
        }

        if (!openingEffect.classList.contains('is-playing')) {
            openingEffect.classList.add('is-playing');
        }
        window.setTimeout(() => {
            openingEffect.remove();
            state.introFinished = true;
            playGameStartSound();
            startBackgroundMusic();
            scheduleRocketHazard();
        }, 1500);
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

    const populateStrictResultModal = (score, message) => {
        const setText = (id, value) => {
            const node = document.getElementById(id);
            if (node) {
                node.textContent = value;
            }
        };

        const normalizedScore = Math.max(0, Math.min(100, Number(score || 0)));
        setText('gameplay-strict-result-name', challengeConfig.challengeTitle || 'Pixelwar');
        setText('gameplay-strict-result-score', `${normalizedScore}%`);
        setText('gameplay-strict-result-selectors', String(state.selectorKeys.length));
        setText('gameplay-strict-result-properties', String(state.totalCount));
        setText('gameplay-strict-result-message', message || `Your run ended with ${normalizedScore}% match.`);
    };

    const submitCompletion = async () => {
        if (state.isCompletionSubmitting || state.isCompleted || state.isUnavailable || !challengeConfig.challengeId || !challengeConfig.userChallengeId) {
            return;
        }

        state.isCompletionSubmitting = true;
        setStatus('Completing challenge...', true);

        try {
            const body = new URLSearchParams({
                _csrf_token: challengeConfig.csrfToken,
                gameplay_action: 'complete',
                challenge_id: String(challengeConfig.challengeId),
                room_id: String(challengeConfig.roomId || 0),
                pvp_id: String(challengeConfig.pvpId || 0),
                user_challenge_id: String(challengeConfig.userChallengeId),
            });

            const response = await fetch('./?c=pixelwar', {
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

            if (payload?.pvp_ended && payload?.data) {
                handlePvpEnded(payload.data);
                return;
            }

            state.isCompleted = true;
            state.skipUnloadWarning = true;
            stopBackgroundMusic();
            stopRocketHazard();
            if (gameplayTime && payload.data?.completed_at) {
                gameplayTime.dataset.startedAt = '';
                gameplayTime.textContent = formatElapsedTime(Number(payload.data?.duration_seconds || 0));
            }
            setStatus('Complete', true);
            launchConfetti();
            populateCompletionModal(payload.data || {});
            completionModal?.show();
            playGameEndSoundAfterResult();
        } catch (error) {
            console.error(error);
            state.isCompletionSubmitting = false;
            setStatus(error instanceof Error ? error.message : 'Unable to complete this challenge right now.');
        }
    };

    const handleChallengeUnavailable = (message) => {
        if (state.isUnavailable) {
            return;
        }

        state.isUnavailable = true;
        state.skipUnloadWarning = true;
        state.isCompletionSubmitting = false;
        stopBackgroundMusic();
        stopRocketHazard();
        setStatus(message || 'Challenge unavailable');
        exitModal?.hide();

        window.setTimeout(() => {
            window.location.href = './?c=challenges';
        }, 1400);
    };

    const handleRoomEnded = (message, redirectUrl) => {
        if (state.isUnavailable || state.isCompleted) {
            return;
        }

        state.isUnavailable = true;
        state.skipUnloadWarning = true;
        state.isCompletionSubmitting = false;
        roomEndSubmitting = true;
        stopBackgroundMusic();
        stopRocketHazard();
        setStatus(message || 'The room was ended.');
        exitModal?.hide();
        completionModal?.hide();

        window.setTimeout(() => {
            window.location.href = redirectUrl || challengeConfig.endedRedirectUrl;
        }, 900);
    };

    const handlePvpEnded = (payload) => {
        if (state.isUnavailable) {
            return;
        }

        const winnerUserId = Number(payload?.winner_user_id || 0);
        const durationSeconds = Math.max(0, Number(payload?.duration_seconds || 0));
        const redirectAtMs = Number(payload?.redirect_at_ms || 0);
        const result = winnerUserId === Number(challengeConfig.currentUserId || 0) ? 'win' : 'loss';

        state.isUnavailable = true;
        state.isCompleted = true;
        state.skipUnloadWarning = true;
        state.isCompletionSubmitting = false;
        stopBackgroundMusic();
        stopRocketHazard();
        setStatus(result === 'win' ? 'You won the duel.' : 'You lost the duel.');
        exitModal?.hide();
        completionModal?.hide();
        strictResultModal?.hide();

        const redirectUrl = `./?c=home&pvp_notice=${encodeURIComponent(result)}&pvp_duration=${encodeURIComponent(String(durationSeconds))}`;
        const delay = Math.max(0, redirectAtMs > 0 ? redirectAtMs - Date.now() : 900);
        window.setTimeout(() => {
            window.location.href = redirectUrl;
        }, delay);
    };

    const submitPvpGiveUp = async () => {
        if (!giveUpForm || !challengeConfig.pvpId || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
            return;
        }

        state.skipUnloadWarning = true;
        state.isCompletionSubmitting = true;
        stopBackgroundMusic();
        stopRocketHazard();
        exitModal?.hide();
        setStatus('Ending 1v1 match...');

        try {
            const body = new URLSearchParams(new FormData(giveUpForm));
            const response = await fetch('./?c=pixelwar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            });

            const payload = await response.json();
            if (!response.ok || !payload?.success) {
                throw new Error(payload?.message || 'Unable to give up this challenge right now.');
            }

            handlePvpEnded(payload?.data || {});
        } catch (error) {
            console.error(error);
            state.isCompletionSubmitting = false;
            state.skipUnloadWarning = false;
            setStatus(error instanceof Error ? error.message : 'Unable to give up this challenge right now.');
        }
    };

    const validateChallengeAvailability = async () => {
        if (!challengeConfig.hasChallenge || !challengeConfig.challengeId || !challengeConfig.userChallengeId) {
            return;
        }

        if (state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
            return;
        }

        try {
            const body = new URLSearchParams({
                _csrf_token: challengeConfig.csrfToken,
                gameplay_action: 'validate_availability',
                challenge_id: String(challengeConfig.challengeId),
                user_challenge_id: String(challengeConfig.userChallengeId),
            });

            const response = await fetch('./?c=pixelwar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            });

            const payload = await response.json().catch(() => null);

            if (state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
                return;
            }

            if (payload?.pvp_ended && payload?.data) {
                handlePvpEnded(payload.data);
                return;
            }

            if (!response.ok || !payload?.success || payload?.available === false) {
                handleChallengeUnavailable(payload?.message || 'This challenge is no longer available.');
            }
        } catch (error) {
            console.error(error);
        }
    };

    const triggerRoomTimeoutEnd = async () => {
        if (roomEndSubmitting || !challengeConfig.roomId || !challengeConfig.csrfToken) {
            return;
        }

        roomEndSubmitting = true;

        try {
            const body = new URLSearchParams({
                _csrf_token: challengeConfig.csrfToken,
                gameplay_action: 'end_room_timeout',
                room_id: String(challengeConfig.roomId),
            });

            const response = await fetch('./?c=pixelwar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            });

            const payload = await response.json().catch(() => null);
            if (payload?.ended) {
                handleRoomEnded(payload?.message || 'The room was ended. Your challenge run was not completed.');
                return;
            }
        } catch (error) {
            console.error(error);
        }
    };

    const handleRoomTimerTick = () => {
        if (!roomSessionTimer || !challengeConfig.roomDeadlineAt || state.isCompleted || state.skipUnloadWarning) {
            return;
        }

        const deadline = new Date(challengeConfig.roomDeadlineAt);
        if (Number.isNaN(deadline.getTime())) {
            return;
        }

        const remainingSeconds = Math.max(0, Math.floor((deadline.getTime() - Date.now()) / 1000));
        roomSessionTimer.textContent = formatElapsedTime(remainingSeconds);

        if (remainingSeconds <= 0) {
            roomSessionTimer.textContent = '00:00';
            setStatus('Room time expired.');
            triggerRoomTimeoutEnd();
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

    const propertyFitsDestination = (propertyKey, destinationKey) => {
        if (destinationKey === 'pool') {
            return null;
        }

        const requiredCount = state.requiredBySelector[destinationKey]?.[propertyKey] || 0;
        const currentCount = getCount(destinationKey, propertyKey);

        return requiredCount > currentCount;
    };

    const showStreakFeedback = (isCorrect) => {
        if (!streakPop || challengeConfig.strictMode) {
            return;
        }

        window.clearTimeout(state.streakTimerId);

        const cheerDelay = Math.max(0, state.identifierCompleteActiveUntil - Date.now());
        if (cheerDelay > 0) {
            state.streakTimerId = window.setTimeout(() => showStreakFeedback(isCorrect), cheerDelay + 80);
            return;
        }

        streakPop.classList.remove('is-visible', 'is-hit', 'is-break');

        if (isCorrect) {
            state.streakCount += 1;
            streakPop.textContent = `Streak ${state.streakCount}x`;
            streakPop.dataset.streakLabel = streakPop.textContent;
            requestAnimationFrame(() => {
                streakPop.classList.add('is-visible', 'is-hit');
            });
            state.streakTimerId = window.setTimeout(() => {
                streakPop.classList.remove('is-visible', 'is-hit');
            }, 1150);
            return;
        }

        if (state.streakCount <= 0) {
            streakPop.textContent = '';
            delete streakPop.dataset.streakLabel;
            return;
        }

        const brokenLabel = `Streak ${state.streakCount}x`;
        state.streakCount = 0;
        streakPop.textContent = brokenLabel;
        streakPop.dataset.streakLabel = brokenLabel;
        playGlassCrackSound();
        requestAnimationFrame(() => {
            streakPop.classList.add('is-visible', 'is-break');
        });
        state.streakTimerId = window.setTimeout(() => {
            streakPop.classList.remove('is-visible', 'is-break');
            streakPop.textContent = '';
            delete streakPop.dataset.streakLabel;
        }, 900);
    };

    const showIdentifierCompleteFeedback = (selectorKey) => {
        if (!identifierCompletePop || challengeConfig.strictMode) {
            return;
        }

        window.clearTimeout(state.identifierCompleteTimerId);
        identifierCompletePop.classList.remove('is-visible', 'is-celebrating');
        identifierCompletePop.innerHTML = `
            <span>Nice work!</span>
            <strong>${escapeHtml(state.selectorLookup[selectorKey] || 'Identifier')} complete</strong>
        `;
        playIdentifierCompleteCheer();
        state.identifierCompleteActiveUntil = Date.now() + 1400;

        requestAnimationFrame(() => {
            identifierCompletePop.classList.add('is-visible', 'is-celebrating');
        });

        state.identifierCompleteTimerId = window.setTimeout(() => {
            identifierCompletePop.classList.remove('is-visible', 'is-celebrating');
            state.identifierCompleteActiveUntil = 0;
        }, 1400);
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

        const placementResult = propertyFitsDestination(payload.propertyKey, destination);

        if (moveOne(payload.propertyKey, payload.sourceKey, destination)) {
            playGameplaySound('drop');
            clearSelectedPayload();
            state.pinnedSelectorKey = null;
            state.hoveredSelectorKey = null;
            clearSelectorCardHighlight();
            render();
            if (placementResult !== null) {
                playPropertyDropSound(placementResult);
                showStreakFeedback(placementResult);
            }
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
        chip.dataset.propertyLabel = rule;
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

        chip.addEventListener('pointerdown', () => {
            if (state.isCompleted || state.isCompletionSubmitting) {
                return;
            }
            playGameplaySound('pickup');
        });

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
            if (sourceKey !== 'pool') {
                movePayloadTo({ propertyKey, sourceKey }, 'pool');
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
        const liveDocument = buildPreviewDocument(state.html, currentPlayerCss());
        [livePreview, liveComparePreview].forEach((frame) => {
            if (frame instanceof HTMLIFrameElement) {
                frame.srcdoc = liveDocument;
            }
        });
    };

    [livePreview, liveComparePreview].forEach((frame) => {
        if (frame instanceof HTMLIFrameElement) {
            frame.addEventListener('load', () => {
                disablePreviewLinks(frame);
                fitPreviewFrame(frame);
            }, { once: false });
        }
    });

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

            if (!challengeConfig.strictMode) {
                if (selectorStatus.complete) {
                    card.classList.add('is-target-complete');
                    if (!state.completedSelectorKeys.has(selectorKey)) {
                        state.completedSelectorKeys.add(selectorKey);
                        showIdentifierCompleteFeedback(selectorKey);
                    }
                } else if (selectorStatus.mismatch) {
                    card.classList.add('is-target-danger');
                }

                if (!selectorStatus.complete) {
                    state.completedSelectorKeys.delete(selectorKey);
                }
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

        if (challengeConfig.strictMode) {
            if (progressBarFill) {
                progressBarFill.style.width = `${Math.max(0, Math.min(100, Number(state.strictProgressPercent ?? 0)))}%`;
            }
            if (state.strictProgressPercent === null) {
                setStatus('Strict mode: submit to record your progress.');
            }
            return { correctCount, hasMismatch, allComplete, progressPercent };
        }

        if (progressBarFill) {
            progressBarFill.style.width = `${progressPercent}%`;
        }

        if (allComplete && correctCount === state.totalCount && state.totalCount > 0) {
            setStatus('Complete', true);
            submitCompletion();
        } else {
            setStatus(hasMismatch ? 'Mismatch detected' : 'In progress');
        }

        return { correctCount, hasMismatch, allComplete, progressPercent };
    };

    const render = () => {
        renderLists();
        renderPreviewStyles();
        renderSelectorStates();
        renderProgress();
    };

    const submitStrictModeScore = async (progressPercent) => {
        if (!challengeConfig.roomId || !challengeConfig.csrfToken || !challengeConfig.challengeId || !challengeConfig.userChallengeId) {
            throw new Error('Strict mode submit is not available.');
        }

        const body = new URLSearchParams({
            _csrf_token: challengeConfig.csrfToken,
            gameplay_action: 'strict_mode_submit',
            room_id: String(challengeConfig.roomId),
            challenge_id: String(challengeConfig.challengeId),
            user_challenge_id: String(challengeConfig.userChallengeId),
            strict_mode_score: String(progressPercent),
        });

        const response = await fetch('./?c=pixelwar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        });

        const payload = await response.json().catch(() => null);

        if (!response.ok || !payload?.success) {
            throw new Error(payload?.message || 'Unable to submit strict mode progress right now.');
        }

        return payload;
    };

    const handleStrictModeSubmit = async () => {
        if (!challengeConfig.strictMode || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
            return;
        }

        state.isCompletionSubmitting = true;
        stopRocketHazard();
        const progressState = renderProgress();
        const progressPercent = Number(progressState?.progressPercent || 0);
        state.strictProgressPercent = progressPercent;
        if (progressBarFill) {
            progressBarFill.style.width = `${progressPercent}%`;
        }

        try {
            const payload = await submitStrictModeScore(progressPercent);
            state.skipUnloadWarning = true;
            state.isCompleted = progressPercent >= 100;
            state.isUnavailable = progressPercent < 100;
            stopBackgroundMusic();
            stopRocketHazard();
            setStatus(payload?.message || `Strict mode result recorded: ${progressPercent}%.`, progressPercent >= 100);
            exitModal?.hide();
            completionModal?.hide();
            if (progressPercent >= 100) {
                launchConfetti();
            }
            populateStrictResultModal(progressPercent, payload?.message || `Your run ended with ${progressPercent}% match.`);
            strictResultModal?.show();
            playGameEndSoundAfterResult();
        } catch (error) {
            console.error(error);
            state.isCompletionSubmitting = false;
            setStatus(error instanceof Error ? error.message : 'Unable to submit strict mode progress right now.');
        }
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
        if (!element) {
            return null;
        }

        let currentElement = element.nodeType === 1 ? element : element.parentElement;
        if (!currentElement || currentElement.nodeType !== 1) {
            return null;
        }

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

        const resolveTargetFromPreviewEvent = (event) => {
            const touch = event.changedTouches?.[0] || event.touches?.[0] || null;
            if (touch && typeof doc.elementFromPoint === 'function') {
                return doc.elementFromPoint(touch.clientX, touch.clientY) || event.target;
            }

            return event.target;
        };

        const selectTargetElement = (event) => {
            if (event.cancelable) {
                event.preventDefault();
            }
            event.stopPropagation();
            event.stopImmediatePropagation?.();
            const key = resolveSelectorKeyFromTarget(resolveTargetFromPreviewEvent(event));
            state.pinnedSelectorKey = key;
            highlightSelectorCard(key, true);

            if (previewModal?.classList.contains('show')) {
                bootstrap.Modal.getOrCreateInstance(previewModal).hide();
            }
        };

        doc.addEventListener('touchstart', selectTargetElement, { capture: true, passive: false });
        doc.addEventListener('pointerdown', selectTargetElement, { capture: true, passive: false });
        doc.body.addEventListener('click', selectTargetElement);
        doc.body.addEventListener('pointerup', selectTargetElement, { passive: false });
        doc.body.addEventListener('touchend', selectTargetElement, { passive: false });

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
                disablePreviewLinks(preview);
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
        frame.addEventListener('load', () => {
            disablePreviewLinks(frame);
            fitPreviewFrame(frame);
        }, { once: false });
    });

    const resetGame = () => {
        if (state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
            return;
        }

        state.selectorKeys.forEach((selectorKey) => {
            state.placements[selectorKey] = {};
        });
        state.placements.pool = { ...state.totalRequiredByProperty };
        state.hoveredSelectorKey = null;
        state.pinnedSelectorKey = null;
        state.strictProgressPercent = null;
        state.completedSelectorKeys.clear();
        state.identifierCompleteActiveUntil = 0;
        window.clearTimeout(state.streakTimerId);
        window.clearTimeout(state.identifierCompleteTimerId);
        streakPop?.classList.remove('is-visible', 'is-hit', 'is-break');
        identifierCompletePop?.classList.remove('is-visible', 'is-celebrating');
        clearSelectorCardHighlight();
        clearSelectedPayload();
        stopRocketHazard();
        render();
        scheduleRocketHazard();
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
            state.challengeLoaded = true;
            startBackgroundMusic();
            if (challengeConfig.strictMode) {
                stopRocketHazard();
            }
            scheduleRocketHazard();
            setStatus(challengeConfig.strictMode ? 'Strict mode: submit to record your progress.' : 'In progress');
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

    resetButton?.addEventListener('click', () => {
        if (state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
            return;
        }
        resetModal?.show();
    });
    confirmResetButton?.addEventListener('click', () => {
        resetModal?.hide();
        resetGame();
    });
    propertySearchInput?.addEventListener('input', renderLists);
    strictModeSubmitButton?.addEventListener('click', handleStrictModeSubmit);
    liveCompareButton?.addEventListener('click', () => {
        renderPreviewStyles();
        liveCompareModal?.show();
    });
    giveUpForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (state.isCompleted || state.isCompletionSubmitting) {
            return;
        }

        exitModal?.show();
    });
    confirmGiveUpButton?.addEventListener('click', () => {
        if (!giveUpForm || state.isCompleted || state.isCompletionSubmitting || state.isUnavailable) {
            return;
        }

        if (challengeConfig.pvpId > 0) {
            submitPvpGiveUp();
            return;
        }

        state.skipUnloadWarning = true;
        stopBackgroundMusic();
        stopRocketHazard();
        exitModal?.hide();
        giveUpForm.submit();
    });
    window.addEventListener('beforeunload', beforeUnloadHandler);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            validateChallengeAvailability();
        }
    });
    previewModal?.addEventListener('shown.bs.modal', () => requestAnimationFrame(() => targetPreviews.forEach((frame) => fitPreviewFrame(frame))));
    liveCompareModalElement?.addEventListener('shown.bs.modal', () => requestAnimationFrame(() => allPreviewFrames.forEach((frame) => fitPreviewFrame(frame))));
    if (allPreviewFrames.length > 0 && 'ResizeObserver' in window) {
        const previewObserver = new ResizeObserver(() => allPreviewFrames.forEach((frame) => fitPreviewFrame(frame)));
        allPreviewFrames.forEach((frame) => frame.parentElement && previewObserver.observe(frame.parentElement));
    }
    window.addEventListener('resize', () => allPreviewFrames.forEach((frame) => fitPreviewFrame(frame)));
    initResizeHandle();
    startGameplayTimer();
    if (roomSessionTimer && challengeConfig.roomDeadlineAt) {
        handleRoomTimerTick();
        window.setInterval(handleRoomTimerTick, 1000);
    }
    if ((challengeConfig.roomId > 0 || challengeConfig.pvpId > 0) && challengeConfig.pusherKey && challengeConfig.pusherCluster && window.Pusher) {
        const pusher = new window.Pusher(challengeConfig.pusherKey, {
            cluster: challengeConfig.pusherCluster,
        });

        if (challengeConfig.roomId > 0) {
            const roomChannel = pusher.subscribe(`room-${challengeConfig.roomId}`);
            roomChannel.bind('session-ended', (payload) => {
                handleRoomEnded(
                    payload?.message || 'The room was ended. Your challenge run was not completed.',
                    payload?.redirect_url || challengeConfig.endedRedirectUrl
                );
            });
        }

        if (challengeConfig.pvpId > 0) {
            const pvpChannel = pusher.subscribe(`pvp-${challengeConfig.pvpId}`);
            pvpChannel.bind('pvp-ended', (payload) => {
                handlePvpEnded(payload);
            });
        }
    }
    boot();
    validateChallengeAvailability();
    const availabilityInterval = window.setInterval(() => {
        validateChallengeAvailability();
        if (state.isCompleted || state.isUnavailable) {
            window.clearInterval(availabilityInterval);
        }
    }, 15000);
})();
</script>
