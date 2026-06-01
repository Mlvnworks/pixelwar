<?php
$rankRows = $rankRepository instanceof RankRepository ? $rankRepository->listAll() : [];
$rankCount = count($rankRows);
$highestRequirement = 0;

foreach ($rankRows as $rankRow) {
    $highestRequirement = max($highestRequirement, (int) ($rankRow['points_requirements'] ?? 0));
}
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Progression</p>
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Rank Management</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Manage rank names and the points required for players to reach each rank.
                </p>
            </div>
            <div class="grid gap-2 sm:grid-cols-2 lg:min-w-[25rem]">
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Ranks</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $rankCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Highest Requirement</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $highestRequirement ?></strong>
                </article>
            </div>
        </section>

        <section class="teacher-panel overflow-hidden p-0">
            <div class="relative overflow-hidden border-b-4 border-arcade-ink bg-gradient-to-br from-arcade-yellow via-arcade-peach to-arcade-cyan/45 p-5 md:p-6">
                <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full border-[18px] border-white/35"></div>
                <div class="absolute bottom-3 right-28 hidden h-16 w-16 rotate-12 rounded-2xl border-4 border-arcade-ink/10 bg-white/20 md:block"></div>
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/65">Rank Ladder</p>
                        <h2 class="mt-1 text-2xl font-bold md:text-3xl">Configured player progression</h2>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-arcade-ink/65">
                            Keep requirements in ascending order so players move through clear milestones.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="teacher-button teacher-button--primary gap-2 self-start whitespace-nowrap"
                        data-bs-toggle="modal"
                        data-bs-target="#rank-create-modal"
                    >
                        <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Create Rank</span>
                    </button>
                </div>
            </div>

            <div class="p-5 md:p-6">
                <?php if ($rankRows === []) : ?>
                    <div class="grid min-h-[18rem] place-items-center rounded-[28px] border-4 border-dashed border-arcade-ink/20 bg-white/70 p-8 text-center">
                        <div class="max-w-md">
                            <span class="mx-auto grid h-16 w-16 place-items-center rounded-3xl border-4 border-arcade-ink bg-arcade-yellow shadow-[5px_5px_0_#26190f]">
                                <i data-lucide="medal" class="h-7 w-7" aria-hidden="true"></i>
                            </span>
                            <h3 class="mt-5 text-2xl font-bold">No ranks yet</h3>
                            <p class="mt-2 text-sm font-semibold leading-6 text-arcade-ink/60">
                                Create the first rank to start building the player progression ladder.
                            </p>
                            <button
                                type="button"
                                class="teacher-button teacher-button--primary mx-auto mt-5 gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#rank-create-modal"
                            >
                                <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Create First Rank</span>
                            </button>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="grid gap-4 lg:grid-cols-2">
                        <?php foreach ($rankRows as $rankIndex => $rankRow) : ?>
                            <?php
                            $rankId = (int) ($rankRow['rank_id'] ?? 0);
                            $rankName = (string) ($rankRow['name'] ?? '');
                            $rankPoints = (int) ($rankRow['points_requirements'] ?? 0);
                            $rankProgress = $highestRequirement > 0 ? min(100, (int) round(($rankPoints / $highestRequirement) * 100)) : 0;
                            ?>
                            <article class="group relative overflow-hidden rounded-[28px] border-4 border-arcade-ink bg-white p-4 shadow-[7px_7px_0_#26190f] transition hover:-translate-y-1 hover:shadow-[10px_10px_0_#26190f]">
                                <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-arcade-yellow via-arcade-orange to-arcade-cyan"></div>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <span class="relative grid h-16 w-16 shrink-0 place-items-center rounded-3xl border-4 border-arcade-ink bg-arcade-yellow text-arcade-ink shadow-[4px_4px_0_#26190f]">
                                            <i data-lucide="medal" class="h-7 w-7" aria-hidden="true"></i>
                                            <span class="absolute -right-2 -top-2 grid h-7 min-w-7 place-items-center rounded-full border-2 border-arcade-ink bg-white px-1 text-xs font-black">
                                                <?= (int) ($rankIndex + 1) ?>
                                            </span>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-xl font-black text-arcade-ink"><?= htmlspecialchars($rankName, ENT_QUOTES, 'UTF-8') ?></p>
                                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/50">Rank ID #<?= $rankId ?></p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <button
                                            type="button"
                                            class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-arcade-cyan/20 text-arcade-ink transition hover:-translate-y-0.5 hover:bg-arcade-cyan"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rank-edit-modal"
                                            data-rank-id="<?= $rankId ?>"
                                            data-rank-name="<?= htmlspecialchars($rankName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-rank-points="<?= $rankPoints ?>"
                                            aria-label="Edit <?= htmlspecialchars($rankName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="square-pen" class="h-4 w-4" aria-hidden="true"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-arcade-coral text-white transition hover:-translate-y-0.5 hover:bg-red-600"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rank-delete-modal"
                                            data-rank-id="<?= $rankId ?>"
                                            data-rank-name="<?= htmlspecialchars($rankName, ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="Delete <?= htmlspecialchars($rankName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-5 rounded-2xl border border-arcade-ink/10 bg-arcade-cream/70 p-4">
                                    <div class="flex items-end justify-between gap-3">
                                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Required Points</span>
                                        <strong class="text-2xl font-black"><?= $rankPoints ?> pts</strong>
                                    </div>
                                    <div class="mt-3 h-3 overflow-hidden rounded-full border border-arcade-ink/15 bg-white">
                                        <div class="h-full rounded-full bg-gradient-to-r from-arcade-orange to-arcade-cyan" style="width: <?= $rankProgress ?>%;"></div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </section>
</main>

<div class="modal fade" id="rank-create-modal" tabindex="-1" aria-labelledby="rank-create-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="rank-create-form" class="modal-content overflow-hidden rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]" method="post" action="./?c=rank-management">
            <?= adminPanelCsrfField() ?>
            <input type="hidden" name="rank_action" value="create">
            <div class="modal-header border-0 bg-gradient-to-br from-arcade-yellow to-arcade-peach px-5 pb-4 pt-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Create Rank</p>
                    <h2 class="modal-title mt-1 text-2xl font-bold" id="rank-create-modal-title">Add new rank</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body grid gap-4 px-5 pb-5 pt-5">
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Name</span>
                    <input
                        id="rank-create-name"
                        type="text"
                        name="name"
                        maxlength="100"
                        pattern="[A-Za-z0-9][A-Za-z0-9 ._-]{1,99}"
                        title="Use 2-100 characters. Start with a letter or number. Letters, numbers, spaces, dots, underscores, and hyphens are allowed."
                        required
                        placeholder="Example: Bronze"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Points Requirement</span>
                    <input
                        id="rank-create-points"
                        type="number"
                        name="points_requirements"
                        min="0"
                        max="999999999"
                        step="1"
                        required
                        value="0"
                        class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange"
                    >
                </label>
                <p class="rounded-2xl border border-arcade-ink/10 bg-white/70 px-4 py-3 text-xs font-semibold leading-5 text-arcade-ink/60">
                    Rank names and points requirements must be unique. Use whole numbers only.
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Create Rank</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="rank-edit-modal" tabindex="-1" aria-labelledby="rank-edit-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="rank-edit-form" class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]" method="post" action="./?c=rank-management">
            <?= adminPanelCsrfField() ?>
            <input type="hidden" name="rank_action" value="update">
            <input type="hidden" name="rank_id" id="rank-edit-id" value="">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Edit Rank</p>
                    <h2 class="modal-title mt-1 text-2xl font-bold" id="rank-edit-modal-title">Update rank</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body grid gap-4 px-5 pb-5 pt-2">
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Name</span>
                    <input id="rank-edit-name" type="text" name="name" maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9 ._-]{1,99}" title="Use 2-100 characters. Start with a letter or number. Letters, numbers, spaces, dots, underscores, and hyphens are allowed." required class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                </label>
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Points Requirement</span>
                    <input id="rank-edit-points" type="number" name="points_requirements" min="0" max="999999999" step="1" required class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                </label>
                <p class="rounded-2xl border border-arcade-ink/10 bg-white/70 px-4 py-3 text-xs font-semibold leading-5 text-arcade-ink/60">
                    Updating a rank cannot reuse another rank name or points requirement.
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="rank-delete-modal" tabindex="-1" aria-labelledby="rank-delete-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]" method="post" action="./?c=rank-management">
            <?= adminPanelCsrfField() ?>
            <input type="hidden" name="rank_action" value="delete">
            <input type="hidden" name="rank_id" id="rank-delete-id" value="">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-coral">Delete Rank</p>
                    <h2 class="modal-title mt-1 text-2xl font-bold" id="rank-delete-modal-title">Delete this rank?</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-5 pb-5 pt-2">
                <p class="text-sm font-semibold leading-6 text-arcade-ink/65">
                    This will remove <strong id="rank-delete-name">this rank</strong> from the rank list.
                </p>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-coral px-5 py-2 text-sm font-bold text-white shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-red-600">Delete Rank</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const editModal = document.getElementById('rank-edit-modal');
    const deleteModal = document.getElementById('rank-delete-modal');
    const rankRows = <?= json_encode(array_map(static function (array $rankRow): array {
        return [
            'id' => (int) ($rankRow['rank_id'] ?? 0),
            'name' => (string) ($rankRow['name'] ?? ''),
            'points' => (int) ($rankRow['points_requirements'] ?? 0),
        ];
    }, $rankRows), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const createForm = document.getElementById('rank-create-form');
    const editForm = document.getElementById('rank-edit-form');
    const createName = document.getElementById('rank-create-name');
    const createPoints = document.getElementById('rank-create-points');
    const editId = document.getElementById('rank-edit-id');
    const editName = document.getElementById('rank-edit-name');
    const editPoints = document.getElementById('rank-edit-points');

    const normalizeName = (value) => String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
    const validateRankFields = (nameInput, pointsInput, excludedId = 0) => {
        if (!(nameInput instanceof HTMLInputElement) || !(pointsInput instanceof HTMLInputElement)) {
            return;
        }

        const currentId = Number(excludedId || 0);
        const name = normalizeName(nameInput.value);
        const points = Number(pointsInput.value);
        const hasDuplicateName = rankRows.some((rank) => Number(rank.id) !== currentId && normalizeName(rank.name) === name);
        const hasDuplicatePoints = rankRows.some((rank) => Number(rank.id) !== currentId && Number(rank.points) === points);

        nameInput.setCustomValidity(hasDuplicateName ? 'A rank with this name already exists.' : '');
        pointsInput.setCustomValidity(hasDuplicatePoints ? 'A rank with this points requirement already exists.' : '');
    };

    const bindValidation = (form, nameInput, pointsInput, getExcludedId) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const validate = () => validateRankFields(nameInput, pointsInput, getExcludedId());
        nameInput?.addEventListener('input', validate);
        pointsInput?.addEventListener('input', validate);
        form.addEventListener('submit', validate);
    };

    bindValidation(createForm, createName, createPoints, () => 0);
    bindValidation(editForm, editName, editPoints, () => Number(editId?.value || 0));

    editModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        document.getElementById('rank-edit-id').value = trigger.dataset.rankId || '';
        document.getElementById('rank-edit-name').value = trigger.dataset.rankName || '';
        document.getElementById('rank-edit-points').value = trigger.dataset.rankPoints || '0';
        validateRankFields(editName, editPoints, Number(trigger.dataset.rankId || 0));
    });

    deleteModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        document.getElementById('rank-delete-id').value = trigger.dataset.rankId || '';
        document.getElementById('rank-delete-name').textContent = trigger.dataset.rankName || 'this rank';
    });

    if (window.lucide) {
        window.lucide.createIcons();
    } else {
        window.addEventListener('load', () => window.lucide?.createIcons());
    }
})();
</script>
