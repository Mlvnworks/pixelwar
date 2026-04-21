<?php
$teacherName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Teacher')) ?: 'Teacher';
$teacherRooms = [
    ['name' => 'Arcade Dawn Practice', 'challenge' => 'Button Border Basics', 'players' => 3, 'status' => 'Waiting', 'host' => $teacherName],
    ['name' => 'CSS Sprint Room', 'challenge' => 'Hero Text Alignment', 'players' => 6, 'status' => 'Live', 'host' => $teacherName],
    ['name' => 'Radius Lab', 'challenge' => 'Panel Radius Run', 'players' => 2, 'status' => 'Draft', 'host' => $teacherName],
];
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
                        Review classroom rooms, active matches, and rooms waiting for players.
                    </p>
                </div>
                <a href="../?c=room" class="teacher-button teacher-button--primary gap-2">
                    <i data-lucide="messages-square" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Create Room</span>
                </a>
            </div>
        </article>

        <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Room Queue</p>
                    <h2 class="mt-2 text-2xl font-black">Current Rooms</h2>
                </div>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-3">
                <?php foreach ($teacherRooms as $room) : ?>
                    <article class="teacher-log-card rounded-2xl border-2 border-arcade-ink/12 bg-white p-4">
                        <div class="flex items-start justify-between gap-3">
                            <span class="teacher-log-badge teacher-log-badge--room">
                                <i data-lucide="message-square" class="h-4 w-4" aria-hidden="true"></i>
                            </span>
                            <span class="teacher-pill <?= $room['status'] === 'Live' ? 'bg-arcade-mint' : 'bg-arcade-yellow' ?>">
                                <?= htmlspecialchars($room['status'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <h3 class="mt-4 text-xl font-black"><?= htmlspecialchars($room['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="mt-2 text-sm font-bold leading-6 text-arcade-ink/62"><?= htmlspecialchars($room['challenge'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm font-black">
                            <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">Players</p>
                                <p class="mt-1"><?= (int) $room['players'] ?></p>
                            </div>
                            <div class="rounded-2xl bg-arcade-cream/80 p-3">
                                <p class="text-[10px] uppercase tracking-[0.14em] text-arcade-orange">Host</p>
                                <p class="mt-1 truncate"><?= htmlspecialchars((string) $room['host'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</main>

<script>
window.addEventListener('load', () => window.lucide?.createIcons());
</script>
