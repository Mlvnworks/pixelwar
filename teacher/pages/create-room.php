<?php
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
$teacherChallenges = $challengeRepository instanceof ChallengeRepository && $teacherId > 0
    ? $challengeRepository->listCreatedChallengesForUser($teacherId, 300)
    : [];
$roomOld = $_SESSION['teacher_rooms_old'] ?? [];
unset($_SESSION['teacher_rooms_old']);

$selectedChallengeId = max(
    0,
    (int) ($roomOld['challenge_id'] ?? ($_GET['challenge_id'] ?? 0))
);
$defaultRoomDescription = 'Describe the room goal, reminders for players, and any warm-up notes before they start solving.';
$defaultRoomName = '';
$defaultTimerLimit = '0';
$defaultStrictMode = 0;

$challengePreviewRows = [];
foreach ($teacherChallenges as $challengeRow) {
    $challengeId = (int) ($challengeRow['challenge_id'] ?? 0);
    if ($challengeId <= 0) {
        continue;
    }

    $ownerChallenge = $challengeRepository instanceof ChallengeRepository
        ? $challengeRepository->findCreatedChallengeForOwner($challengeId, $teacherId)
        : null;

    if (!is_array($ownerChallenge)) {
        continue;
    }

    $challengePreviewRows[$challengeId] = [
        'challenge_id' => $challengeId,
        'name' => (string) ($ownerChallenge['name'] ?? ''),
        'instruction' => (string) ($ownerChallenge['instruction'] ?? ''),
        'difficulty_name' => (string) ($ownerChallenge['difficulty_name'] ?? ''),
        'points' => (int) ($ownerChallenge['points'] ?? 0),
        'author' => trim((string) ($ownerChallenge['firstname'] ?? '') . ' ' . (string) ($ownerChallenge['lastname'] ?? '')) ?: (string) ($ownerChallenge['author'] ?? $teacherName),
        'html_source' => (string) ($ownerChallenge['html_source'] ?? ''),
        'css_source' => (string) ($ownerChallenge['css_source'] ?? ''),
    ];
}

$selectedChallenge = $selectedChallengeId > 0 && isset($challengePreviewRows[$selectedChallengeId])
    ? $challengePreviewRows[$selectedChallengeId]
    : ($challengePreviewRows !== [] ? reset($challengePreviewRows) : null);

if (is_array($selectedChallenge)) {
    $selectedChallengeId = (int) ($selectedChallenge['challenge_id'] ?? 0);
}

$roomNameValue = (string) ($roomOld['room_name'] ?? $defaultRoomName);
$roomDescriptionValue = (string) ($roomOld['room_description'] ?? $defaultRoomDescription);
$timerLimitValue = (string) ($roomOld['timer_limit'] ?? $defaultTimerLimit);
$strictModeValue = (int) ($roomOld['strict_mode'] ?? $defaultStrictMode);
?>

<main class="teacher-shell create-room-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Create Room</p>
                    <h1 class="mt-3 text-3xl font-black md:text-5xl">Build a classroom room.</h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Select one of your challenges, review its preview, and shape the room details before creating the lobby.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="./?c=rooms" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Rooms</span>
                    </a>
                </div>
            </div>
        </article>

        <?php if ($challengePreviewRows === []) : ?>
            <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f] md:p-6">
                <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-coral">Blocked</p>
                <h2 class="mt-3 text-3xl font-black">Create a challenge first.</h2>
                <p class="mt-2 text-sm font-bold leading-7 text-arcade-ink/65">
                    Rooms are attached to your own challenges. Create at least one challenge before opening a room.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="./?c=create-challenge" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="sparkles" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Create Challenge</span>
                    </a>
                    <a href="./?c=rooms" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Back to Rooms</span>
                    </a>
                </div>
            </article>
        <?php else : ?>
            <form id="create-room-form" action="./?c=create-room" method="post">
                <?= teacherPanelCsrfField() ?>
                <section class="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
                    <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Room Details</p>
                        <h2 class="mt-2 text-2xl font-black">Setup</h2>

                        <div class="mt-5 grid gap-4">
                            <label class="create-room-field create-room-field--challenge">
                                <div class="create-room-field-heading">
                                    <span>Challenge</span>
                                    <button type="button" class="create-room-details-button" data-bs-toggle="modal" data-bs-target="#create-room-challenge-preview-modal">
                                        <i data-lucide="eye" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                        <span>Details</span>
                                    </button>
                                </div>
                                <input id="room-challenge-id" name="challenge_id" type="hidden" value="<?= (int) $selectedChallengeId ?>" required>
                                <div class="create-room-combobox" data-challenge-combobox>
                                    <div class="create-room-combobox__control">
                                        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                                        <input
                                            id="room-challenge-search"
                                            type="search"
                                            autocomplete="off"
                                            role="combobox"
                                            aria-controls="room-challenge-results"
                                            aria-expanded="false"
                                            placeholder="Type challenge name..."
                                            value="<?= htmlspecialchars((string) ($selectedChallenge['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div id="room-challenge-results" class="create-room-combobox__results" role="listbox" hidden></div>
                                    <p class="create-room-combobox__hint">Search and select one of your challenges.</p>
                                </div>
                            </label>

                            <label class="create-room-field">
                                <span>Room Name</span>
                                <input id="room-name" type="text" name="room_name" maxlength="150" value="<?= htmlspecialchars($roomNameValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Example: Arcade Dawn Practice Room" required>
                            </label>

                            <label class="create-room-field">
                                <span>Room Description</span>
                                <textarea id="room-description" name="room_description" rows="7" maxlength="255" placeholder="Describe the room focus, team reminders, or any warm-up instructions." required><?= htmlspecialchars($roomDescriptionValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="create-room-field">
                                    <span>Timer Limit (minutes)</span>
                                    <input id="room-timer-limit" type="number" name="timer_limit" min="0" value="<?= htmlspecialchars($timerLimitValue, ENT_QUOTES, 'UTF-8') ?>" required>
                                </label>

                                <label class="create-room-field">
                                    <span>Strict Mode</span>
                                    <select id="room-strict-mode" name="strict_mode" required>
                                        <option value="0" <?= $strictModeValue === 0 ? 'selected' : '' ?>>Normal</option>
                                        <option value="1" <?= $strictModeValue === 1 ? 'selected' : '' ?>>Strict</option>
                                    </select>
                                </label>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <button type="submit" class="teacher-button teacher-button--primary gap-2">
                                <i data-lucide="messages-square" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Create Room</span>
                            </button>
                            <a href="./?c=rooms" class="teacher-button teacher-button--light gap-2">
                                <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Cancel</span>
                            </a>
                        </div>
                    </article>

                    <aside class="grid gap-5">
                        <article class="teacher-panel create-room-summary-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                            <div class="create-room-summary-card__header flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Room Preview</p>
                                    <h2 id="room-preview-name" class="mt-2 text-3xl font-black">Room name pending</h2>
                                </div>
                                <span id="room-preview-code" class="create-room-pill create-room-pill--code">Room code after create</span>
                            </div>

                            <div class="create-room-summary-card__pills mt-3 flex flex-wrap gap-2">
                                <span class="create-room-pill create-room-pill--host">
                                    Host: <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span id="room-preview-mode" class="create-room-pill">Normal mode</span>
                                <span id="room-preview-timer" class="create-room-pill">No timer</span>
                            </div>

                            <div id="room-preview-description" class="create-room-copy mt-4">
                                <?= $tools->formatRichText($roomDescriptionValue) ?>
                            </div>
                        </article>

                        <article class="teacher-panel create-room-challenge-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                            <div class="create-room-challenge-card__header flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Challenge Preview</p>
                                    <h2 id="room-challenge-name" class="mt-2 text-2xl font-black"><?= htmlspecialchars((string) ($selectedChallenge['name'] ?? 'Select a challenge'), ENT_QUOTES, 'UTF-8') ?></h2>
                                </div>
                                <span id="room-challenge-difficulty" class="create-room-pill create-room-pill--difficulty">
                                    <?= htmlspecialchars((string) ($selectedChallenge['difficulty_name'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <div class="create-room-challenge-card__info mt-3 grid gap-2 sm:grid-cols-2">
                                <div class="create-room-info-card create-room-info-card--author">
                                    <p>Author</p>
                                    <strong id="room-challenge-author"><?= htmlspecialchars((string) ($selectedChallenge['author'] ?? $teacherName), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="create-room-info-card create-room-info-card--points">
                                    <p>Points</p>
                                    <strong id="room-challenge-points"><?= (int) ($selectedChallenge['points'] ?? 0) ?> pts</strong>
                                </div>
                            </div>

                            <div id="room-challenge-instruction" class="create-room-copy mt-4">
                                <?= $tools->formatRichText((string) ($selectedChallenge['instruction'] ?? 'Select a challenge to preview its instruction.')) ?>
                            </div>

                            <div class="create-room-preview-frame mt-4">
                                <div id="room-preview-loader" class="create-room-preview-loader">
                                    <span class="create-room-preview-loader__spinner" aria-hidden="true"></span>
                                    <strong>Loading preview...</strong>
                                    <small>Fetching the selected challenge source files.</small>
                                </div>
                                <div class="create-room-preview-stage">
                                    <iframe
                                        id="room-challenge-preview"
                                        title="Room challenge preview"
                                        sandbox="allow-same-origin"
                                        data-html-source="<?= htmlspecialchars((string) ($selectedChallenge['html_source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-css-source="<?= htmlspecialchars((string) ($selectedChallenge['css_source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></iframe>
                                </div>
                            </div>
                        </article>
                    </aside>
                </section>
            </form>

            <div class="modal fade create-room-challenge-preview-modal" id="create-room-challenge-preview-modal" tabindex="-1" aria-labelledby="create-room-challenge-preview-modal-title" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]">
                        <div class="modal-header border-b-2 border-arcade-ink/10 px-4 py-3">
                            <div class="min-w-0">
                                <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-orange">Challenge Preview</p>
                                <h2 id="create-room-challenge-preview-modal-title" class="mt-1 truncate text-xl font-black"><?= htmlspecialchars((string) ($selectedChallenge['name'] ?? 'Select a challenge'), ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="create-room-modal-details grid gap-3 sm:grid-cols-3">
                                <div class="create-room-info-card">
                                    <p>Difficulty</p>
                                    <strong id="room-modal-challenge-difficulty"><?= htmlspecialchars((string) ($selectedChallenge['difficulty_name'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="create-room-info-card">
                                    <p>Author</p>
                                    <strong id="room-modal-challenge-author"><?= htmlspecialchars((string) ($selectedChallenge['author'] ?? $teacherName), ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div class="create-room-info-card">
                                    <p>Points</p>
                                    <strong id="room-modal-challenge-points"><?= (int) ($selectedChallenge['points'] ?? 0) ?> pts</strong>
                                </div>
                            </div>
                            <div id="room-modal-challenge-instruction" class="create-room-copy mt-4">
                                <?= $tools->formatRichText((string) ($selectedChallenge['instruction'] ?? 'Select a challenge to preview its instruction.')) ?>
                            </div>
                            <div class="create-room-preview-frame create-room-preview-frame--modal mt-4">
                                <div id="room-modal-preview-loader" class="create-room-preview-loader">
                                    <span class="create-room-preview-loader__spinner" aria-hidden="true"></span>
                                    <strong>Loading preview...</strong>
                                    <small>Fetching the selected challenge source files.</small>
                                </div>
                                <div class="create-room-preview-stage">
                                    <iframe id="room-modal-challenge-preview" title="Room challenge modal preview" sandbox="allow-same-origin"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            window.addEventListener('load', () => {
                window.lucide?.createIcons();

                const challengeMap = <?= json_encode($challengePreviewRows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
                const challengeSelect = document.getElementById('room-challenge-id');
                const challengeSearchInput = document.getElementById('room-challenge-search');
                const challengeResults = document.getElementById('room-challenge-results');
                const challengeCombobox = document.querySelector('[data-challenge-combobox]');
                const roomNameInput = document.getElementById('room-name');
                const roomDescriptionInput = document.getElementById('room-description');
                const timerLimitInput = document.getElementById('room-timer-limit');
                const strictModeInput = document.getElementById('room-strict-mode');
                const roomPreviewName = document.getElementById('room-preview-name');
                const roomPreviewDescription = document.getElementById('room-preview-description');
                const roomPreviewMode = document.getElementById('room-preview-mode');
                const roomPreviewTimer = document.getElementById('room-preview-timer');
                const challengeName = document.getElementById('room-challenge-name');
                const challengeDifficulty = document.getElementById('room-challenge-difficulty');
                const challengeAuthor = document.getElementById('room-challenge-author');
                const challengePoints = document.getElementById('room-challenge-points');
                const challengeInstruction = document.getElementById('room-challenge-instruction');
                const modalChallengeTitle = document.getElementById('create-room-challenge-preview-modal-title');
                const modalChallengeDifficulty = document.getElementById('room-modal-challenge-difficulty');
                const modalChallengeAuthor = document.getElementById('room-modal-challenge-author');
                const modalChallengePoints = document.getElementById('room-modal-challenge-points');
                const modalChallengeInstruction = document.getElementById('room-modal-challenge-instruction');
                const previewFrame = document.getElementById('room-challenge-preview');
                const modalPreviewFrame = document.getElementById('room-modal-challenge-preview');
                const previewLoader = document.getElementById('room-preview-loader');
                const modalPreviewLoader = document.getElementById('room-modal-preview-loader');
                const challengePreviewModal = document.getElementById('create-room-challenge-preview-modal');

                const escapeHtml = (value) => String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const formatRichTextPreview = (value) => {
                    const escaped = escapeHtml(value).replace(/\r\n?/g, '\n');
                    const withLinks = escaped
                        .replace(/((https?:\/\/|www\.)[^\s<]+)/gi, (match) => {
                            const href = /^https?:\/\//i.test(match) ? match : `https://${match}`;
                            return `<a href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer">${match}</a>`;
                        });

                    return withLinks.replace(/\n/g, '<br>');
                };

                const setPreviewStatus = (label, error = false) => {
                    [previewLoader, modalPreviewLoader].forEach((loader) => {
                        if (!(loader instanceof HTMLElement)) {
                            return;
                        }

                        const strong = loader.querySelector('strong');
                        const small = loader.querySelector('small');
                        if (strong) {
                            strong.textContent = label;
                        }
                        if (small) {
                            small.textContent = error
                                ? 'The selected challenge preview could not be loaded right now.'
                                : 'Fetching the selected challenge source files.';
                        }
                        loader.classList.toggle('is-error', error);
                        loader.hidden = false;
                    });
                };

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
            style.textContent = 'a, area { cursor: default !important; }';
            doc.head?.appendChild(style);
        }

        doc.querySelectorAll('a, area').forEach((link) => {
            link.setAttribute('tabindex', '-1');
            link.setAttribute('aria-disabled', 'true');
        });

        if (doc.defaultView?.pixelwarPreviewLinksBlocked) {
            return;
        }

        doc.defaultView.pixelwarPreviewLinksBlocked = true;
        doc.addEventListener('click', (event) => {
            if (event.target?.closest?.('a, area')) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);
        doc.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && event.target?.closest?.('a, area')) {
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
                    const stage = frame.parentElement;
                    if (!doc || !body || !html || !(stage instanceof HTMLElement)) {
                        return;
                    }

                    const shell = stage.parentElement;
                    if (!(shell instanceof HTMLElement)) {
                        return;
                    }

                    const naturalWidth = Math.max(body.scrollWidth, body.offsetWidth, html.scrollWidth, html.offsetWidth, 1);
                    const naturalHeight = Math.max(body.scrollHeight, body.offsetHeight, html.scrollHeight, html.offsetHeight, 1);
                    const shellWidth = shell.clientWidth;
                    const shellHeight = shell.clientHeight;
                    if (shellWidth <= 0 || shellHeight <= 0) {
                        return;
                    }

                    const scale = Math.min(1, shellWidth / naturalWidth, shellHeight / naturalHeight);
                    const scaledWidth = naturalWidth * scale;
                    const scaledHeight = naturalHeight * scale;
                    stage.style.width = `${Math.ceil(scaledWidth)}px`;
                    stage.style.height = `${Math.ceil(scaledHeight)}px`;
                    stage.style.left = `${Math.max(0, (shellWidth - scaledWidth) / 2)}px`;
                    stage.style.top = `${Math.max(0, (shellHeight - scaledHeight) / 2)}px`;
                    frame.style.width = `${naturalWidth}px`;
                    frame.style.height = `${naturalHeight}px`;
                    frame.style.maxWidth = 'none';
                    frame.style.maxHeight = 'none';
                    frame.style.left = '0';
                    frame.style.top = '0';
                    frame.style.transform = `scale(${scale})`;
                    frame.style.transformOrigin = 'top left';
                };

                const loadPreview = async (htmlSource, cssSource) => {
                    const previewFrames = [previewFrame, modalPreviewFrame].filter((frame) => frame instanceof HTMLIFrameElement);
                    if (previewFrames.length === 0) {
                        return;
                    }

                    if (!htmlSource || !cssSource) {
                        previewFrames.forEach((frame) => frame.removeAttribute('srcdoc'));
                        setPreviewStatus('Preview unavailable', true);
                        return;
                    }

                    setPreviewStatus('Loading preview...');

                    try {
                        const [htmlResponse, cssResponse] = await Promise.all([
                            fetch(htmlSource, { credentials: 'omit' }),
                            fetch(cssSource, { credentials: 'omit' }),
                        ]);

                        if (!htmlResponse.ok || !cssResponse.ok) {
                            throw new Error('Challenge source request failed.');
                        }

                        const [htmlText, cssText] = await Promise.all([
                            htmlResponse.text(),
                            cssResponse.text(),
                        ]);

                        const srcdoc = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:#fff7e8;width:max-content;height:max-content}
body{display:inline-block;font-family:Arial,sans-serif}
a,area{cursor:default!important}
.preview-canvas{display:inline-block;padding:24px}
${cssText}
</style>
</head>
<body>
<div class="preview-canvas">${htmlText}</div>
</body>
</html>`;
                        previewFrames.forEach((frame) => {
                            frame.onload = () => {
                                disablePreviewLinks(frame);
                                if (frame === previewFrame && previewLoader) {
                                    previewLoader.hidden = true;
                                }
                                if (frame === modalPreviewFrame && modalPreviewLoader) {
                                    modalPreviewLoader.hidden = true;
                                }
                                fitPreviewFrame(frame);
                            };
                            frame.srcdoc = srcdoc;
                        });
                    } catch (error) {
                        previewFrames.forEach((frame) => frame.removeAttribute('srcdoc'));
                        setPreviewStatus('Preview unavailable', true);
                    }
                };

                const updateRoomPreview = () => {
                    if (roomPreviewName) {
                        roomPreviewName.textContent = roomNameInput.value.trim() || 'Room name pending';
                    }
                    if (roomPreviewDescription) {
                        roomPreviewDescription.innerHTML = formatRichTextPreview(
                            roomDescriptionInput.value.trim() || 'Add room notes so players know how this room should be used.'
                        );
                    }

                    const timerValue = Math.max(0, parseInt(timerLimitInput.value || '0', 10) || 0);
                    if (roomPreviewTimer) {
                        roomPreviewTimer.textContent = timerValue > 0 ? `${timerValue} min timer` : 'No timer';
                    }
                    if (roomPreviewMode) {
                        roomPreviewMode.textContent = strictModeInput.value === '1' ? 'Strict mode' : 'Normal mode';
                    }
                };

                const challengeRows = Object.values(challengeMap);
                const selectedChallengeName = () => {
                    const selected = challengeMap[challengeSelect.value] || null;
                    return selected?.name || '';
                };
                const closeChallengeResults = () => {
                    if (challengeResults) {
                        challengeResults.hidden = true;
                    }
                    challengeSearchInput?.setAttribute('aria-expanded', 'false');
                };
                const selectChallenge = (challengeId) => {
                    const selected = challengeMap[String(challengeId)] || null;
                    if (!selected || !challengeSelect) {
                        return;
                    }

                    challengeSelect.value = String(selected.challenge_id || challengeId);
                    if (challengeSearchInput) {
                        challengeSearchInput.value = selected.name || '';
                    }
                    closeChallengeResults();
                    challengeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                };
                const renderChallengeResults = () => {
                    if (!challengeResults || !challengeSearchInput) {
                        return;
                    }

                    const query = challengeSearchInput.value.trim().toLowerCase();
                    const matches = challengeRows
                        .filter((challenge) => !query || String(challenge.name || '').toLowerCase().includes(query))
                        .slice(0, 8);

                    challengeResults.innerHTML = matches.length > 0
                        ? matches.map((challenge) => `
                            <button type="button" class="create-room-combobox__option" role="option" data-challenge-option="${Number(challenge.challenge_id || 0)}">
                                <span>${escapeHtml(challenge.name || 'Untitled challenge')}</span>
                                <small>${escapeHtml(challenge.difficulty_name || 'Not set')} · ${Number(challenge.points || 0)} pts</small>
                            </button>
                        `).join('')
                        : '<p class="create-room-combobox__empty">No matching challenges.</p>';
                    challengeResults.hidden = false;
                    challengeSearchInput.setAttribute('aria-expanded', 'true');
                };

                const updateChallengePreview = () => {
                    const selected = challengeMap[challengeSelect.value] || null;
                    if (!selected) {
                        challengeName.textContent = 'Select a challenge';
                        challengeDifficulty.textContent = 'Not set';
                        challengeAuthor.textContent = <?= json_encode($teacherName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        challengePoints.textContent = '0 pts';
                        challengeInstruction.innerHTML = formatRichTextPreview('Select a challenge to preview its instruction.');
                        loadPreview('', '');
                        return;
                    }

                    challengeName.textContent = selected.name || 'Untitled challenge';
                    challengeDifficulty.textContent = selected.difficulty_name || 'Not set';
                    challengeAuthor.textContent = selected.author || <?= json_encode($teacherName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                    challengePoints.textContent = `${parseInt(selected.points || 0, 10)} pts`;
                    challengeInstruction.innerHTML = formatRichTextPreview(
                        selected.instruction || 'No instruction added for this challenge yet.'
                    );
                    loadPreview(selected.html_source || '', selected.css_source || '');
                };

                challengeSearchInput?.addEventListener('focus', renderChallengeResults);
                challengeSearchInput?.addEventListener('input', () => {
                    if (challengeSelect) {
                        challengeSelect.value = '';
                    }
                    renderChallengeResults();
                    updateChallengePreview();
                });
                challengeResults?.addEventListener('click', (event) => {
                    const option = event.target instanceof Element ? event.target.closest('[data-challenge-option]') : null;
                    if (!(option instanceof HTMLElement)) {
                        return;
                    }
                    selectChallenge(option.dataset.challengeOption || '');
                });
                document.addEventListener('click', (event) => {
                    if (challengeCombobox && !challengeCombobox.contains(event.target)) {
                        if (challengeSearchInput && challengeSelect?.value === '') {
                            challengeSearchInput.value = selectedChallengeName();
                        }
                        closeChallengeResults();
                    }
                });
                challengeSearchInput?.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        if (challengeSelect?.value === '') {
                            challengeSearchInput.value = selectedChallengeName();
                        }
                        closeChallengeResults();
                    }
                });

                [challengeSelect, roomNameInput, roomDescriptionInput, timerLimitInput, strictModeInput].forEach((field) => {
                    if (!field) {
                        return;
                    }
                    field.addEventListener('input', () => {
                        updateRoomPreview();
                        if (field === challengeSelect) {
                            updateChallengePreview();
                        }
                    });
                    field.addEventListener('change', () => {
                        updateRoomPreview();
                        if (field === challengeSelect) {
                            updateChallengePreview();
                        }
                    });
                });

                updateRoomPreview();
                updateChallengePreview();
                window.addEventListener('resize', () => {
                    fitPreviewFrame(previewFrame);
                    fitPreviewFrame(modalPreviewFrame);
                });
                challengePreviewModal?.addEventListener('shown.bs.modal', () => requestAnimationFrame(() => fitPreviewFrame(modalPreviewFrame)));
            });
            </script>
        <?php endif; ?>
    </section>
</main>
