<?php
$adminCurrentPage = isset($normalizedContent) ? (string) $normalizedContent : 'dashboard';
$adminUsername = trim((string) ($_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$adminEmail = trim((string) ($_SESSION['email'] ?? 'admin@pixelwar.local')) ?: 'admin@pixelwar.local';
$adminInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $adminUsername)) ?: 'AD', 0, 2));
$adminAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$adminNavItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'layout-dashboard'],
    'teachers' => ['label' => 'Teachers', 'icon' => 'graduation-cap'],
    'students' => ['label' => 'Students', 'icon' => 'users'],
    'logs' => ['label' => 'Logs', 'icon' => 'history'],
    'settings' => ['label' => 'Settings', 'icon' => 'settings'],
];
?>

<header class="teacher-header admin-shell-nav">
    <aside class="teacher-sidebar teacher-sidebar--admin" aria-label="Admin panel sidebar">
        <div class="teacher-sidebar__brand">
            <a class="teacher-sidebar__logo" href="./?c=dashboard" aria-label="Go to admin dashboard">
                <img class="teacher-sidebar__mark" src="../assets/img/pixelwar-braces-logo.svg" alt="" aria-hidden="true">
                <span>
                    <span class="teacher-sidebar__app"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="teacher-sidebar__role">Admin Panel</span>
                </span>
            </a>
        </div>

        <div class="teacher-sidebar__account">
            <span class="teacher-sidebar__avatar" aria-hidden="true">
                <?php if ($adminAvatarUrl !== '') : ?>
                    <img src="<?= htmlspecialchars($adminAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                <?php else : ?>
                    <?= htmlspecialchars($adminInitials, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
            <div class="min-w-0">
                <p class="teacher-sidebar__name"><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="teacher-sidebar__email"><?= htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <nav class="teacher-nav" aria-label="Admin navigation">
            <?php foreach ($adminNavItems as $adminPage => $adminItem) : ?>
                <a class="teacher-nav__link <?= $adminCurrentPage === $adminPage ? 'teacher-nav__link--active' : '' ?>" href="./?c=<?= htmlspecialchars($adminPage, ENT_QUOTES, 'UTF-8') ?>">
                    <i data-lucide="<?= htmlspecialchars($adminItem['icon'], ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($adminItem['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="teacher-sidebar__controls mt-auto">
            <p class="teacher-sidebar__section-title">Options</p>
            <label class="teacher-profile__item admin-sidebar-control">
                <span>Sound</span>
                <input id="pixelwar-sound-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle sound">
            </label>
            <label class="teacher-profile__item admin-sidebar-control">
                <span>Dark Mode</span>
                <input id="pixelwar-dark-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle dark mode">
            </label>
            <a class="teacher-sidebar__switch" href="./?c=settings">
                <i data-lucide="settings" class="h-4 w-4" aria-hidden="true"></i>
                <span>Settings</span>
            </a>
            <form action="../?c=logout" method="post">
                <?= adminPanelCsrfField() ?>
                <button class="teacher-sidebar__switch teacher-sidebar__switch--logout" type="submit">
                    <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

</header>

<script>
(() => {
    const soundToggle = document.getElementById('pixelwar-sound-toggle');
    const darkToggle = document.getElementById('pixelwar-dark-toggle');
    let audioContext = null;
    const storageGet = (key) => {
        try { return localStorage.getItem(key); } catch (error) { return null; }
    };
    const storageSet = (key, value) => {
        try { localStorage.setItem(key, value); } catch (error) { return; }
    };
    const soundIsOn = () => storageGet('pixelwarSound') !== 'off';
    const playPopSound = () => {
        if (!soundIsOn()) { return; }
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) { return; }
            audioContext = audioContext || new AudioContext();
            if (audioContext.state === 'suspended') { audioContext.resume(); }
            const now = audioContext.currentTime;
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(560, now);
            oscillator.frequency.exponentialRampToValueAtTime(980, now + 0.035);
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.12, now + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.09);
            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            oscillator.start(now);
            oscillator.stop(now + 0.1);
        } catch (error) { return; }
    };

    if (soundToggle) {
        soundToggle.checked = storageGet('pixelwarSound') !== 'off';
        soundToggle.addEventListener('change', () => {
            storageSet('pixelwarSound', soundToggle.checked ? 'on' : 'off');
            if (soundToggle.checked) { playPopSound(); }
        });
    }

    if (darkToggle) {
        darkToggle.checked = storageGet('pixelwarDarkMode') === 'on';
        document.body.classList.toggle('pixelwar-dark-mode', darkToggle.checked);
        darkToggle.addEventListener('change', () => {
            document.body.classList.toggle('pixelwar-dark-mode', darkToggle.checked);
            storageSet('pixelwarDarkMode', darkToggle.checked ? 'on' : 'off');
        });
    }

    document.addEventListener('pointerdown', (event) => {
        const soundTarget = event.target.closest('a, button, summary, input[type="checkbox"]');
        if (soundTarget && !soundTarget.matches('#pixelwar-sound-toggle')) {
            playPopSound();
        }
    }, { capture: true });
})();
</script>
