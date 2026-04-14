<section id="game-test" class="p-0">
        <div id="game-opening-effect" class="game-opening-effect" aria-hidden="true">
            <div class="game-opening-effect__panel">
                <span class="game-opening-effect__eyebrow">Loading Arena</span>
                <strong>Pixelwar</strong>
                <span class="game-opening-effect__bar"></span>
            </div>
        </div>

        <div class="challenge-shell border-4 border-arcade-ink/10 bg-arcade-panel/80 p-2">
            <aside class="floating-hud" aria-live="polite">
                <span id="game-status" class="hud-pill">Waiting for your first move.</span>
            </aside>

            <div class="challenge-grid" id="challenge-grid">
                <section class="builder-pane rounded-[26px] border-4 border-arcade-ink/10 bg-white/70 p-4 md:p-5">
                    <section class="panel-card panel-card--preview rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                        <div class="preview-card-header mb-3">
                            <h2 class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">1. Live Preview</h2>
                            <button type="button" class="mobile-preview-toggle rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-3 py-1.5 text-[11px] font-bold text-arcade-ink shadow-[0_3px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" data-bs-toggle="modal" data-bs-target="#mobile-preview-modal">
                                View Target
                            </button>
                        </div>
                        <div class="preview-frame rounded-[20px] border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-4">
                            <div class="preview-stage">
                                <div class="preview-scope">
                                    <article class="pixel-card">
                                        <span class="pixel-badge">Student Build</span>
                                        <h3 class="pixel-title">Pixelwar</h3>
                                        <p class="pixel-subtitle">Drag CSS properties to match the target design.</p>
                                        <a class="pixel-cta" href="#game-test">Launch Run</a>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="panel-card panel-card--identifiers rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                        <div class="identifiers-header mb-3">
                            <h2 class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">2. Identifier Containers</h2>
                            <div class="progress-inline" aria-label="Challenge progress">
                                <span class="progress-inline__track">
                                    <span id="progress-bar-fill" class="progress-inline__fill"></span>
                                </span>
                            </div>
                        </div>
                        <div class="identifiers-scroll">
                            <div class="grid gap-3 md:grid-cols-2">
                                <article class="selector-card rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/60 p-3" data-selector-card="card">
                                    <div class="selector-head">
                                        <p class="mb-2 font-mono text-xs font-semibold text-arcade-ink/80">.pixel-card</p>
                                        <span class="selector-meta" data-selector-meta="card"></span>
                                    </div>
                                    <div class="drop-zone selector-zone" data-drop-key="card">
                                        <div class="chip-list" data-property-list="card"></div>
                                    </div>
                                </article>

                                <article class="selector-card rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/60 p-3" data-selector-card="badge">
                                    <div class="selector-head">
                                        <p class="mb-2 font-mono text-xs font-semibold text-arcade-ink/80">.pixel-badge</p>
                                        <span class="selector-meta" data-selector-meta="badge"></span>
                                    </div>
                                    <div class="drop-zone selector-zone" data-drop-key="badge">
                                        <div class="chip-list" data-property-list="badge"></div>
                                    </div>
                                </article>

                                <article class="selector-card rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/60 p-3" data-selector-card="title">
                                    <div class="selector-head">
                                        <p class="mb-2 font-mono text-xs font-semibold text-arcade-ink/80">.pixel-title</p>
                                        <span class="selector-meta" data-selector-meta="title"></span>
                                    </div>
                                    <div class="drop-zone selector-zone" data-drop-key="title">
                                        <div class="chip-list" data-property-list="title"></div>
                                    </div>
                                </article>

                                <article class="selector-card rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/60 p-3" data-selector-card="subtitle">
                                    <div class="selector-head">
                                        <p class="mb-2 font-mono text-xs font-semibold text-arcade-ink/80">.pixel-subtitle</p>
                                        <span class="selector-meta" data-selector-meta="subtitle"></span>
                                    </div>
                                    <div class="drop-zone selector-zone" data-drop-key="subtitle">
                                        <div class="chip-list" data-property-list="subtitle"></div>
                                    </div>
                                </article>

                                <article class="selector-card rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream/60 p-3 md:col-span-2" data-selector-card="cta">
                                    <div class="selector-head">
                                        <p class="mb-2 font-mono text-xs font-semibold text-arcade-ink/80">.pixel-cta</p>
                                        <span class="selector-meta" data-selector-meta="cta"></span>
                                    </div>
                                    <div class="drop-zone selector-zone" data-drop-key="cta">
                                        <div class="chip-list" data-property-list="cta"></div>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

                    <section class="panel-card panel-card--properties rounded-[20px] border-2 border-arcade-ink/10 bg-white p-4">
                        <h2 class="mb-2 font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">3. Properties Panel</h2>
                        <div class="property-controls mb-2">
                            <div class="property-search-wrap">
                                <input
                                    id="property-search"
                                    type="search"
                                    autocomplete="off"
                                    spellcheck="false"
                                    placeholder="Search properties..."
                                    class="w-full rounded-xl border-2 border-arcade-ink/10 bg-white px-3 py-2 text-sm text-arcade-ink outline-none transition focus:border-arcade-orange">
                            </div>
                            <button id="reset-layout-btn" type="button" class="rounded-xl border-2 border-arcade-ink/10 bg-arcade-peach/60 px-3 py-2 text-xs font-semibold text-arcade-ink transition hover:bg-arcade-yellow/70">
                                Reset Placements
                            </button>
                        </div>
                        <div class="drop-zone property-zone" data-drop-key="pool">
                            <div class="chip-list chip-list--horizontal" data-property-list="pool"></div>
                        </div>
                    </section>
                </section>

                <div id="split-handle" class="split-handle" role="separator" aria-orientation="vertical" aria-label="Resize target panel"></div>

                <section class="target-pane rounded-[26px] border-4 border-arcade-ink/10 bg-white/80 p-4 md:p-5" id="target-pane">
                    <header class="mb-4 rounded-[18px] border-2 border-arcade-ink/10 bg-arcade-cream px-3 py-3">
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Target Design</p>
                    </header>

                    <div class="target-panel-body">
                        <div class="target-frame rounded-[20px] border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-4">
                            <div class="target-stage">
                                <div class="target-scope">
                                    <article class="pixel-card">
                                        <span class="pixel-badge">Student Build</span>
                                        <h3 class="pixel-title">Pixelwar</h3>
                                        <p class="pixel-subtitle">Drag CSS properties to match the target design.</p>
                                        <a class="pixel-cta" href="#game-test">Launch Run</a>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
</section>

<div class="modal fade mobile-preview-modal" id="mobile-preview-modal" tabindex="-1" aria-labelledby="mobile-preview-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel p-0 text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-0 px-4 pb-2 pt-4">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Target Design</p>
                    <h2 id="mobile-preview-modal-title" class="modal-title mt-2 text-xl font-bold">Reference preview</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <div class="mobile-preview-frame rounded-[20px] border-2 border-dashed border-arcade-ink/15 bg-[#f7efe1] p-4">
                    <div class="mobile-preview-stage">
                        <div class="target-scope">
                            <article class="pixel-card">
                                <span class="pixel-badge">Student Build</span>
                                <h3 class="pixel-title">Pixelwar</h3>
                                <p class="pixel-subtitle">Drag CSS properties to match the target design.</p>
                                <a class="pixel-cta" href="#game-test">Launch Run</a>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style id="preview-style"></style>
<style id="target-style">
.target-scope .pixel-card {
    width: 320px;
    min-height: 228px;
    background: #fffdf6;
    border: 4px solid #26190f;
    border-radius: 24px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 12px 0 #26190f;
}
.target-scope .pixel-badge {
    display: inline-block;
    background: #ffd166;
    color: #26190f;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 700;
}
.target-scope .pixel-title {
    margin: 14px 0 8px;
    font-size: 36px;
    font-weight: 700;
    color: #ff8c42;
    line-height: 1.05;
}
.target-scope .pixel-subtitle {
    margin-bottom: 16px;
    font-size: 15px;
    color: #26190f;
}
.target-scope .pixel-cta {
    display: inline-block;
    background: #4cc9f0;
    color: #26190f;
    border-radius: 12px;
    padding: 10px 18px;
    font-weight: 700;
    text-decoration: none;
}
</style>

<script>
(() => {
    const previewFrame = document.querySelector('.mobile-preview-frame');
    const previewModal = document.getElementById('mobile-preview-modal');
    const referenceWidth = 424;
    const referenceHeight = 330;

    const resizeMobileTargetPreview = () => {
        if (!previewFrame) {
            return;
        }

        const frameWidth = previewFrame.clientWidth;
        if (frameWidth <= 0) {
            return;
        }

        const scale = Math.min(1, frameWidth / referenceWidth);
        previewFrame.style.setProperty('--mobile-target-scale', scale.toFixed(4));
        previewFrame.style.height = `${Math.ceil(referenceHeight * scale)}px`;
    };

    if (previewFrame && 'ResizeObserver' in window) {
        new ResizeObserver(resizeMobileTargetPreview).observe(previewFrame);
    }

    previewModal?.addEventListener('shown.bs.modal', () => requestAnimationFrame(resizeMobileTargetPreview));
    window.addEventListener('resize', resizeMobileTargetPreview);
    resizeMobileTargetPreview();
})();

(() => {
    const selectorDefinitions = [
        { key: 'card', selector: '.pixel-card' },
        { key: 'badge', selector: '.pixel-badge' },
        { key: 'title', selector: '.pixel-title' },
        { key: 'subtitle', selector: '.pixel-subtitle' },
        { key: 'cta', selector: '.pixel-cta' },
    ];

    const propertyOccurrences = [
        { rule: 'width: 320px;', target: 'card' },
        { rule: 'min-height: 228px;', target: 'card' },
        { rule: 'background: #fffdf6;', target: 'card' },
        { rule: 'border: 4px solid #26190f;', target: 'card' },
        { rule: 'border-radius: 24px;', target: 'card' },
        { rule: 'padding: 24px;', target: 'card' },
        { rule: 'text-align: center;', target: 'card' },
        { rule: 'box-shadow: 0 12px 0 #26190f;', target: 'card' },
        { rule: 'display: inline-block;', target: 'badge' },
        { rule: 'background: #ffd166;', target: 'badge' },
        { rule: 'color: #26190f;', target: 'badge' },
        { rule: 'border-radius: 999px;', target: 'badge' },
        { rule: 'padding: 6px 12px;', target: 'badge' },
        { rule: 'font-size: 12px;', target: 'badge' },
        { rule: 'font-weight: 700;', target: 'badge' },
        { rule: 'font-size: 36px;', target: 'title' },
        { rule: 'margin: 14px 0 8px;', target: 'title' },
        { rule: 'font-weight: 700;', target: 'title' },
        { rule: 'color: #ff8c42;', target: 'title' },
        { rule: 'line-height: 1.05;', target: 'title' },
        { rule: 'margin-bottom: 16px;', target: 'subtitle' },
        { rule: 'font-size: 15px;', target: 'subtitle' },
        { rule: 'color: #26190f;', target: 'subtitle' },
        { rule: 'display: inline-block;', target: 'cta' },
        { rule: 'background: #4cc9f0;', target: 'cta' },
        { rule: 'color: #26190f;', target: 'cta' },
        { rule: 'border-radius: 12px;', target: 'cta' },
        { rule: 'padding: 10px 18px;', target: 'cta' },
        { rule: 'font-weight: 700;', target: 'cta' },
        { rule: 'text-decoration: none;', target: 'cta' },
    ];

    const selectorKeys = selectorDefinitions.map((selector) => selector.key);
    const placements = { pool: {} };
    selectorKeys.forEach((key) => {
        placements[key] = {};
    });

    const requiredBySelector = {};
    selectorKeys.forEach((key) => {
        requiredBySelector[key] = {};
    });

    const propertyCatalog = {};
    const totalRequiredByProperty = {};
    const keyByRule = new Map();
    let propertyCounter = 1;

    propertyOccurrences.forEach((occurrence) => {
        if (!keyByRule.has(occurrence.rule)) {
            const generatedKey = `p${propertyCounter}`;
            propertyCounter += 1;
            keyByRule.set(occurrence.rule, generatedKey);
            propertyCatalog[generatedKey] = { rule: occurrence.rule };
        }

        const propertyKey = keyByRule.get(occurrence.rule);
        requiredBySelector[occurrence.target][propertyKey] = (requiredBySelector[occurrence.target][propertyKey] || 0) + 1;
        totalRequiredByProperty[propertyKey] = (totalRequiredByProperty[propertyKey] || 0) + 1;
    });

    Object.keys(totalRequiredByProperty).forEach((propertyKey) => {
        placements.pool[propertyKey] = totalRequiredByProperty[propertyKey];
    });

    const poolOrder = Object.keys(propertyCatalog);
    for (let index = poolOrder.length - 1; index > 0; index -= 1) {
        const randomIndex = Math.floor(Math.random() * (index + 1));
        const swap = poolOrder[index];
        poolOrder[index] = poolOrder[randomIndex];
        poolOrder[randomIndex] = swap;
    }

    const totalCount = propertyOccurrences.length;

    const previewStyle = document.getElementById('preview-style');
    const statusLabel = document.getElementById('game-status');
    const progressBarFill = document.getElementById('progress-bar-fill');
    const resetButton = document.getElementById('reset-layout-btn');
    const propertySearchInput = document.getElementById('property-search');
    const openingEffect = document.getElementById('game-opening-effect');

    const targetGrid = document.getElementById('challenge-grid');
    const splitHandle = document.getElementById('split-handle');
    const targetScope = document.querySelector('.target-scope');
    const selectorCards = Array.from(document.querySelectorAll('[data-selector-card]'));
    const identifiersScrollContainer = document.querySelector('.identifiers-scroll');
    const selectorMetaLookup = {};
    document.querySelectorAll('[data-selector-meta]').forEach((metaNode) => {
        selectorMetaLookup[metaNode.dataset.selectorMeta] = metaNode;
    });
    const listNodes = {};
    document.querySelectorAll('[data-property-list]').forEach((listNode) => {
        listNodes[listNode.dataset.propertyList] = listNode;
    });

    let draggedPayload = null;
    let isResizing = false;
    let hoveredSelectorKey = null;
    let pinnedSelectorKey = null;
    let lastHighlightedSelectorKey = null;
    let selectedPayload = null;

    const selectorLookup = Object.fromEntries(selectorDefinitions.map((selector) => [selector.key, selector.selector]));
    const selectorCardLookup = Object.fromEntries(
        selectorCards.map((card) => [card.dataset.selectorCard, card])
    );
    const selectorPriority = [
        { className: 'pixel-cta', key: 'cta' },
        { className: 'pixel-subtitle', key: 'subtitle' },
        { className: 'pixel-title', key: 'title' },
        { className: 'pixel-badge', key: 'badge' },
        { className: 'pixel-card', key: 'card' },
    ];
    const extractColorPreview = (rule) => {
        const hexMatch = rule.match(/#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/);
        if (hexMatch) {
            return hexMatch[0];
        }

        const fnColorMatch = rule.match(/\b(?:rgb|rgba|hsl|hsla)\([^)]+\)/i);
        if (fnColorMatch) {
            return fnColorMatch[0];
        }

        return null;
    };

    const runOpeningEffect = () => {
        if (!openingEffect) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        if (params.get('intro') !== '1') {
            openingEffect.remove();
            return;
        }

        openingEffect.classList.add('is-playing');
        window.setTimeout(() => {
            openingEffect.remove();
        }, 1500);
    };
    const requiredTotalBySelector = {};
    selectorKeys.forEach((selectorKey) => {
        requiredTotalBySelector[selectorKey] = Object.values(requiredBySelector[selectorKey]).reduce(
            (sum, value) => sum + value,
            0
        );
    });

    const getCount = (locationKey, propertyKey) => placements[locationKey][propertyKey] || 0;

    const setCount = (locationKey, propertyKey, nextValue) => {
        if (nextValue <= 0) {
            delete placements[locationKey][propertyKey];
            return;
        }

        placements[locationKey][propertyKey] = nextValue;
    };

    const moveOne = (propertyKey, sourceKey, destinationKey) => {
        if (!(sourceKey in placements) || !(destinationKey in placements)) {
            return false;
        }

        if (sourceKey === destinationKey) {
            return false;
        }

        const sourceCount = getCount(sourceKey, propertyKey);
        if (sourceCount <= 0) {
            return false;
        }

        setCount(sourceKey, propertyKey, sourceCount - 1);
        setCount(destinationKey, propertyKey, getCount(destinationKey, propertyKey) + 1);
        return true;
    };

    const isSamePayload = (left, right) => {
        return Boolean(left && right && left.propertyKey === right.propertyKey && left.sourceKey === right.sourceKey);
    };

    const clearSelectedPayload = () => {
        selectedPayload = null;
    };

    const movePayloadTo = (payload, destination) => {
        if (!payload || !payload.sourceKey || !payload.propertyKey) {
            return false;
        }

        if (moveOne(payload.propertyKey, payload.sourceKey, destination)) {
            clearSelectedPayload();
            pinnedSelectorKey = null;
            hoveredSelectorKey = null;
            clearSelectorCardHighlight();
            render();
            return true;
        }

        return false;
    };

    const createChip = (propertyKey, sourceKey) => {
        const count = getCount(sourceKey, propertyKey);
        if (count <= 0) {
            return null;
        }

        const rule = propertyCatalog[propertyKey].rule;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'property-chip';
        chip.draggable = true;
        chip.dataset.propertyKey = propertyKey;
        chip.dataset.sourceKey = sourceKey;
        chip.setAttribute('aria-pressed', isSamePayload(selectedPayload, { propertyKey, sourceKey }) ? 'true' : 'false');

        if (isSamePayload(selectedPayload, { propertyKey, sourceKey })) {
            chip.classList.add('is-selected');
        }

        const label = document.createElement('span');
        label.className = 'property-chip__label';
        label.textContent = rule;

        const colorPreviewValue = extractColorPreview(rule);
        if (colorPreviewValue) {
            const swatch = document.createElement('span');
            swatch.className = 'property-chip__swatch';
            swatch.style.backgroundColor = colorPreviewValue;
            swatch.title = colorPreviewValue;
            chip.append(swatch);
        }

        chip.append(label);

        if (count > 1) {
            const countBadge = document.createElement('span');
            countBadge.className = 'property-chip__count';
            countBadge.textContent = String(count);
            chip.append(countBadge);
        }

        chip.addEventListener('dragstart', (event) => {
            draggedPayload = { propertyKey, sourceKey };
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('application/json', JSON.stringify(draggedPayload));
            event.dataTransfer.setData('text/plain', `${sourceKey}|${propertyKey}`);
        });

        chip.addEventListener('dragend', () => {
            draggedPayload = null;
            document.querySelectorAll('.drop-zone').forEach((zone) => zone.classList.remove('is-over'));
        });

        chip.addEventListener('click', (event) => {
            event.stopPropagation();

            if (isSamePayload(selectedPayload, { propertyKey, sourceKey })) {
                clearSelectedPayload();
            } else {
                selectedPayload = { propertyKey, sourceKey };
            }

            renderLists();
        });

        chip.addEventListener('dblclick', (event) => {
            event.stopPropagation();
            if (sourceKey !== 'pool' && moveOne(propertyKey, sourceKey, 'pool')) {
                clearSelectedPayload();
                render();
            }
        });

        return chip;
    };

    const renderLocationList = (locationKey, searchQuery) => {
        const listNode = listNodes[locationKey];
        if (!listNode) {
            return;
        }

        listNode.innerHTML = '';

        let keysToRender = [];
        if (locationKey === 'pool') {
            keysToRender = poolOrder.filter((propertyKey) => {
                if (getCount('pool', propertyKey) <= 0) {
                    return false;
                }

                if (!searchQuery) {
                    return true;
                }

                return propertyCatalog[propertyKey].rule.toLowerCase().includes(searchQuery);
            });
        } else {
            keysToRender = Object.keys(placements[locationKey]).sort((leftKey, rightKey) => {
                return propertyCatalog[leftKey].rule.localeCompare(propertyCatalog[rightKey].rule);
            });
        }

        if (keysToRender.length === 0) {
            const emptyState = document.createElement('p');
            emptyState.className = 'empty-zone';
            emptyState.textContent = locationKey === 'pool'
                ? (searchQuery ? 'No properties match this search.' : 'Drag or select properties from here.')
                : 'Drop or tap selected properties here.';
            listNode.appendChild(emptyState);
            return;
        }

        keysToRender.forEach((propertyKey) => {
            const chip = createChip(propertyKey, locationKey);
            if (chip) {
                listNode.appendChild(chip);
            }
        });
    };

    const renderLists = () => {
        if (selectedPayload && getCount(selectedPayload.sourceKey, selectedPayload.propertyKey) <= 0) {
            clearSelectedPayload();
        }

        const searchQuery = (propertySearchInput?.value || '').trim().toLowerCase();
        renderLocationList('pool', searchQuery);
        selectorKeys.forEach((selectorKey) => renderLocationList(selectorKey, searchQuery));
    };

    const renderPreviewStyles = () => {
        const cssChunks = selectorDefinitions.map((selectorDefinition) => {
            const rules = Object.keys(placements[selectorDefinition.key])
                .map((propertyKey) => {
                    const count = getCount(selectorDefinition.key, propertyKey);
                    return Array.from({ length: count }, () => propertyCatalog[propertyKey].rule).join(' ');
                })
                .join(' ');

            return `.preview-scope ${selectorLookup[selectorDefinition.key]} { ${rules} }`;
        });

        previewStyle.textContent = cssChunks.join('\n');
    };

    const selectorState = (selectorKey) => {
        const requiredMap = requiredBySelector[selectorKey];
        const placedMap = placements[selectorKey];

        let mismatch = false;
        let missing = false;

        Object.keys(placedMap).forEach((propertyKey) => {
            const placedCount = getCount(selectorKey, propertyKey);
            const requiredCount = requiredMap[propertyKey] || 0;
            if (requiredCount === 0 || placedCount > requiredCount) {
                mismatch = true;
            }
        });

        Object.keys(requiredMap).forEach((propertyKey) => {
            if (getCount(selectorKey, propertyKey) < requiredMap[propertyKey]) {
                missing = true;
            }
        });

        return {
            mismatch,
            complete: !mismatch && !missing,
        };
    };

    const renderSelectorStates = () => {
        selectorKeys.forEach((selectorKey) => {
            const card = selectorCardLookup[selectorKey];
            if (!card) {
                return;
            }

            const state = selectorState(selectorKey);
            const placedTotal = Object.values(placements[selectorKey]).reduce((sum, value) => sum + value, 0);
            const requiredTotal = requiredTotalBySelector[selectorKey] || 0;
            const metaNode = selectorMetaLookup[selectorKey];
            card.classList.remove('is-target-danger', 'is-target-complete');

            if (state.complete) {
                card.classList.add('is-target-complete');
            } else if (state.mismatch) {
                card.classList.add('is-target-danger');
            }

            if (metaNode) {
                metaNode.textContent = `${placedTotal}/${requiredTotal} props`;
            }
        });
    };

    const renderProgress = () => {
        let correctCount = 0;
        let hasMismatch = false;
        let allComplete = true;

        selectorKeys.forEach((selectorKey) => {
            const requiredMap = requiredBySelector[selectorKey];
            Object.keys(requiredMap).forEach((propertyKey) => {
                correctCount += Math.min(getCount(selectorKey, propertyKey), requiredMap[propertyKey]);
            });

            const state = selectorState(selectorKey);
            if (state.mismatch) {
                hasMismatch = true;
            }
            if (!state.complete) {
                allComplete = false;
            }
        });

        const progressPercent = totalCount > 0 ? Math.round((correctCount / totalCount) * 100) : 0;
        if (progressBarFill) {
            progressBarFill.style.width = `${progressPercent}%`;
        }

        if (allComplete && correctCount === totalCount) {
            statusLabel.textContent = 'Complete';
            statusLabel.classList.add('is-success');
        } else {
            statusLabel.textContent = hasMismatch
                ? 'Mismatch detected'
                : 'In progress';
            statusLabel.classList.remove('is-success');
        }
    };

    const render = () => {
        renderLists();
        renderPreviewStyles();
        renderSelectorStates();
        renderProgress();
    };

    const clearSelectorCardHighlight = (resetTrackedKey = true) => {
        selectorCards.forEach((card) => card.classList.remove('is-target-active'));
        if (resetTrackedKey) {
            lastHighlightedSelectorKey = null;
        }
    };

    const scrollSelectorCardIntoView = (key) => {
        if (!identifiersScrollContainer || !key || !selectorCardLookup[key]) {
            return;
        }

        const card = selectorCardLookup[key];
        const containerRect = identifiersScrollContainer.getBoundingClientRect();
        const cardRect = card.getBoundingClientRect();
        const cardIsOutsideView = cardRect.top < containerRect.top || cardRect.bottom > containerRect.bottom;

        if (!cardIsOutsideView) {
            return;
        }

        card.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'nearest',
        });
    };

    const highlightSelectorCard = (key, shouldAutoScroll = false) => {
        const previousKey = lastHighlightedSelectorKey;
        clearSelectorCardHighlight(false);
        if (!key || !selectorCardLookup[key]) {
            lastHighlightedSelectorKey = null;
            return;
        }

        selectorCardLookup[key].classList.add('is-target-active');

        if (shouldAutoScroll && key !== previousKey) {
            scrollSelectorCardIntoView(key);
        }

        lastHighlightedSelectorKey = key;
    };

    const resolveSelectorKeyFromTarget = (element) => {
        if (!element) {
            return null;
        }

        for (const entry of selectorPriority) {
            if (element.closest(`.${entry.className}`)) {
                return entry.key;
            }
        }

        return null;
    };

    const attachDropHandlers = () => {
        const dropZones = document.querySelectorAll('.drop-zone');

        dropZones.forEach((zone) => {
            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                zone.classList.add('is-over');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('is-over');
            });

            zone.addEventListener('drop', (event) => {
                event.preventDefault();
                zone.classList.remove('is-over');

                const destination = zone.dataset.dropKey || 'pool';
                const rawJson = event.dataTransfer.getData('application/json');
                const rawText = event.dataTransfer.getData('text/plain');

                let payload = null;
                if (rawJson) {
                    try {
                        payload = JSON.parse(rawJson);
                    } catch (err) {
                        payload = null;
                    }
                }

                if (!payload && rawText.includes('|')) {
                    const [sourceKey, propertyKey] = rawText.split('|');
                    payload = { sourceKey, propertyKey };
                }

                if (!payload) {
                    payload = draggedPayload;
                }

                movePayloadTo(payload, destination);
            });

            zone.addEventListener('click', (event) => {
                if (event.target.closest('.property-chip')) {
                    return;
                }

                movePayloadTo(selectedPayload, zone.dataset.dropKey || 'pool');
            });
        });
    };

    const attachTargetInspectorHandlers = () => {
        if (!targetScope) {
            return;
        }

        const interactiveNodes = targetScope.querySelectorAll('a,button,input,select,textarea,[role="button"]');
        interactiveNodes.forEach((node) => {
            node.setAttribute('tabindex', '-1');
            node.setAttribute('aria-disabled', 'true');
        });

        targetScope.addEventListener('dragstart', (event) => {
            event.preventDefault();
        });

        targetScope.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
            }
        });

        targetScope.addEventListener('click', (event) => {
            const interactive = event.target.closest('a,button,input,select,textarea,[role="button"]');
            if (interactive) {
                event.preventDefault();
            }

            const key = resolveSelectorKeyFromTarget(event.target);
            pinnedSelectorKey = key;
            highlightSelectorCard(key, true);
        });

        targetScope.addEventListener('mousemove', (event) => {
            if (pinnedSelectorKey !== null) {
                return;
            }

            hoveredSelectorKey = resolveSelectorKeyFromTarget(event.target);
            highlightSelectorCard(hoveredSelectorKey, true);
        });

        targetScope.addEventListener('mouseleave', () => {
            hoveredSelectorKey = null;
            if (pinnedSelectorKey === null) {
                clearSelectorCardHighlight();
            }
        });

        // Clear pinned selector when clicking outside target scope.
        document.addEventListener('click', (event) => {
            if (targetScope.contains(event.target)) {
                return;
            }

            pinnedSelectorKey = null;
            highlightSelectorCard(hoveredSelectorKey);
        });
    };

    const clampTargetWidth = (value) => {
        const gridRect = targetGrid.getBoundingClientRect();
        const minTargetWidth = 360;
        const maxTargetWidth = Math.max(420, gridRect.width - 520);
        return Math.max(minTargetWidth, Math.min(maxTargetWidth, value));
    };

    const setTargetWidth = (value) => {
        const width = clampTargetWidth(value);
        targetGrid.style.setProperty('--target-width', `${width}px`);
    };

    const handleResizeMove = (clientX) => {
        const gridRect = targetGrid.getBoundingClientRect();
        const proposedWidth = gridRect.right - clientX;
        setTargetWidth(proposedWidth);
    };

    splitHandle.addEventListener('mousedown', () => {
        if (window.matchMedia('(max-width: 1220px)').matches) {
            return;
        }

        isResizing = true;
        document.body.classList.add('is-resizing-split');
    });

    window.addEventListener('mousemove', (event) => {
        if (!isResizing) {
            return;
        }

        handleResizeMove(event.clientX);
    });

    window.addEventListener('mouseup', () => {
        if (!isResizing) {
            return;
        }

        isResizing = false;
        document.body.classList.remove('is-resizing-split');
    });

    window.addEventListener('resize', () => {
        const current = parseInt(getComputedStyle(targetGrid).getPropertyValue('--target-width'), 10);
        if (!Number.isNaN(current)) {
            setTargetWidth(current);
        }
    });

    resetButton.addEventListener('click', () => {
        selectorKeys.forEach((selectorKey) => {
            placements[selectorKey] = {};
        });
        placements.pool = { ...totalRequiredByProperty };
        hoveredSelectorKey = null;
        pinnedSelectorKey = null;
        clearSelectorCardHighlight();
        clearSelectedPayload();

        render();
    });

    propertySearchInput?.addEventListener('input', () => {
        renderLists();
    });

    attachDropHandlers();
    attachTargetInspectorHandlers();
    runOpeningEffect();
    render();
})();
</script>
