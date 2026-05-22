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

$formatPlayerDuration = static function (array $player, bool $roomEnded = false) use ($statusForPlayer): string {
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

    $statusMeta = $statusForPlayer($player, $roomEnded);
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

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <?php
        $roomCode = trim((string) ($roomSessionRoom['room_code'] ?? '')) ?: 'Not set';
        $roomName = trim((string) ($roomSessionRoom['room_name'] ?? 'Untitled Room')) ?: 'Untitled Room';
        $challengeName = trim((string) ($roomSessionRoom['challenge_name'] ?? 'Unknown Challenge')) ?: 'Unknown Challenge';
        $strictModeEnabled = (int) ($roomSessionRoom['strict_mode'] ?? 0) === 1;
        $roomStateIsOpen = (int) ($roomSessionRoom['status'] ?? 1) === 1;
        ?>

        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Room Session</p>
                    <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="teacher-pill bg-arcade-yellow"><?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="teacher-pill bg-arcade-cyan/25"><?= htmlspecialchars($challengeName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="teacher-pill <?= $strictModeEnabled ? 'bg-arcade-coral/25' : 'bg-arcade-mint/35' ?>"><?= $strictModeEnabled ? 'Strict' : 'Normal' ?></span>
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
                    <a href="./?c=room-session&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>&export=csv" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </a>
                    <a href="./?c=room-view&id=<?= (int) ($roomSessionRoom['room_id'] ?? 0) ?>" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Room Details</span>
                    </a>
                </div>
            </div>
        </article>

        <section class="grid gap-3 md:grid-cols-3">
            <article class="teacher-panel rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Joined</p>
                <strong id="room-session-joined-count" class="mt-3 block text-3xl font-black"><?= (int) $roomSessionJoinedCount ?></strong>
            </article>
            <article class="teacher-panel rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Solving</p>
                <strong id="room-session-solving-count" class="mt-3 block text-3xl font-black"><?= (int) $roomSessionStartedCount ?></strong>
            </article>
            <article class="teacher-panel rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Completed</p>
                <strong id="room-session-completed-count" class="mt-3 block text-3xl font-black"><?= (int) $roomSessionCompletedCount ?></strong>
            </article>
        </section>

        <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Joined Players</p>
                    <h2 class="mt-2 text-2xl font-black">Player Records</h2>
                </div>
                <p id="room-session-record-count" class="text-sm font-bold text-arcade-ink/60"><?= (int) $roomSessionJoinedCount ?> record<?= $roomSessionJoinedCount === 1 ? '' : 's' ?></p>
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
            <div id="room-session-player-list" class="mt-4 grid gap-3<?= $roomSessionPlayers === [] ? ' hidden' : '' ?>">
                <?php foreach ($roomSessionPlayers as $roomSessionPlayer) : ?>
                    <?php
                    $playerStatus = $statusForPlayer($roomSessionPlayer, $roomSessionIsEnded);
                    $playerDuration = $formatPlayerDuration($roomSessionPlayer, $roomSessionIsEnded);
                    $displayName = trim((string) ($roomSessionPlayer['firstname'] ?? '') . ' ' . (string) ($roomSessionPlayer['lastname'] ?? ''))
                        ?: trim((string) ($roomSessionPlayer['username'] ?? 'Student'))
                        ?: 'Student';
                    $playerInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'ST', 0, 2));
                    $avatarUrl = trim((string) ($roomSessionPlayer['avatar_url'] ?? ''));
                    $studentNumber = trim((string) ($roomSessionPlayer['student_number'] ?? ''));
                    ?>
                    <article
                        class="room-session-card rounded-[22px] border-2 border-arcade-ink/12 bg-white p-4"
                        data-room-player-user-id="<?= (int) ($roomSessionPlayer['user_id'] ?? 0) ?>">
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
                            <span
                                class="teacher-pill room-session-pill <?= htmlspecialchars($playerStatus['class'], ENT_QUOTES, 'UTF-8') ?>"
                                data-room-player-status-pill>
                                <?= htmlspecialchars($playerStatus['label'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="room-session-info-card">
                                <p>Student ID</p>
                                <strong><?= htmlspecialchars($studentNumber !== '' ? $studentNumber : 'Not set', ENT_QUOTES, 'UTF-8') ?></strong>
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

<style>
.room-session-card {
    box-shadow: 0 10px 26px rgba(38, 25, 15, 0.08);
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
        const playerList = document.getElementById('room-session-player-list');
        const emptyState = document.getElementById('room-session-empty-state');
        const recordCount = document.getElementById('room-session-record-count');
        const startedPill = document.getElementById('room-session-started-pill');
        const timerPill = document.getElementById('room-session-timer-pill');
        const joinedCount = document.getElementById('room-session-joined-count');
        const solvingCount = document.getElementById('room-session-solving-count');
        const completedCount = document.getElementById('room-session-completed-count');
        let roomEndSubmitting = false;

        const statusClassMap = {
            waiting: 'room-session-pill--joined',
            solving: 'room-session-pill--started',
            completed: 'room-session-pill--completed',
            gave_up: 'room-session-pill--gave-up',
        };

        const renderStatus = (label) => {
            const normalized = String(label || 'waiting').toLowerCase();
            if (normalized === 'failed' || normalized === 'gave_up' || normalized === 'gave up') {
                return { label: 'Failed', className: statusClassMap.gave_up };
            }
            if (normalized === 'completed') {
                return { label: 'Completed', className: statusClassMap.completed };
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
                } else if (statusText === 'completed') {
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
            article.setAttribute('data-room-player-user-id', String(payload.user_id || 0));
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
                    <span class="teacher-pill room-session-pill room-session-pill--joined" data-room-player-status-pill>Waiting</span>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="room-session-info-card">
                        <p>Student ID</p>
                        <strong>${payload.student_number || 'Not set'}</strong>
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
            updateCount();
        };

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
            const recordId = card.querySelector('.room-session-info-card:nth-child(2) strong');

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
            if (recordId) {
                recordId.textContent = `#${payload.rp_id || 0}`;
            }

            applyStatus(card, payload.status_label || 'waiting', payload.started_at || '', payload.completed_at || '', payload.duration_label || '');
            updateCount();
        };

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
                            startedPill.textContent = 'Room Ended';
                            startedPill.classList.remove('bg-white', 'bg-arcade-orange');
                            startedPill.classList.add('bg-arcade-coral', 'text-white');
                        } else if (data.room_started) {
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
        renderCountdown();
        window.setInterval(renderCountdown, 1000);
        window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                syncPresenceSnapshot();
            }
        }, 4000);
    })();
</script>
