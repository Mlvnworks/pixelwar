<?php
$teacherCurrentPage = isset($normalizedContent) ? (string) $normalizedContent : 'dashboard';
$isTeacherLoggedIn = isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
$teacherUsername = $isTeacherLoggedIn ? (trim((string) ($_SESSION['username'] ?? 'Teacher')) ?: 'Teacher') : 'Guest Builder';
$teacherEmail = trim((string) ($_SESSION['email'] ?? 'teacher@pixelwar.local'));
$teacherInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $teacherUsername)) ?: 'TR', 0, 2));
$teacherAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$teacherNavItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'layout-dashboard'],
    'students' => ['label' => 'Students', 'icon' => 'users'],
    'rooms' => ['label' => 'Rooms', 'icon' => 'messages-square'],
    'challenges' => ['label' => 'Challenges', 'icon' => 'swords'],
    'settings' => ['label' => 'Settings', 'icon' => 'settings'],
];
$teacherTopbarLabels = $teacherNavItems + [
    'activity-logs' => ['label' => 'Activity Logs', 'icon' => 'activity'],
    'create-challenge' => ['label' => 'Create Challenge', 'icon' => 'square-plus'],
    'challenge-view' => ['label' => 'Challenge Details', 'icon' => 'file-text'],
];
$teacherNotifications = [
    [
        'title' => 'Room waiting',
        'body' => 'Arcade Dawn practice room has 3 players waiting.',
        'time' => 'Now',
        'unread' => true,
    ],
    [
        'title' => 'Challenge draft',
        'body' => 'Button Border Basics is ready for review.',
        'time' => '18m',
        'unread' => true,
    ],
    [
        'title' => 'Student activity',
        'body' => '12 students completed a challenge today.',
        'time' => '1h',
        'unread' => false,
    ],
];
$teacherUnreadNotifications = count(array_filter($teacherNotifications, static fn (array $notification): bool => (bool) $notification['unread']));
?>

<header class="teacher-header admin-shell-nav">
    <aside class="teacher-sidebar" aria-label="Teacher panel sidebar">
        <div class="teacher-sidebar__brand">
            <a class="teacher-sidebar__logo" href="./?c=dashboard" aria-label="Go to teacher dashboard">
                <img class="teacher-sidebar__mark" src="../assets/img/pixelwar-braces-logo.svg" alt="" aria-hidden="true">
                <span>
                    <span class="teacher-sidebar__app"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="teacher-sidebar__role">Teacher Panel</span>
                </span>
            </a>
        </div>

        <section class="teacher-sidebar__account" aria-label="Teacher account">
            <span class="teacher-sidebar__avatar" aria-hidden="true">
                <?php if ($teacherAvatarUrl !== '') : ?>
                    <img src="<?= htmlspecialchars($teacherAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                <?php else : ?>
                    <?= htmlspecialchars($teacherInitials, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
            <div class="min-w-0">
                <p class="teacher-sidebar__name"><?= htmlspecialchars($teacherUsername, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="teacher-sidebar__email"><?= htmlspecialchars($teacherEmail, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </section>

        <nav class="teacher-nav" aria-label="Teacher navigation">
            <?php foreach ($teacherNavItems as $teacherPage => $teacherItem) : ?>
                <a class="teacher-nav__link <?= $teacherCurrentPage === $teacherPage ? 'teacher-nav__link--active' : '' ?>" href="./?c=<?= htmlspecialchars($teacherPage, ENT_QUOTES, 'UTF-8') ?>">
                    <i data-lucide="<?= htmlspecialchars($teacherItem['icon'], ENT_QUOTES, 'UTF-8') ?>" class="h-4 w-4" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($teacherItem['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <section class="teacher-sidebar__controls" aria-label="Teacher options">
            <p class="teacher-sidebar__section-title">Options</p>
            <label class="teacher-profile__item">
                <span>Sound</span>
                <input id="pixelwar-sound-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle sound">
            </label>
            <label class="teacher-profile__item">
                <span>Dark Mode</span>
                <input id="pixelwar-dark-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle dark mode">
            </label>
            <?php if ($isTeacherLoggedIn) : ?>
                <form action="../?c=logout" method="post">
                    <?= teacherPanelCsrfField() ?>
                    <button class="teacher-profile__item teacher-profile__item--danger" type="submit">
                        <span>Logout</span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </button>
                </form>
            <?php else : ?>
                <a class="teacher-profile__item no-underline" href="../?c=login">
                    <span>Login as Player</span>
                    <span aria-hidden="true">&rsaquo;</span>
                </a>
            <?php endif; ?>
        </section>
    </aside>

    <div class="teacher-topbar">
        <div>
            <p class="teacher-topbar__eyebrow">Workspace</p>
            <p class="teacher-topbar__title"><?= htmlspecialchars($teacherTopbarLabels[$teacherCurrentPage]['label'] ?? 'Teacher Panel', ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <details class="pixelwar-notifications teacher-notifications relative">
            <summary class="pixelwar-notifications__summary relative grid h-11 w-11 list-none place-items-center rounded-2xl border-2 border-arcade-ink bg-white text-arcade-ink shadow-[0_4px_0_rgba(38,25,15,0.25)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" aria-label="Open notifications">
                <svg class="h-5 w-5" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M8 1.5A4.5 4.5 0 0 0 3.5 6v2.4L2.3 11v1h11.4v-1l-1.2-2.6V6A4.5 4.5 0 0 0 8 1.5Zm0 13a2 2 0 0 0 1.9-1.4H6.1A2 2 0 0 0 8 14.5Z" />
                </svg>
                <?php if ($teacherUnreadNotifications > 0) : ?>
                    <span class="pixelwar-notifications__badge" aria-label="<?= (int) $teacherUnreadNotifications ?> new notifications"><?= (int) $teacherUnreadNotifications ?></span>
                <?php endif; ?>
            </summary>

            <div class="pixelwar-notifications__menu absolute right-0 mt-3 w-[19rem] rounded-[22px] border-4 border-arcade-ink bg-arcade-panel p-3 text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.28)]">
                <div class="mb-2 flex items-center justify-between gap-3 px-1">
                    <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-orange">Notifications</p>
                    <?php if ($teacherUnreadNotifications > 0) : ?>
                        <span class="rounded-full bg-arcade-coral px-2 py-1 text-[10px] font-black text-white"><?= (int) $teacherUnreadNotifications ?> new</span>
                    <?php endif; ?>
                </div>
                <div class="grid gap-2">
                    <?php foreach ($teacherNotifications as $notification) : ?>
                        <article class="pixelwar-notification <?= $notification['unread'] ? 'pixelwar-notification--unread' : '' ?>">
                            <div>
                                <p class="text-sm font-black"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-1 text-xs font-bold leading-5 text-arcade-ink/60"><?= htmlspecialchars($notification['body'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-[0.12em] text-arcade-orange"><?= htmlspecialchars($notification['time'], ENT_QUOTES, 'UTF-8') ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>
    </div>
</header>

<script>
(() => {
    const soundToggle = document.getElementById('pixelwar-sound-toggle');
    const darkToggle = document.getElementById('pixelwar-dark-toggle');
    const notificationMenu = document.querySelector('.pixelwar-notifications');
    let audioContext = null;
    const storageGet = (key) => {
        try {
            return localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    };
    const storageSet = (key, value) => {
        try {
            localStorage.setItem(key, value);
        } catch (error) {
            return;
        }
    };
    const soundIsOn = () => storageGet('pixelwarSound') !== 'off';
    const renderIcons = () => window.lucide?.createIcons();
    const playPopSound = () => {
        if (!soundIsOn()) {
            return;
        }

        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return;
            }

            audioContext = audioContext || new AudioContext();
            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }

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
        } catch (error) {
            return;
        }
    };

    if (window.lucide) {
        renderIcons();
    } else {
        window.addEventListener('load', renderIcons);
    }

    if (soundToggle) {
        soundToggle.checked = storageGet('pixelwarSound') !== 'off';
        soundToggle.addEventListener('change', () => {
            storageSet('pixelwarSound', soundToggle.checked ? 'on' : 'off');
            if (soundToggle.checked) {
                playPopSound();
            }
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
        if (notificationMenu && !notificationMenu.contains(event.target)) {
            notificationMenu.removeAttribute('open');
        }
    });
})();
</script>
