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
$teacherNotifications = [];

if ($isTeacherLoggedIn && isset($connection) && $connection instanceof mysqli) {
    $teacherNotificationStatement = $connection->prepare(
        'SELECT notif_id, `text`, `type`, created_at
         FROM notifications
         WHERE type IN (?, ?)
         ORDER BY created_at DESC, notif_id DESC
         LIMIT 10'
    );
    $typeAll = 'announcement_all';
    $typeTeacher = 'announcement_teacher';
    $teacherNotificationStatement->bind_param('ss', $typeAll, $typeTeacher);
    $teacherNotificationStatement->execute();
    $teacherNotificationRows = $teacherNotificationStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $teacherNotificationStatement->close();

    foreach ($teacherNotificationRows as $notificationRow) {
        $createdAt = strtotime((string) ($notificationRow['created_at'] ?? ''));
        $teacherNotifications[] = [
            'id' => (int) ($notificationRow['notif_id'] ?? 0),
            'title' => (string) ($notificationRow['type'] ?? '') === 'announcement_teacher' ? 'Teacher Announcement' : 'Announcement',
            'body' => (string) ($notificationRow['text'] ?? ''),
            'time' => $createdAt > 0 ? date('M j, g:i A', $createdAt) : 'Recently',
        ];
    }
}

$teacherLatestNotificationId = $teacherNotifications !== [] ? max(array_column($teacherNotifications, 'id')) : 0;
?>

<header class="teacher-header admin-shell-nav">
    <div class="teacher-mobile-topbar" data-teacher-mobile-topbar>
        <button type="button" class="teacher-mobile-menu-button" data-teacher-menu-toggle aria-controls="teacher-sidebar" aria-expanded="false" aria-label="Open teacher navigation">
            <i data-lucide="menu" class="h-5 w-5" aria-hidden="true"></i>
            <span>Menu</span>
        </button>
        <a class="teacher-mobile-brand" href="./?c=dashboard" aria-label="Go to teacher dashboard">
            <img src="../assets/img/pixelwar-braces-logo.svg" alt="" aria-hidden="true">
            <span><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    </div>
    <div class="teacher-sidebar-backdrop" data-teacher-sidebar-backdrop hidden></div>
    <aside id="teacher-sidebar" class="teacher-sidebar" aria-label="Teacher panel sidebar" data-teacher-sidebar>
        <div class="teacher-sidebar__brand">
            <button type="button" class="teacher-sidebar-close" data-teacher-menu-close aria-label="Close teacher navigation">
                <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
            </button>
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

        <details class="pixelwar-notifications teacher-notifications relative" data-notification-menu data-notification-scope="teacher" data-latest-notification-id="<?= (int) $teacherLatestNotificationId ?>">
            <summary class="pixelwar-notifications__summary relative grid h-11 w-11 list-none place-items-center rounded-2xl border-2 border-arcade-ink bg-white text-arcade-ink shadow-[0_4px_0_rgba(38,25,15,0.25)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" aria-label="Open notifications">
                <svg class="h-5 w-5" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M8 1.5A4.5 4.5 0 0 0 3.5 6v2.4L2.3 11v1h11.4v-1l-1.2-2.6V6A4.5 4.5 0 0 0 8 1.5Zm0 13a2 2 0 0 0 1.9-1.4H6.1A2 2 0 0 0 8 14.5Z" />
                </svg>
                <?php if ($teacherLatestNotificationId > 0) : ?>
                    <span class="pixelwar-notifications__badge hidden" data-notification-badge aria-label="New announcements">!</span>
                <?php endif; ?>
            </summary>

            <div class="pixelwar-notifications__menu absolute right-0 mt-3 w-[19rem] rounded-[22px] border-4 border-arcade-ink bg-arcade-panel p-3 text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.28)]">
                <div class="mb-2 flex items-center justify-between gap-3 px-1">
                    <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-orange">Notifications</p>
                    <?php if ($teacherLatestNotificationId > 0) : ?>
                        <span class="hidden rounded-full bg-arcade-coral px-2 py-1 text-[10px] font-black text-white" data-notification-new-label>New</span>
                    <?php endif; ?>
                </div>
                <div class="grid gap-2">
                    <?php if ($teacherNotifications === []) : ?>
                        <article class="pixelwar-notification">
                            <p class="text-xs font-bold leading-5 text-arcade-ink/60">No announcements yet.</p>
                        </article>
                    <?php else : ?>
                    <?php foreach ($teacherNotifications as $notification) : ?>
                        <button
                            type="button"
                            class="pixelwar-notification text-left"
                            data-notification-item
                            data-notification-id="<?= (int) $notification['id'] ?>"
                            data-announcement-title="<?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?>"
                            data-announcement-body="<?= htmlspecialchars($notification['body'], ENT_QUOTES, 'UTF-8') ?>"
                            data-announcement-time="<?= htmlspecialchars($notification['time'], ENT_QUOTES, 'UTF-8') ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#announcement-notification-modal"
                        >
                            <div>
                                <p class="text-sm font-black"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-1 text-xs font-bold leading-5 text-arcade-ink/60"><?= htmlspecialchars($notification['body'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-[0.12em] text-arcade-orange"><?= htmlspecialchars($notification['time'], ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </details>
    </div>
</header>

<?php if ($isTeacherLoggedIn) : ?>
<div class="modal fade" id="announcement-notification-modal" tabindex="-1" aria-labelledby="announcement-notification-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content relative rounded-[26px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]">
            <div class="modal-header border-b-2 border-arcade-ink/10 px-4 py-3 pe-12">
                <div>
                    <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-orange">Announcement</p>
                    <h2 id="announcement-notification-modal-title" class="mb-0 mt-1 text-xl font-black">Announcement</h2>
                    <p id="announcement-notification-modal-time" class="mt-1 text-xs font-black uppercase tracking-[0.12em] text-arcade-ink/45"></p>
                </div>
                <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div id="announcement-notification-modal-body" class="announcement-notification-modal__body rounded-2xl border-2 border-arcade-ink/10 bg-white/80 p-4 text-sm font-bold leading-7 text-arcade-ink/72"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(() => {
    const soundToggle = document.getElementById('pixelwar-sound-toggle');
    const darkToggle = document.getElementById('pixelwar-dark-toggle');
    const notificationMenu = document.querySelector('.pixelwar-notifications');
    const teacherSidebar = document.querySelector('[data-teacher-sidebar]');
    const teacherMenuToggle = document.querySelector('[data-teacher-menu-toggle]');
    const teacherMenuClose = document.querySelector('[data-teacher-menu-close]');
    const teacherSidebarBackdrop = document.querySelector('[data-teacher-sidebar-backdrop]');
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

    const setTeacherMenuOpen = (isOpen) => {
        document.body.classList.toggle('teacher-sidebar-open', isOpen);
        teacherSidebar?.classList.toggle('is-open', isOpen);
        teacherMenuToggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (teacherSidebarBackdrop) {
            teacherSidebarBackdrop.hidden = !isOpen;
        }
    };

    teacherMenuToggle?.addEventListener('click', () => setTeacherMenuOpen(true));
    teacherMenuClose?.addEventListener('click', () => setTeacherMenuOpen(false));
    teacherSidebarBackdrop?.addEventListener('click', () => setTeacherMenuOpen(false));
    teacherSidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setTeacherMenuOpen(false));
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            setTeacherMenuOpen(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setTeacherMenuOpen(false);
        }
    });

    document.addEventListener('pointerdown', (event) => {
        const soundTarget = event.target.closest('a, button, summary, input[type="checkbox"]');
        if (soundTarget && !soundTarget.matches('#pixelwar-sound-toggle')) {
            playPopSound();
        }
    }, { capture: true });

    if (notificationMenu) {
        const scope = notificationMenu.dataset.notificationScope || 'teacher';
        const latestId = Number(notificationMenu.dataset.latestNotificationId || 0);
        const seenKey = `pixelwarSeenNotification_${scope}`;
        const seenId = Number(storageGet(seenKey) || 0);
        const badge = notificationMenu.querySelector('[data-notification-badge]');
        const newLabel = notificationMenu.querySelector('[data-notification-new-label]');
        const markNotificationsSeen = () => {
            if (latestId <= 0) {
                return;
            }

            storageSet(seenKey, String(latestId));
            badge?.classList.add('hidden');
            newLabel?.classList.add('hidden');
            notificationMenu.querySelectorAll('[data-notification-item]').forEach((item) => {
                item.classList.remove('pixelwar-notification--unread');
            });
        };

        if (latestId > seenId) {
            badge?.classList.remove('hidden');
            newLabel?.classList.remove('hidden');
            notificationMenu.querySelectorAll('[data-notification-item]').forEach((item) => {
                if (Number(item.dataset.notificationId || 0) > seenId) {
                    item.classList.add('pixelwar-notification--unread');
                }
            });
        }

        notificationMenu.addEventListener('toggle', () => {
            if (notificationMenu.open) {
                markNotificationsSeen();
            }
        });

        notificationMenu.querySelectorAll('[data-notification-item]').forEach((item) => {
            item.addEventListener('click', markNotificationsSeen);
        });
    }

    const announcementModal = document.getElementById('announcement-notification-modal');
    const announcementModalTitle = document.getElementById('announcement-notification-modal-title');
    const announcementModalTime = document.getElementById('announcement-notification-modal-time');
    const announcementModalBody = document.getElementById('announcement-notification-modal-body');
    const appendFormattedAnnouncementText = (container, text) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';
        const urlPattern = /(https?:\/\/[^\s<]+|www\.[^\s<]+)/gi;
        const parts = String(text || '').split(urlPattern);

        parts.forEach((part) => {
            if (part === '') {
                return;
            }

            urlPattern.lastIndex = 0;
            if (urlPattern.test(part)) {
                urlPattern.lastIndex = 0;
                const link = document.createElement('a');
                link.href = part.toLowerCase().startsWith('www.') ? `https://${part}` : part;
                link.textContent = part;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                container.appendChild(link);
                return;
            }

            part.split('\n').forEach((line, index, lines) => {
                if (line !== '') {
                    container.appendChild(document.createTextNode(line));
                }
                if (index < lines.length - 1) {
                    container.appendChild(document.createElement('br'));
                }
            });
        });
    };

    announcementModal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        if (announcementModalTitle) {
            announcementModalTitle.textContent = trigger.dataset.announcementTitle || 'Announcement';
        }

        if (announcementModalTime) {
            announcementModalTime.textContent = trigger.dataset.announcementTime || '';
        }

        appendFormattedAnnouncementText(announcementModalBody, trigger.dataset.announcementBody || '');
    });

    document.addEventListener('click', (event) => {
        if (notificationMenu && !notificationMenu.contains(event.target)) {
            notificationMenu.removeAttribute('open');
        }
    });
})();
</script>
