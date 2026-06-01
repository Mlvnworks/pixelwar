<?php
require_once __DIR__ . '/../classes/challenge-catalog.php';

$roomId = max(0, (int) ($_GET['id'] ?? 0));
$requestedRoomCode = strtoupper(trim((string) ($_GET['room_code'] ?? '')));
$roomRecord = null;

if (isset($roomRepository) && $roomRepository instanceof RoomRepository) {
    if ($roomId > 0) {
        $roomRecord = $roomRepository->findById($roomId);
    } elseif ($requestedRoomCode !== '') {
        $roomRecord = $roomRepository->findByCode($requestedRoomCode);
    }
}

$isRealRoom = $roomRecord !== null;
$sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
$sessionRoleId = (int) ($_SESSION['role_id'] ?? 0);
$currentPlayerJoinedRoom = false;
$roomIsClosedForStudent = $isRealRoom && (int) ($roomRecord['status'] ?? 1) !== 1;
$roomSessionStarted = $isRealRoom && trim((string) ($roomRecord['started_at'] ?? '')) !== '';
$roomSessionEnded = $isRealRoom && trim((string) ($roomRecord['ended_at'] ?? '')) !== '';
$roomReentryBlocked = false;
$roomJoinAfterStartBlocked = false;
$roomReentryMessage = '';
$roomGameUrl = $isRealRoom
    ? './?c=pixelwar&challenge_id=' . (int) ($roomRecord['challenge_id'] ?? 0) . '&room_id=' . (int) ($roomRecord['room_id'] ?? 0)
    : '';
$pusherEnabled = isset($pusherService) && $pusherService instanceof PusherService && $pusherService->isConfigured();
$joinedRoomPlayer = null;
$roomLeaveCsrfToken = $isRealRoom ? pixelwarCsrfToken() : '';

if (
    $isRealRoom
    && !$roomIsClosedForStudent
    && $sessionUserId > 0
    && $sessionRoleId === pixelwarStudentRoleId()
    && isset($roomPlayerRepository)
    && $roomPlayerRepository instanceof RoomPlayerRepository
) {
    $joinedRoomPlayer = $roomPlayerRepository->findByUserAndRoom($sessionUserId, (int) ($roomRecord['room_id'] ?? 0));

    if (
        $joinedRoomPlayer !== null
        && (int) ($joinedRoomPlayer['status'] ?? 0) === 3
        && $roomSessionStarted
        && !$roomSessionEnded
    ) {
        $roomReentryBlocked = true;
        $roomReentryMessage = 'You already gave up this room. Re-entry is locked while the room is still ongoing.';
    } elseif ($joinedRoomPlayer === null && $roomSessionStarted) {
        $roomJoinAfterStartBlocked = true;
    } else {
        $joinedRoomPlayer = $roomPlayerRepository->ensureJoined($sessionUserId, (int) ($roomRecord['room_id'] ?? 0));
        $currentPlayerJoinedRoom = true;
    }

    if (
        !$roomReentryBlocked
        && !empty($joinedRoomPlayer['was_created'])
        && isset($pusherService)
        && $pusherService instanceof PusherService
        && $pusherService->isConfigured()
    ) {
        try {
            $fullName = trim((string) ($_SESSION['firstname'] ?? '') . ' ' . (string) ($_SESSION['lastname'] ?? ''))
                ?: trim((string) ($_SESSION['username'] ?? 'Student'))
                ?: 'Student';
            $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $fullName) ?: 'ST', 0, 2));
            $pusherService->trigger(
                'room-' . (int) ($roomRecord['room_id'] ?? 0),
                'player-joined',
                [
                    'rp_id' => (int) ($joinedRoomPlayer['rp_id'] ?? 0),
                    'user_id' => $sessionUserId,
                    'name' => $fullName,
                    'username' => (string) ($_SESSION['username'] ?? ''),
                    'email' => (string) ($_SESSION['email'] ?? ''),
                    'student_number' => (string) ($_SESSION['student_number'] ?? ''),
                    'avatar_url' => (string) ($_SESSION['avatar_url'] ?? ''),
                    'initials' => $initials,
                ]
            );
        } catch (Throwable $pusherError) {
            error_log('Pixelwar room join pusher error: ' . $pusherError->getMessage());
        }
    }
}

$roomCode = $isRealRoom
    ? (trim((string) ($roomRecord['room_code'] ?? '')) !== ''
        ? strtoupper(trim((string) $roomRecord['room_code']))
        : 'ROOM')
    : ($requestedRoomCode !== '' ? $requestedRoomCode : 'PIXEL-123');
$playerName = trim((string) ($_SESSION['firstname'] ?? '') . ' ' . (string) ($_SESSION['lastname'] ?? ''));
$playerName = $playerName !== '' ? $playerName : trim((string) ($_SESSION['username'] ?? 'Pixel Rookie'));
$roomCode = $roomCode !== '' ? $roomCode : 'PIXEL-123';
$playerName = $playerName !== '' ? $playerName : 'Pixel Rookie';

if ($isRealRoom) {
    $challengeDifficulty = ucfirst(strtolower((string) ($roomRecord['difficulty_name'] ?? 'Unknown')));
    $challengePoints = (int) ($roomRecord['points'] ?? 0);
    $challenge = [
        'title' => (string) ($roomRecord['challenge_name'] ?? 'Challenge'),
        'objective' => (string) ($roomRecord['challenge_instruction'] ?? ''),
        'author' => trim((string) ($roomRecord['teacher_firstname'] ?? '') . ' ' . (string) ($roomRecord['teacher_lastname'] ?? '')) ?: (string) ($roomRecord['teacher_username'] ?? 'Teacher'),
        'focus' => (int) ($roomRecord['strict_mode'] ?? 0) === 1 ? 'Strict mode' : 'Standard mode',
        'estimate' => (int) ($roomRecord['timer_limit'] ?? 0) > 0 ? ((int) ($roomRecord['timer_limit'] ?? 0)) . ' min timer' : 'No timer',
        'reward' => $challengePoints . ' pts',
        'level' => $challengeDifficulty,
        'levelClass' => 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', strtolower($challengeDifficulty)),
    ];
    $roomMaker = $challenge['author'];
    $roomName = (string) ($roomRecord['room_name'] ?? 'Untitled Room');
    $roomDescription = (string) ($roomRecord['room_description'] ?? '');
    $joinedPlayers = [];
    if (isset($roomPlayerRepository) && $roomPlayerRepository instanceof RoomPlayerRepository) {
        foreach ($roomPlayerRepository->listJoinedForRoom((int) ($roomRecord['room_id'] ?? 0)) as $joinedRow) {
            $joinedName = trim((string) ($joinedRow['firstname'] ?? '') . ' ' . (string) ($joinedRow['lastname'] ?? ''))
                ?: (string) ($joinedRow['username'] ?? 'Player');
            $joinedStatus = (int) ($joinedRow['status'] ?? 0);
            $joinedPoints = (int) ($joinedRow['points'] ?? 0);
            $joinedRankProgress = $rankRepository instanceof RankRepository
                ? $rankRepository->progressForPoints($joinedPoints)
                : ['current_name' => 'Student'];
            $joinedPlayers[] = [
                'name' => $joinedName,
                'role' => (int) ($joinedRow['user_id'] ?? 0) === $sessionUserId ? 'You' : 'Player',
                'rank' => (string) ($joinedRankProgress['current_name'] ?? 'Student'),
                'status' => $joinedStatus === 3 ? 'Gave Up' : ($joinedStatus === 2 ? 'Completed' : ($joinedStatus === 1 ? 'Solving' : 'Waiting')),
                'accent' => (int) ($joinedRow['user_id'] ?? 0) === $sessionUserId ? 'yellow' : 'mint',
            ];
        }
    }
    $htmlSourceUrl = (string) ($roomRecord['html_source'] ?? '');
    $cssSourceUrl = (string) ($roomRecord['css_source'] ?? '');
} else {
    $challenge = ChallengeCatalog::first();
    $roomMaker = 'Mika Reyes';
    $roomName = 'Arcade Dawn Practice Room';
    $roomDescription = 'A casual room for matching the target design together before starting the Pixelwar run.';
    $joinedPlayers = [
        [
            'name' => $playerName,
            'role' => 'You',
            'rank' => 'Beginner',
            'status' => 'Joined',
            'accent' => 'yellow',
        ],
        [
            'name' => 'CSSRunner',
            'role' => 'Player 2',
            'rank' => 'Builder',
            'status' => 'Ready',
            'accent' => 'cyan',
        ],
        [
            'name' => 'BorderBuddy',
            'role' => 'Player 3',
            'rank' => 'Matcher',
            'status' => 'Waiting',
            'accent' => 'mint',
        ],
    ];
    $htmlSourceUrl = '';
    $cssSourceUrl = '';
}

$previewSrcdoc = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
*{box-sizing:border-box}html,body{width:100%;height:100%;margin:0}body{display:grid;place-items:center;background:#f7efe1;font-family:Arial,sans-serif;color:#26190f}.pixel-card{width:320px;background:#fffdf6;border:4px solid #26190f;border-radius:24px;padding:24px;text-align:center;box-shadow:0 12px 0 #26190f}.pixel-badge{display:inline-block;background:#ffd166;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:700}.pixel-title{margin:14px 0 8px;color:#ff8c42;font-size:36px;line-height:1.05}.pixel-subtitle{margin:0 0 16px;color:#26190f;font-size:15px;line-height:1.6}.pixel-cta{display:inline-block;background:#4cc9f0;color:#26190f;border-radius:12px;padding:10px 18px;font-size:14px;font-weight:700;text-decoration:none}
</style>
</head>
<body>
<article class="pixel-card">
<span class="pixel-badge">Target Design</span>
<h1 class="pixel-title">Pixelwar</h1>
<p class="pixel-subtitle">Match the CSS properties to complete the challenge.</p>
<span class="pixel-cta">Launch Run</span>
</article>
</body>
</html>
HTML;
?>

<main class="room-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_14%,rgba(76,201,240,0.22),transparent_24%),radial-gradient(circle_at_88%_16%,rgba(255,209,102,0.28),transparent_24%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_38%)]"></div>
    <div class="room-page__grid absolute inset-0"></div>

    <section class="container relative">
        <a href="./?c=home" class="room-back-button inline-flex items-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_rgba(38,25,15,0.22)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
            <span aria-hidden="true">&larr;</span>
            Back Home
        </a>

        <?php if (($roomId > 0 || $requestedRoomCode !== '') && !$isRealRoom) : ?>
            <article class="room-shell mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
                <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-coral">Missing Room</p>
                <h1 class="mt-3 text-3xl font-bold">Room not found.</h1>
                <p class="mt-3 text-sm leading-7 text-arcade-ink/70">The entered room code is invalid, or the room is no longer available.</p>
            </article>
        <?php elseif ($roomIsClosedForStudent) : ?>
            <article class="room-shell mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
                <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-coral">Room Closed</p>
                <h1 class="mt-3 text-3xl font-bold">This room is closed.</h1>
                <p class="mt-3 text-sm leading-7 text-arcade-ink/70">The teacher has closed this room, so new players can no longer join.</p>
            </article>
        <?php elseif ($roomReentryBlocked) : ?>
            <article class="room-shell mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
                <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-coral">Re-entry Locked</p>
                <h1 class="mt-3 text-3xl font-bold">You cannot re-enter this room.</h1>
                <p class="mt-3 text-sm leading-7 text-arcade-ink/70"><?= htmlspecialchars($roomReentryMessage, ENT_QUOTES, 'UTF-8') ?></p>
            </article>
        <?php elseif ($roomJoinAfterStartBlocked) : ?>
            <article class="room-shell mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
                <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-coral">Room Already Started</p>
                <h1 class="mt-3 text-3xl font-bold">New players cannot join now.</h1>
                <p class="mt-3 text-sm leading-7 text-arcade-ink/70">This room already started. Only players who joined before the room started can continue.</p>
            </article>
        <?php else : ?>
        <article class="room-shell mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
            <div class="max-w-4xl">
                <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-cyan">Room Lobby</p>
                <h1 class="room-title mt-3 text-4xl font-bold leading-tight md:text-6xl">Room <?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="room-summary mt-5 rounded-[22px] border-2 border-arcade-ink/15 bg-white/75 p-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-arcade-ink/55">Room Name</p>
                    <h2 class="mt-2 text-2xl font-black"><?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="mt-3 text-sm leading-7 text-arcade-ink/70"><?= $tools->formatRichText($roomDescription) ?></div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="room-meta-pill">
                            Host: <?= htmlspecialchars($roomMaker, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <?php if ($isRealRoom) : ?>
                            <span class="room-meta-pill">
                                Created <?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($roomRecord['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="room-meta-pill">
                                <?= count($joinedPlayers) ?> player<?= count($joinedPlayers) === 1 ? '' : 's' ?> joined
                            </span>
                            <span class="room-meta-pill">
                                <?= $roomSessionStarted ? 'Session started' : 'Waiting for start' ?>
                            </span>
                        <?php else : ?>
                            <span class="room-meta-pill">
                                <?= count($joinedPlayers) ?> players joined
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="room-waiting-message mt-4">
                        <span aria-hidden="true">...</span>
                        <?= $isRealRoom
                            ? ($roomSessionStarted
                                ? 'Session started. Redirecting players to the challenge...'
                                : ($currentPlayerJoinedRoom ? 'You joined the room successfully. Waiting for the host to start the room.' : 'Waiting for host to start the room.'))
                            : 'Waiting for host to start the room.' ?>
                    </p>
                    <?php if ($isRealRoom && $currentPlayerJoinedRoom && !$roomSessionStarted) : ?>
                        <form id="room-leave-form" action="./?c=room&id=<?= (int) ($roomRecord['room_id'] ?? 0) ?>" method="post" class="mt-4">
                            <?= pixelwarCsrfField() ?>
                            <input type="hidden" name="room_action" value="leave_room">
                            <input type="hidden" name="room_id" value="<?= (int) ($roomRecord['room_id'] ?? 0) ?>">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-4 py-3 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-coral hover:text-white">
                                <span aria-hidden="true">&larr;</span>
                                Leave Room
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="room-content mt-7 grid gap-5">
                <section class="room-challenge-card rounded-[24px] border-2 border-arcade-ink/15 bg-white/75 p-5">
                    <div class="room-challenge-layout grid gap-6 xl:grid-cols-[1fr_0.78fr] xl:items-start">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="challenge-difficulty <?= htmlspecialchars($challenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['level'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="rounded-full bg-arcade-cyan/30 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['estimate'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['reward'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <p class="mt-6 font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Challenge Info</p>
                            <h2 class="mt-3 text-3xl font-bold leading-tight"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="mt-3 text-sm leading-7 text-arcade-ink/70"><?= $tools->formatRichText((string) ($challenge['objective'] ?? '')) ?></div>
                            <button type="button" class="room-preview-toggle mt-4 inline-flex w-full justify-center rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-4 py-3 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" data-bs-toggle="modal" data-bs-target="#room-preview-modal">
                                Preview
                            </button>

                            <div class="room-info-grid mt-5 grid gap-3 md:grid-cols-3">
                                <div class="room-info-card rounded-2xl border-2 border-arcade-ink/15 bg-arcade-panel p-4">
                                    <p>Author</p>
                                    <strong><?= htmlspecialchars($challenge['author'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="room-info-card rounded-2xl border-2 border-arcade-ink/15 bg-arcade-panel p-4">
                                    <p>Focus</p>
                                    <strong><?= htmlspecialchars($challenge['focus'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="room-info-card rounded-2xl border-2 border-arcade-ink/15 bg-arcade-panel p-4">
                                    <p>Goal</p>
                                    <strong>Match UI</strong>
                                </div>
                            </div>
                        </div>

                        <section class="room-preview rounded-[24px] bg-arcade-cream/80 p-4 shadow-[0_10px_30px_rgba(38,25,15,0.12)]">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Target Preview</p>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/50"><?= $isRealRoom ? 'Live source preview' : 'Static design' ?></p>
                            </div>
                            <iframe
                                id="room-source-preview-inline"
                                class="mt-4 h-[340px] w-full rounded-[20px] bg-transparent"
                                title="Room challenge preview"
                                sandbox="allow-same-origin"
                                loading="lazy"
                                <?php if ($isRealRoom) : ?>
                                    data-html-source="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    data-css-source="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                                <?php else : ?>
                                    srcdoc="<?= htmlspecialchars($previewSrcdoc, ENT_QUOTES, 'UTF-8') ?>"
                                <?php endif; ?>
                            ></iframe>
                        </section>
                    </div>

                </section>

                <section class="room-players-card rounded-[24px] border-2 border-arcade-ink/15 bg-white/75 p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-bold">Joined Players</h2>
                        </div>
                        <span id="room-joined-count" class="rounded-full bg-arcade-yellow px-3 py-1 text-xs font-black text-arcade-ink"><?= count($joinedPlayers) . ' joined' ?></span>
                    </div>
                    <div id="room-players-grid" class="room-players-grid mt-4 grid gap-3 <?= $isRealRoom ? 'lg:grid-cols-1' : 'lg:grid-cols-3' ?>">
                        <?php foreach ($joinedPlayers as $player) : ?>
                            <?php $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $player['name']) ?: 'PR', 0, 2)); ?>
                            <article class="room-player-card room-player-card--<?= htmlspecialchars($player['accent'], ENT_QUOTES, 'UTF-8') ?> rounded-[20px] border-2 border-arcade-ink/15 bg-arcade-panel p-4" data-room-player-user-id="<?= htmlspecialchars((string) ($player['user_id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="room-player-avatar grid h-12 w-12 shrink-0 place-items-center rounded-2xl border-2 border-arcade-ink font-arcade text-[10px] text-arcade-ink"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                        <div class="min-w-0">
                                            <h3 class="truncate text-base font-black"><?= htmlspecialchars($player['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                            <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-arcade-ink/50"><?= htmlspecialchars($player['role'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <p class="mt-1 text-xs font-black text-arcade-orange">Rank: <?= htmlspecialchars($player['rank'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-arcade-mint/50 px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-arcade-ink"><?= htmlspecialchars($player['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($isRealRoom) : ?>
                        <p class="mt-4 text-sm leading-7 text-arcade-ink/65">Joined players are now recorded from the room code flow.</p>
                    <?php endif; ?>
                </section>
            </div>
        </article>
        <?php endif; ?>
    </section>
</main>

<div class="modal fade room-preview-modal" id="room-preview-modal" tabindex="-1" aria-labelledby="room-preview-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Target Preview</p>
                    <h2 id="room-preview-modal-title" class="modal-title mt-2 text-xl font-bold"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <div class="room-preview-frame-shell">
                    <iframe
                        id="room-source-preview-modal"
                        class="room-preview-frame h-[340px] w-full rounded-[20px] bg-transparent"
                        title="Room challenge preview"
                        sandbox="allow-same-origin"
                        loading="lazy"
                        <?php if ($isRealRoom) : ?>
                            data-html-source="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                            data-css-source="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                        <?php else : ?>
                            srcdoc="<?= htmlspecialchars($previewSrcdoc, ENT_QUOTES, 'UTF-8') ?>"
                        <?php endif; ?>
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isRealRoom && $pusherEnabled) : ?>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<?php endif; ?>

<script>
(() => {
    const roomRealtimeConfig = {
        roomId: <?= (int) ($roomRecord['room_id'] ?? 0) ?>,
        roomStarted: <?= $roomSessionStarted ? 'true' : 'false' ?>,
        canAutoStart: <?= ($isRealRoom && !$roomIsClosedForStudent && $sessionUserId > 0 && $sessionRoleId === pixelwarStudentRoleId() && $roomGameUrl !== '') ? 'true' : 'false' ?>,
        gameUrl: <?= json_encode($roomGameUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        snapshotUrl: <?= json_encode(rtrim(APP_URL, '/') . '/?c=room', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        leaveUrl: <?= json_encode(rtrim(APP_URL, '/') . '/?c=room', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        leaveCsrfToken: <?= json_encode($roomLeaveCsrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        joinedAsPlayer: <?= ($isRealRoom && !$roomIsClosedForStudent && $sessionUserId > 0 && $sessionRoleId === pixelwarStudentRoleId() && $currentPlayerJoinedRoom) ? 'true' : 'false' ?>,
        pusherKey: <?= json_encode($pusherEnabled ? (string) PUSHER_KEY : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
        pusherCluster: <?= json_encode($pusherEnabled ? (string) PUSHER_CLUSTER : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: "''" ?>,
    };
    const previewFrames = Array.from(document.querySelectorAll('.room-preview iframe, .room-preview-frame'));
    const previewModal = document.getElementById('room-preview-modal');
    const roomPlayersGrid = document.getElementById('room-players-grid');
    const roomJoinedCount = document.getElementById('room-joined-count');
    const roomLeaveForm = document.getElementById('room-leave-form');
    const isRealRoom = <?= $isRealRoom ? 'true' : 'false' ?>;
    let isLeavingRoom = false;

    const buildPreviewDocument = (html, css) => `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff7e8; width: max-content; height: max-content; }
body { display: inline-block; font-family: Arial, sans-serif; }
.preview-canvas { display: inline-block; padding: 24px; }
${css}
</style>
</head>
<body>
<div class="preview-canvas">${html}</div>
</body>
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

        html.style.width = '100%';
        html.style.height = '100%';
        body.style.width = '100%';
        body.style.height = '100%';
        body.style.margin = '0';
        body.style.overflow = 'hidden';

        let stage = doc.querySelector('[data-preview-fit-stage]');
        let sizer = doc.querySelector('[data-preview-fit-sizer]');
        let content = doc.querySelector('[data-preview-fit-content]');

        if (!stage || !sizer || !content) {
            const existingNodes = Array.from(body.childNodes);
            body.innerHTML = '';

            stage = doc.createElement('div');
            stage.setAttribute('data-preview-fit-stage', 'true');
            stage.style.cssText = 'width:100%;height:100%;display:grid;place-items:center;padding:24px;overflow:hidden;box-sizing:border-box;';

            sizer = doc.createElement('div');
            sizer.setAttribute('data-preview-fit-sizer', 'true');
            sizer.style.cssText = 'position:relative;display:block;overflow:visible;';

            content = doc.createElement('div');
            content.setAttribute('data-preview-fit-content', 'true');
            content.style.cssText = 'display:block;width:max-content;height:auto;transform-origin:top left;';

            existingNodes.forEach((node) => content.appendChild(node));
            sizer.appendChild(content);
            stage.appendChild(sizer);
            body.appendChild(stage);
        }

        content.style.transform = 'scale(1)';
        sizer.style.width = 'max-content';
        sizer.style.height = 'max-content';
        content.style.width = 'max-content';
        content.style.height = 'auto';

        const stageStyles = window.getComputedStyle(stage);
        const availableWidth = stage.clientWidth - parseFloat(stageStyles.paddingLeft || '0') - parseFloat(stageStyles.paddingRight || '0');
        const availableHeight = stage.clientHeight - parseFloat(stageStyles.paddingTop || '0') - parseFloat(stageStyles.paddingBottom || '0');
        const naturalWidth = Math.max(content.scrollWidth, content.offsetWidth, 1);
        const naturalHeight = Math.max(content.scrollHeight, content.offsetHeight, 1);
        const scale = Math.min(1, availableWidth / naturalWidth, availableHeight / naturalHeight);

        sizer.style.width = `${Math.ceil(naturalWidth * scale)}px`;
        sizer.style.height = `${Math.ceil(naturalHeight * scale)}px`;
        content.style.width = `${naturalWidth}px`;
        content.style.height = `${naturalHeight}px`;
        content.style.transform = `scale(${scale})`;
    };

    previewFrames.forEach((frame) => {
        frame.addEventListener('load', () => fitPreviewFrame(frame), { once: false });
    });

    const resizePreview = () => previewFrames.forEach((frame) => fitPreviewFrame(frame));

    if (isRealRoom) {
        Promise.all([
            fetch(previewFrames[0]?.dataset.htmlSource || '').then((response) => response.ok ? response.text() : Promise.reject(new Error('HTML source unavailable'))),
            fetch(previewFrames[0]?.dataset.cssSource || '').then((response) => response.ok ? response.text() : Promise.reject(new Error('CSS source unavailable'))),
        ]).then(([html, css]) => {
            previewFrames.forEach((frame) => {
                frame.srcdoc = buildPreviewDocument(html, css);
            });
        }).catch(() => {
            const fallback = buildPreviewDocument(
                '<div class="preview-warning">Preview unavailable. Open the challenge source from the teacher panel to inspect the files.</div>',
                '.preview-warning { max-width: 320px; border: 3px solid #26190f; border-radius: 18px; background: #ffd166; padding: 18px; color: #26190f; font-weight: 900; text-align: center; box-shadow: 6px 6px 0 #26190f; }'
            );
            previewFrames.forEach((frame) => {
                frame.srcdoc = fallback;
            });
        });
    }

    if (previewFrames.length > 0 && 'ResizeObserver' in window) {
        const previewObserver = new ResizeObserver(resizePreview);
        previewFrames.forEach((frame) => frame.parentElement && previewObserver.observe(frame.parentElement));
    }

    if (roomRealtimeConfig.canAutoStart && roomRealtimeConfig.roomStarted && roomRealtimeConfig.gameUrl) {
        window.setTimeout(() => {
            window.location.href = roomRealtimeConfig.gameUrl;
        }, 800);
    }

    const upsertRoomPlayer = (player) => {
        if (!roomPlayersGrid || !player?.user_id) {
            return;
        }

        let card = roomPlayersGrid.querySelector(`[data-room-player-user-id="${player.user_id}"]`);
        const avatar = player.avatar_url
            ? `<img src="${player.avatar_url}" alt="" class="h-full w-full object-cover">`
            : String(player.initials || 'PR');

        if (!card) {
            card = document.createElement('article');
            card.setAttribute('data-room-player-user-id', String(player.user_id));
            roomPlayersGrid.appendChild(card);
        }

        card.className = `room-player-card room-player-card--${player.accent || 'mint'} rounded-[20px] border-2 border-arcade-ink/15 bg-arcade-panel p-4`;
        card.innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="room-player-avatar grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink font-arcade text-[10px] text-arcade-ink">${avatar}</span>
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-black">${player.name || 'Player'}</h3>
                        <p class="mt-1 text-xs font-bold uppercase tracking-[0.14em] text-arcade-ink/50">${player.role || 'Player'}</p>
                        <p class="mt-1 text-xs font-black text-arcade-orange">Rank: ${player.rank || 'Student'}</p>
                    </div>
                </div>
                <span class="rounded-full bg-arcade-mint/50 px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-arcade-ink">${player.status || 'Waiting'}</span>
            </div>
        `;
    };

    const syncRoomPresence = () => {
        if (!roomRealtimeConfig.roomId || !roomRealtimeConfig.leaveCsrfToken || !roomPlayersGrid) {
            return;
        }

        const payload = new URLSearchParams();
        payload.set('room_action', 'sync_room_presence');
        payload.set('room_id', String(roomRealtimeConfig.roomId));
        payload.set('_csrf_token', roomRealtimeConfig.leaveCsrfToken);

        fetch(roomRealtimeConfig.snapshotUrl, {
            method: 'POST',
            body: payload.toString(),
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
        })
            .then((response) => response.ok ? response.json() : Promise.reject(new Error('Presence unavailable')))
            .then((data) => {
                if (!data || data.ok !== true || !Array.isArray(data.players)) {
                    return;
                }

                if (data.room_started && roomRealtimeConfig.canAutoStart && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }

                const seen = new Set();
                data.players.forEach((player) => {
                    if (!player?.user_id) {
                        return;
                    }
                    seen.add(String(player.user_id));
                    upsertRoomPlayer(player);
                });

                Array.from(roomPlayersGrid.querySelectorAll('[data-room-player-user-id]')).forEach((card) => {
                    const userId = String(card.getAttribute('data-room-player-user-id') || '');
                    if (userId !== '' && !seen.has(userId)) {
                        card.remove();
                    }
                });

                if (roomJoinedCount) {
                    roomJoinedCount.textContent = `${data.players.length} joined`;
                }
            })
            .catch(() => {});
    };

    roomLeaveForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (isLeavingRoom) {
            return;
        }

        isLeavingRoom = true;
        const formData = new FormData(roomLeaveForm);
        fetch(roomLeaveForm.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        })
            .then(() => {
                window.location.href = './?c=home';
            })
            .catch(() => {
                isLeavingRoom = false;
            });
    });

    if (roomRealtimeConfig.roomId > 0 && roomRealtimeConfig.pusherKey && roomRealtimeConfig.pusherCluster && window.Pusher) {
        const pusher = new window.Pusher(roomRealtimeConfig.pusherKey, {
            cluster: roomRealtimeConfig.pusherCluster,
        });
        const channel = pusher.subscribe(`room-${roomRealtimeConfig.roomId}`);

        channel.bind('session-started', (payload) => {
            const redirectUrl = payload?.redirect_url || roomRealtimeConfig.gameUrl;
            if (!redirectUrl) {
                return;
            }

            window.location.href = redirectUrl;
        });

        channel.bind('session-ended', (payload) => {
            window.location.href = payload?.redirect_url || './?c=home&room_notice=ended_incomplete';
        });

        ['player-joined', 'player-left', 'player-status'].forEach((eventName) => {
            channel.bind(eventName, () => {
                syncRoomPresence();
            });
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            syncRoomPresence();
        }
    });
    if (isRealRoom) {
        syncRoomPresence();
        window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                syncRoomPresence();
            }
        }, 3000);
    }
    previewModal?.addEventListener('shown.bs.modal', () => requestAnimationFrame(resizePreview));
    window.addEventListener('resize', resizePreview);
    resizePreview();
})();
</script>
