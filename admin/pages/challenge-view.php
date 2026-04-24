<?php
$challengeId = (int) ($_GET['id'] ?? 0);
$challenge = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->findCreatedChallenge($challengeId)
    : null;
$solvedPlayerCount = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->countCompletedByChallenge($challengeId)
    : 0;

$dummyComments = [
    ['name' => 'Mika Reyes', 'body' => 'Clear target design. Students should focus on spacing and border weight first.', 'time' => 'Today, 10:24 AM'],
    ['name' => 'Jon Cruz', 'body' => 'Good practice challenge for selector accuracy and visual matching.', 'time' => 'Yesterday, 4:18 PM'],
    ['name' => 'Ari Santos', 'body' => 'The button shadow makes this one easier to explain during review.', 'time' => 'Apr 18, 2026'],
];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <?php if ($challenge === null) : ?>
            <article class="teacher-panel p-5 md:p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-coral">Missing Challenge</p>
                <h1 class="mt-3 text-3xl font-bold">Challenge not found.</h1>
                <p class="mt-2 text-sm font-medium leading-7 text-arcade-ink/62">The challenge may have been removed or the link is invalid.</p>
                <a href="./?c=teachers" class="teacher-button teacher-button--light mt-4 w-fit no-underline">Back to Teachers</a>
            </article>
        <?php else : ?>
            <?php
            $difficulty = strtolower((string) $challenge['difficulty_name']);
            $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', $difficulty);
            $firstname = trim((string) ($challenge['firstname'] ?? ''));
            $lastname = trim((string) ($challenge['lastname'] ?? ''));
            $author = trim($firstname . ' ' . $lastname) ?: (string) $challenge['author'];
            $createdLabel = date('M j, Y g:i A', strtotime((string) $challenge['date_created']));
            $htmlSourceUrl = (string) $challenge['html_source'];
            $cssSourceUrl = (string) $challenge['css_source'];
            ?>

            <article class="teacher-panel p-5 md:p-6">
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Challenge Details</p>
                        <h1 class="mt-3 text-3xl font-bold leading-tight md:text-5xl"><?= htmlspecialchars((string) $challenge['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <div class="mt-3 max-w-4xl text-sm font-medium leading-7 text-arcade-ink/65 md:text-base">
                            <?= $tools->formatRichText((string) $challenge['instruction']) ?>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <a href="./?c=teacher-activity&id=<?= (int) $challenge['user_id'] ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Back to Activity</span>
                        </a>
                        <a href="./?c=teachers" class="teacher-button teacher-button--light gap-2 no-underline">
                            <i data-lucide="users" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Teachers</span>
                        </a>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 xl:grid-cols-[0.88fr_1.12fr]">
                    <div class="grid content-start gap-4">
                        <div class="challenge-detail-meta">
                            <span>
                                <small>Difficulty</small>
                                <strong class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($difficulty), ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Points</small>
                                <strong><?= (int) $challenge['points'] ?></strong>
                            </span>
                            <span>
                                <small>Author</small>
                                <strong><?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Created</small>
                                <strong><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                            <span>
                                <small>Solved</small>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <strong><?= (int) $solvedPlayerCount ?> players</strong>
                                    <a href="./?c=challenge-completions&id=<?= (int) $challenge['challenge_id'] ?>" class="teacher-link-button no-underline">View Records</a>
                                </div>
                            </span>
                        </div>

                        <div class="challenge-source-inline">
                            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Source</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="teacher-link-button no-underline">HTML Source</a>
                                <a href="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="teacher-link-button no-underline">CSS Source</a>
                            </div>
                        </div>
                    </div>

                    <div class="admin-challenge-preview">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Preview</p>
                                <h2 class="mt-1 text-xl font-bold">Target Design</h2>
                            </div>
                            <span id="challenge-preview-status" class="teacher-pill bg-arcade-yellow">Loading</span>
                        </div>
                        <div class="admin-challenge-preview__frame mt-3">
                            <div id="challenge-preview-loader" class="admin-challenge-preview-loader">
                                <span class="admin-challenge-preview-loader__spinner" aria-hidden="true"></span>
                                <strong>Loading preview...</strong>
                                <small>Fetching uploaded HTML and CSS sources.</small>
                            </div>
                            <div class="admin-challenge-preview__stage">
                                <iframe
                                    id="challenge-source-preview"
                                    title="Challenge target preview"
                                    sandbox="allow-same-origin"
                                    data-html-source="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    data-css-source="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <section class="teacher-panel p-5 md:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Comments</p>
                        <h2 class="mt-1 text-2xl font-bold">Player Notes</h2>
                    </div>
                    <span class="teacher-pill bg-arcade-yellow/35"><?= count($dummyComments) ?> comments</span>
                </div>
                <div class="mt-4 grid gap-3">
                    <?php foreach ($dummyComments as $comment) : ?>
                        <article class="rounded-2xl border border-arcade-ink/10 bg-white/80 p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-bold"><?= htmlspecialchars($comment['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="mt-1 text-sm leading-6 text-arcade-ink/62"><?= htmlspecialchars($comment['body'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55"><?= htmlspecialchars($comment['time'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>

<style>
.challenge-detail-meta {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
}

.challenge-detail-meta span,
.challenge-source-inline {
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.8);
    padding: 0.95rem 1rem;
}

.challenge-detail-meta small {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.55);
}

.challenge-detail-meta strong {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #26190f;
}

.admin-challenge-preview {
    min-width: 0;
}

.admin-challenge-preview__frame {
    position: relative;
    overflow: hidden;
    min-height: 28rem;
    border: 1px solid rgba(17, 24, 39, 0.08);
    border-radius: 1.25rem;
    background: linear-gradient(180deg, rgba(255, 247, 232, 0.9), rgba(255, 255, 255, 0.92));
}

.admin-challenge-preview__stage {
    position: absolute;
    inset: 0;
}

.admin-challenge-preview__frame iframe {
    position: absolute;
    top: 0;
    left: 0;
    border: 0;
    background: transparent;
    opacity: 0;
    transition: opacity 0.18s ease;
}

.admin-challenge-preview__frame iframe.is-ready {
    opacity: 1;
}

.admin-challenge-preview-loader {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    gap: 0.55rem;
    text-align: center;
    padding: 1.5rem;
    color: #26190f;
}

.admin-challenge-preview-loader[hidden] {
    display: none;
}

.admin-challenge-preview-loader strong {
    font-size: 0.95rem;
    font-weight: 700;
}

.admin-challenge-preview-loader small {
    font-size: 0.78rem;
    color: rgba(38, 25, 15, 0.6);
}

.admin-challenge-preview-loader__spinner {
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 999px;
    border: 3px solid rgba(37, 99, 235, 0.18);
    border-top-color: #2563eb;
    animation: adminPreviewSpin 0.8s linear infinite;
}

@keyframes adminPreviewSpin {
    to {
        transform: rotate(360deg);
    }
}

body.pixelwar-dark-mode .challenge-detail-meta span,
body.pixelwar-dark-mode .challenge-source-inline {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(17, 24, 39, 0.72);
}

body.pixelwar-dark-mode .challenge-detail-meta strong,
body.pixelwar-dark-mode .admin-challenge-preview-loader {
    color: #f8fafc;
}

body.pixelwar-dark-mode .challenge-detail-meta small,
body.pixelwar-dark-mode .admin-challenge-preview-loader small {
    color: rgba(248, 250, 252, 0.62);
}

body.pixelwar-dark-mode .admin-challenge-preview__frame {
    border-color: rgba(148, 163, 184, 0.18);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.94), rgba(17, 24, 39, 0.9));
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const preview = document.getElementById('challenge-source-preview');
    const status = document.getElementById('challenge-preview-status');
    const loader = document.getElementById('challenge-preview-loader');

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
        if (!doc || !body || !html) {
            return;
        }

        const stage = frame.parentElement;
        if (!(stage instanceof HTMLElement)) {
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
        preview.classList.add('is-ready');
    }, { once: false });

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
    ]).then(([html, css]) => {
        preview.srcdoc = buildPreviewDocument(html, css);
        if (loader) {
            loader.hidden = true;
        }
        status.textContent = 'Loaded';
        status.className = 'teacher-pill bg-arcade-mint';
    }).catch(() => {
        preview.srcdoc = buildPreviewDocument('<div class="preview-warning">Preview unavailable. Open the source links to inspect the files.</div>', '.preview-warning { max-width: 320px; border: 3px solid #26190f; border-radius: 18px; background: #ffd166; padding: 18px; color: #26190f; font-weight: 900; text-align: center; box-shadow: 6px 6px 0 #26190f; }');
        if (loader) {
            loader.hidden = true;
        }
        status.textContent = 'Unavailable';
        status.className = 'teacher-pill bg-arcade-coral/30';
    });

    if ('ResizeObserver' in window && preview.parentElement) {
        const previewObserver = new ResizeObserver(() => fitPreviewFrame(preview));
        previewObserver.observe(preview.parentElement);
    }

    window.addEventListener('resize', () => fitPreviewFrame(preview));
});
</script>
