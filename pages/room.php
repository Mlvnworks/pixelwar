<?php
require_once __DIR__ . '/../classes/challenge-catalog.php';

$roomCode = strtoupper(trim((string) ($_GET['room_code'] ?? 'PIXEL-123')));
$playerName = trim((string) ($_GET['player_name'] ?? ($_SESSION['username'] ?? 'Pixel Rookie')));
$roomCode = $roomCode !== '' ? $roomCode : 'PIXEL-123';
$playerName = $playerName !== '' ? $playerName : 'Pixel Rookie';
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

        <article class="room-shell mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
            <div class="max-w-4xl">
                <p class="font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-cyan">Room Lobby</p>
                <h1 class="room-title mt-3 text-4xl font-bold leading-tight md:text-6xl">Room <?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="room-summary mt-5 rounded-[22px] border-2 border-arcade-ink/15 bg-white/75 p-4">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-arcade-ink/55">Room Name</p>
                    <h2 class="mt-2 text-2xl font-black"><?= htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="mt-3 text-sm leading-7 text-arcade-ink/70"><?= htmlspecialchars($roomDescription, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="room-meta-pill">
                            Host: <?= htmlspecialchars($roomMaker, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="room-meta-pill">
                            <?= count($joinedPlayers) ?> players joined
                        </span>
                    </div>
                    <p class="room-waiting-message mt-4">
                        <span aria-hidden="true">...</span>
                        Waiting for host to start the room.
                    </p>
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
                            <p class="mt-3 text-sm leading-7 text-arcade-ink/70"><?= htmlspecialchars($challenge['objective'], ENT_QUOTES, 'UTF-8') ?></p>
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
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/50">Static design</p>
                            </div>
                            <iframe
                                class="mt-4 h-[340px] w-full rounded-[20px] bg-transparent"
                                title="Static isolated room challenge preview"
                                sandbox="allow-same-origin"
                                loading="lazy"
                                srcdoc="<?= htmlspecialchars($previewSrcdoc, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                        </section>
                    </div>

                </section>

                <section class="room-players-card rounded-[24px] border-2 border-arcade-ink/15 bg-white/75 p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Players</p>
                            <h2 class="mt-2 text-2xl font-bold">Joined Players</h2>
                        </div>
                        <span class="rounded-full bg-arcade-yellow px-3 py-1 text-xs font-black text-arcade-ink"><?= count($joinedPlayers) ?> joined</span>
                    </div>
                    <div class="room-players-grid mt-4 grid gap-3 lg:grid-cols-3">
                        <?php foreach ($joinedPlayers as $player) : ?>
                            <?php $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $player['name']) ?: 'PR', 0, 2)); ?>
                            <article class="room-player-card room-player-card--<?= htmlspecialchars($player['accent'], ENT_QUOTES, 'UTF-8') ?> rounded-[20px] border-2 border-arcade-ink/15 bg-arcade-panel p-4">
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
                </section>
            </div>
        </article>
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
                        class="room-preview-frame h-[340px] w-full rounded-[20px] bg-transparent"
                        title="Static isolated room challenge preview"
                        sandbox="allow-same-origin"
                        loading="lazy"
                        srcdoc="<?= htmlspecialchars($previewSrcdoc, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const previewFrames = Array.from(document.querySelectorAll('.room-preview iframe, .room-preview-frame'));
    const previewModal = document.getElementById('room-preview-modal');

    const fitPreviewFrame = (frame) => {
        if (!(frame instanceof HTMLIFrameElement)) {
            return;
        }

        const doc = frame.contentDocument;
        const body = doc?.body;
        if (!doc || !body) {
            return;
        }

        let stage = doc.querySelector('[data-preview-fit-stage]');
        let sizer = doc.querySelector('[data-preview-fit-sizer]');
        let content = doc.querySelector('[data-preview-fit-content]');

        if (!stage || !sizer || !content) {
            const existingNodes = Array.from(body.childNodes);
            body.innerHTML = '';

            stage = doc.createElement('div');
            stage.setAttribute('data-preview-fit-stage', 'true');
            stage.style.cssText = 'width:100%;min-height:100vh;display:grid;place-items:center;padding:24px;overflow:visible;box-sizing:border-box;';

            sizer = doc.createElement('div');
            sizer.setAttribute('data-preview-fit-sizer', 'true');
            sizer.style.cssText = 'position:relative;display:block;';

            content = doc.createElement('div');
            content.setAttribute('data-preview-fit-content', 'true');
            content.style.cssText = 'display:block;width:max-content;transform-origin:top left;';

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

    if (previewFrames.length > 0 && 'ResizeObserver' in window) {
        const previewObserver = new ResizeObserver(resizePreview);
        previewFrames.forEach((frame) => frame.parentElement && previewObserver.observe(frame.parentElement));
    }

    previewModal?.addEventListener('shown.bs.modal', () => requestAnimationFrame(resizePreview));
    window.addEventListener('resize', resizePreview);
    resizePreview();
})();
</script>
