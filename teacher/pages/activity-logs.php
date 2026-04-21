<?php
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$teacherActivityLogs = $activityLogRepository instanceof ActivityLogRepository && $teacherId > 0
    ? $activityLogRepository->listLatestForUser($teacherId, 50)
    : [];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Activity Logs</p>
                    <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl">Rooms & Challenges</h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Review recent teacher-created rooms, challenge updates, and classroom activity.
                    </p>
                </div>
                <a href="./?c=dashboard" class="teacher-button teacher-button--light gap-2">
                    <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </article>

        <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
            <div class="grid gap-3">
                <?php if ($teacherActivityLogs === []) : ?>
                    <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-4 text-sm font-black text-arcade-ink/55">
                        No activity logs yet.
                    </div>
                <?php endif; ?>

                <?php foreach ($teacherActivityLogs as $activityLog) : ?>
                    <?php
                    $category = strtolower((string) ($activityLog['category'] ?? 'general'));
                    $categoryLabel = ucfirst($category);
                    $logText = (string) ($activityLog['log_text'] ?? '');
                    $createdAt = (string) ($activityLog['date_created'] ?? '');
                    $timeLabel = $createdAt !== '' ? date('M j, Y g:i A', strtotime($createdAt)) : '';
                    ?>
                    <article class="teacher-log-card rounded-2xl border-2 border-arcade-ink/12 bg-arcade-cream/72 p-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-3">
                                <span class="teacher-log-badge <?= $category === 'room' ? 'teacher-log-badge--room' : 'teacher-log-badge--challenge' ?>">
                                    <?= htmlspecialchars(strtoupper(substr($categoryLabel, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <div>
                                    <p class="text-sm font-black uppercase tracking-[0.16em] text-arcade-orange"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                    <h2 class="mt-1 text-base font-black"><?= htmlspecialchars($logText, ENT_QUOTES, 'UTF-8') ?></h2>
                                </div>
                            </div>
                            <p class="text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/45"><?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?></p>
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
