<?php
$difficultyRows = $challengeRepository instanceof ChallengeRepository
    ? $challengeRepository->listDifficulties()
    : [];
$highestPoints = 0;

foreach ($difficultyRows as $difficultyRow) {
    $points = (int) ($difficultyRow['points'] ?? 0);
    $highestPoints = max($highestPoints, $points);
}

$difficultyAccents = ['easy' => 'mint', 'medium' => 'yellow', 'hard' => 'coral'];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/60">Rewards</p>
                <h1 class="mt-1 text-3xl font-bold md:text-4xl">Point Rates</h1>
                <p class="mt-2 max-w-3xl text-sm font-medium leading-7 text-arcade-ink/62 md:text-base">
                    Configure the reward points players earn for each challenge difficulty.
                </p>
            </div>
        </section>

        <form class="teacher-panel overflow-hidden p-0" method="post" action="./?c=point-rates">
            <?= adminPanelCsrfField() ?>
            <div class="relative overflow-hidden border-b-4 border-arcade-ink bg-gradient-to-br from-arcade-cyan/55 via-arcade-peach to-arcade-yellow p-5 md:p-6">
                <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full border-[18px] border-white/35"></div>
                <div class="relative flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-arcade-ink/65">Difficulty Rewards</p>
                        <h2 class="mt-1 text-2xl font-bold md:text-3xl">Update point rewards</h2>
                        <p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-arcade-ink/65">
                            These values are used when challenges reward players in solo, room, and 1v1 flows.
                        </p>
                    </div>
                    <button type="submit" class="teacher-button teacher-button--primary gap-2 self-start whitespace-nowrap">
                        <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Save Rates</span>
                    </button>
                </div>
            </div>

            <div class="p-5 md:p-6">
                <?php if ($difficultyRows === []) : ?>
                    <div class="rounded-[28px] border-4 border-dashed border-arcade-ink/20 bg-white/70 p-8 text-center">
                        <h3 class="text-2xl font-bold">No difficulties found</h3>
                        <p class="mt-2 text-sm font-semibold leading-6 text-arcade-ink/60">Difficulty records must exist before point rates can be edited.</p>
                    </div>
                <?php else : ?>
                    <div class="admin-point-rate-card-grid">
                        <?php foreach ($difficultyRows as $difficultyIndex => $difficultyRow) : ?>
                            <?php
                            $difficultyId = (int) ($difficultyRow['difficulty_id'] ?? 0);
                            $difficultyName = (string) ($difficultyRow['name'] ?? 'Difficulty');
                            $difficultyKey = strtolower($difficultyName);
                            $difficultyPoints = (int) ($difficultyRow['points'] ?? 0);
                            $accent = $difficultyAccents[$difficultyKey] ?? 'cyan';
                            $barPercent = $highestPoints > 0 ? min(100, (int) round(($difficultyPoints / $highestPoints) * 100)) : 0;
                            ?>
                            <article class="point-rate-card point-rate-card--<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?> rounded-[28px] border-4 border-arcade-ink bg-white p-4 shadow-[7px_7px_0_#26190f]">
                                <input type="hidden" name="difficulty_id[]" value="<?= (int) $difficultyId ?>">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-arcade-ink/50">Rate <?= (int) ($difficultyIndex + 1) ?></p>
                                        <h3 class="mt-1 text-2xl font-black capitalize"><?= htmlspecialchars($difficultyName, ENT_QUOTES, 'UTF-8') ?></h3>
                                    </div>
                                    <span class="grid h-12 w-12 place-items-center rounded-2xl border-2 border-arcade-ink bg-arcade-<?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?> font-arcade text-[10px] text-arcade-ink">
                                        <?= htmlspecialchars(strtoupper(substr($difficultyName, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <p class="mt-3 min-h-[3rem] text-sm font-semibold leading-6 text-arcade-ink/62">
                                    <?= htmlspecialchars((string) ($difficultyRow['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <label class="mt-4 grid gap-2">
                                    <span class="text-xs font-black uppercase tracking-[0.12em] text-arcade-orange">Reward Points</span>
                                    <input
                                        type="number"
                                        name="points[]"
                                        min="0"
                                        max="999999"
                                        step="1"
                                        required
                                        value="<?= (int) $difficultyPoints ?>"
                                        class="w-full rounded-2xl border-2 border-arcade-ink/15 bg-arcade-cream px-4 py-3 text-2xl font-black text-arcade-ink outline-none transition focus:border-arcade-orange"
                                    >
                                </label>
                                <div class="mt-4 h-3 overflow-hidden rounded-full border-2 border-arcade-ink bg-arcade-cream">
                                    <span class="block h-full rounded-full bg-gradient-to-r from-arcade-orange via-arcade-yellow to-arcade-cyan" style="width: <?= (int) $barPercent ?>%;"></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </section>
</main>

<style>
.point-rate-card {
    animation: point-rate-rise 420ms cubic-bezier(0.2, 0.9, 0.2, 1) both;
    transition: transform 160ms ease, box-shadow 160ms ease;
}

.point-rate-card:hover {
    transform: translateY(-3px);
    box-shadow: 10px 10px 0 #26190f;
}

.point-rate-card--mint {
    background: linear-gradient(180deg, rgba(139, 211, 199, 0.28), rgba(255, 255, 255, 0.94));
}

.point-rate-card--yellow {
    background: linear-gradient(180deg, rgba(255, 209, 102, 0.32), rgba(255, 255, 255, 0.94));
}

.point-rate-card--coral {
    background: linear-gradient(180deg, rgba(249, 115, 115, 0.24), rgba(255, 255, 255, 0.94));
}

@keyframes point-rate-rise {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();
});
</script>
