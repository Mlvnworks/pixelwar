<?php
$adminCurrentPage = isset($normalizedContent) ? (string) $normalizedContent : 'dashboard';
$adminUsername = trim((string) ($_SESSION['username'] ?? 'Admin')) ?: 'Admin';
$adminEmail = trim((string) ($_SESSION['email'] ?? 'admin@pixelwar.local')) ?: 'admin@pixelwar.local';
$adminInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $adminUsername)) ?: 'AD', 0, 2));
$adminAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$adminPendingReviewCount = $userRepository instanceof UserRepository
    ? $userRepository->countPendingStudentReviews('', 0)
    : 0;
$adminNavItems = [
    ['type' => 'link', 'page' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
    [
        'type' => 'group',
        'label' => 'User',
        'icon' => 'users',
        'children' => [
            'teachers' => ['label' => 'Teachers', 'icon' => 'graduation-cap'],
            'students' => ['label' => 'Students', 'icon' => 'users'],
        ],
    ],
    ['type' => 'link', 'page' => 'student-verification', 'label' => 'Reviews', 'icon' => 'badge-check', 'badge' => $adminPendingReviewCount],
    [
        'type' => 'group',
        'label' => 'Game Settings',
        'icon' => 'sliders-horizontal',
        'children' => [
            'rank-management' => ['label' => 'Ranks', 'icon' => 'medal'],
            'season-management' => ['label' => 'Seasons', 'icon' => 'calendar-range'],
            'point-rates' => ['label' => 'Point Rates', 'icon' => 'coins'],
        ],
    ],
    ['type' => 'link', 'page' => 'announcement', 'label' => 'Announcement', 'icon' => 'megaphone'],
    ['type' => 'link', 'page' => 'logs', 'label' => 'Logs', 'icon' => 'history'],
    ['type' => 'link', 'page' => 'settings', 'label' => 'Settings', 'icon' => 'settings'],
];
?>

<header class="teacher-header admin-shell-nav">
    <div class="admin-mobile-topbar" data-admin-mobile-topbar>
        <button type="button" class="admin-mobile-menu-button" data-admin-menu-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open admin navigation">
            <i data-lucide="menu" class="h-5 w-5" aria-hidden="true"></i>
            <span>Menu</span>
        </button>
        <a class="admin-mobile-brand" href="./?c=dashboard" aria-label="Go to admin dashboard">
            <img src="../assets/img/pixelwar-braces-logo.svg" alt="" aria-hidden="true">
            <span><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    </div>
    <div class="admin-sidebar-backdrop" data-admin-sidebar-backdrop hidden></div>
    <aside id="admin-sidebar" class="teacher-sidebar teacher-sidebar--admin" aria-label="Admin panel sidebar" data-admin-sidebar>
        <div class="teacher-sidebar__brand">
            <button type="button" class="admin-sidebar-close" data-admin-menu-close aria-label="Close admin navigation">
                <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
            </button>
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
            <?php foreach ($adminNavItems as $adminItem) : ?>
                <?php if (($adminItem['type'] ?? 'link') === 'group') : ?>
                    <?php
                    $adminChildren = $adminItem['children'] ?? [];
                    $adminGroupOpen = array_key_exists($adminCurrentPage, $adminChildren);
                    ?>
                    <details class="teacher-nav__group" <?= $adminGroupOpen ? 'open' : '' ?>>
                        <summary class="teacher-nav__summary <?= $adminGroupOpen ? 'teacher-nav__summary--active' : '' ?>">
                            <i data-lucide="<?= htmlspecialchars((string) ($adminItem['icon'] ?? 'folder'), ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4" aria-hidden="true"></i>
                            <span><?= htmlspecialchars((string) ($adminItem['label'] ?? 'Group'), ENT_QUOTES, 'UTF-8') ?></span>
                            <i data-lucide="chevron-down" class="teacher-nav__summary-chevron ml-auto h-4 w-4" aria-hidden="true"></i>
                        </summary>
                        <div class="teacher-nav__children" data-admin-nav-panel>
                            <?php foreach ($adminChildren as $adminPage => $adminChild) : ?>
                                <a class="teacher-nav__link teacher-nav__link--child <?= $adminCurrentPage === $adminPage ? 'teacher-nav__link--active' : '' ?>" href="./?c=<?= htmlspecialchars($adminPage, ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="<?= htmlspecialchars($adminChild['icon'], ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($adminChild['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php else : ?>
                    <?php $adminPage = (string) ($adminItem['page'] ?? 'dashboard'); ?>
                    <a class="teacher-nav__link <?= $adminCurrentPage === $adminPage ? 'teacher-nav__link--active' : '' ?>" href="./?c=<?= htmlspecialchars($adminPage, ENT_QUOTES, 'UTF-8') ?>">
                        <i data-lucide="<?= htmlspecialchars((string) ($adminItem['icon'] ?? 'circle'), ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4" aria-hidden="true"></i>
                        <span><?= htmlspecialchars((string) ($adminItem['label'] ?? $adminPage), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (isset($adminItem['badge']) && (int) $adminItem['badge'] > 0) : ?>
                            <span class="ml-auto inline-flex min-w-[1.45rem] items-center justify-center rounded-full border border-arcade-ink/10 bg-arcade-yellow px-1.5 py-0.5 text-[10px] font-bold leading-none text-arcade-ink">
                                <?= (int) $adminItem['badge'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
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
    const adminSidebar = document.querySelector('[data-admin-sidebar]');
    const adminMenuToggle = document.querySelector('[data-admin-menu-toggle]');
    const adminMenuClose = document.querySelector('[data-admin-menu-close]');
    const adminSidebarBackdrop = document.querySelector('[data-admin-sidebar-backdrop]');
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

    const applyPixelwarTheme = (isDarkMode) => {
        document.body.classList.toggle('pixelwar-dark-mode', isDarkMode);
        storageSet('pixelwarDarkMode', isDarkMode ? 'on' : 'off');
        window.dispatchEvent(new CustomEvent('pixelwar:theme-change', {
            detail: { theme: isDarkMode ? 'dark' : 'light', isDarkMode },
        }));
    };

    if (darkToggle) {
        darkToggle.checked = storageGet('pixelwarDarkMode') === 'on';
        applyPixelwarTheme(darkToggle.checked);
        darkToggle.addEventListener('change', () => {
            applyPixelwarTheme(darkToggle.checked);
        });
    }

    const setAdminMenuOpen = (isOpen) => {
        document.body.classList.toggle('admin-sidebar-open', isOpen);
        adminSidebar?.classList.toggle('is-open', isOpen);
        adminMenuToggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.hidden = !isOpen;
        }
    };

    adminMenuToggle?.addEventListener('click', () => setAdminMenuOpen(true));
    adminMenuClose?.addEventListener('click', () => setAdminMenuOpen(false));
    adminSidebarBackdrop?.addEventListener('click', () => setAdminMenuOpen(false));
    adminSidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setAdminMenuOpen(false));
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            setAdminMenuOpen(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setAdminMenuOpen(false);
        }
    });

    document.addEventListener('pointerdown', (event) => {
        const soundTarget = event.target.closest('a, button, summary, input[type="checkbox"]');
        if (soundTarget && !soundTarget.matches('#pixelwar-sound-toggle')) {
            playPopSound();
        }
    }, { capture: true });

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const dropdownDuration = 220;

    document.querySelectorAll('.teacher-nav__group').forEach((group) => {
        const summary = group.querySelector('.teacher-nav__summary');
        const panel = group.querySelector('[data-admin-nav-panel]');

        if (!summary || !panel) {
            return;
        }

        panel.style.height = group.open ? 'auto' : '0px';

        const finishDropdownAnimation = () => {
            group.classList.remove('teacher-nav__group--animating');
            panel.style.height = group.open ? 'auto' : '0px';
            panel.style.opacity = '';
        };

        summary.addEventListener('click', (event) => {
            event.preventDefault();

            if (group.classList.contains('teacher-nav__group--animating')) {
                return;
            }

            if (prefersReducedMotion) {
                group.open = !group.open;
                panel.style.height = group.open ? 'auto' : '0px';
                return;
            }

            group.classList.add('teacher-nav__group--animating');

            if (group.open) {
                panel.style.height = `${panel.scrollHeight}px`;
                panel.style.opacity = '1';
                window.requestAnimationFrame(() => {
                    panel.style.height = '0px';
                    panel.style.opacity = '0';
                });
                window.setTimeout(() => {
                    group.open = false;
                    finishDropdownAnimation();
                }, dropdownDuration);
                return;
            }

            group.open = true;
            panel.style.height = '0px';
            panel.style.opacity = '0';
            window.requestAnimationFrame(() => {
                panel.style.height = `${panel.scrollHeight}px`;
                panel.style.opacity = '1';
            });
            window.setTimeout(finishDropdownAnimation, dropdownDuration);
        });
    });
})();
</script>
