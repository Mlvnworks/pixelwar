<?php
$seasonRows = $seasonRepository instanceof SeasonRepository ? $seasonRepository->listAll() : [];
$seasonCount = count($seasonRows);
$activeCount = 0;
$upcomingCount = 0;
$endedCount = 0;
$activeSeasonName = 'No active season';

foreach ($seasonRows as $seasonRow) {
    $status = (string) ($seasonRow['season_status'] ?? 'ended');
    if ($status === 'active') {
        $activeCount++;
        if ($activeSeasonName === 'No active season') {
            $activeSeasonName = (string) ($seasonRow['name'] ?? 'Active season');
        }
    } elseif ($status === 'upcoming') {
        $upcomingCount++;
    } else {
        $endedCount++;
    }
}

$formatSeasonDate = static function (?string $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return 'Not set';
    }

    try {
        return (new DateTimeImmutable($raw))->format('M j, Y g:i A');
    } catch (Throwable) {
        return $raw;
    }
};

$formatInputDate = static function (?string $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    try {
        return (new DateTimeImmutable($raw))->format('Y-m-d\TH:i');
    } catch (Throwable) {
        return '';
    }
};

$statusClasses = [
    'active' => 'border-emerald-500/30 bg-emerald-100 text-emerald-700',
    'upcoming' => 'border-arcade-cyan/40 bg-arcade-cyan/20 text-sky-700',
    'ended' => 'border-arcade-ink/10 bg-arcade-ink/5 text-arcade-ink/55',
];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Progression</p>
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Season Management</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Manage leaderboard seasons and keep submitted attempts tied to the active season window.
                </p>
                <div class="mt-3 inline-flex items-center gap-2 rounded-2xl border-2 border-arcade-ink bg-white/85 px-4 py-2 text-sm font-black shadow-[4px_4px_0_rgba(38,25,15,0.18)]">
                    <i data-lucide="sparkles" class="h-4 w-4 text-arcade-orange" aria-hidden="true"></i>
                    <span class="text-arcade-ink/55">Current Season:</span>
                    <span><?= htmlspecialchars($activeSeasonName, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
            <div class="grid gap-2 sm:grid-cols-4 lg:min-w-[34rem]">
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Seasons</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $seasonCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Active</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $activeCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Upcoming</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $upcomingCount ?></strong>
                </article>
                <article class="teacher-panel px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Ended</p>
                    <strong class="mt-1 block text-2xl font-bold"><?= (int) $endedCount ?></strong>
                </article>
            </div>
        </section>

        <section class="teacher-panel overflow-hidden p-0">
            <div class="relative overflow-hidden border-b-4 border-arcade-ink bg-gradient-to-br from-arcade-cyan/55 via-arcade-yellow to-arcade-peach p-5 md:p-6">
                <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full border-[18px] border-white/35"></div>
                <div class="absolute bottom-4 right-28 hidden h-16 w-16 rotate-12 rounded-2xl border-4 border-arcade-ink/10 bg-white/25 md:block"></div>
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/65">Season Windows</p>
                        <h2 class="mt-1 text-2xl font-bold md:text-3xl">Configure active play periods</h2>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-arcade-ink/65">
                            Only one season window should be active at a time. New submissions and point rewards use the current active season.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="teacher-button teacher-button--primary gap-2 self-start whitespace-nowrap"
                        data-bs-toggle="modal"
                        data-bs-target="#season-create-modal"
                    >
                        <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Create Season</span>
                    </button>
                </div>
            </div>

            <div class="p-5 md:p-6">
                <?php if ($seasonRows === []) : ?>
                    <div class="grid min-h-[18rem] place-items-center rounded-[28px] border-4 border-dashed border-arcade-ink/20 bg-white/70 p-8 text-center">
                        <div class="max-w-md">
                            <span class="mx-auto grid h-16 w-16 place-items-center rounded-3xl border-4 border-arcade-ink bg-arcade-cyan shadow-[5px_5px_0_#26190f]">
                                <i data-lucide="calendar-range" class="h-7 w-7" aria-hidden="true"></i>
                            </span>
                            <h3 class="mt-5 text-2xl font-bold">No seasons yet</h3>
                            <p class="mt-2 text-sm font-semibold leading-6 text-arcade-ink/60">
                                Create a season so new attempts and progress rewards can be grouped by play period.
                            </p>
                            <button type="button" class="teacher-button teacher-button--primary mx-auto mt-5 gap-2" data-bs-toggle="modal" data-bs-target="#season-create-modal">
                                <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                                <span>Create First Season</span>
                            </button>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="admin-season-card-grid">
                        <?php foreach ($seasonRows as $seasonRow) : ?>
                            <?php
                            $seasonId = (int) ($seasonRow['season_id'] ?? 0);
                            $seasonName = (string) ($seasonRow['name'] ?? '');
                            $seasonStatus = (string) ($seasonRow['season_status'] ?? 'ended');
                            $badgeClass = $statusClasses[$seasonStatus] ?? $statusClasses['ended'];
                            $totalAttempts = (int) ($seasonRow['total_attempts'] ?? 0);
                            $totalPlayers = (int) ($seasonRow['total_players'] ?? 0);
                            $startRaw = (string) ($seasonRow['start_date'] ?? '');
                            $endRaw = (string) ($seasonRow['end_date'] ?? '');
                            ?>
                            <article class="admin-season-card group relative overflow-hidden rounded-[30px] border-4 border-arcade-ink bg-white p-5 shadow-[7px_7px_0_#26190f] transition hover:-translate-y-1 hover:shadow-[10px_10px_0_#26190f]">
                                <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-arcade-cyan via-arcade-yellow to-arcade-orange"></div>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-black uppercase tracking-[0.08em] <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($seasonStatus, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/45">Season #<?= (int) $seasonId ?></span>
                                        </div>
                                        <h3 class="mt-3 truncate text-2xl font-black text-arcade-ink"><?= htmlspecialchars($seasonName, ENT_QUOTES, 'UTF-8') ?></h3>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <a
                                            href="./?c=season-leaderboards&id=<?= (int) $seasonId ?>"
                                            class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-arcade-yellow text-arcade-ink no-underline transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white"
                                            aria-label="Open leaderboard for <?= htmlspecialchars($seasonName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="trophy" class="h-4 w-4" aria-hidden="true"></i>
                                        </a>
                                        <button
                                            type="button"
                                            class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-arcade-cyan/20 text-arcade-ink transition hover:-translate-y-0.5 hover:bg-arcade-cyan"
                                            data-bs-toggle="modal"
                                            data-bs-target="#season-edit-modal"
                                            data-season-id="<?= (int) $seasonId ?>"
                                            data-season-name="<?= htmlspecialchars($seasonName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-season-start="<?= htmlspecialchars($formatInputDate($startRaw), ENT_QUOTES, 'UTF-8') ?>"
                                            data-season-end="<?= htmlspecialchars($formatInputDate($endRaw), ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="Edit <?= htmlspecialchars($seasonName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="square-pen" class="h-4 w-4" aria-hidden="true"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="grid h-10 w-10 place-items-center rounded-xl border-2 border-arcade-ink bg-arcade-coral text-white transition hover:-translate-y-0.5 hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-45"
                                            data-bs-toggle="modal"
                                            data-bs-target="#season-delete-modal"
                                            data-season-id="<?= (int) $seasonId ?>"
                                            data-season-name="<?= htmlspecialchars($seasonName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-season-locked="<?= ($totalAttempts + $totalPlayers) > 0 ? '1' : '0' ?>"
                                            aria-label="Delete <?= htmlspecialchars($seasonName, ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-3 md:grid-cols-2">
                                    <div class="rounded-2xl border border-arcade-ink/10 bg-arcade-cream/75 p-4">
                                        <p class="text-xs font-black uppercase tracking-[0.1em] text-arcade-ink/45">Starts</p>
                                        <p class="mt-1 text-sm font-bold text-arcade-ink"><?= htmlspecialchars($formatSeasonDate($startRaw), ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                    <div class="rounded-2xl border border-arcade-ink/10 bg-arcade-cream/75 p-4">
                                        <p class="text-xs font-black uppercase tracking-[0.1em] text-arcade-ink/45">Ends</p>
                                        <p class="mt-1 text-sm font-bold text-arcade-ink"><?= htmlspecialchars($formatSeasonDate($endRaw), ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-arcade-ink/10 bg-white/80 p-4">
                                    <p class="text-xs font-black uppercase tracking-[0.1em] text-arcade-ink/45">Total Game Submissions</p>
                                    <p class="mt-1 text-3xl font-black text-arcade-ink"><?= (int) $totalAttempts ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </section>
</main>

<div class="modal fade" id="season-create-modal" tabindex="-1" aria-labelledby="season-create-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content overflow-hidden rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]" method="post" action="./?c=season-management">
            <?= adminPanelCsrfField() ?>
            <input type="hidden" name="season_action" value="create">
            <div class="modal-header border-0 bg-gradient-to-br from-arcade-cyan/60 to-arcade-yellow px-5 pb-4 pt-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Create Season</p>
                    <h2 class="modal-title mt-1 text-2xl font-bold" id="season-create-modal-title">Add new season</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body grid gap-4 px-5 pb-5 pt-5">
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Name</span>
                    <input type="text" name="name" maxlength="150" pattern="[A-Za-z0-9][A-Za-z0-9 ._-]{1,149}" required placeholder="Example: Summer Sprint" class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                </label>
                <div class="grid gap-3 rounded-2xl border border-arcade-ink/10 bg-white/60 p-3">
                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Start Date</span>
                        <input id="season-create-start" type="datetime-local" name="start_date" required class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                    </label>
                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">End Date</span>
                        <input id="season-create-end" type="datetime-local" name="end_date" required class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                    </label>
                    <div id="season-create-duration" class="rounded-xl border border-arcade-ink/10 bg-arcade-cream px-4 py-3 text-sm font-bold text-arcade-ink/60">
                        Select start and end dates to calculate season duration.
                    </div>
                </div>
                <p class="rounded-2xl border border-arcade-ink/10 bg-white/70 px-4 py-3 text-xs font-semibold leading-5 text-arcade-ink/60">
                    Season names must be unique and date ranges cannot overlap another season.
                </p>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Create Season</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="season-edit-modal" tabindex="-1" aria-labelledby="season-edit-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]" method="post" action="./?c=season-management">
            <?= adminPanelCsrfField() ?>
            <input type="hidden" name="season_action" value="update">
            <input type="hidden" name="season_id" id="season-edit-id" value="">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Edit Season</p>
                    <h2 class="modal-title mt-1 text-2xl font-bold" id="season-edit-modal-title">Update season</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body grid gap-4 px-5 pb-5 pt-2">
                <label class="grid gap-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Name</span>
                    <input id="season-edit-name" type="text" name="name" maxlength="150" pattern="[A-Za-z0-9][A-Za-z0-9 ._-]{1,149}" required class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                </label>
                <div class="grid gap-3 rounded-2xl border border-arcade-ink/10 bg-white/60 p-3">
                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">Start Date</span>
                        <input id="season-edit-start" type="datetime-local" name="start_date" required class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                    </label>
                    <label class="grid gap-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-arcade-ink/55">End Date</span>
                        <input id="season-edit-end" type="datetime-local" name="end_date" required class="w-full rounded-xl border border-arcade-ink/15 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-arcade-orange">
                    </label>
                    <div id="season-edit-duration" class="rounded-xl border border-arcade-ink/10 bg-arcade-cream px-4 py-3 text-sm font-bold text-arcade-ink/60">
                        Select start and end dates to calculate season duration.
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="season-delete-modal" tabindex="-1" aria-labelledby="season-delete-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]" method="post" action="./?c=season-management">
            <?= adminPanelCsrfField() ?>
            <input type="hidden" name="season_action" value="delete">
            <input type="hidden" name="season_id" id="season-delete-id" value="">
            <div class="modal-header border-0 px-5 pb-2 pt-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-coral">Delete Season</p>
                    <h2 class="modal-title mt-1 text-2xl font-bold" id="season-delete-modal-title">Delete this season?</h2>
                </div>
                <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-5 pb-5 pt-2">
                <p class="text-sm font-semibold leading-6 text-arcade-ink/65">
                    This will remove <strong id="season-delete-name">this season</strong>. Seasons with attempts or progress records cannot be deleted.
                </p>
                <p id="season-delete-warning" class="mt-3 hidden rounded-2xl border border-arcade-coral/20 bg-arcade-coral/10 px-4 py-3 text-xs font-bold leading-5 text-arcade-coral">
                    This season already has records. Delete is blocked to protect history.
                </p>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" class="rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-2 text-sm font-bold text-arcade-ink transition hover:bg-arcade-peach/60" data-bs-dismiss="modal">Cancel</button>
                    <button id="season-delete-submit" type="submit" class="rounded-xl border-2 border-arcade-ink bg-arcade-coral px-5 py-2 text-sm font-bold text-white shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-50">Delete Season</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const createStartInput = document.getElementById('season-create-start');
    const createEndInput = document.getElementById('season-create-end');
    const createDuration = document.getElementById('season-create-duration');
    const editStartInput = document.getElementById('season-edit-start');
    const editEndInput = document.getElementById('season-edit-end');
    const editDuration = document.getElementById('season-edit-duration');
    const formatDuration = (milliseconds) => {
        const totalMinutes = Math.floor(milliseconds / 60000);
        const days = Math.floor(totalMinutes / 1440);
        const hours = Math.floor((totalMinutes % 1440) / 60);
        const minutes = totalMinutes % 60;
        const parts = [];

        if (days > 0) {
            parts.push(`${days} day${days === 1 ? '' : 's'}`);
        }

        if (hours > 0) {
            parts.push(`${hours} hour${hours === 1 ? '' : 's'}`);
        }

        if (minutes > 0 || parts.length === 0) {
            parts.push(`${minutes} minute${minutes === 1 ? '' : 's'}`);
        }

        return parts.join(' ');
    };
    const updateDuration = (startInput, endInput, durationElement) => {
        if (!startInput || !endInput || !durationElement) {
            return;
        }

        const startValue = startInput.value;
        const endValue = endInput.value;

        durationElement.classList.remove('border-arcade-coral/30', 'bg-arcade-coral/10', 'text-arcade-coral');
        durationElement.classList.add('border-arcade-ink/10', 'bg-arcade-cream', 'text-arcade-ink/60');

        if (!startValue || !endValue) {
            durationElement.textContent = 'Select start and end dates to calculate season duration.';
            return;
        }

        const startDate = new Date(startValue);
        const endDate = new Date(endValue);
        const diff = endDate.getTime() - startDate.getTime();

        if (!Number.isFinite(diff) || diff <= 0) {
            durationElement.classList.remove('border-arcade-ink/10', 'bg-arcade-cream', 'text-arcade-ink/60');
            durationElement.classList.add('border-arcade-coral/30', 'bg-arcade-coral/10', 'text-arcade-coral');
            durationElement.textContent = 'End date must be after the start date.';
            return;
        }

        durationElement.textContent = `Season duration: ${formatDuration(diff)}.`;
    };
    const updateCreateDuration = () => updateDuration(createStartInput, createEndInput, createDuration);
    const updateEditDuration = () => updateDuration(editStartInput, editEndInput, editDuration);

    createStartInput?.addEventListener('input', updateCreateDuration);
    createEndInput?.addEventListener('input', updateCreateDuration);
    editStartInput?.addEventListener('input', updateEditDuration);
    editEndInput?.addEventListener('input', updateEditDuration);

    const editModal = document.getElementById('season-edit-modal');
    editModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        document.getElementById('season-edit-id').value = trigger.getAttribute('data-season-id') || '';
        document.getElementById('season-edit-name').value = trigger.getAttribute('data-season-name') || '';
        document.getElementById('season-edit-start').value = trigger.getAttribute('data-season-start') || '';
        document.getElementById('season-edit-end').value = trigger.getAttribute('data-season-end') || '';
        updateEditDuration();
    });

    const deleteModal = document.getElementById('season-delete-modal');
    deleteModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        const locked = trigger.getAttribute('data-season-locked') === '1';
        document.getElementById('season-delete-id').value = trigger.getAttribute('data-season-id') || '';
        document.getElementById('season-delete-name').textContent = trigger.getAttribute('data-season-name') || 'this season';
        document.getElementById('season-delete-warning')?.classList.toggle('hidden', !locked);
        const submitButton = document.getElementById('season-delete-submit');
        if (submitButton) {
            submitButton.disabled = locked;
        }
    });
});
</script>
