<?php
require_once __DIR__ . '/../classes/challenge-catalog.php';

$challengeSlug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$challenge = ChallengeCatalog::find($challengeSlug) ?? ChallengeCatalog::first();
$challengeStartUrl = './?c=pixelwar&intro=1&challenge=' . urlencode($challenge['slug']);
$comments = $challenge['comments'];
$moreChallenges = array_filter(ChallengeCatalog::all(), static function (array $catalogChallenge) use ($challenge): bool {
    return $catalogChallenge['slug'] !== $challenge['slug'];
});
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

<main class="challenge-detail-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_16%,rgba(255,209,102,0.28),transparent_24%),radial-gradient(circle_at_90%_12%,rgba(76,201,240,0.22),transparent_25%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_42%)]"></div>
    <div class="challenge-detail-grid absolute inset-0"></div>

    <section class="container relative">
        <a href="./?c=home" class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/60">
            <span aria-hidden="true">&larr;</span>
            Back Home
        </a>

        <article class="challenge-detail-card mt-5 rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-7">
            <div class="challenge-detail-layout grid gap-7 xl:grid-cols-[1.05fr_0.95fr]">
                <section>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="challenge-difficulty <?= htmlspecialchars($challenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold">
                            <?= htmlspecialchars($challenge['level'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="rounded-full bg-arcade-cyan/30 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['estimate'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="rounded-full bg-arcade-coral/20 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($challenge['reward'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <p class="mt-6 font-arcade text-[10px] uppercase tracking-[0.28em] text-arcade-orange">Challenge Brief</p>
                    <h1 class="mt-3 text-4xl font-bold leading-tight md:text-6xl"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mt-4 max-w-3xl text-base leading-8 text-arcade-ink/70"><?= htmlspecialchars($challenge['objective'], ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="challenge-meta-grid mt-6 grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl border-2 border-arcade-ink/15 bg-white/75 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Author</p>
                            <p class="mt-1 text-lg font-extrabold"><?= htmlspecialchars($challenge['author'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-2xl border-2 border-arcade-ink/15 bg-white/75 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Focus</p>
                            <p class="mt-1 text-lg font-extrabold"><?= htmlspecialchars($challenge['focus'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="rounded-2xl border-2 border-arcade-ink/15 bg-white/75 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-arcade-ink/55">Goal</p>
                            <p class="mt-1 text-lg font-extrabold">Match UI</p>
                        </div>
                    </div>

                    <div class="challenge-actions mt-6 flex flex-wrap gap-2 sm:flex-nowrap">
                        <button id="preview-toggle" type="button" class="challenge-preview-toggle inline-flex w-full justify-center rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-4 py-3 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" data-bs-toggle="modal" data-bs-target="#challenge-preview-modal">
                            <span>Preview</span>
                        </button>
                        <button id="comments-toggle" type="button" class="relative inline-flex w-full justify-center rounded-xl border-2 border-arcade-ink bg-white px-4 py-3 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow sm:w-auto sm:min-w-[8.5rem]" aria-expanded="false" aria-controls="challenge-comments-section">
                            <span data-comments-toggle-label>Comments</span>
                            <span class="comments-count-badge absolute -right-2 -top-2 grid h-6 min-w-6 place-items-center rounded-full border-2 border-arcade-ink bg-arcade-yellow px-1 text-[10px] font-extrabold text-arcade-ink">
                                <?= count($comments) ?>
                            </span>
                        </button>
                        <a href="<?= htmlspecialchars($challengeStartUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex w-full justify-center rounded-xl border-2 border-arcade-ink bg-arcade-orange px-6 py-3 text-sm font-bold text-white no-underline shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink sm:flex-1">
                            Start Challenge
                        </a>
                    </div>
                </section>

                <section id="challenge-preview-card" class="challenge-preview-card rounded-[24px] bg-white/75 p-4 shadow-[0_10px_30px_rgba(38,25,15,0.12)]">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Preview</p>
                    </div>
                    <div class="challenge-preview-frame-shell mt-4">
                        <iframe
                            class="challenge-preview-frame h-[360px] w-full rounded-[20px] bg-transparent"
                            title="Static isolated challenge preview"
                            sandbox=""
                            loading="lazy"
                            srcdoc="<?= htmlspecialchars($previewSrcdoc, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                    </div>
                </section>
            </div>

            <section id="challenge-comments-section" class="mt-7 rounded-[24px] border-2 border-arcade-ink/15 bg-white/75 p-5 shadow-[0_10px_30px_rgba(38,25,15,0.12)]" tabindex="-1" hidden>
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Discussion</p>
                        <h2 class="mt-2 text-2xl font-bold">Player Comments</h2>
                    </div>
                    <span class="rounded-full bg-arcade-yellow px-3 py-1 text-xs font-bold text-arcade-ink"><?= count($comments) ?> posts</span>
                </div>

                <form class="mt-4 rounded-2xl border-2 border-arcade-ink/15 bg-arcade-cream/80 p-4">
                    <label class="text-sm font-bold" for="challenge-comment">Post a comment</label>
                    <textarea id="challenge-comment" class="mt-2 min-h-24 w-full rounded-xl bg-white px-3 py-2 text-sm outline-none transition focus:ring-4 focus:ring-arcade-orange/20" placeholder="Share a tip, question, or note about this challenge."></textarea>
                    <button type="button" class="mt-3 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                        Post Comment
                    </button>
                </form>

                <div class="mt-4 grid gap-3 md:grid-cols-2" data-comment-list>
                    <?php foreach ($comments as $commentIndex => $comment) : ?>
                        <article class="challenge-comment rounded-2xl border-2 border-arcade-ink/15 bg-arcade-panel p-4" data-comment-row data-comment-index="<?= (int) $commentIndex ?>">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-bold"><?= htmlspecialchars($comment['player'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-arcade-ink/50"><?= htmlspecialchars($comment['posted'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-arcade-ink/70"><?= htmlspecialchars($comment['body'], ENT_QUOTES, 'UTF-8') ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <button id="comments-prev" type="button" class="rounded-xl bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Prev</button>
                    <span id="comments-page-status" class="text-xs font-bold text-arcade-ink/60"></span>
                    <button id="comments-next" type="button" class="rounded-xl bg-white px-3 py-1.5 text-xs font-bold transition hover:bg-arcade-yellow/50">Next</button>
                </div>
            </section>
        </article>

        <section class="challenge-more-section mt-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">More Like This</p>
                    <h2 class="mt-2 text-2xl font-bold">Challenges</h2>
                </div>
                <a href="./?c=home" class="rounded-xl bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline transition hover:bg-arcade-yellow/60">View All</a>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($moreChallenges as $moreChallenge) : ?>
                    <article class="challenge-more-card rounded-[22px] border-2 border-arcade-ink/15 bg-arcade-panel p-4 shadow-[5px_5px_0_rgba(38,25,15,0.18)]">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="challenge-difficulty <?= htmlspecialchars($moreChallenge['levelClass'], ENT_QUOTES, 'UTF-8') ?> rounded-full px-3 py-1 text-xs font-bold"><?= htmlspecialchars($moreChallenge['level'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="rounded-full bg-arcade-cyan/30 px-3 py-1 text-xs font-bold"><?= htmlspecialchars($moreChallenge['estimate'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <h3 class="mt-3 text-xl font-bold"><?= htmlspecialchars($moreChallenge['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="mt-2 text-sm leading-6 text-arcade-ink/70"><?= htmlspecialchars($moreChallenge['description'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="challenge-more-card__footer mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-arcade-ink/50">By <?= htmlspecialchars($moreChallenge['author'], ENT_QUOTES, 'UTF-8') ?></p>
                            <a href="./?c=challenge&slug=<?= urlencode($moreChallenge['slug']) ?>" class="challenge-more-card__action rounded-xl border-2 border-arcade-ink bg-arcade-orange px-3 py-1.5 text-sm font-bold text-white no-underline shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow hover:text-arcade-ink">Train</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</main>

<div class="modal fade challenge-preview-modal" id="challenge-preview-modal" tabindex="-1" aria-labelledby="challenge-preview-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Preview</p>
                    <h2 id="challenge-preview-modal-title" class="modal-title mt-2 text-xl font-bold"><?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <div class="challenge-preview-frame-shell">
                    <iframe
                        class="challenge-preview-frame h-[360px] w-full rounded-[20px] bg-transparent"
                        title="Static isolated challenge preview"
                        sandbox=""
                        loading="lazy"
                        srcdoc="<?= htmlspecialchars($previewSrcdoc, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const rows = Array.from(document.querySelectorAll('[data-comment-row]'));
    const previousButton = document.getElementById('comments-prev');
    const nextButton = document.getElementById('comments-next');
    const pageStatus = document.getElementById('comments-page-status');
    const commentsToggle = document.getElementById('comments-toggle');
    const commentsToggleLabel = commentsToggle?.querySelector('[data-comments-toggle-label]');
    const commentsSection = document.getElementById('challenge-comments-section');
    const previewShells = Array.from(document.querySelectorAll('.challenge-preview-frame-shell'));
    const previewModal = document.getElementById('challenge-preview-modal');
    const mobileQuery = window.matchMedia('(max-width: 768px)');
    const pageSize = 2;
    let currentPage = 1;
    const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
    const previewWidth = 390;
    const previewHeight = 360;

    const renderPage = () => {
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        rows.forEach((row, index) => {
            row.hidden = index < start || index >= end;
        });

        if (pageStatus) {
            pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;
        }

        if (previousButton) {
            previousButton.disabled = currentPage === 1;
        }

        if (nextButton) {
            nextButton.disabled = currentPage === totalPages;
        }
    };

    previousButton?.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        renderPage();
    });

    nextButton?.addEventListener('click', () => {
        currentPage = Math.min(totalPages, currentPage + 1);
        renderPage();
    });

    commentsToggle?.addEventListener('click', () => {
        if (!commentsSection) {
            return;
        }

        const isOpening = commentsSection.hidden;
        commentsSection.hidden = !isOpening;
        commentsToggle.setAttribute('aria-expanded', isOpening ? 'true' : 'false');

        if (commentsToggleLabel) {
            commentsToggleLabel.textContent = isOpening ? 'Hide Comments' : 'Comments';
        }

        if (isOpening && mobileQuery.matches) {
            requestAnimationFrame(() => {
                commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                commentsSection.focus({ preventScroll: true });
            });
        }
    });

    const resizePreview = () => {
        if (previewShells.length === 0) {
            return;
        }

        previewShells.forEach((previewShell) => {
            const shellWidth = previewShell.clientWidth;
            if (shellWidth <= 0) {
                return;
            }

            const scale = Math.min(1, shellWidth / previewWidth);
            previewShell.style.setProperty('--challenge-preview-scale', scale.toFixed(4));
            previewShell.style.height = `${Math.ceil(previewHeight * scale)}px`;
        });
    };

    if (previewShells.length > 0 && 'ResizeObserver' in window) {
        const previewObserver = new ResizeObserver(resizePreview);
        previewShells.forEach((previewShell) => previewObserver.observe(previewShell));
    }

    previewModal?.addEventListener('shown.bs.modal', () => requestAnimationFrame(resizePreview));
    window.addEventListener('resize', resizePreview);
    resizePreview();
    renderPage();
})();
</script>
