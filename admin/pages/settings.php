<?php
$adminDisplayName = trim((string) ($_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$adminEmail = trim((string) ($_SESSION['email'] ?? 'admin@pixelwar.local')) ?: 'admin@pixelwar.local';
$adminFirstname = trim((string) ($_SESSION['firstname'] ?? ''));
$adminLastname = trim((string) ($_SESSION['lastname'] ?? ''));
$adminFullName = trim($adminFirstname . ' ' . $adminLastname);
$adminDisplayName = $adminFullName !== '' ? $adminFullName : $adminDisplayName;
$adminInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $adminDisplayName)) ?: 'AD', 0, 2));
$adminAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$adminPasswordResetAvailableAt = (int) ($_SESSION['admin_password_reset_available_at'] ?? 0);
$adminPasswordResetSecondsLeft = max(0, $adminPasswordResetAvailableAt - time());
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative">
        <div class="grid gap-5 lg:grid-cols-[0.72fr_1.28fr]">
            <aside class="teacher-page-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f] md:p-6">
                <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Settings</p>
                <div class="mt-5 flex flex-col items-center text-center">
                    <span class="grid h-28 w-28 place-items-center overflow-hidden rounded-[30px] border-4 border-arcade-ink bg-arcade-yellow shadow-[7px_7px_0_rgba(38,25,15,0.24)]">
                        <?php if ($adminAvatarUrl !== '') : ?>
                            <img src="<?= htmlspecialchars($adminAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                        <?php else : ?>
                            <span class="font-arcade text-2xl text-arcade-ink"><?= htmlspecialchars($adminInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </span>
                    <h1 class="mt-5 text-3xl font-black leading-tight"><?= htmlspecialchars($adminDisplayName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mt-2 break-all text-sm font-bold text-arcade-ink/60"><?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?></p>
                    <span class="mt-4 inline-flex rounded-full border-2 border-arcade-ink bg-arcade-cyan px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-arcade-ink">Admin</span>
                </div>
            </aside>

            <section class="teacher-page-card rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f] md:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-cyan">Admin Controls</p>
                        <h2 class="mt-3 text-3xl font-black md:text-4xl">Account settings</h2>
                    </div>
                    <?php if (isset($_GET['updated'])) : ?>
                        <span class="inline-flex rounded-full border-2 border-arcade-ink bg-arcade-mint px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em] text-arcade-ink">Saved</span>
                    <?php endif; ?>
                </div>

                <form id="admin-settings-profile-form" class="mt-6 rounded-2xl border-2 border-arcade-ink/10 bg-white/75 p-4" method="post" action="./?c=settings">
                    <?= adminPanelCsrfField() ?>
                    <input type="hidden" name="settings_action" value="profile_update">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-arcade-orange">Profile</p>
                        <h3 class="mt-1 text-lg font-bold text-arcade-ink">Admin name</h3>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-xs font-bold uppercase tracking-[0.12em] text-arcade-ink/55">First Name</span>
                            <input
                                name="firstname"
                                type="text"
                                autocomplete="given-name"
                                maxlength="80"
                                required
                                value="<?= htmlspecialchars($adminFirstname, ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-3 text-sm font-bold text-arcade-ink outline-none transition focus:border-arcade-orange"
                            >
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-bold uppercase tracking-[0.12em] text-arcade-ink/55">Last Name</span>
                            <input
                                name="lastname"
                                type="text"
                                autocomplete="family-name"
                                maxlength="80"
                                required
                                value="<?= htmlspecialchars($adminLastname, ENT_QUOTES, 'UTF-8') ?>"
                                class="w-full rounded-xl border-2 border-arcade-ink/15 bg-white px-4 py-3 text-sm font-bold text-arcade-ink outline-none transition focus:border-arcade-orange"
                            >
                        </label>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2.5 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:bg-arcade-yellow disabled:hover:text-arcade-ink"
                            data-admin-settings-save-button
                        >
                            <span class="admin-settings-save-button__spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                            <i data-lucide="save" class="admin-settings-save-button__icon h-4 w-4" aria-hidden="true"></i>
                            <span class="admin-settings-save-button__text">Save Changes</span>
                        </button>
                    </div>
                </form>

                <form id="admin-password-reset-form" class="mt-6 rounded-2xl border-2 border-arcade-ink/10 bg-white/75 p-4" method="post" action="./?c=settings">
                    <?= adminPanelCsrfField() ?>
                    <input type="hidden" name="settings_action" value="password_reset">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-arcade-orange">Security</p>
                            <h3 class="mt-1 text-lg font-bold text-arcade-ink">Password access</h3>
                            <p class="mt-1 text-sm font-bold leading-6 text-arcade-ink/58">
                                Send a secure password reset link to your registered admin email.
                            </p>
                        </div>
                        <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-white px-4 py-2.5 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-cyan disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:bg-white"
                                data-admin-password-reset-button
                                data-admin-password-reset-available-at="<?= (int) $adminPasswordResetAvailableAt ?>"
                                <?= $adminPasswordResetSecondsLeft > 0 ? 'disabled' : '' ?>
                            >
                                <span class="admin-password-reset-button__spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true"></span>
                                <i data-lucide="lock-keyhole" class="admin-password-reset-button__icon h-4 w-4" aria-hidden="true"></i>
                                <span class="admin-password-reset-button__text">Send Reset Link</span>
                            </button>
                            <p
                                class="<?= $adminPasswordResetSecondsLeft > 0 ? '' : 'hidden' ?> text-xs font-bold text-arcade-ink/55"
                                data-admin-password-reset-countdown
                            >
                                Resend available in <?= (int) $adminPasswordResetSecondsLeft ?>s.
                            </p>
                        </div>
                    </div>
                </form>

                <div class="mt-5 rounded-2xl border border-arcade-ink/10 bg-arcade-cream/70 px-4 py-3">
                    <p class="text-xs font-bold leading-5 text-arcade-ink/58">
                        The reset link expires in 20 minutes and becomes invalid after one successful password change.
                    </p>
                </div>
            </section>
        </div>
    </section>
</main>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const profileForm = document.getElementById('admin-settings-profile-form');
    const profileButton = document.querySelector('[data-admin-settings-save-button]');
    const profileSpinner = profileButton?.querySelector('.admin-settings-save-button__spinner');
    const profileIcon = profileButton?.querySelector('.admin-settings-save-button__icon');
    const profileLabel = profileButton?.querySelector('.admin-settings-save-button__text');
    const form = document.getElementById('admin-password-reset-form');
    const button = document.querySelector('[data-admin-password-reset-button]');
    const spinner = button?.querySelector('.admin-password-reset-button__spinner');
    const icon = button?.querySelector('.admin-password-reset-button__icon');
    const label = button?.querySelector('.admin-password-reset-button__text');
    const countdown = document.querySelector('[data-admin-password-reset-countdown]');

    const updateCountdown = () => {
        if (!button || !label || !countdown) {
            return;
        }

        const availableAt = Number(button.dataset.adminPasswordResetAvailableAt || 0);
        const secondsLeft = Math.max(0, Math.ceil(availableAt - (Date.now() / 1000)));

        if (secondsLeft <= 0) {
            button.disabled = false;
            countdown.classList.add('hidden');
            label.textContent = 'Send Reset Link';
            return;
        }

        button.disabled = true;
        countdown.classList.remove('hidden');
        countdown.textContent = `Resend available in ${secondsLeft}s.`;
        label.textContent = 'Reset Link Sent';
    };

    updateCountdown();
    window.setInterval(updateCountdown, 1000);

    profileForm?.addEventListener('submit', () => {
        if (!profileButton || !profileSpinner || !profileIcon || !profileLabel) {
            return;
        }

        profileButton.disabled = true;
        profileSpinner.classList.remove('hidden');
        profileIcon.classList.add('hidden');
        profileLabel.textContent = 'Saving...';
        profileButton.setAttribute('aria-busy', 'true');
    });

    form?.addEventListener('submit', () => {
        if (!button || !spinner || !icon || !label) {
            return;
        }

        button.disabled = true;
        spinner.classList.remove('hidden');
        icon.classList.add('hidden');
        label.textContent = 'Sending...';
        button.setAttribute('aria-busy', 'true');
    });
});
</script>
