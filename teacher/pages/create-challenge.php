<?php
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
?>

<main class="teacher-shell create-challenge-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Create Challenge</p>
                    <h1 class="mt-3 text-3xl font-black md:text-5xl">Build a Pixelwar challenge.</h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Start with challenge details, then create the HTML and CSS target source. Publishing is disabled for now while the backend flow is prepared.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="./?c=challenges" class="teacher-button teacher-button--light gap-2">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Challenges</span>
                    </a>
                </div>
            </div>
        </article>

        <article class="teacher-panel create-stepper-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Step <span id="challenge-step-current">1</span> of 3</p>
                    <h2 id="challenge-step-title" class="mt-2 text-2xl font-black">Challenge Info</h2>
                </div>
                <div class="create-progress-shell" aria-hidden="true">
                    <span id="challenge-progress-bar"></span>
                </div>
            </div>

            <div class="create-step-list mt-4" aria-label="Create challenge steps">
                <button type="button" class="create-step-pill is-active" data-step-indicator="1">
                    <span>1</span>
                    Challenge Info
                </button>
                <button type="button" class="create-step-pill" data-step-indicator="2" disabled>
                    <span>2</span>
                    Source Code
                </button>
                <button type="button" class="create-step-pill" data-step-indicator="3" disabled>
                    <span>3</span>
                    Confirmation
                </button>
            </div>
        </article>

        <section class="create-step-panel" data-step-panel="1">
            <div class="grid items-start gap-5 xl:grid-cols-[0.9fr_1.1fr]">
                <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">First Step</p>
                    <h2 class="mt-2 text-2xl font-black">Challenge Details</h2>
                    <p class="mt-2 text-sm font-bold leading-7 text-arcade-ink/65">
                        Fill the basic information first. These details will stay visible while creating the source code, so the target design stays aligned with the challenge goal.
                    </p>

                    <div class="mt-5 grid gap-4">
                        <label class="create-field">
                            <span>Challenge Name</span>
                            <input id="challenge-name" type="text" placeholder="Example: Arcade Button Match" maxlength="80">
                        </label>

                        <label class="create-field">
                            <span>Challenge Instruction</span>
                            <textarea id="challenge-instruction" rows="6" placeholder="Example: Match the border, rounded corners, shadow, spacing, alignment, and button styling shown in the target design."></textarea>
                        </label>

                        <label class="create-field">
                            <span>Difficulty</span>
                            <select id="challenge-difficulty">
                                <option value="">Select difficulty</option>
                                <option value="Easy">Easy</option>
                                <option value="Medium">Medium</option>
                                <option value="Hard">Hard</option>
                            </select>
                        </label>

                        <div id="challenge-info-feedback" class="create-feedback create-feedback--warn" role="status">
                            <i data-lucide="triangle-alert" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Complete the challenge details to continue.</span>
                        </div>
                    </div>
                </article>

                <aside class="teacher-panel create-example-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Preview Info Card</p>
                    <h2 id="info-preview-name" class="mt-2 text-3xl font-black">Challenge name pending</h2>
                    <p class="mt-2 inline-flex rounded-full border-2 border-arcade-ink/12 bg-arcade-yellow/35 px-3 py-1 text-xs font-black text-arcade-ink/70">
                        Teacher: <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p id="info-preview-instruction" class="mt-3 text-sm font-bold leading-7 text-arcade-ink/65">
                        Add clear instructions so players know what visual details to match.
                    </p>
                    <div class="create-example-meta mt-4 grid gap-2 sm:grid-cols-2">
                        <span><strong>Difficulty</strong><em id="info-preview-difficulty">Not Set</em></span>
                        <span><strong>Status</strong><em>Draft</em></span>
                    </div>
                </aside>
            </div>
        </section>

        <section class="create-step-panel hidden" data-step-panel="2">
            <article class="teacher-panel create-info-summary rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Challenge Info</p>
                        <h2 id="source-summary-name" class="mt-2 text-2xl font-black">Challenge name pending</h2>
                        <p id="source-summary-instruction" class="mt-2 max-w-4xl text-sm font-bold leading-7 text-arcade-ink/65">
                            Add clear instructions so players know what visual details to match.
                        </p>
                    </div>
                    <span id="source-summary-difficulty" class="create-difficulty-badge create-difficulty-badge--unset">Not Set</span>
                </div>
            </article>

            <section class="create-challenge-grid mt-5 grid items-start gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="grid gap-5">
                    <article class="teacher-panel create-editor-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Source 01</p>
                                <h2 class="mt-2 text-2xl font-black">HTML</h2>
                            </div>
                            <label class="create-file-drop">
                                <i data-lucide="upload" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Upload .html</span>
                                <input id="challenge-html-file" type="file" accept=".html,.htm,text/html" aria-label="Upload HTML file">
                            </label>
                        </div>

                        <div class="create-editor-wrap mt-4" data-editor-shell>
                            <div class="create-editor-gutter" id="html-line-count" aria-hidden="true">1</div>
                            <textarea id="challenge-html-code" class="create-code-editor" spellcheck="false" autocomplete="off" autocapitalize="off" aria-label="HTML source code"><section class="target-card">
  <p class="eyebrow">Pixelwar</p>
  <h1>Arcade Button</h1>
  <button class="target-button">Start Run</button>
</section></textarea>
                        </div>

                        <div id="challenge-html-feedback" class="create-feedback mt-3" role="status"></div>
                    </article>

                    <article class="teacher-panel create-editor-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Source 02</p>
                                <h2 class="mt-2 text-2xl font-black">CSS</h2>
                            </div>
                            <label class="create-file-drop">
                                <i data-lucide="upload" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Upload .css</span>
                                <input id="challenge-css-file" type="file" accept=".css,text/css" aria-label="Upload CSS file">
                            </label>
                        </div>

                        <div class="create-editor-wrap mt-4" data-editor-shell>
                            <div class="create-editor-gutter" id="css-line-count" aria-hidden="true">1</div>
                            <textarea id="challenge-css-code" class="create-code-editor" spellcheck="false" autocomplete="off" autocapitalize="off" aria-label="CSS source code">.target-card {
  width: 280px;
  border: 4px solid #26190f;
  border-radius: 24px;
  background: #fffdf6;
  padding: 24px;
  box-shadow: 8px 8px 0 #26190f;
  text-align: center;
}

.eyebrow {
  color: #ff8c42;
  font-size: 12px;
  font-weight: 900;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.target-card h1 {
  margin: 12px 0 18px;
  color: #26190f;
  font-size: 28px;
}

.target-button {
  border: 3px solid #26190f;
  border-radius: 14px;
  background: #ffd166;
  padding: 12px 18px;
  color: #26190f;
  font-weight: 900;
}</textarea>
                        </div>

                        <div id="challenge-css-feedback" class="create-feedback mt-3" role="status"></div>
                    </article>
                </div>

                <aside class="teacher-panel create-preview-card sticky top-5 rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Live Preview</p>
                            <h2 class="mt-2 text-2xl font-black">Target Design</h2>
                        </div>
                        <span id="challenge-preview-status" class="create-status-pill create-status-pill--ok">Valid</span>
                    </div>

                    <div class="create-preview-frame mt-4">
                        <iframe id="challenge-preview" title="Challenge target preview" sandbox=""></iframe>
                    </div>

                    <div class="create-rules mt-4 rounded-2xl border-2 border-arcade-ink/12 bg-arcade-cream/75 p-3">
                        <p class="font-arcade text-[9px] uppercase tracking-[0.2em] text-arcade-cyan">Rules</p>
                        <ul class="mt-3 grid gap-2 text-sm font-bold leading-6 text-arcade-ink/65">
                            <li>HTML must not include <code>&lt;style&gt;</code>, stylesheet links, or inline <code>style=""</code>.</li>
                            <li>CSS must only contain stylesheet code. HTML tags and scripts are blocked.</li>
                            <li>Uploads replace the matching editor and update preview instantly.</li>
                        </ul>
                    </div>
                </aside>
            </section>
        </section>

        <section class="create-step-panel hidden" data-step-panel="3">
            <div class="grid items-start gap-5 xl:grid-cols-[0.95fr_1.05fr]">
                <article class="teacher-panel create-confirm-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Final Check</p>
                    <h2 class="mt-2 text-3xl font-black">Confirm challenge draft</h2>
                    <p class="mt-3 text-sm font-bold leading-7 text-arcade-ink/65">
                        Review the challenge information and source validation before saving functionality is connected.
                    </p>

                    <div class="create-confirm-list mt-5 grid gap-3">
                        <div>
                            <strong>Challenge Name</strong>
                            <span id="confirm-name">Challenge name pending</span>
                        </div>
                        <div>
                            <strong>Difficulty</strong>
                            <span id="confirm-difficulty">Not Set</span>
                        </div>
                        <div>
                            <strong>Instruction</strong>
                            <span id="confirm-instruction">Add clear instructions so players know what visual details to match.</span>
                        </div>
                        <div>
                            <strong>Source Status</strong>
                            <span id="confirm-source-status">Valid HTML and CSS</span>
                        </div>
                    </div>
                </article>

                <aside class="teacher-panel create-preview-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Preview</p>
                            <h2 class="mt-2 text-2xl font-black">Final Target</h2>
                        </div>
                        <span id="confirm-preview-status" class="create-status-pill create-status-pill--ok">Ready</span>
                    </div>
                    <div class="create-preview-frame mt-4">
                        <iframe id="challenge-confirm-preview" title="Final challenge target preview" sandbox=""></iframe>
                    </div>
                </aside>
            </div>
        </section>

        <article class="teacher-panel create-actions rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p id="challenge-step-note" class="text-sm font-black leading-6 text-arcade-ink/60">Step 1: Complete the challenge information before adding source code.</p>
                <div class="flex flex-wrap gap-2">
                    <button id="challenge-back-step" type="button" class="teacher-button teacher-button--light gap-2" disabled>
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Back</span>
                    </button>
                    <button id="challenge-next-step" type="button" class="teacher-button teacher-button--primary gap-2">
                        <span>Continue</span>
                        <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </article>
    </section>
</main>

<script>
(() => {
    const htmlEditor = document.getElementById('challenge-html-code');
    const cssEditor = document.getElementById('challenge-css-code');
    const htmlFile = document.getElementById('challenge-html-file');
    const cssFile = document.getElementById('challenge-css-file');
    const preview = document.getElementById('challenge-preview');
    const confirmPreview = document.getElementById('challenge-confirm-preview');
    const previewStatus = document.getElementById('challenge-preview-status');
    const confirmPreviewStatus = document.getElementById('confirm-preview-status');
    const htmlFeedback = document.getElementById('challenge-html-feedback');
    const cssFeedback = document.getElementById('challenge-css-feedback');
    const htmlLineCount = document.getElementById('html-line-count');
    const cssLineCount = document.getElementById('css-line-count');
    const nameInput = document.getElementById('challenge-name');
    const instructionInput = document.getElementById('challenge-instruction');
    const difficultyInput = document.getElementById('challenge-difficulty');
    const infoFeedback = document.getElementById('challenge-info-feedback');
    const nextButton = document.getElementById('challenge-next-step');
    const backButton = document.getElementById('challenge-back-step');
    const stepPanels = document.querySelectorAll('[data-step-panel]');
    const stepPills = document.querySelectorAll('[data-step-indicator]');
    const currentStepLabel = document.getElementById('challenge-step-current');
    const stepTitle = document.getElementById('challenge-step-title');
    const progressBar = document.getElementById('challenge-progress-bar');
    const stepNote = document.getElementById('challenge-step-note');
    let currentStep = 1;
    let sourceIsValid = true;

    const stepTitles = {
        1: 'Challenge Info',
        2: 'Source Code Creation',
        3: 'Confirmation'
    };

    const stepNotes = {
        1: 'Step 1: Complete the challenge information before adding source code.',
        2: 'Step 2: Add clean HTML and CSS. The preview updates in real time.',
        3: 'Step 3: Confirm the draft. Backend publishing will be connected later.'
    };

    const escapeHtml = (value) => value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const setLines = (editor, gutter) => {
        const total = Math.max(1, editor.value.split('\n').length);
        gutter.textContent = Array.from({ length: total }, (_, index) => index + 1).join('\n');
    };

    const getChallengeInfo = () => ({
        name: nameInput.value.trim(),
        instruction: instructionInput.value.trim(),
        difficulty: difficultyInput.value.trim()
    });

    const validateInfo = () => {
        const info = getChallengeInfo();
        const errors = [];
        if (info.name.length < 4) {
            errors.push('Challenge name must be at least 4 characters.');
        }
        if (info.instruction.length < 20) {
            errors.push('Instruction must explain the challenge in at least 20 characters.');
        }
        if (!info.difficulty) {
            errors.push('Difficulty is required.');
        }
        return errors;
    };

    const updateInfoViews = () => {
        const info = getChallengeInfo();
        const safeName = info.name || 'Challenge name pending';
        const safeInstruction = info.instruction || 'Add clear instructions so players know what visual details to match.';
        const safeDifficulty = info.difficulty || 'Not Set';

        document.getElementById('info-preview-name').textContent = safeName;
        document.getElementById('info-preview-instruction').textContent = safeInstruction;
        document.getElementById('info-preview-difficulty').textContent = safeDifficulty;
        document.getElementById('source-summary-name').textContent = safeName;
        document.getElementById('source-summary-instruction').textContent = safeInstruction;
        document.getElementById('confirm-name').textContent = safeName;
        document.getElementById('confirm-instruction').textContent = safeInstruction;
        document.getElementById('confirm-difficulty').textContent = safeDifficulty;

        const difficultyBadge = document.getElementById('source-summary-difficulty');
        difficultyBadge.textContent = safeDifficulty;
        difficultyBadge.className = `create-difficulty-badge create-difficulty-badge--${safeDifficulty.toLowerCase().replace(/[^a-z]/g, '') || 'unset'}`;

        const errors = validateInfo();
        renderFeedback(infoFeedback, errors, 'Challenge information is ready.');
        return errors.length === 0;
    };

    const validateHtml = (code) => {
        const errors = [];
        if (/<style\b[\s\S]*?>[\s\S]*?<\/style>/i.test(code)) {
            errors.push('Internal CSS is not allowed. Move style rules to the CSS editor.');
        }
        if (/\sstyle\s*=\s*(['"]).*?\1/i.test(code)) {
            errors.push('Inline style attributes are not allowed.');
        }
        if (/<link\b[^>]*rel\s*=\s*(['"])stylesheet\1[^>]*>/i.test(code)) {
            errors.push('External stylesheet links are not allowed. Use the CSS editor only.');
        }
        if (/<script\b[\s\S]*?>[\s\S]*?<\/script>/i.test(code)) {
            errors.push('Script tags are not allowed in the target HTML.');
        }
        if (/<\/?(?:html|head|body|meta|title)\b/i.test(code)) {
            errors.push('Use target markup only. Full document tags are not needed here.');
        }
        return errors;
    };

    const validateCss = (code) => {
        const errors = [];
        if (/<\/?[a-z][\s\S]*?>/i.test(code)) {
            errors.push('CSS editor must not contain HTML tags.');
        }
        if (/javascript\s*:/i.test(code) || /expression\s*\(/i.test(code)) {
            errors.push('Unsafe CSS expressions are not allowed.');
        }
        if (/<script\b|<style\b/i.test(code)) {
            errors.push('Only plain CSS rules are allowed.');
        }
        return errors;
    };

    const renderFeedback = (target, errors, okMessage) => {
        if (errors.length === 0) {
            target.className = 'create-feedback create-feedback--ok mt-3';
            target.innerHTML = `<i data-lucide="check-circle-2" class="h-4 w-4" aria-hidden="true"></i><span>${okMessage}</span>`;
            window.lucide?.createIcons();
            return;
        }

        target.className = 'create-feedback create-feedback--warn mt-3';
        target.innerHTML = `<i data-lucide="triangle-alert" class="h-4 w-4" aria-hidden="true"></i><div>${errors.map((error) => `<p>${escapeHtml(error)}</p>`).join('')}</div>`;
        window.lucide?.createIcons();
    };

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

    const refreshSource = () => {
        setLines(htmlEditor, htmlLineCount);
        setLines(cssEditor, cssLineCount);

        const htmlErrors = validateHtml(htmlEditor.value);
        const cssErrors = validateCss(cssEditor.value);
        const hasErrors = htmlErrors.length > 0 || cssErrors.length > 0;
        sourceIsValid = !hasErrors;

        renderFeedback(htmlFeedback, htmlErrors, 'HTML source is clean for challenge use.');
        renderFeedback(cssFeedback, cssErrors, 'CSS source is stylesheet-only.');

        const previewDocument = hasErrors
            ? buildPreviewDocument('<div class="preview-warning">Fix source warnings to preview the target.</div>', '.preview-warning { max-width: 320px; border: 3px solid #26190f; border-radius: 18px; background: #ffd166; padding: 18px; color: #26190f; font-weight: 900; text-align: center; box-shadow: 6px 6px 0 #26190f; }')
            : buildPreviewDocument(htmlEditor.value, cssEditor.value);

        previewStatus.textContent = hasErrors ? 'Needs Fix' : 'Valid';
        previewStatus.className = hasErrors ? 'create-status-pill create-status-pill--warn' : 'create-status-pill create-status-pill--ok';
        confirmPreviewStatus.textContent = hasErrors ? 'Needs Fix' : 'Ready';
        confirmPreviewStatus.className = hasErrors ? 'create-status-pill create-status-pill--warn' : 'create-status-pill create-status-pill--ok';
        document.getElementById('confirm-source-status').textContent = hasErrors ? 'Source needs fixes' : 'Valid HTML and CSS';
        preview.srcdoc = previewDocument;
        confirmPreview.srcdoc = previewDocument;
        updateNextButton();
    };

    const showStep = (step) => {
        currentStep = step;
        stepPanels.forEach((panel) => panel.classList.toggle('hidden', Number(panel.dataset.stepPanel) !== currentStep));
        stepPills.forEach((pill) => {
            const pillStep = Number(pill.dataset.stepIndicator);
            pill.classList.toggle('is-active', pillStep === currentStep);
            pill.classList.toggle('is-complete', pillStep < currentStep);
            pill.disabled = pillStep > currentStep;
        });
        currentStepLabel.textContent = String(currentStep);
        stepTitle.textContent = stepTitles[currentStep];
        stepNote.textContent = stepNotes[currentStep];
        progressBar.style.width = `${((currentStep - 1) / 2) * 100}%`;
        backButton.disabled = currentStep === 1;
        nextButton.querySelector('span').textContent = currentStep === 3 ? 'Create Challenge' : 'Continue';
        updateNextButton();
        window.lucide?.createIcons();
        document.querySelector('.create-stepper-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const updateNextButton = () => {
        const infoValid = updateInfoViews();
        nextButton.disabled = (currentStep === 1 && !infoValid) || (currentStep === 2 && !sourceIsValid);
    };

    const handleEditorKeydown = (event) => {
        if (event.key !== 'Tab') {
            return;
        }

        event.preventDefault();
        const editor = event.currentTarget;
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        editor.value = `${editor.value.slice(0, start)}  ${editor.value.slice(end)}`;
        editor.selectionStart = editor.selectionEnd = start + 2;
        refreshSource();
    };

    const readFileIntoEditor = (fileInput, editor, allowedExtensions) => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) {
            return;
        }

        const fileName = file.name.toLowerCase();
        if (!allowedExtensions.some((extension) => fileName.endsWith(extension))) {
            fileInput.value = '';
            refreshSource();
            return;
        }

        const reader = new FileReader();
        reader.addEventListener('load', () => {
            editor.value = String(reader.result || '');
            refreshSource();
        });
        reader.readAsText(file);
    };

    [htmlEditor, cssEditor].forEach((editor) => {
        editor.addEventListener('input', refreshSource);
        editor.addEventListener('keydown', handleEditorKeydown);
        editor.addEventListener('scroll', () => {
            const gutter = editor === htmlEditor ? htmlLineCount : cssLineCount;
            gutter.scrollTop = editor.scrollTop;
        });
    });

    [nameInput, instructionInput, difficultyInput].forEach((input) => {
        input.addEventListener('input', updateNextButton);
        input.addEventListener('change', updateNextButton);
    });

    htmlFile.addEventListener('change', () => readFileIntoEditor(htmlFile, htmlEditor, ['.html', '.htm']));
    cssFile.addEventListener('change', () => readFileIntoEditor(cssFile, cssEditor, ['.css']));
    backButton.addEventListener('click', () => showStep(Math.max(1, currentStep - 1)));
    nextButton.addEventListener('click', () => {
        if (currentStep < 3) {
            showStep(currentStep + 1);
            return;
        }

        stepNote.textContent = 'Draft confirmed. Save and database functionality will be connected next.';
        nextButton.disabled = true;
    });

    window.addEventListener('load', () => {
        window.lucide?.createIcons();
        refreshSource();
        showStep(1);
    });
    refreshSource();
    showStep(1);
})();
</script>
