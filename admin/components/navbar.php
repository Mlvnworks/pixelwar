<?php
$adminCurrentPage = isset($normalizedContent) ? (string) $normalizedContent : 'dashboard';
$adminUsername = trim((string) ($_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$adminInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $adminUsername)) ?: 'AD', 0, 2));
$adminAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$adminNavItems = [
    'dashboard' => 'Dashboard',
    'teachers' => 'Teachers',
];
?>

<header class="teacher-header relative z-40 w-full px-4 py-3">
    <div class="container flex flex-col gap-3 rounded-[22px] border-4 border-arcade-ink bg-arcade-panel/92 px-4 py-3 shadow-[6px_6px_0_#26190f] lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center justify-between gap-3">
            <a class="font-arcade text-sm uppercase tracking-[0.22em] text-arcade-orange no-underline transition hover:text-arcade-coral md:text-base" href="./?c=dashboard" aria-label="Go to admin dashboard">
                <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> Admin
            </a>
        </div>

        <nav class="teacher-nav flex gap-2" aria-label="Admin navigation">
            <?php foreach ($adminNavItems as $adminPage => $adminLabel) : ?>
                <a class="teacher-nav__link <?= $adminCurrentPage === $adminPage ? 'teacher-nav__link--active' : '' ?>" href="./?c=<?= htmlspecialchars($adminPage, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($adminLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="flex items-center justify-between gap-3 lg:justify-end">
            <details class="teacher-profile relative">
                <summary class="teacher-profile__summary flex list-none items-center gap-2 rounded-2xl border-2 border-arcade-ink bg-white px-2 py-1 text-arcade-ink shadow-[0_4px_0_rgba(38,25,15,0.25)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
                    <span class="grid h-9 w-9 place-items-center overflow-hidden rounded-xl border-2 border-arcade-ink bg-arcade-orange font-arcade text-[10px] text-white" aria-hidden="true">
                        <?php if ($adminAvatarUrl !== '') : ?>
                            <img src="<?= htmlspecialchars($adminAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                        <?php else : ?>
                            <?= htmlspecialchars($adminInitials, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </span>
                    <span class="hidden text-sm font-bold leading-none sm:inline"><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?></span>
                    <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M4 6h8l-4 4-4-4Z" />
                    </svg>
                </summary>

                <div class="teacher-profile__menu absolute right-0 mt-3 w-[17rem] rounded-[22px] border-4 border-arcade-ink bg-arcade-panel p-3 text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.28)]">
                    <div class="mb-3 rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream px-3 py-2">
                        <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-orange">Admin</p>
                        <p class="mt-1 text-sm font-bold"><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <label class="teacher-profile__item">
                        <span>Sound</span>
                        <input id="pixelwar-sound-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle sound">
                    </label>
                    <label class="teacher-profile__item">
                        <span>Dark Mode</span>
                        <input id="pixelwar-dark-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle dark mode">
                    </label>
                    <form action="../?c=logout" method="post">
                        <?= adminPanelCsrfField() ?>
                        <button class="teacher-profile__item teacher-profile__item--danger" type="submit">
                            <span>Logout</span>
                            <span aria-hidden="true">&rsaquo;</span>
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>

<script>
(() => {
    const profileMenu = document.querySelector('.teacher-profile');
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

    document.addEventListener('click', (event) => {
        if (profileMenu && !profileMenu.contains(event.target)) {
            profileMenu.removeAttribute('open');
        }
    });
})();
</script>
