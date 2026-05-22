<?php
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
$teacherRooms = $roomRepository instanceof RoomRepository && $teacherId > 0
    ? $roomRepository->listForOwner($teacherId, 300)
    : [];

$totalRooms = count($teacherRooms);
$ongoingRooms = count(array_filter($teacherRooms, static fn(array $room): bool => !empty($room['started_at']) && empty($room['ended_at'])));
$completedRooms = count(array_filter($teacherRooms, static fn(array $room): bool => !empty($room['ended_at'])));
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Teacher Rooms</p>
                    <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl">Rooms</h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Create and review classroom rooms tied to your own challenges.
                    </p>
                </div>
                <a href="./?c=create-room" class="teacher-button teacher-button--primary gap-2">
                    <i data-lucide="messages-square" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Create Room</span>
                </a>
            </div>
        </article>

        <section class="grid gap-3 md:grid-cols-3">
            <article class="teacher-panel rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Total</p>
                <strong class="mt-3 block text-3xl font-black"><?= (int) $totalRooms ?></strong>
            </article>
            <article class="teacher-panel rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Ongoing</p>
                <strong class="mt-3 block text-3xl font-black"><?= (int) $ongoingRooms ?></strong>
            </article>
            <article class="teacher-panel rounded-[24px] border-4 border-arcade-ink bg-arcade-panel px-4 py-4 shadow-[7px_7px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.18em] text-arcade-orange">Completed</p>
                <strong class="mt-3 block text-3xl font-black"><?= (int) $completedRooms ?></strong>
            </article>
        </section>

        <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Room Queue</p>
                    <h2 class="mt-2 text-2xl font-black">Current Rooms</h2>
                </div>
                <p class="text-sm font-bold text-arcade-ink/60">Host: <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <?php if ($teacherRooms === []) : ?>
                <div class="mt-4 rounded-2xl border-2 border-dashed border-arcade-ink/12 bg-white/80 px-4 py-5 text-sm font-bold text-arcade-ink/60">
                    No rooms created yet. Create your first room to start organizing a challenge session.
                </div>
            <?php else : ?>
                <div class="mt-4 grid gap-3 lg:grid-cols-3">
                    <?php foreach ($teacherRooms as $room) : ?>
                        <?php
                        $strictModeEnabled = (int) ($room['strict_mode'] ?? 0) === 1;
                        $roomIsOpen = (int) ($room['status'] ?? 1) === 1;
                        ?>
                        <article class="teacher-log-card rounded-2xl border-2 border-arcade-ink/12 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <span class="teacher-log-badge teacher-log-badge--room">
                                    <i data-lucide="message-square" class="h-4 w-4" aria-hidden="true"></i>
                                </span>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <span class="teacher-pill <?= $roomIsOpen ? 'bg-arcade-mint/40' : 'bg-arcade-coral/25' ?>">
                                        <?= $roomIsOpen ? 'Open' : 'Closed' ?>
                                    </span>
                                    <span class="teacher-pill <?= $strictModeEnabled ? 'bg-arcade-coral/25' : 'bg-arcade-cyan/25' ?>">
                                        <?= $strictModeEnabled ? 'Strict' : 'Normal' ?>
                                    </span>
                                </div>
                            </div>
                            <h3 class="mt-4 text-xl font-black"><?= htmlspecialchars((string) ($room['room_name'] ?? 'Untitled Room'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="mt-2 text-sm font-bold leading-6 text-arcade-ink/62"><?= htmlspecialchars((string) ($room['room_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="mt-4 grid gap-2 text-sm font-black">
                                <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                    <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">Challenge</p>
                                    <p class="mt-1 truncate"><?= htmlspecialchars((string) ($room['challenge_name'] ?? 'Unknown Challenge'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                        <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">Timer</p>
                                        <p class="mt-1"><?= (int) ($room['timer_limit'] ?? 0) ?> min</p>
                                    </div>
                                    <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                        <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">State</p>
                                        <p class="mt-1"><?= $roomIsOpen ? 'Open' : 'Closed' ?></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                        <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">Mode</p>
                                        <p class="mt-1"><?= $strictModeEnabled ? 'Strict' : 'Normal' ?></p>
                                    </div>
                                    <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                        <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">Code</p>
                                        <p class="mt-1 truncate"><?= htmlspecialchars((string) ($room['room_code'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>
                                <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                    <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">Created</p>
                                    <p class="mt-1"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($room['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="teacher-room-actions mt-4">
                                <button
                                    type="button"
                                    class="teacher-button teacher-button--light teacher-room-actions__button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#teacher-delete-room-modal"
                                    data-room-delete-button
                                    data-room-id="<?= (int) ($room['room_id'] ?? 0) ?>"
                                    data-room-name="<?= htmlspecialchars((string) ($room['room_name'] ?? 'Untitled Room'), ENT_QUOTES, 'UTF-8') ?>"
                                    aria-label="Delete room"
                                    title="Delete room">
                                    <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                </button>
                                <a href="./?c=edit-room&id=<?= (int) ($room['room_id'] ?? 0) ?>" class="teacher-button teacher-button--light teacher-room-actions__button" aria-label="Edit room" title="Edit room">
                                    <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                </a>
                                <a href="./?c=room-view&id=<?= (int) ($room['room_id'] ?? 0) ?>" class="teacher-button teacher-button--light teacher-room-actions__button" aria-label="Open room" title="Open room">
                                    <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<div class="modal fade" id="teacher-delete-room-modal" tabindex="-1" aria-labelledby="teacher-delete-room-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-[24px] border-4 border-arcade-ink bg-arcade-panel shadow-[8px_8px_0_rgba(38,25,15,0.28)]">
            <form action="./?c=rooms" method="post">
                <div class="modal-header border-b-2 border-arcade-ink/10 px-4 py-3">
                    <div>
                        <p class="mb-1 font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-coral">Delete Room</p>
                        <h2 id="teacher-delete-room-modal-label" class="mb-0 text-xl font-black">Delete this room?</h2>
                    </div>
                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <?= teacherPanelCsrfField() ?>
                    <input type="hidden" name="room_action" value="delete">
                    <input type="hidden" name="room_id" id="teacher-delete-room-id" value="">
                    <p class="mb-0 text-sm font-bold leading-6 text-arcade-ink/70">
                        This will remove <strong id="teacher-delete-room-name">this room</strong> from your room list.
                    </p>
                </div>
                <div class="modal-footer border-t-2 border-arcade-ink/10 px-4 py-3">
                    <button type="button" class="teacher-button teacher-button--light" data-bs-dismiss="modal">No</button>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2">
                        <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Yes, Delete</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.teacher-room-actions {
    display: flex;
    flex-wrap: nowrap;
    justify-content: flex-end;
    gap: 0.45rem;
}

.teacher-room-actions__button {
    flex: 0 0 auto;
    min-width: 2.5rem;
    width: 2.5rem;
    height: 2.5rem;
    padding: 0;
    justify-content: center;
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const deleteModalElement = document.getElementById('teacher-delete-room-modal');
    const deleteRoomIdInput = document.getElementById('teacher-delete-room-id');
    const deleteRoomName = document.getElementById('teacher-delete-room-name');

    if (!(deleteModalElement instanceof HTMLElement) || !(deleteRoomIdInput instanceof HTMLInputElement) || !(deleteRoomName instanceof HTMLElement)) {
        return;
    }

    deleteModalElement.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget instanceof HTMLElement ? event.relatedTarget : null;
        const roomId = trigger?.getAttribute('data-room-id') || '';
        const roomName = trigger?.getAttribute('data-room-name') || 'this room';
        deleteRoomIdInput.value = roomId;
        deleteRoomName.textContent = roomName;
    });
});
</script>
