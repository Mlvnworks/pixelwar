<?php
$teachers = $userRepository instanceof UserRepository ? $userRepository->listUsersByRole(2, 50) : [];
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative">
        <div class="teacher-page-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-4 shadow-[7px_7px_0_#26190f] md:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Teacher Management</p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">Teachers</h1>
                    <p class="mt-2 max-w-2xl text-sm font-bold leading-7 text-arcade-ink/62">
                        Review teacher accounts and prepare access controls. Create, edit, and disable actions will be wired after the admin workflow is finalized.
                    </p>
                </div>
                <button type="button" class="teacher-button teacher-button--primary gap-2" disabled aria-disabled="true">
                    <i data-lucide="user-plus" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Add Teacher Soon</span>
                </button>
            </div>

            <div class="mt-5 grid gap-3">
                <?php if ($teachers === []) : ?>
                    <div class="rounded-2xl border-2 border-dashed border-arcade-ink/18 bg-white/80 p-5 text-sm font-black text-arcade-ink/55">
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
                    <article class="teacher-student-card rounded-2xl border-2 border-arcade-ink/14 bg-white p-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-2xl border-2 border-arcade-ink bg-arcade-yellow font-arcade text-[10px] text-arcade-ink">
                                    <?php if (trim((string) ($teacher['avatar_url'] ?? '')) !== '') : ?>
                                        <img src="<?= htmlspecialchars((string) $teacher['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                                    <?php else : ?>
                                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <h2 class="text-lg font-black"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p class="text-sm font-bold text-arcade-ink/58">@<?= htmlspecialchars((string) $teacher['username'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $teacher['email'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="teacher-pill <?= (int) $teacher['is_verified'] === 1 ? 'bg-arcade-mint' : 'bg-arcade-coral/30' ?>"><?= (int) $teacher['is_verified'] === 1 ? 'Verified' : 'Pending' ?></span>
                                <span class="teacher-pill bg-arcade-cyan">Joined <?= htmlspecialchars(date('M j, Y', strtotime((string) $teacher['registration_date'])), ENT_QUOTES, 'UTF-8') ?></span>
                                <button type="button" class="teacher-small-button teacher-small-button--light" disabled>Edit Soon</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<script>
window.addEventListener('load', () => window.lucide?.createIcons());
</script>
