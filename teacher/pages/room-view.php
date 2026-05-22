<?php
$roomId = max(0, (int) ($_GET['id'] ?? 0));
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$room = $roomRepository instanceof RoomRepository
    ? $roomRepository->findByIdForOwner($roomId, $teacherId)
    : null;

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
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <?php if ($room === null) : ?>
            <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f] md:p-6">
                <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-coral">Missing Room</p>
                <h1 class="mt-3 text-3xl font-black">Room not found.</h1>
                <p class="mt-2 text-sm font-bold leading-7 text-arcade-ink/62">The room may have been removed or does not belong to this teacher account.</p>
                <a href="./?c=rooms" class="teacher-button teacher-button--light mt-4 gap-2">
                    <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Back to Rooms</span>
                </a>
            </article>
        <?php else : ?>
            <?php
            $difficulty = ucfirst(strtolower((string) ($room['difficulty_name'] ?? 'Unknown')));
            $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', strtolower($difficulty));
            $author = trim((string) ($room['teacher_firstname'] ?? '') . ' ' . (string) ($room['teacher_lastname'] ?? ''))
                ?: (string) ($room['teacher_username'] ?? 'Teacher');
            $strictModeEnabled = (int) ($room['strict_mode'] ?? 0) === 1;
            $roomIsOpen = (int) ($room['status'] ?? 1) === 1;
            $roomCode = trim((string) ($room['room_code'] ?? '')) ?: 'Not set';
            $roomDescription = trim((string) ($room['room_description'] ?? ''));
            $htmlSourceUrl = (string) ($room['html_source'] ?? '');
            $cssSourceUrl = (string) ($room['css_source'] ?? '');
            $dummyShareLink = 'https://pixelwar.local/room/' . rawurlencode($roomCode !== 'Not set' ? $roomCode : 'ROOM-CODE');
            ?>

            <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Room Details</p>
                        <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars((string) ($room['room_name'] ?? 'Untitled Room'), ENT_QUOTES, 'UTF-8') ?></h1>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="teacher-pill <?= $roomIsOpen ? 'bg-arcade-mint/40' : 'bg-arcade-coral/25' ?>">
                                <?= $roomIsOpen ? 'Open' : 'Closed' ?>
                            </span>
                            <span class="teacher-pill <?= $strictModeEnabled ? 'bg-arcade-coral/25' : 'bg-arcade-cyan/25' ?>">
                                <?= $strictModeEnabled ? 'Strict' : 'Normal' ?>
                            </span>
                        </div>
                        <?php if ($roomDescription !== '') : ?>
                            <div class="mt-3 max-w-4xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                                <?= $tools->formatRichText($roomDescription) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="room-view-actions">
                        <form action="./?c=room-view&id=<?= (int) ($room['room_id'] ?? 0) ?>" method="post" class="contents">
                            <?= teacherPanelCsrfField() ?>
                            <input type="hidden" name="room_action" value="toggle_status">
                            <input type="hidden" name="room_id" value="<?= (int) ($room['room_id'] ?? 0) ?>">
                            <input type="hidden" name="next_status" value="<?= $roomIsOpen ? 0 : 1 ?>">
                            <button type="submit" class="teacher-button <?= $roomIsOpen ? 'teacher-button--light' : 'teacher-button--primary' ?> room-view-actions__button" aria-label="<?= $roomIsOpen ? 'Close room' : 'Open room' ?>" title="<?= $roomIsOpen ? 'Close room' : 'Open room' ?>">
                                <i data-lucide="<?= $roomIsOpen ? 'lock' : 'unlock' ?>" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        </form>
                        <a href="./?c=room-session&id=<?= (int) ($room['room_id'] ?? 0) ?>" class="teacher-button teacher-button--primary room-view-actions__button" aria-label="Room session" title="Room session">
                            <i data-lucide="play-circle" class="h-4 w-4" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="teacher-button teacher-button--light room-view-actions__button" data-bs-toggle="modal" data-bs-target="#room-share-modal" aria-label="Share room" title="Share room">
                            <i data-lucide="share-2" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                        <a href="./?c=rooms" class="teacher-button teacher-button--light room-view-actions__button" aria-label="Back to rooms" title="Back to rooms">
                            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 xl:grid-cols-[0.92fr_1.08fr]">
                    <div class="grid content-start gap-4">
                        <div class="challenge-detail-meta">
                            <span>
                                <small>Room Code</small>
                                <strong><?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Challenge</small>
                                <strong><?= htmlspecialchars((string) ($room['challenge_name'] ?? 'Unknown Challenge'), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Difficulty</small>
                                <strong class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Points</small>
                                <strong><?= (int) ($room['points'] ?? 0) ?> pts</strong>
                            </span>
                            <span>
                                <small>Timer</small>
                                <strong><?= (int) ($room['timer_limit'] ?? 0) > 0 ? (int) ($room['timer_limit'] ?? 0) . ' min' : 'No timer' ?></strong>
                            </span>
                            <span>
                                <small>Mode</small>
                                <strong><?= $strictModeEnabled ? 'Strict' : 'Normal' ?></strong>
                            </span>
                            <span>
                                <small>State</small>
                                <strong><?= $roomIsOpen ? 'Open' : 'Closed' ?></strong>
                            </span>
                            <span>
                                <small>Started</small>
                                <strong><?= htmlspecialchars($formatTimestamp($room['started_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Ended</small>
                                <strong><?= htmlspecialchars($formatTimestamp($room['ended_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Created</small>
                                <strong><?= htmlspecialchars($formatTimestamp($room['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                        </div>

                        <div class="challenge-source-inline">
                            <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-cyan">Challenge Info</p>
                            <h2 class="mt-3 text-2xl font-black"><?= htmlspecialchars((string) ($room['challenge_name'] ?? 'Unknown Challenge'), ENT_QUOTES, 'UTF-8') ?></h2>
                            <div class="mt-3 text-sm font-bold leading-7 text-arcade-ink/65">
                                <?= $tools->formatRichText((string) ($room['challenge_instruction'] ?? '')) ?>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="teacher-pill bg-arcade-yellow"><?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></span>
                                <a href="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="teacher-mini-link">HTML Source</a>
                                <a href="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="teacher-mini-link">CSS Source</a>
                            </div>
                        </div>
                    </div>

                    <div class="challenge-detail-preview">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-orange">Preview</p>
                                <h2 class="mt-1 text-xl font-black">Target Design</h2>
                            </div>
                            <span id="room-preview-status" class="teacher-pill bg-arcade-yellow">Loading</span>
                        </div>
                        <div class="challenge-detail-preview__frame mt-3">
                            <div id="room-preview-loader" class="challenge-preview-loader">
                                <span class="challenge-preview-loader__spinner" aria-hidden="true"></span>
                                <strong>Loading preview...</strong>
                                <small>Fetching uploaded HTML and CSS sources.</small>
                            </div>
                            <div class="challenge-detail-preview__stage">
                                <iframe
                                    id="room-source-preview"
                                    title="Room challenge preview"
                                    sandbox="allow-same-origin"
                                    data-html-source="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    data-css-source="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </section>
</main>

<?php if ($room !== null) : ?>
<div class="modal fade" id="room-share-modal" tabindex="-1" aria-labelledby="room-share-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content room-share-modal rounded-[24px] border-4 border-arcade-ink bg-arcade-panel shadow-[8px_8px_0_rgba(38,25,15,0.28)]">
            <div class="modal-header border-b-2 border-arcade-ink/10 px-4 py-3">
                <div>
                    <p class="mb-1 font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-cyan">Share Room</p>
                    <h2 id="room-share-modal-label" class="mb-0 text-xl font-black">Send access details</h2>
                </div>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <p class="mb-4 text-sm font-bold leading-6 text-arcade-ink/70">
                    Copy the room code or the room link. The link is placeholder-only for now.
                </p>

                <div class="room-share-stack">
                    <div class="room-share-card">
                        <div>
                            <p class="room-share-label">Room Code</p>
                            <strong class="room-share-value"><?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <button
                            type="button"
                            class="room-share-copy-button"
                            data-copy-text="<?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?>"
                            data-default-label="Copy Code">
                            Copy Code
                        </button>
                    </div>

                    <div class="room-share-card">
                        <div>
                            <p class="room-share-label">Room Link</p>
                            <strong class="room-share-value room-share-value--link"><?= htmlspecialchars($dummyShareLink, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <button
                            type="button"
                            class="room-share-copy-button"
                            data-copy-text="<?= htmlspecialchars($dummyShareLink, ENT_QUOTES, 'UTF-8') ?>"
                            data-default-label="Copy Link">
                            Copy Link
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-t-2 border-arcade-ink/10 px-4 py-3">
                <button type="button" class="teacher-button teacher-button--light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.room-view-actions {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
}

.room-view-actions__button {
    flex: 0 0 auto;
    width: 2.75rem;
    min-width: 2.75rem;
    height: 2.75rem;
    padding: 0;
    justify-content: center;
}

.challenge-detail-meta {
    display: grid;
    gap: 0.75rem;
}

.challenge-detail-meta span,
.challenge-source-inline {
    border: 2px solid rgba(38, 25, 15, 0.12);
    border-radius: 1.1rem;
    background: rgba(255, 255, 255, 0.82);
    padding: 1rem 1.05rem;
}

.challenge-detail-meta small {
    display: block;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.56);
}

.challenge-detail-meta strong {
    display: block;
    margin-top: 0.45rem;
    font-size: 1rem;
    font-weight: 900;
    color: #26190f;
}

.challenge-detail-preview__frame {
    position: relative;
    min-height: 25rem;
    overflow: hidden;
    border: 3px solid rgba(38, 25, 15, 0.12);
    border-radius: 1.5rem;
    background: linear-gradient(180deg, rgba(255, 247, 232, 0.96), rgba(255, 255, 255, 0.9));
}

.challenge-detail-preview__stage {
    position: absolute;
}

.challenge-detail-preview__frame iframe {
    position: absolute;
    border: 0;
    background: transparent;
}

.challenge-preview-loader {
    position: absolute;
    inset: 0;
    z-index: 1;
    display: grid;
    place-items: center;
    align-content: center;
    gap: 0.65rem;
    text-align: center;
    padding: 1.5rem;
    color: #26190f;
}

.challenge-preview-loader[hidden] {
    display: none;
}

.challenge-preview-loader strong {
    font-size: 0.95rem;
    font-weight: 700;
}

.challenge-preview-loader small {
    font-size: 0.78rem;
    color: rgba(38, 25, 15, 0.6);
}

.challenge-preview-loader__spinner {
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 999px;
    border: 3px solid rgba(76, 201, 240, 0.2);
    border-top-color: #4cc9f0;
    animation: roomPreviewSpin 0.8s linear infinite;
}

@keyframes roomPreviewSpin {
    to { transform: rotate(360deg); }
}

body.pixelwar-dark-mode .challenge-detail-meta span,
body.pixelwar-dark-mode .challenge-source-inline {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(17, 24, 39, 0.72);
}

body.pixelwar-dark-mode .challenge-detail-meta strong,
body.pixelwar-dark-mode .challenge-preview-loader {
    color: #f8fafc;
}

body.pixelwar-dark-mode .challenge-detail-meta small,
body.pixelwar-dark-mode .challenge-preview-loader small {
    color: rgba(248, 250, 252, 0.62);
}

body.pixelwar-dark-mode .challenge-detail-preview__frame {
    border-color: rgba(148, 163, 184, 0.18);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.94), rgba(17, 24, 39, 0.9));
}

.room-share-stack {
    display: grid;
    gap: 0.85rem;
}

.room-share-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border: 2px solid rgba(38, 25, 15, 0.1);
    border-radius: 1.1rem;
    background: rgba(255, 255, 255, 0.84);
    padding: 1rem;
}

.room-share-label {
    margin: 0;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.56);
}

.room-share-value {
    display: block;
    margin-top: 0.4rem;
    color: #26190f;
    font-size: 0.98rem;
    font-weight: 900;
    word-break: break-word;
}

.room-share-value--link {
    font-size: 0.84rem;
}

.room-share-copy-button {
    flex: 0 0 auto;
    border: 2px solid rgba(38, 25, 15, 0.14);
    border-radius: 999px;
    background: rgba(76, 201, 240, 0.16);
    padding: 0.55rem 0.9rem;
    color: #26190f;
    font-size: 0.74rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    transition: transform 160ms ease, background-color 160ms ease;
}

.room-share-copy-button:hover {
    transform: translateY(-1px);
    background: rgba(255, 209, 102, 0.34);
}

body.pixelwar-dark-mode .room-share-card {
    border-color: rgba(148, 163, 184, 0.16);
    background: rgba(17, 24, 39, 0.76);
}

body.pixelwar-dark-mode .room-share-label {
    color: rgba(248, 250, 252, 0.56);
}

body.pixelwar-dark-mode .room-share-value {
    color: #f8fafc;
}

body.pixelwar-dark-mode .room-share-copy-button {
    border-color: rgba(226, 232, 240, 0.16);
    background: rgba(76, 201, 240, 0.18);
    color: #f8fafc;
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const preview = document.getElementById('room-source-preview');
    const status = document.getElementById('room-preview-status');
    const loader = document.getElementById('room-preview-loader');
    const shareCopyButtons = Array.from(document.querySelectorAll('.room-share-copy-button'));

    if (!preview || !status) {
        return;
    }

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

    preview.addEventListener('load', () => {
        fitPreviewFrame(preview);
        status.textContent = 'Ready';
        status.classList.remove('bg-arcade-coral');
        status.classList.add('bg-arcade-cyan');
        if (loader) {
            loader.hidden = true;
        }
    });

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

    Promise.all([
        fetch(preview.dataset.htmlSource || '').then((response) => response.ok ? response.text() : Promise.reject(new Error('HTML source unavailable'))),
        fetch(preview.dataset.cssSource || '').then((response) => response.ok ? response.text() : Promise.reject(new Error('CSS source unavailable'))),
    ])
        .then(([html, css]) => {
            preview.srcdoc = buildPreviewDocument(html, css);
        })
        .catch(() => {
            status.textContent = 'Unavailable';
            status.classList.remove('bg-arcade-yellow');
            status.classList.add('bg-arcade-coral');
            if (loader) {
                loader.querySelector('strong')?.replaceChildren(document.createTextNode('Preview unavailable'));
                loader.querySelector('small')?.replaceChildren(document.createTextNode('The source files could not be loaded right now.'));
            }
        });

    window.addEventListener('resize', () => fitPreviewFrame(preview));

    shareCopyButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const copyText = button.dataset.copyText || '';
            const defaultLabel = button.dataset.defaultLabel || 'Copy';
            if (copyText === '') {
                return;
            }

            try {
                await navigator.clipboard.writeText(copyText);
                button.textContent = 'Copied';
                window.setTimeout(() => {
                    button.textContent = defaultLabel;
                }, 1600);
            } catch (error) {
                button.textContent = 'Failed';
                window.setTimeout(() => {
                    button.textContent = defaultLabel;
                }, 1600);
            }
        });
    });
});
</script>
