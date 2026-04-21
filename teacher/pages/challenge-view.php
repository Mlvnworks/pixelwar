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
            <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-coral">Missing Challenge</p>
                <h1 class="mt-3 text-3xl font-black">Challenge not found.</h1>
                <p class="mt-2 text-sm font-bold leading-7 text-arcade-ink/62">The challenge may have been removed or the link is invalid.</p>
                <a href="./?c=dashboard" class="teacher-button teacher-button--light mt-4">Back to Dashboard</a>
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
            $isOwner = (int) ($_SESSION['user_id'] ?? 0) === (int) $challenge['user_id'];
            ?>

            <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Challenge Details</p>
                        <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars((string) $challenge['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="mt-3 max-w-4xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                            <?= htmlspecialchars((string) $challenge['instruction'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <?php if ($isOwner) : ?>
                            <a href="./?c=create-challenge&edit=<?= (int) $challenge['challenge_id'] ?>" class="teacher-button teacher-button--primary gap-2">
                                <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Edit Challenge</span>
                            </a>
                        <?php endif; ?>
                        <a href="./?c=dashboard" class="teacher-button teacher-button--light gap-2">
                            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Dashboard</span>
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
                                <strong><?= (int) $solvedPlayerCount ?> players</strong>
                            </span>
                        </div>

                        <div class="challenge-source-inline">
                            <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-cyan">Source</p>
                            <div class="mt-3 flex flex-wrap gap-2">
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
                            <span id="challenge-preview-status" class="teacher-pill bg-arcade-yellow">Loading</span>
                        </div>
                        <div class="challenge-detail-preview__frame mt-3">
                            <div id="challenge-preview-loader" class="challenge-preview-loader">
                                <span class="challenge-preview-loader__spinner" aria-hidden="true"></span>
                                <strong>Loading preview...</strong>
                                <small>Fetching uploaded HTML and CSS sources.</small>
                            </div>
                            <iframe
                                id="challenge-source-preview"
                                title="Challenge target preview"
                                sandbox="allow-same-origin"
                                data-html-source="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                                data-css-source="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                        </div>
                    </div>
                </div>
            </article>

            <section class="grid items-start gap-5">
                    <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Dummy Only</p>
                                <h2 class="mt-2 text-2xl font-black">Comments</h2>
                            </div>
                            <span class="teacher-pill bg-arcade-yellow"><?= count($dummyComments) ?> comments</span>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <?php foreach ($dummyComments as $comment) : ?>
                                <article class="rounded-2xl border-2 border-arcade-ink/12 bg-white p-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="text-sm font-black"><?= htmlspecialchars($comment['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <p class="mt-1 text-sm font-bold leading-6 text-arcade-ink/62"><?= htmlspecialchars($comment['body'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                        <p class="text-xs font-black uppercase tracking-[0.12em] text-arcade-orange"><?= htmlspecialchars($comment['time'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </article>
            </section>
        <?php endif; ?>
    </section>
</main>

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

    preview.addEventListener('load', () => fitPreviewFrame(preview), { once: false });

    const buildPreviewDocument = (html, css) => `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { box-sizing: border-box; }
html, body { margin: 0; min-height: 100%; }
body { display: grid; min-height: 100vh; place-items: center; background: #fff7e8; font-family: Arial, sans-serif; padding: 24px; }
${css}
</style>
</head>
<body>
${html}
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
