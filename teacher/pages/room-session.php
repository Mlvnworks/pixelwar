<?php
$roomSessionId = max(0, (int) ($_GET['id'] ?? 0));
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$roomSessionRoom = $roomRepository instanceof RoomRepository
    ? $roomRepository->findByIdForOwner($roomSessionId, $teacherId)
    : null;

if ($roomSessionId <= 0 || $roomSessionRoom === null) {
    $_SESSION['alert'] = [
        'error' => true,
        'content' => 'The requested room session is unavailable.',
    ];
    header('Location: ./?c=rooms');
    exit;
}

$roomSessionPlayers = $roomSessionRoom !== null && isset($roomPlayerRepository) && $roomPlayerRepository instanceof RoomPlayerRepository
    ? $roomPlayerRepository->listJoinedForRoom((int) ($roomSessionRoom['room_id'] ?? 0))
    : [];

$roomSessionJoinedCount = count($roomSessionPlayers);
$roomSessionStartedCount = 0;
$roomSessionCompletedCount = 0;
$roomSessionIsStarted = $roomSessionRoom !== null && trim((string) ($roomSessionRoom['started_at'] ?? '')) !== '';
$roomSessionIsEnded = $roomSessionRoom !== null && trim((string) ($roomSessionRoom['ended_at'] ?? '')) !== '';
$roomSessionDeadlineIso = '';
$pusherEnabled = isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured();

if ($roomSessionIsStarted && !$roomSessionIsEnded) {
    $roomSessionTimerLimit = max(0, (int) ($roomSessionRoom['timer_limit'] ?? 0));
    $roomSessionStartedTs = strtotime((string) ($roomSessionRoom['started_at'] ?? ''));
    if ($roomSessionTimerLimit > 0 && $roomSessionStartedTs !== false) {
        $roomSessionDeadlineIso = date(DATE_ATOM, $roomSessionStartedTs + ($roomSessionTimerLimit * 60));
    }
}

$formatTimestamp = static function (?string $value): string {
    if (!is_string($value) || trim($value) === '') {
        return 'Not set';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return 'Not set';
    }

    return date('M j, Y g:i A', $timestamp);
};

$statusForPlayer = static function (array $player, bool $roomEnded = false): array {
    $completedAt = trim((string) ($player['completed_at'] ?? ''));
    $startedAt = trim((string) ($player['started_at'] ?? ''));
    $status = (int) ($player['status'] ?? 0);

    if ($roomEnded && $completedAt === '' && $status !== 2) {
        return ['label' => 'Failed', 'class' => 'room-session-pill--gave-up'];
    }

    if ($status === 3) {
        return ['label' => 'Failed', 'class' => 'room-session-pill--gave-up'];
    }

    if ($completedAt !== '' || $status === 2) {
        return ['label' => 'Completed', 'class' => 'room-session-pill--completed'];
    }

    if ($startedAt !== '' || $status === 1) {
        return ['label' => 'Solving', 'class' => 'room-session-pill--started'];
    }

    return ['label' => 'Waiting', 'class' => 'room-session-pill--joined'];
};

$strictScoreForPlayer = static function (array $player): int {
    return max(0, min(100, (int) ($player['strict_mode_score'] ?? 0)));
};

$displayStatusForPlayer = static function (array $player, bool $roomEnded = false, bool $strictModeEnabled = false, bool $hardCodeModeEnabled = false) use ($statusForPlayer, $strictScoreForPlayer): array {
    $statusMeta = $statusForPlayer($player, $roomEnded);
    $status = (int) ($player['status'] ?? 0);

    if ($hardCodeModeEnabled && $status === 2) {
        return ['label' => 'Submitted', 'class' => 'room-session-pill--completed'];
    }

    if ($strictModeEnabled && in_array($status, [2, 3], true)) {
        $score = $strictScoreForPlayer($player);

        if ($score >= 100) {
            return ['label' => '100%', 'class' => 'room-session-pill--completed'];
        }

        if ($score > 0) {
            return ['label' => $score . '%', 'class' => 'room-session-pill--started'];
        }

        return ['label' => '0%', 'class' => 'room-session-pill--gave-up'];
    }

    return $statusMeta;
};

$formatPlayerDuration = static function (array $player, bool $roomEnded = false, bool $strictModeEnabled = false, bool $hardCodeModeEnabled = false) use ($statusForPlayer, $displayStatusForPlayer): string {
    $startedAt = trim((string) ($player['started_at'] ?? ''));
    $completedAt = trim((string) ($player['completed_at'] ?? ''));

    if ($startedAt !== '' && $completedAt !== '') {
        $startedTs = strtotime($startedAt);
        $completedTs = strtotime($completedAt);
        if ($startedTs !== false && $completedTs !== false && $completedTs >= $startedTs) {
            $remaining = $completedTs - $startedTs;
            $hours = (int) floor($remaining / 3600);
            $remaining -= $hours * 3600;
            $minutes = (int) floor($remaining / 60);
            $seconds = $remaining % 60;

            if ($hours > 0) {
                return sprintf('%dh %02dm', $hours, $minutes);
            }

            if ($minutes > 0) {
                return sprintf('%dm %ds', $minutes, $seconds);
            }

            return sprintf('%ds', $seconds);
        }
    }

    $statusMeta = $strictModeEnabled
        ? $displayStatusForPlayer($player, $roomEnded, $strictModeEnabled, $hardCodeModeEnabled)
        : $statusForPlayer($player, $roomEnded);
    return $statusMeta['label'];
};

foreach ($roomSessionPlayers as $roomSessionPlayer) {
    $statusMeta = $statusForPlayer($roomSessionPlayer, $roomSessionIsEnded);
    if ($statusMeta['label'] === 'Solving') {
        $roomSessionStartedCount++;
    } elseif ($statusMeta['label'] === 'Completed') {
        $roomSessionCompletedCount++;
    }
}
?>

<main class="teacher-shell teacher-room-session-page relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <?php
        $roomCode = trim((string) ($roomSessionRoom['room_code'] ?? '')) ?: 'Not set';
        $roomName = trim((string) ($roomSessionRoom['room_name'] ?? 'Untitled Room')) ?: 'Untitled Room';
        $challengeName = trim((string) ($roomSessionRoom['challenge_name'] ?? 'Unknown Challenge')) ?: 'Unknown Challenge';
        $strictModeEnabled = (int) ($roomSessionRoom['mode'] ?? 0) === 1;
        $hardCodeModeEnabled = (int) ($roomSessionRoom['mode'] ?? 0) === 3;
        $roomStateIsOpen = (int) ($roomSessionRoom['status'] ?? 1) === 1;
        ?>

        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Room Session</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?></h1>
                        <?php if ($hardCodeModeEnabled) : ?>
                            <span class="teacher-pill shrink-0 bg-arcade-yellow"><?= (int) ($roomSessionRoom['room_points'] ?? 0) ?> activity pts</span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="teacher-pill bg-arcade-yellow"><?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="teacher-pill bg-arcade-cyan/25"><?= htmlspecialchars($challengeName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="teacher-pill <?= ($strictModeEnabled || $hardCodeModeEnabled) ? 'bg-arcade-coral/25' : 'bg-arcade-mint/35' ?>"><?= $hardCodeModeEnabled ? 'Hard code' : ($strictModeEnabled ? 'Strict mode' : 'Practice mode') ?></span>
                        <span class="teacher-pill bg-white">Timer: <?= (int) ($roomSessionRoom['timer_limit'] ?? 0) > 0 ? (int) ($roomSessionRoom['timer_limit'] ?? 0) . ' min' : 'No timer' ?></span>
                        <span class="teacher-pill <?= $roomStateIsOpen ? 'bg-arcade-mint/40' : 'bg-arcade-coral/25' ?>"><?= $roomStateIsOpen ? 'Open' : 'Closed' ?></span>
                        <span id="room-session-started-pill" class="teacher-pill <?= $roomSessionIsEnded ? 'bg-arcade-coral text-white' : ($roomSessionIsStarted ? 'bg-arcade-orange text-white' : 'bg-white') ?>"><?= $roomSessionIsEnded ? 'Room Ended' : ($roomSessionIsStarted ? 'Room Started' : 'Waiting to Start') ?></span>
                        <?php if ($roomSessionDeadlineIso !== '') : ?>
                            <span id="room-session-timer-pill" class="teacher-pill bg-white" data-deadline-at="<?= htmlspecialchars($roomSessionDeadlineIso, ENT_QUOTES, 'UTF-8') ?>">Time Left: --:--</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php if (!$roomSessionIsStarted) : ?>
                        <form action="./?c=room-session&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>" method="post" class="contents">
                            <?= teacherPanelCsrfField() ?>
                            <input type="hidden" name="room_action" value="start_session">
                            <input type="hidden" name="room_id" value="<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>">
                            <button type="submit" class="teacher-button teacher-button--primary gap-2">
                                <i data-lucide="play" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Start Room</span>
                            </button>
                        </form>
                    <?php elseif (!$roomSessionIsEnded) : ?>
                        <button type="button" class="teacher-button teacher-button--danger gap-2" data-bs-toggle="modal" data-bs-target="#room-session-end-modal">
                            <i data-lucide="square" class="h-4 w-4" aria-hidden="true"></i>
                            <span>End Room</span>
                        </button>
                    <?php endif; ?>
                    <a href="./?c=room-session&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>&export=csv" class="teacher-button teacher-button--light gap-2" aria-label="Export room records as CSV">
                        <i data-lucide="file-spreadsheet" class="h-4 w-4" aria-hidden="true"></i>
                        <span>CSV</span>
                    </a>
                    <a href="./?c=room-session&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>&export=pdf" class="teacher-button teacher-button--light gap-2" aria-label="Export room records as PDF">
                        <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
                        <span>PDF</span>
                    </a>
                    <a href="./?c=room-view&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Room Details</span>
                    </a>
                </div>
            </div>
        </article>

        <section class="room-session-summary-grid grid gap-3 md:grid-cols-3">
            <article class="teacher-panel room-session-summary-card rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Joined</p>
                <strong id="room-session-joined-count" class="mt-3 block text-3xl font-black"><?= (int) $roomSessionJoinedCount ?></strong>
            </article>
            <article class="teacher-panel room-session-summary-card rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Solving</p>
                <strong id="room-session-solving-count" class="mt-3 block text-3xl font-black"><?= (int) $roomSessionStartedCount ?></strong>
            </article>
            <article class="teacher-panel room-session-summary-card rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Completed</p>
                <strong id="room-session-completed-count" class="mt-3 block text-3xl font-black"><?= (int) $roomSessionCompletedCount ?></strong>
            </article>
        </section>

        <section class="teacher-panel room-session-player-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Joined Players</p>
                    <h2 class="mt-2 text-2xl font-black">Player Records</h2>
                </div>
                <div class="flex flex-col gap-2 sm:items-end">
                    <p id="room-session-record-count" class="text-sm font-bold text-arcade-ink/60"><?= (int) $roomSessionJoinedCount ?> record<?= $roomSessionJoinedCount === 1 ? '' : 's' ?></p>
                    <label class="flex items-center gap-2 text-sm font-bold text-arcade-ink/70">
                        <span class="shrink-0">Sort by</span>
                        <select id="room-session-player-sort" class="min-w-0 rounded-xl border-2 border-arcade-ink/15 bg-white px-3 py-2 text-sm font-bold text-arcade-ink outline-none transition focus:border-arcade-orange">
                            <option value="name-asc">Name (A-Z)</option>
                            <option value="name-desc">Name (Z-A)</option>
                            <option value="completed-desc">Completion date (newest)</option>
                            <option value="completed-asc">Completion date (oldest)</option>
                            <?php if ($strictModeEnabled) : ?>
                                <option value="score-desc">Highest score</option>
                            <?php endif; ?>
                            <option value="duration-asc">Fastest</option>
                        </select>
                    </label>
                </div>
            </div>

            <?php if ($roomSessionPlayers === []) : ?>
                <div id="room-session-empty-state" class="mt-4 rounded-2xl border-2 border-dashed border-arcade-ink/12 bg-white/80 px-4 py-5 text-sm font-bold text-arcade-ink/60">
                    No players have joined this room yet.
                </div>
            <?php else : ?>
                <div id="room-session-empty-state" class="mt-4 hidden rounded-2xl border-2 border-dashed border-arcade-ink/12 bg-white/80 px-4 py-5 text-sm font-bold text-arcade-ink/60">
                    No players have joined this room yet.
                </div>
            <?php endif; ?>
            <div id="room-session-player-list" class="room-session-player-list mt-4 grid gap-3<?= $roomSessionPlayers === [] ? ' hidden' : '' ?>">
                <?php foreach ($roomSessionPlayers as $roomSessionPlayer) : ?>
                    <?php
                    $playerStatus = $displayStatusForPlayer($roomSessionPlayer, $roomSessionIsEnded, $strictModeEnabled, $hardCodeModeEnabled);
                    $playerDuration = $formatPlayerDuration($roomSessionPlayer, $roomSessionIsEnded, $strictModeEnabled, $hardCodeModeEnabled);
                    $displayName = trim((string) ($roomSessionPlayer['firstname'] ?? '') . ' ' . (string) ($roomSessionPlayer['lastname'] ?? ''))
                        ?: trim((string) ($roomSessionPlayer['username'] ?? 'Student'))
                        ?: 'Student';
                    $playerInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2));
                    $avatarUrl = trim((string) ($roomSessionPlayer['avatar_url'] ?? ''));
                    $studentNumber = trim((string) ($roomSessionPlayer['student_number'] ?? ''));
                    $studentSection = trim((string) ($roomSessionPlayer['section'] ?? ''));
                    $rawCodeSolutionGrade = $roomSessionPlayer['code_solution_grade'] ?? null;
                    $codeSolutionGrade = $rawCodeSolutionGrade === null
                        ? null
                        : max(0, (int) $rawCodeSolutionGrade);
                    ?>
                    <article
                        class="room-session-card rounded-[22px] border-2 border-arcade-ink/12 bg-white p-4"
                        data-room-player-id="<?= (int) ($roomSessionPlayer['rp_id'] ?? 0) ?>"
                        data-room-player-user-id="<?= (int) ($roomSessionPlayer['user_id'] ?? 0) ?>"
                        data-player-name="<?= htmlspecialchars(strtolower($displayName), ENT_QUOTES, 'UTF-8') ?>"
                        data-player-started-at="<?= htmlspecialchars((string) ($roomSessionPlayer['started_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-player-completed-at="<?= htmlspecialchars((string) ($roomSessionPlayer['completed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-code-solution-url="<?= htmlspecialchars((string) ($roomSessionPlayer['code_solution_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-player-score="<?= (int) ($roomSessionPlayer['strict_mode_score'] ?? 0) ?>">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-arcade text-[11px] text-arcade-ink">
                                    <?php if ($avatarUrl !== '') : ?>
                                        <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                    <?php else : ?>
                                        <?= htmlspecialchars($playerInitials, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-xl font-black"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p class="mt-1 truncate text-sm font-bold text-arcade-ink/60">@<?= htmlspecialchars((string) ($roomSessionPlayer['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="truncate text-sm font-bold text-arcade-ink/60"><?= htmlspecialchars((string) ($roomSessionPlayer['email'] ?? 'No email'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <span
                                    class="teacher-pill room-session-pill <?= htmlspecialchars($playerStatus['class'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-room-player-status-pill>
                                    <?= htmlspecialchars($playerStatus['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if (!$roomSessionIsEnded) : ?>
                                    <button type="button"
                                        class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-coral bg-arcade-coral/10 text-arcade-coral transition hover:bg-arcade-coral hover:text-white disabled:cursor-wait disabled:opacity-60"
                                        data-remove-room-player
                                        data-player-user-id="<?= (int) ($roomSessionPlayer['user_id'] ?? 0) ?>"
                                        data-player-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Remove <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?> from room"
                                        title="Remove player">
                                        <i data-lucide="user-round-x" class="h-5 w-5" aria-hidden="true"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($hardCodeModeEnabled && trim((string) ($roomSessionPlayer['code_solution_url'] ?? '')) !== '') : ?>
                                    <button type="button" class="teacher-button teacher-button--light gap-2" data-view-code-solution data-solution-url="<?= htmlspecialchars((string) $roomSessionPlayer['code_solution_url'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i data-lucide="code-2" class="h-4 w-4" aria-hidden="true"></i>
                                        <span>View Design</span>
                                    </button>
                                    <button type="button"
                                        class="teacher-button teacher-button--primary gap-2"
                                        data-add-hard-code-grade
                                        data-room-player-id="<?= (int) ($roomSessionPlayer['rp_id'] ?? 0) ?>"
                                        data-player-name="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-current-grade="<?= $codeSolutionGrade === null ? '' : $codeSolutionGrade ?>">
                                        <i data-lucide="clipboard-check" class="h-4 w-4" aria-hidden="true"></i>
                                        <span data-grade-button-label><?= $codeSolutionGrade === null ? 'Add Grade' : 'Edit Grade (' . $codeSolutionGrade . ')' ?></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="room-session-info-grid mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="room-session-info-card">
                                <p>Student ID</p>
                                <strong><?= htmlspecialchars($studentNumber !== '' ? $studentNumber : 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="room-session-info-card">
                                <p>Section</p>
                                <strong><?= htmlspecialchars($studentSection !== '' ? $studentSection : 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="room-session-info-card">
                                <p>Record ID</p>
                                <strong>#<?= (int) ($roomSessionPlayer['rp_id'] ?? 0) ?></strong>
                            </div>
                            <div class="room-session-info-card">
                                <p>Duration</p>
                                <strong data-room-player-duration><?= htmlspecialchars($playerDuration, ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</main>

<?php if ($roomSessionIsStarted && !$roomSessionIsEnded) : ?>
    <div class="modal fade" id="room-session-end-modal" tabindex="-1" aria-labelledby="room-session-end-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
                <div class="modal-header border-0 px-4 pb-2 pt-4">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-coral">End Room</p>
                        <h2 id="room-session-end-modal-title" class="modal-title mt-2 text-xl font-bold">End this room now?</h2>
                    </div>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4 pt-2">
                    <p class="text-sm font-semibold leading-7 text-arcade-ink/70">
                        This will mark the room as ended and stop the current room session.
                    </p>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                        <form action="./?c=room-session&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>" method="post">
                            <?= teacherPanelCsrfField() ?>
                            <input type="hidden" name="room_action" value="end_session">
                            <input type="hidden" name="room_id" value="<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>">
                            <button type="submit" class="teacher-button teacher-button--danger gap-2">
                                <i data-lucide="square" class="h-4 w-4" aria-hidden="true"></i>
                                <span>End Room</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="room-player-remove-modal" tabindex="-1" aria-labelledby="room-player-remove-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-coral">Remove Player</p>
                    <h2 id="room-player-remove-modal-title" class="modal-title mt-2 text-xl font-bold">Remove this player?</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <p class="text-sm font-semibold leading-7 text-arcade-ink/70">
                    <strong data-remove-player-name>Player</strong> will be removed immediately and redirected to the dashboard.
                </p>
                <p class="mt-3 hidden rounded-xl border-2 border-arcade-coral/30 bg-arcade-coral/10 px-3 py-2 text-sm font-bold text-arcade-coral" data-remove-player-error></p>
                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="teacher-button teacher-button--danger gap-2" data-confirm-remove-player>
                        <i data-lucide="user-round-x" class="h-4 w-4" aria-hidden="true"></i>
                        <span data-remove-player-button-text>Remove Player</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($hardCodeModeEnabled) : ?>
<div class="modal fade" id="hard-code-grade-modal" tabindex="-1" aria-labelledby="hard-code-grade-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Hard Code Grade</p>
                    <h2 id="hard-code-grade-modal-title" class="modal-title mt-2 text-xl font-bold">Grade submitted design</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <p class="text-sm font-semibold leading-6 text-arcade-ink/70">
                    Enter the grade for <strong data-grade-player-name>this student</strong>, then confirm to save it.
                </p>
                <label class="mt-4 block text-sm font-bold" for="hard-code-grade-input">
                    Grade
                    <span class="mt-2 flex w-full overflow-hidden rounded-xl border-2 border-arcade-ink/15 bg-white transition focus-within:border-arcade-orange">
                        <input id="hard-code-grade-input" type="number" min="0" step="1" inputmode="numeric" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-lg font-black outline-none" placeholder="Enter grade" aria-describedby="hard-code-grade-total" data-hard-code-grade-input>
                        <span id="hard-code-grade-total" class="flex shrink-0 items-center border-l-2 border-arcade-ink/10 bg-arcade-cream px-4 text-lg font-black text-arcade-ink/70" aria-label="out of <?= (int) ($roomSessionRoom['room_points'] ?? 0) ?> points">
                            / <?= (int) ($roomSessionRoom['room_points'] ?? 0) ?>
                        </span>
                    </span>
                </label>
                <p class="mt-3 hidden rounded-xl border-2 border-arcade-coral/30 bg-arcade-coral/10 px-3 py-2 text-sm font-bold text-arcade-coral" data-hard-code-grade-error></p>
                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" class="teacher-button teacher-button--light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="teacher-button teacher-button--primary gap-2" data-confirm-hard-code-grade>
                        <span class="hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true" data-grade-submit-spinner></span>
                        <i data-lucide="check" class="h-4 w-4" aria-hidden="true" data-grade-submit-icon></i>
                        <span data-grade-submit-label>Save Grade</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($hardCodeModeEnabled) : ?>
<div class="modal fade" id="hard-code-preview-modal" tabindex="-1" aria-labelledby="hard-code-preview-title" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-arcade-panel text-arcade-ink">
            <div class="modal-header border-b-2 border-arcade-ink/10">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Hard Code Submission</p>
                    <h2 id="hard-code-preview-title" class="modal-title mt-2 text-xl font-bold">Submitted design</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body flex min-h-0 flex-col">
                <div class="hard-code-preview-layout grid min-h-0 flex-1 gap-4 lg:grid-cols-[minmax(280px,0.7fr)_minmax(0,1.3fr)]" data-hard-code-preview-layout>
                    <section class="hard-code-submission-source flex min-h-[420px] flex-col rounded-2xl border-2 p-3" data-hard-code-source-panel>
                        <p class="mb-2 font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Submitted CSS</p>
                        <pre class="min-h-0 flex-1 overflow-auto whitespace-pre-wrap font-mono text-sm leading-6" data-hard-code-source>Loading...</pre>
                    </section>
                    <section class="flex min-h-[420px] flex-col rounded-2xl border-2 border-arcade-ink/10 bg-white p-3">
                        <div class="mb-2 flex min-h-10 items-center justify-between gap-3">
                            <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Student Design</p>
                            <button type="button" class="teacher-button teacher-button--light shrink-0 gap-2" data-hard-code-compare-toggle disabled>
                                <i data-lucide="columns-2" class="h-4 w-4" aria-hidden="true"></i>
                                <span data-hard-code-compare-label>Compare Target</span>
                            </button>
                        </div>
                        <div class="hard-code-submission-frame min-h-0 flex-1 rounded-xl border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-3" data-hard-code-preview-frame>
                            <iframe class="hard-code-submission-preview" title="Submitted hard code design" sandbox="allow-same-origin" data-hard-code-preview></iframe>
                        </div>
                    </section>
                    <section class="hidden min-h-[420px] flex-col rounded-2xl border-2 border-arcade-ink/10 bg-white p-3" data-hard-code-target-panel>
                        <p class="mb-2 font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-cyan">Target Design</p>
                        <div class="hard-code-submission-frame min-h-0 flex-1 rounded-xl border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-3">
                            <iframe class="hard-code-submission-preview" title="Target challenge design" sandbox="allow-same-origin" data-hard-code-target-preview></iframe>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.room-session-card {
    box-shadow: 0 10px 26px rgba(38, 25, 15, 0.08);
}

.hard-code-submission-frame {
    position: relative;
    overflow: hidden;
}

.hard-code-submission-source {
    border-color: rgba(38, 25, 15, 0.28);
    background:
        linear-gradient(rgba(255, 255, 255, 0.42), rgba(255, 255, 255, 0.08)),
        #fff0bd;
    color: #26190f;
    box-shadow: inset 0 0 0 2px rgba(255, 209, 102, 0.28), inset 0 3px 0 rgba(255, 255, 255, 0.5);
}

body.pixelwar-dark-mode .hard-code-submission-source {
    border-color: rgba(255, 209, 102, 0.5);
    background:
        linear-gradient(145deg, rgba(255, 140, 66, 0.12), rgba(255, 209, 102, 0.04)),
        #2d1d12;
    color: #fff1cb;
    box-shadow: inset 0 0 0 2px rgba(255, 209, 102, 0.08);
}

.hard-code-submission-preview {
    display: block;
    width: 100%;
    height: 100%;
    min-height: 100%;
    border: 0;
    border-radius: 0.75rem;
    background: #f7efe1;
}

@media (min-width: 1024px) {
    .hard-code-preview-layout.is-comparing {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
}

.room-session-pill--joined {
    background: rgba(76, 201, 240, 0.22);
}

.room-session-pill--started {
    background: rgba(255, 209, 102, 0.35);
}

.room-session-pill--completed {
    background: rgba(139, 211, 199, 0.45);
}

.room-session-pill--gave-up {
    background: rgba(249, 115, 115, 0.28);
}

.room-session-info-card {
    border: 2px solid rgba(38, 25, 15, 0.1);
    border-radius: 1rem;
    background: rgba(255, 247, 232, 0.78);
    padding: 0.9rem 1rem;
}

.room-session-info-card p {
    margin: 0;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.56);
}

.room-session-info-card strong {
    display: block;
    margin-top: 0.45rem;
    font-size: 0.98rem;
    font-weight: 900;
    color: #26190f;
}

@media (max-width: 640px) {
    .teacher-room-session-page {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .teacher-room-session-page .teacher-hero,
    .teacher-room-session-page .room-session-summary-grid,
    .teacher-room-session-page .room-session-player-panel {
        width: min(95vw, 34rem) !important;
        max-width: min(95vw, 34rem) !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    .teacher-room-session-page .room-session-summary-grid,
    .teacher-room-session-page .room-session-player-list,
    .teacher-room-session-page .room-session-info-grid {
        display: block !important;
    }

    .teacher-room-session-page .room-session-summary-card,
    .teacher-room-session-page .room-session-card {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .teacher-room-session-page .room-session-summary-card + .room-session-summary-card,
    .teacher-room-session-page .room-session-card + .room-session-card,
    .teacher-room-session-page .room-session-info-card + .room-session-info-card {
        margin-top: 0.75rem !important;
    }

    .teacher-room-session-page .room-session-summary-card {
        padding: 0.8rem 1rem !important;
        text-align: center;
    }

    .teacher-room-session-page .room-session-summary-card strong {
        margin-top: 0.35rem !important;
        font-size: 1.65rem !important;
    }

    .teacher-room-session-page .room-session-player-panel,
    .teacher-room-session-page .room-session-card {
        padding: 1rem !important;
    }

    .teacher-room-session-page .room-session-card h3 {
        font-size: 1.05rem !important;
        line-height: 1.35 !important;
    }

    .teacher-room-session-page .room-session-pill {
        align-self: flex-start;
        margin-left: 4.25rem;
    }

    .teacher-room-session-page .room-session-info-grid {
        margin-top: 0.85rem !important;
    }

    .teacher-room-session-page .room-session-info-card {
        padding: 0.72rem 0.85rem !important;
    }

    .teacher-room-session-page .room-session-info-card:nth-child(2) {
        display: none !important;
    }
}
</style>

<?php if ($pusherEnabled) : ?>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<?php endif; ?>
<script>
    (() => {
        const roomId = <?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>;
        const syncUrl = './?c=room-session&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>';
        const sessionCsrfToken = <?= json_encode(teacherPanelCsrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const pusherKey = <?= json_encode($pusherEnabled ? (string) PUSHER_KEY : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const pusherCluster = <?= json_encode($pusherEnabled ? (string) PUSHER_CLUSTER : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const strictModeEnabled = <?= $strictModeEnabled ? 'true' : 'false' ?>;
        const hardCodeModeEnabled = <?= $hardCodeModeEnabled ? 'true' : 'false' ?>;
        const hardCodeHtmlUrl = <?= json_encode((string) ($roomSessionRoom['html_source'] ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const hardCodeTargetCssUrl = <?= json_encode((string) ($roomSessionRoom['css_source'] ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const playerList = document.getElementById('room-session-player-list');
        const playerSort = document.getElementById('room-session-player-sort');
        const emptyState = document.getElementById('room-session-empty-state');
        const recordCount = document.getElementById('room-session-record-count');
        const startedPill = document.getElementById('room-session-started-pill');
        const timerPill = document.getElementById('room-session-timer-pill');
        const joinedCount = document.getElementById('room-session-joined-count');
        const solvingCount = document.getElementById('room-session-solving-count');
        const completedCount = document.getElementById('room-session-completed-count');
        const removePlayerModalElement = document.getElementById('room-player-remove-modal');
        const removePlayerModal = removePlayerModalElement && window.bootstrap?.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(removePlayerModalElement)
            : null;
        const removePlayerName = removePlayerModalElement?.querySelector('[data-remove-player-name]');
        const removePlayerError = removePlayerModalElement?.querySelector('[data-remove-player-error]');
        const confirmRemovePlayer = removePlayerModalElement?.querySelector('[data-confirm-remove-player]');
        const removePlayerButtonText = removePlayerModalElement?.querySelector('[data-remove-player-button-text]');
        let selectedPlayerUserId = 0;
        const gradeModalElement = document.getElementById('hard-code-grade-modal');
        const gradeModal = gradeModalElement && window.bootstrap?.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(gradeModalElement)
            : null;
        const gradePlayerName = gradeModalElement?.querySelector('[data-grade-player-name]');
        const gradeInput = gradeModalElement?.querySelector('[data-hard-code-grade-input]');
        const gradeError = gradeModalElement?.querySelector('[data-hard-code-grade-error]');
        const confirmGradeButton = gradeModalElement?.querySelector('[data-confirm-hard-code-grade]');
        const gradeSubmitLabel = gradeModalElement?.querySelector('[data-grade-submit-label]');
        const gradeSubmitSpinner = gradeModalElement?.querySelector('[data-grade-submit-spinner]');
        const gradeSubmitIcon = gradeModalElement?.querySelector('[data-grade-submit-icon]');
        let selectedGradeRoomPlayerId = 0;
        let selectedGradeTrigger = null;
        let roomEndSubmitting = false;
        let roomEnded = <?= $roomSessionIsEnded ? 'true' : 'false' ?>;

        const statusClassMap = {
            waiting: 'room-session-pill--joined',
            solving: 'room-session-pill--started',
            completed: 'room-session-pill--completed',
            gave_up: 'room-session-pill--gave-up',
        };

        const renderStatus = (label) => {
            const normalized = String(label || 'waiting').toLowerCase();
            if (strictModeEnabled && /^\d{1,3}%$/.test(String(label || '').trim())) {
                const score = Math.max(0, Math.min(100, Number.parseInt(String(label).trim(), 10) || 0));
                if (score >= 100) {
                    return { label: '100%', className: statusClassMap.completed };
                }
                if (score > 0) {
                    return { label: `${score}%`, className: statusClassMap.solving };
                }
                return { label: '0%', className: statusClassMap.gave_up };
            }
            if (normalized === 'failed' || normalized === 'gave_up' || normalized === 'gave up') {
                return { label: 'Failed', className: statusClassMap.gave_up };
            }
            if (normalized === 'completed') {
                return { label: 'Completed', className: statusClassMap.completed };
            }
            if (normalized === 'submitted') {
                return { label: 'Submitted', className: statusClassMap.completed };
            }
            if (normalized === 'solving') {
                return { label: 'Solving', className: statusClassMap.solving };
            }
            return { label: 'Waiting', className: statusClassMap.waiting };
        };

        const formatDuration = (startedAt, completedAt, statusLabel) => {
            if (startedAt && completedAt) {
                const startedMs = new Date(startedAt).getTime();
                const completedMs = new Date(completedAt).getTime();
                if (!Number.isNaN(startedMs) && !Number.isNaN(completedMs) && completedMs >= startedMs) {
                    let remaining = Math.floor((completedMs - startedMs) / 1000);
                    const hours = Math.floor(remaining / 3600);
                    remaining -= hours * 3600;
                    const minutes = Math.floor(remaining / 60);
                    const seconds = remaining % 60;

                    if (hours > 0) {
                        return `${hours}h ${String(minutes).padStart(2, '0')}m`;
                    }
                    if (minutes > 0) {
                        return `${minutes}m ${seconds}s`;
                    }
                    return `${seconds}s`;
                }
            }

            return renderStatus(statusLabel).label;
        };

        const timestampValue = (value) => {
            const normalized = String(value || '').trim().replace(' ', 'T');
            const timestamp = normalized ? new Date(normalized).getTime() : Number.NaN;
            return Number.isNaN(timestamp) ? null : timestamp;
        };

        const completedDuration = (card) => {
            const startedAt = timestampValue(card.dataset.playerStartedAt || '');
            const completedAt = timestampValue(card.dataset.playerCompletedAt || '');
            return startedAt !== null && completedAt !== null && completedAt >= startedAt
                ? completedAt - startedAt
                : null;
        };

        const sortPlayerCards = () => {
            if (!playerList || !playerSort) {
                return;
            }

            const cards = Array.from(playerList.querySelectorAll('[data-room-player-user-id]'));
            const sortMode = playerSort.value;
            const compareNullableNumbers = (left, right, direction = 1) => {
                if (left === null && right === null) return 0;
                if (left === null) return 1;
                if (right === null) return -1;
                return (left - right) * direction;
            };
            const byName = (left, right) => (left.dataset.playerName || '').localeCompare(right.dataset.playerName || '', undefined, { sensitivity: 'base' });

            cards.sort((left, right) => {
                let result = 0;
                if (sortMode === 'name-desc') {
                    result = -byName(left, right);
                } else if (sortMode === 'completed-desc') {
                    result = compareNullableNumbers(timestampValue(left.dataset.playerCompletedAt || ''), timestampValue(right.dataset.playerCompletedAt || ''), -1);
                } else if (sortMode === 'completed-asc') {
                    result = compareNullableNumbers(timestampValue(left.dataset.playerCompletedAt || ''), timestampValue(right.dataset.playerCompletedAt || ''));
                } else if (sortMode === 'score-desc') {
                    result = Number(right.dataset.playerScore || 0) - Number(left.dataset.playerScore || 0);
                } else if (sortMode === 'duration-asc') {
                    result = compareNullableNumbers(completedDuration(left), completedDuration(right));
                } else {
                    result = byName(left, right);
                }

                return result || byName(left, right);
            });

            cards.forEach((card) => playerList.appendChild(card));
        };

        const renderCountdown = () => {
            if (!timerPill) {
                return;
            }

            const deadlineAt = timerPill.getAttribute('data-deadline-at') || '';
            if (!deadlineAt) {
                timerPill.textContent = 'Time Left: --:--';
                return;
            }

            const deadlineMs = new Date(deadlineAt).getTime();
            if (Number.isNaN(deadlineMs)) {
                timerPill.textContent = 'Time Left: --:--';
                return;
            }

            const remainingMs = Math.max(0, deadlineMs - Date.now());
            const totalSeconds = Math.floor(remainingMs / 1000);
            const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            timerPill.textContent = `Time Left: ${minutes}:${seconds}`;

            if (totalSeconds <= 0 && !roomEndSubmitting) {
                roomEndSubmitting = true;
                const body = new URLSearchParams();
                body.set('room_action', 'end_session');
                body.set('room_id', String(roomId));
                body.set('_csrf_token', sessionCsrfToken || '');

                fetch(syncUrl, {
                    method: 'POST',
                    body: body.toString(),
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                }).catch(() => {});
            }
        };

        const updateCount = () => {
            if (!playerList || !recordCount) {
                return;
            }

            const cards = Array.from(playerList.querySelectorAll('[data-room-player-user-id]'));
            const total = cards.length;
            let solving = 0;
            let completed = 0;

            cards.forEach((card) => {
                const pill = card.querySelector('[data-room-player-status-pill]');
                const statusText = (pill?.textContent || '').trim().toLowerCase();
                if (statusText === 'solving') {
                    solving++;
                } else if (statusText === 'completed' || statusText === 'submitted' || statusText === '100%') {
                    completed++;
                }
            });

            recordCount.textContent = `${total} record${total === 1 ? '' : 's'}`;
            if (emptyState) {
                emptyState.classList.toggle('hidden', total > 0);
            }
            if (playerList) {
                playerList.classList.toggle('hidden', total === 0);
            }
            if (joinedCount) {
                joinedCount.textContent = String(total);
            }
            if (solvingCount) {
                solvingCount.textContent = String(solving);
            }
            if (completedCount) {
                completedCount.textContent = String(completed);
            }
        };

        const applyStatus = (card, label, startedAt, completedAt, durationLabel = '') => {
            const status = renderStatus(label);
            const pill = card.querySelector('[data-room-player-status-pill]');
            const duration = card.querySelector('[data-room-player-duration]');

            if (pill) {
                pill.textContent = status.label;
                pill.classList.remove('room-session-pill--joined', 'room-session-pill--started', 'room-session-pill--completed', 'room-session-pill--gave-up');
                pill.classList.add(status.className);
            }

            if (duration) {
                duration.textContent = durationLabel || formatDuration(startedAt, completedAt, label);
            }
        };

        const createCard = (payload) => {
            if (!playerList) {
                return;
            }

            const article = document.createElement('article');
            article.className = 'room-session-card rounded-[22px] border-2 border-arcade-ink/12 bg-white p-4';
            article.setAttribute('data-room-player-id', String(payload.rp_id || 0));
            article.setAttribute('data-room-player-user-id', String(payload.user_id || 0));
            article.dataset.playerName = String(payload.name || payload.username || 'Student').toLowerCase();
            article.dataset.playerStartedAt = String(payload.started_at || '');
            article.dataset.playerCompletedAt = String(payload.completed_at || '');
            article.dataset.playerScore = String(payload.strict_mode_score || 0);
            const initials = String(payload.initials || 'ST');
            const avatar = payload.avatar_url
                ? `<img src="${payload.avatar_url}" alt="" class="h-full w-full object-cover">`
                : initials;

            article.innerHTML = `
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-arcade text-[11px] text-arcade-ink">${avatar}</span>
                        <div class="min-w-0">
                            <h3 class="truncate text-xl font-black">${payload.name || 'Student'}</h3>
                            <p class="mt-1 truncate text-sm font-bold text-arcade-ink/60">@${payload.username || ''}</p>
                            <p class="truncate text-sm font-bold text-arcade-ink/60">${payload.email || 'No email'}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <span class="teacher-pill room-session-pill room-session-pill--joined" data-room-player-status-pill>Waiting</span>
                        ${hardCodeModeEnabled && payload.code_solution_url ? `<button type="button" class="teacher-button teacher-button--light gap-2" data-view-code-solution data-solution-url="${payload.code_solution_url}"><i data-lucide="code-2" class="h-4 w-4" aria-hidden="true"></i><span>View Design</span></button>` : ''}
                        ${hardCodeModeEnabled && payload.code_solution_url ? `<button type="button" class="teacher-button teacher-button--primary gap-2" data-add-hard-code-grade data-room-player-id="${payload.rp_id || 0}" data-player-name="${payload.name || 'Student'}" data-current-grade="${payload.code_solution_grade ?? ''}"><i data-lucide="clipboard-check" class="h-4 w-4" aria-hidden="true"></i><span data-grade-button-label>${payload.code_solution_grade == null ? 'Add Grade' : `Edit Grade (${payload.code_solution_grade})`}</span></button>` : ''}
                        ${roomEnded ? '' : `<button type="button" class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-coral bg-arcade-coral/10 text-arcade-coral transition hover:bg-arcade-coral hover:text-white disabled:cursor-wait disabled:opacity-60" data-remove-room-player data-player-user-id="${payload.user_id || 0}" data-player-name="${payload.name || 'Student'}" aria-label="Remove player from room" title="Remove player">
                            <i data-lucide="user-round-x" class="h-5 w-5" aria-hidden="true"></i>
                        </button>`}
                    </div>
                </div>
                <div class="room-session-info-grid mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="room-session-info-card">
                        <p>Student ID</p>
                        <strong>${payload.student_number || 'Not set'}</strong>
                    </div>
                    <div class="room-session-info-card">
                        <p>Section</p>
                        <strong>${payload.section || 'Not set'}</strong>
                    </div>
                    <div class="room-session-info-card">
                        <p>Record ID</p>
                        <strong>#${payload.rp_id || 0}</strong>
                    </div>
                    <div class="room-session-info-card">
                        <p>Duration</p>
                        <strong data-room-player-duration>${payload.duration_label || 'Waiting'}</strong>
                    </div>
                </div>
            `;

            playerList.appendChild(article);
            window.lucide?.createIcons();
            updateCount();
            sortPlayerCards();
        };

        playerList?.addEventListener('click', (event) => {
            const gradeButton = event.target.closest('[data-add-hard-code-grade]');
            if (!gradeButton || !gradeModal || !(gradeInput instanceof HTMLInputElement)) {
                return;
            }

            selectedGradeRoomPlayerId = Number(gradeButton.dataset.roomPlayerId || 0);
            if (selectedGradeRoomPlayerId <= 0) {
                return;
            }

            selectedGradeTrigger = gradeButton;
            gradeInput.value = gradeButton.dataset.currentGrade || '';
            if (gradePlayerName) {
                gradePlayerName.textContent = gradeButton.dataset.playerName || 'this student';
            }
            if (gradeError) {
                gradeError.textContent = '';
                gradeError.classList.add('hidden');
            }
            gradeModal.show();
            gradeModalElement?.addEventListener('shown.bs.modal', () => gradeInput.focus(), { once: true });
        });

        confirmGradeButton?.addEventListener('click', () => {
            if (!(gradeInput instanceof HTMLInputElement) || selectedGradeRoomPlayerId <= 0 || confirmGradeButton.disabled) {
                return;
            }

            const grade = Number(gradeInput.value);
            if (gradeInput.value.trim() === '' || !Number.isSafeInteger(grade) || grade < 0 || grade > 2147483647) {
                if (gradeError) {
                    gradeError.textContent = 'Enter a non-negative whole-number grade.';
                    gradeError.classList.remove('hidden');
                }
                gradeInput.focus();
                return;
            }

            confirmGradeButton.disabled = true;
            confirmGradeButton.setAttribute('aria-busy', 'true');
            gradeSubmitSpinner?.classList.remove('hidden');
            gradeSubmitIcon?.classList.add('hidden');
            if (gradeSubmitLabel) {
                gradeSubmitLabel.textContent = 'Saving...';
            }

            const payload = new URLSearchParams();
            payload.set('room_action', 'grade_hard_code');
            payload.set('room_id', String(roomId));
            payload.set('rp_id', String(selectedGradeRoomPlayerId));
            payload.set('grade', String(grade));
            payload.set('_csrf_token', sessionCsrfToken || '');

            fetch(syncUrl, {
                method: 'POST',
                body: payload.toString(),
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                },
            })
                .then(async (response) => {
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok || result.ok !== true) {
                        throw new Error(result.message || 'The grade could not be saved.');
                    }

                    if (selectedGradeTrigger instanceof HTMLElement) {
                        selectedGradeTrigger.dataset.currentGrade = String(result.grade);
                        const label = selectedGradeTrigger.querySelector('[data-grade-button-label]');
                        if (label) {
                            label.textContent = `Edit Grade (${result.grade})`;
                        }
                    }
                    gradeModal?.hide();
                })
                .catch((error) => {
                    if (gradeError) {
                        gradeError.textContent = error.message || 'The grade could not be saved.';
                        gradeError.classList.remove('hidden');
                    }
                })
                .finally(() => {
                    confirmGradeButton.disabled = false;
                    confirmGradeButton.removeAttribute('aria-busy');
                    gradeSubmitSpinner?.classList.add('hidden');
                    gradeSubmitIcon?.classList.remove('hidden');
                    if (gradeSubmitLabel) {
                        gradeSubmitLabel.textContent = 'Save Grade';
                    }
                });
        });

        gradeInput?.addEventListener('input', () => {
            if (gradeError) {
                gradeError.textContent = '';
                gradeError.classList.add('hidden');
            }
        });

        playerList?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-room-player]');
            if (!button) {
                return;
            }

            selectedPlayerUserId = Number(button.dataset.playerUserId || 0);
            if (selectedPlayerUserId <= 0) {
                return;
            }

            if (removePlayerName) {
                removePlayerName.textContent = button.dataset.playerName || 'This player';
            }
            if (removePlayerError) {
                removePlayerError.textContent = '';
                removePlayerError.classList.add('hidden');
            }
            removePlayerModal?.show();
        });

        confirmRemovePlayer?.addEventListener('click', () => {
            if (selectedPlayerUserId <= 0 || confirmRemovePlayer.disabled) {
                return;
            }

            confirmRemovePlayer.disabled = true;
            if (removePlayerButtonText) {
                removePlayerButtonText.textContent = 'Removing...';
            }

            const payload = new URLSearchParams();
            payload.set('room_action', 'remove_player');
            payload.set('room_id', String(roomId));
            payload.set('player_user_id', String(selectedPlayerUserId));
            payload.set('_csrf_token', sessionCsrfToken || '');

            fetch(syncUrl, {
                method: 'POST',
                body: payload.toString(),
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                },
            })
                .then(async (response) => {
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok || result.ok !== true) {
                        throw new Error(result.message || 'The player could not be removed.');
                    }

                    playerList?.querySelector(`[data-room-player-user-id="${selectedPlayerUserId}"]`)?.remove();
                    selectedPlayerUserId = 0;
                    updateCount();
                    removePlayerModal?.hide();
                })
                .catch((error) => {
                    if (removePlayerError) {
                        removePlayerError.textContent = error.message || 'The player could not be removed.';
                        removePlayerError.classList.remove('hidden');
                    }
                })
                .finally(() => {
                    confirmRemovePlayer.disabled = false;
                    if (removePlayerButtonText) {
                        removePlayerButtonText.textContent = 'Remove Player';
                    }
                });
        });

        const upsertCard = (payload) => {
            if (!playerList || !payload?.user_id) {
                return;
            }

            let card = playerList.querySelector(`[data-room-player-user-id="${payload.user_id}"]`);
            if (!card) {
                createCard(payload);
                card = playerList.querySelector(`[data-room-player-user-id="${payload.user_id}"]`);
            }

            if (!card) {
                return;
            }

            const name = card.querySelector('h3');
            const username = card.querySelector('p:nth-of-type(1)');
            const email = card.querySelector('p:nth-of-type(2)');
            const avatarWrap = card.querySelector('span.grid');
            const studentNumber = card.querySelector('.room-session-info-card:nth-child(1) strong');
            const studentSection = card.querySelector('.room-session-info-card:nth-child(2) strong');
            const recordId = card.querySelector('.room-session-info-card:nth-child(3) strong');

            if (name) {
                name.textContent = payload.name || 'Student';
            }
            if (username) {
                username.textContent = `@${payload.username || ''}`;
            }
            if (email) {
                email.textContent = payload.email || 'No email';
            }
            if (avatarWrap) {
                avatarWrap.innerHTML = payload.avatar_url
                    ? `<img src="${payload.avatar_url}" alt="" class="h-full w-full object-cover">`
                    : String(payload.initials || 'ST');
            }
            if (studentNumber) {
                studentNumber.textContent = payload.student_number || 'Not set';
            }
            if (studentSection) {
                studentSection.textContent = payload.section || 'Not set';
            }
            if (recordId) {
                recordId.textContent = `#${payload.rp_id || 0}`;
            }

            card.dataset.playerName = String(payload.name || name?.textContent || payload.username || 'Student').toLowerCase();
            card.dataset.playerStartedAt = String(payload.started_at || card.dataset.playerStartedAt || '');
            card.dataset.playerCompletedAt = String(payload.completed_at || card.dataset.playerCompletedAt || '');
            card.dataset.playerScore = String(payload.strict_mode_score ?? card.dataset.playerScore ?? 0);
            card.dataset.codeSolutionUrl = String(payload.code_solution_url ?? card.dataset.codeSolutionUrl ?? '');
            card.dataset.roomPlayerId = String(payload.rp_id || card.dataset.roomPlayerId || 0);

            if (hardCodeModeEnabled && payload.code_solution_url && !card.querySelector('[data-view-code-solution]')) {
                const actionWrap = card.querySelector('.flex.shrink-0');
                if (actionWrap) {
                    const previewButton = document.createElement('button');
                    previewButton.type = 'button';
                    previewButton.className = 'teacher-button teacher-button--light gap-2';
                    previewButton.dataset.viewCodeSolution = '';
                    previewButton.dataset.solutionUrl = payload.code_solution_url;
                    previewButton.innerHTML = '<i data-lucide="code-2" class="h-4 w-4" aria-hidden="true"></i><span>View Design</span>';
                    actionWrap.appendChild(previewButton);
                    window.lucide?.createIcons();
                }
            }

            if (hardCodeModeEnabled && payload.code_solution_url) {
                let gradeButton = card.querySelector('[data-add-hard-code-grade]');
                if (!gradeButton) {
                    const actionWrap = card.querySelector('.flex.shrink-0');
                    if (actionWrap) {
                        gradeButton = document.createElement('button');
                        gradeButton.type = 'button';
                        gradeButton.className = 'teacher-button teacher-button--primary gap-2';
                        gradeButton.dataset.addHardCodeGrade = '';
                        gradeButton.innerHTML = '<i data-lucide="clipboard-check" class="h-4 w-4" aria-hidden="true"></i><span data-grade-button-label>Add Grade</span>';
                        actionWrap.appendChild(gradeButton);
                        window.lucide?.createIcons();
                    }
                }

                if (gradeButton instanceof HTMLElement) {
                    gradeButton.dataset.roomPlayerId = String(payload.rp_id || card.dataset.roomPlayerId || 0);
                    gradeButton.dataset.playerName = String(payload.name || name?.textContent || payload.username || 'Student');
                    gradeButton.dataset.currentGrade = payload.code_solution_grade == null ? '' : String(payload.code_solution_grade);
                    const gradeLabel = gradeButton.querySelector('[data-grade-button-label]');
                    if (gradeLabel) {
                        gradeLabel.textContent = payload.code_solution_grade == null
                            ? 'Add Grade'
                            : `Edit Grade (${payload.code_solution_grade})`;
                    }
                }
            }

            applyStatus(card, payload.status_label || 'waiting', payload.started_at || '', payload.completed_at || '', payload.duration_label || '');
            updateCount();
            sortPlayerCards();
        };

        const hardCodeModalElement = document.getElementById('hard-code-preview-modal');
        const hardCodeModal = hardCodeModalElement && window.bootstrap?.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(hardCodeModalElement)
            : null;
        const hardCodeSource = hardCodeModalElement?.querySelector('[data-hard-code-source]');
        const hardCodePreview = hardCodeModalElement?.querySelector('[data-hard-code-preview]');
        const hardCodeTargetPreview = hardCodeModalElement?.querySelector('[data-hard-code-target-preview]');
        const hardCodePreviewLayout = hardCodeModalElement?.querySelector('[data-hard-code-preview-layout]');
        const hardCodeSourcePanel = hardCodeModalElement?.querySelector('[data-hard-code-source-panel]');
        const hardCodeTargetPanel = hardCodeModalElement?.querySelector('[data-hard-code-target-panel]');
        const hardCodeCompareToggle = hardCodeModalElement?.querySelector('[data-hard-code-compare-toggle]');
        const hardCodeCompareLabel = hardCodeModalElement?.querySelector('[data-hard-code-compare-label]');
        let hardCodeIsComparing = false;
        const buildHardCodePreviewDocument = (html, css) => `<!doctype html>
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

        const disableHardCodePreviewActions = (frame) => {
            if (!(frame instanceof HTMLIFrameElement) || !frame.contentDocument) {
                return;
            }

            const doc = frame.contentDocument;
            const style = doc.createElement('style');
            style.textContent = 'a, area, button, [role="button"], input, select, textarea { cursor: default !important; }';
            doc.head?.appendChild(style);
            doc.querySelectorAll('a, area').forEach((link) => {
                link.removeAttribute('href');
                link.removeAttribute('target');
                link.setAttribute('tabindex', '-1');
                link.setAttribute('aria-disabled', 'true');
            });

            const blockActivation = (event) => {
                if (event.target?.closest?.('a, area, form, button[type="submit"], input[type="submit"], input[type="image"]')) {
                    event.preventDefault();
                }
            };
            doc.addEventListener('click', blockActivation, true);
            doc.addEventListener('auxclick', blockActivation, true);
            doc.addEventListener('pointerup', blockActivation, true);
            doc.addEventListener('touchend', blockActivation, true);
            doc.addEventListener('submit', blockActivation, true);
        };

        const fitHardCodePreview = (frame) => {
            if (!(frame instanceof HTMLIFrameElement)) {
                return;
            }

            const doc = frame.contentDocument;
            const body = doc?.body;
            const html = doc?.documentElement;
            const shell = frame.parentElement;
            if (!doc || !body || !html || !(shell instanceof HTMLElement)) {
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
            const naturalWidth = Math.max(body.scrollWidth, body.offsetWidth, html.scrollWidth, html.offsetWidth, viewportWidth, 1);
            const naturalHeight = Math.max(body.scrollHeight, body.offsetHeight, html.scrollHeight, html.offsetHeight, viewportHeight, 1);
            const scale = Math.min(shellWidth / naturalWidth, shellHeight / naturalHeight);
            const centeredLeft = paddingLeft + Math.max(0, (shellWidth - (naturalWidth * scale)) / 2);
            const centeredTop = paddingTop + Math.max(0, (shellHeight - (naturalHeight * scale)) / 2);

            frame.style.width = `${naturalWidth}px`;
            frame.style.height = `${naturalHeight}px`;
            frame.style.left = `${centeredLeft}px`;
            frame.style.top = `${centeredTop}px`;
            frame.style.transform = `scale(${scale})`;
        };

        hardCodePreview?.addEventListener('load', () => {
            disableHardCodePreviewActions(hardCodePreview);
            requestAnimationFrame(() => fitHardCodePreview(hardCodePreview));
        });
        hardCodeTargetPreview?.addEventListener('load', () => {
            disableHardCodePreviewActions(hardCodeTargetPreview);
            requestAnimationFrame(() => fitHardCodePreview(hardCodeTargetPreview));
        });
        hardCodeModalElement?.addEventListener('shown.bs.modal', () => {
            requestAnimationFrame(() => fitHardCodePreview(hardCodePreview));
            if (hardCodeIsComparing) {
                requestAnimationFrame(() => fitHardCodePreview(hardCodeTargetPreview));
            }
        });
        if (hardCodePreview?.parentElement && 'ResizeObserver' in window) {
            const hardCodePreviewObserver = new ResizeObserver(() => fitHardCodePreview(hardCodePreview));
            hardCodePreviewObserver.observe(hardCodePreview.parentElement);
        }
        if (hardCodeTargetPreview?.parentElement && 'ResizeObserver' in window) {
            const hardCodeTargetObserver = new ResizeObserver(() => fitHardCodePreview(hardCodeTargetPreview));
            hardCodeTargetObserver.observe(hardCodeTargetPreview.parentElement);
        }

        const setHardCodeCompareMode = (enabled) => {
            hardCodeIsComparing = Boolean(enabled);
            hardCodePreviewLayout?.classList.toggle('is-comparing', hardCodeIsComparing);
            hardCodeSourcePanel?.classList.toggle('hidden', hardCodeIsComparing);
            hardCodeTargetPanel?.classList.toggle('hidden', !hardCodeIsComparing);
            hardCodeTargetPanel?.classList.toggle('flex', hardCodeIsComparing);
            if (hardCodeCompareLabel) {
                hardCodeCompareLabel.textContent = hardCodeIsComparing ? 'Show Code' : 'Compare Target';
            }
            requestAnimationFrame(() => {
                fitHardCodePreview(hardCodePreview);
                if (hardCodeIsComparing) {
                    fitHardCodePreview(hardCodeTargetPreview);
                }
            });
        };

        hardCodeCompareToggle?.addEventListener('click', () => {
            setHardCodeCompareMode(!hardCodeIsComparing);
        });

        playerList?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-view-code-solution]');
            if (!button || !hardCodeModal || !hardCodeSource || !(hardCodePreview instanceof HTMLIFrameElement)) {
                return;
            }

            hardCodeSource.textContent = 'Loading submitted code...';
            hardCodePreview.srcdoc = '';
            if (hardCodeTargetPreview instanceof HTMLIFrameElement) {
                hardCodeTargetPreview.srcdoc = '';
            }
            setHardCodeCompareMode(false);
            if (hardCodeCompareToggle) {
                hardCodeCompareToggle.disabled = true;
            }
            hardCodeModal.show();

            try {
                if (!hardCodeHtmlUrl || !hardCodeTargetCssUrl || !button.dataset.solutionUrl) {
                    throw new Error('The submitted or target design source is unavailable.');
                }

                const [htmlResponse, cssResponse, targetCssResponse] = await Promise.all([
                    fetch(hardCodeHtmlUrl, { cache: 'no-store' }),
                    fetch(button.dataset.solutionUrl, { cache: 'no-store' }),
                    fetch(hardCodeTargetCssUrl, { cache: 'no-store' }),
                ]);
                if (!htmlResponse.ok || !cssResponse.ok || !targetCssResponse.ok) {
                    throw new Error('Submitted design could not be loaded.');
                }
                const [html, css, targetCss] = await Promise.all([htmlResponse.text(), cssResponse.text(), targetCssResponse.text()]);
                const safeCss = css.replace(/<\/style/gi, '<\\/style');
                const safeTargetCss = targetCss.replace(/<\/style/gi, '<\\/style');
                hardCodeSource.textContent = css;
                hardCodePreview.srcdoc = buildHardCodePreviewDocument(html, safeCss);
                if (hardCodeTargetPreview instanceof HTMLIFrameElement) {
                    hardCodeTargetPreview.srcdoc = buildHardCodePreviewDocument(html, safeTargetCss);
                }
                if (hardCodeCompareToggle) {
                    hardCodeCompareToggle.disabled = false;
                }
            } catch (error) {
                hardCodeSource.textContent = error instanceof Error ? error.message : 'Submitted design could not be loaded.';
            }
        });

        const syncPresenceSnapshot = () => {
            const payload = new URLSearchParams();
            payload.set('room_action', 'sync_presence');
            payload.set('room_id', String(roomId));
            payload.set('_csrf_token', sessionCsrfToken || '');

            fetch(syncUrl, {
                method: 'POST',
                body: payload.toString(),
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
            })
                .then((response) => response.ok ? response.json() : Promise.reject(new Error('Snapshot unavailable')))
                .then((data) => {
                    if (!playerList || !data || data.ok !== true || !Array.isArray(data.players)) {
                        return;
                    }

                    if (startedPill) {
                        if (data.room_ended) {
                            roomEnded = true;
                            startedPill.textContent = 'Room Ended';
                            startedPill.classList.remove('bg-white', 'bg-arcade-orange');
                            startedPill.classList.add('bg-arcade-coral', 'text-white');
                        } else if (data.room_started) {
                            roomEnded = false;
                            startedPill.textContent = 'Room Started';
                            startedPill.classList.remove('bg-white', 'bg-arcade-coral');
                            startedPill.classList.add('bg-arcade-orange', 'text-white');
                        }
                    }

                    const seen = new Set();
                    data.players.forEach((player) => {
                        if (!player?.user_id) {
                            return;
                        }

                        seen.add(String(player.user_id));
                        upsertCard(player);
                    });

                    Array.from(playerList.querySelectorAll('[data-room-player-user-id]')).forEach((card) => {
                        const userId = String(card.getAttribute('data-room-player-user-id') || '');
                        if (userId !== '' && !seen.has(userId)) {
                            card.remove();
                        }
                    });

                    updateCount();
                })
                .catch(() => {});
        };

        if (roomId > 0 && pusherKey && pusherCluster && window.Pusher) {
            const pusher = new window.Pusher(pusherKey, {
                cluster: pusherCluster,
            });
            const channel = pusher.subscribe(`room-${roomId}`);

            channel.bind('session-started', () => {
                if (startedPill) {
                    startedPill.textContent = 'Room Started';
                    startedPill.classList.remove('bg-white', 'bg-arcade-coral');
                    startedPill.classList.add('bg-arcade-orange', 'text-white');
                }
                syncPresenceSnapshot();
            });

            channel.bind('session-ended', () => {
                roomEndSubmitting = true;
                roomEnded = true;
                if (startedPill) {
                    startedPill.textContent = 'Room Ended';
                    startedPill.classList.remove('bg-white', 'bg-arcade-orange');
                    startedPill.classList.add('bg-arcade-coral', 'text-white');
                }
                if (timerPill) {
                    timerPill.textContent = 'Time Left: 00:00';
                }
                syncPresenceSnapshot();
            });

            channel.bind('player-joined', () => {
                syncPresenceSnapshot();
            });

            channel.bind('player-left', () => {
                syncPresenceSnapshot();
            });

            channel.bind('player-removed', (payload) => {
                if (payload?.user_id) {
                    playerList?.querySelector(`[data-room-player-user-id="${payload.user_id}"]`)?.remove();
                    updateCount();
                }
            });

            channel.bind('player-status', (payload) => {
                if (payload?.user_id) {
                    upsertCard({
                        user_id: payload.user_id,
                        status_label: payload.status_label || 'waiting',
                        started_at: payload.started_at || '',
                        completed_at: payload.completed_at || '',
                        duration_label: payload.duration_label || '',
                    });
                }
                syncPresenceSnapshot();
            });
        }

        syncPresenceSnapshot();
        playerSort?.addEventListener('change', sortPlayerCards);
        sortPlayerCards();
        renderCountdown();
        window.setInterval(renderCountdown, 1000);
        window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                syncPresenceSnapshot();
            }
        }, 4000);
    })();
</script>
