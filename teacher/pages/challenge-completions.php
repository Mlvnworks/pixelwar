<?php
$challengeId = (int) ($_GET['id'] ?? 0);
$teacherId = (int) ($_SESSION['user_id'] ?? 0);
$challenge = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->findCreatedChallengeForOwner($challengeId, $teacherId)
    : null;
$completionRows = $userChallengeRepository instanceof UserChallengeRepository
    ? $userChallengeRepository->listCompletedByChallenge($challengeId, 300)
    : [];

$formatDuration = static function (?string $startedAt, ?string $completedAt): string {
    if (!$startedAt || !$completedAt) {
        return 'Unavailable';
    }

    try {
        $start = new DateTimeImmutable($startedAt);
        $end = new DateTimeImmutable($completedAt);
        $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());
    } catch (Throwable) {
        return 'Unavailable';
    }

    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;
    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }
    if ($minutes > 0) {
        $parts[] = $minutes . 'm';
    }
    if ($remainingSeconds > 0 || $parts === []) {
        $parts[] = $remainingSeconds . 's';
    }

    return implode(' ', $parts);
};
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
            <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Completion Records</p>
                        <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars((string) $challenge['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                            Review players who completed this challenge and how long each run took.
                        </p>
                    </div>
                    <a href="./?c=challenge-view&id=<?= (int) $challengeId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                        <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Back to Challenge</span>
                    </a>
                </div>
            </article>

            <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-0 shadow-[7px_7px_0_#26190f] overflow-hidden">
                <?php if ($completionRows === []) : ?>
                    <div class="px-5 py-6 text-sm font-bold text-arcade-ink/55">
                        No completed records yet for this challenge.
                    </div>
                <?php else : ?>
                    <div class="max-h-[42rem] overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="sticky top-0 z-[1] bg-white/95">
                                <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                    <th class="px-4 py-3 font-semibold">Player</th>
                                    <th class="px-4 py-3 font-semibold">Started</th>
                                    <th class="px-4 py-3 font-semibold">Completed</th>
                                    <th class="px-4 py-3 font-semibold">Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completionRows as $row) : ?>
                                    <?php
                                    $firstname = trim((string) ($row['firstname'] ?? ''));
                                    $lastname = trim((string) ($row['lastname'] ?? ''));
                                    $displayName = trim($firstname . ' ' . $lastname) ?: (string) ($row['username'] ?? 'Player');
                                    $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'PL', 0, 2));
                                    ?>
                                    <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-bold text-arcade-ink">
                                                    <?php if (trim((string) ($row['avatar_url'] ?? '')) !== '') : ?>
                                                        <img src="<?= htmlspecialchars((string) $row['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                                    <?php else : ?>
                                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="truncate font-bold text-arcade-ink"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs font-bold text-arcade-ink/55">@<?= htmlspecialchars((string) ($row['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="truncate text-xs font-bold text-arcade-ink/55"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-bold text-arcade-ink/65"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($row['started_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap font-bold text-arcade-ink/65"><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($row['completed_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="px-4 py-3 font-black text-arcade-ink"><?= htmlspecialchars($formatDuration((string) ($row['started_at'] ?? ''), (string) ($row['completed_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
</main>

<script>
window.addEventListener('load', () => window.lucide?.createIcons());
</script>
