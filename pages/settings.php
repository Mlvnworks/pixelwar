<?php
$profileName = trim((string) ($_SESSION['username'] ?? 'Pixel Rookie'));
$profileEmail = trim((string) ($_SESSION['email'] ?? 'player@example.com'));
$profileAvatarInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $profileName)) ?: 'PR', 0, 2));
$profileAvatarColor = (string) ($_SESSION['avatar_color'] ?? 'yellow');
$profileAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$avatarColors = [
    'yellow' => 'Arcade Yellow',
    'cyan' => 'Cyber Cyan',
    'orange' => 'Pixel Orange',
    'mint' => 'Mint Win',
];
?>

<main class="settings-page relative overflow-hidden bg-arcade-cream px-4 py-8 text-arcade-ink md:py-10">
    <div class="settings-page__glow absolute inset-0 bg-[radial-gradient(circle_at_14%_12%,rgba(255,209,102,0.28),transparent_22%),radial-gradient(circle_at_88%_20%,rgba(76,201,240,0.22),transparent_24%),linear-gradient(135deg,rgba(249,115,115,0.12),transparent_38%)]"></div>
    <div class="settings-page__grid absolute inset-0"></div>

    <section class="container relative">
        <a href="./?c=home" class="inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-bold text-arcade-ink no-underline shadow-[0_4px_0_rgba(38,25,15,0.22)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
            <span aria-hidden="true">&larr;</span>
            Back Home
        </a>

        <div class="mt-5 grid gap-5 lg:grid-cols-[0.74fr_1.26fr]">
            <aside class="settings-card rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f]">
                <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Player Settings</p>
                <div class="mt-5 flex flex-col items-center text-center">
                    <div class="settings-avatar settings-avatar--<?= htmlspecialchars($profileAvatarColor, ENT_QUOTES, 'UTF-8') ?> grid h-32 w-32 place-items-center overflow-hidden rounded-[32px] border-4 border-arcade-ink shadow-[7px_7px_0_rgba(38,25,15,0.24)]" aria-label="Current avatar preview">
                        <?php if ($profileAvatarUrl !== '') : ?>
                            <img src="<?= htmlspecialchars($profileAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?> avatar" class="h-full w-full object-cover">
                        <?php else : ?>
                            <span class="font-arcade text-3xl text-arcade-ink"><?= htmlspecialchars($profileAvatarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="mt-5 text-3xl font-bold leading-tight"><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mt-2 break-all text-sm font-bold text-arcade-ink/60"><?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </aside>

            <form class="settings-form rounded-[28px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[8px_8px_0_#26190f] md:p-6" action="./?c=settings" method="post">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Edit Profile</p>
                        <h2 class="mt-3 text-2xl font-bold">Account details</h2>
                    </div>
                    <?php if (isset($_GET['updated'])) : ?>
                        <span class="inline-flex rounded-full border-2 border-arcade-ink bg-arcade-mint px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em] text-arcade-ink">Saved</span>
                    <?php endif; ?>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <label class="settings-field sm:col-span-2" for="settings-name">
                        <span>Name</span>
                        <input id="settings-name" name="username" type="text" autocomplete="name" value="<?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?>" placeholder="Pixel Rookie" required>
                    </label>

                    <label class="settings-field sm:col-span-2" for="settings-email">
                        <span>Email</span>
                        <input id="settings-email" name="email" type="email" autocomplete="email" value="<?= htmlspecialchars($profileEmail, ENT_QUOTES, 'UTF-8') ?>" placeholder="player@example.com" required>
                    </label>

                    <label class="settings-field" for="settings-avatar-initials">
                        <span>Avatar initials</span>
                        <input id="settings-avatar-initials" name="avatar_initials" type="text" maxlength="2" value="<?= htmlspecialchars($profileAvatarInitials, ENT_QUOTES, 'UTF-8') ?>" placeholder="PR">
                    </label>

                    <label class="settings-field" for="settings-avatar-color">
                        <span>Avatar color</span>
                        <select id="settings-avatar-color" name="avatar_color">
                            <?php foreach ($avatarColors as $colorValue => $colorLabel) : ?>
                                <option value="<?= htmlspecialchars($colorValue, ENT_QUOTES, 'UTF-8') ?>" <?= $profileAvatarColor === $colorValue ? 'selected' : '' ?>><?= htmlspecialchars($colorLabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="settings-field sm:col-span-2" for="settings-avatar-url">
                        <span>Avatar image URL</span>
                        <input id="settings-avatar-url" name="avatar_url" type="url" value="<?= htmlspecialchars($profileAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" placeholder="https://example.com/avatar.png">
                    </label>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-bold leading-6 text-arcade-ink/62">Avatar URL is optional. If left empty, Pixelwar uses your initials and selected color.</p>
                    <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border-2 border-arcade-ink bg-arcade-yellow px-5 py-2.5 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-orange hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M3 2h8l2 2v10H3V2Zm2 2v3h5V4H5Zm0 6v2h6v-2H5Z" />
                        </svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>
