<?php
$adminName = trim((string) ($_SESSION['firstname'] ?? $_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$teachers = $userRepository instanceof UserRepository ? $userRepository->listUsersByRole(2, 6) : [];
$teacherCount = count($teachers);
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Admin Dashboard</p>
                    <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl">Welcome <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>,</h1>
                    <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-arcade-ink/65 md:text-base">
                        Manage teacher access and keep the Pixelwar learning workspace organized.
                    </p>
                </div>
                <a href="./?c=teachers" class="teacher-button teacher-button--primary gap-2">
                    <i data-lucide="users-round" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Teacher Management</span>
                </a>
            </div>
        </article>

        <div class="grid gap-5 xl:grid-cols-[0.82fr_1.18fr]">
            <article class="teacher-panel self-start rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Admin Scope</p>
                <h2 class="mt-2 text-2xl font-black">Teacher Management</h2>
                <div class="mt-4 grid gap-3">
                    <div class="admin-metric-card rounded-2xl border-2 border-arcade-ink/12 bg-white p-4">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-arcade-orange">Current Teachers</p>
                        <strong class="mt-2 block text-4xl font-black"><?= (int) $teacherCount ?></strong>
                    </div>
                    <div class="rounded-2xl border-2 border-arcade-ink/12 bg-arcade-cream/75 p-4 text-sm font-bold leading-7 text-arcade-ink/65">
                        Teacher creation and editing actions are placeholders for now. The panel is ready for CRUD integration without changing the folder structure.
                    </div>
                </div>
            </article>

            <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-orange">Latest</p>
                        <h2 class="mt-2 text-2xl font-black">Teachers</h2>
                    </div>
                    <a href="./?c=teachers" class="teacher-link-button">View All</a>
                </div>

                <div class="mt-4 grid gap-3">
                    <?php if ($teachers === []) : ?>
                        <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-4 text-sm font-black text-arcade-ink/55">
                            No teacher accounts found yet.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($teachers as $teacher) : ?>
                        <?php
                        $firstname = trim((string) ($teacher['firstname'] ?? ''));
                        $lastname = trim((string) ($teacher['lastname'] ?? ''));
                        $displayName = trim($firstname . ' ' . $lastname) ?: (string) $teacher['username'];
                        $initials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $displayName) ?: 'TR', 0, 2));
                        ?>
                        <article class="teacher-log-card rounded-2xl border-2 border-arcade-ink/12 bg-white p-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-cyan font-arcade text-[10px] text-arcade-ink">
                                        <?php if (trim((string) ($teacher['avatar_url'] ?? '')) !== '') : ?>
                                            <img src="<?= htmlspecialchars((string) $teacher['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                        <?php else : ?>
                                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </span>
                                    <div>
                                        <h3 class="text-base font-black"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="text-sm font-bold text-arcade-ink/58"><?= htmlspecialchars((string) $teacher['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>
                                <span class="teacher-pill <?= (int) $teacher['is_verified'] === 1 ? 'bg-arcade-mint' : 'bg-arcade-coral/30' ?>">
                                    <?= (int) $teacher['is_verified'] === 1 ? 'Verified' : 'Pending' ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </section>
</main>

<script>
window.addEventListener('load', () => window.lucide?.createIcons());
</script>
