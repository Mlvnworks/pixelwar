<?php
$isLoggedIn = isset($_SESSION['username']) && trim((string) $_SESSION['username']) !== '';
$headerUsername = $isLoggedIn ? (string) $_SESSION['username'] : 'Options';
$headerInitials = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', (string) ($_SESSION['avatar_initials'] ?? $headerUsername)) ?: 'PR', 0, 2));
$headerAvatarColor = (string) ($_SESSION['avatar_color'] ?? 'yellow');
$headerAvatarUrl = trim((string) ($_SESSION['avatar_url'] ?? ''));
$headerAvatarClasses = [
    'yellow' => 'bg-arcade-yellow',
    'cyan' => 'bg-arcade-cyan',
    'orange' => 'bg-arcade-orange',
    'mint' => 'bg-arcade-mint',
];
$headerAvatarClass = $headerAvatarClasses[$headerAvatarColor] ?? $headerAvatarClasses['yellow'];
?>

<header class="pixelwar-header <?= $isLoggedIn ? 'pixelwar-header--user' : 'pixelwar-header--guest' ?> relative z-50 w-full px-4 py-3">
    <div class="container flex items-center justify-between gap-4">
        <a class="font-arcade text-sm uppercase tracking-[0.22em] text-arcade-orange no-underline transition hover:text-arcade-coral md:text-lg" href="./" aria-label="Go to Pixelwar landing page">
            <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <details class="pixelwar-profile relative">
            <summary class="pixelwar-profile__summary flex list-none items-center gap-2 rounded-2xl border-2 border-arcade-ink bg-white px-2 py-1 text-arcade-ink shadow-[0_4px_0_rgba(38,25,15,0.25)] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
                <?php if ($isLoggedIn) : ?>
                    <span class="grid h-9 w-9 place-items-center overflow-hidden rounded-xl border-2 border-arcade-ink <?= htmlspecialchars($headerAvatarClass, ENT_QUOTES, 'UTF-8') ?> font-arcade text-[10px] text-arcade-ink" aria-hidden="true">
                        <?php if ($headerAvatarUrl !== '') : ?>
                            <img src="<?= htmlspecialchars($headerAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                        <?php else : ?>
                            <?= htmlspecialchars($headerInitials, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                <span class="hidden text-sm font-bold leading-none sm:inline"><?= htmlspecialchars($headerUsername, ENT_QUOTES, 'UTF-8') ?></span>
                <svg class="h-4 w-4 transition" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M4 6h8l-4 4-4-4Z" />
                </svg>
            </summary>

            <div class="pixelwar-profile__menu absolute right-0 mt-3 w-[17rem] rounded-[22px] border-4 border-arcade-ink bg-arcade-panel p-3 text-arcade-ink shadow-[8px_8px_0_rgba(38,25,15,0.28)]">
                <?php if ($isLoggedIn) : ?>
                    <div class="mb-3 rounded-2xl border-2 border-arcade-ink/10 bg-arcade-cream px-3 py-2">
                        <p class="font-arcade text-[9px] uppercase tracking-[0.18em] text-arcade-orange">Player</p>
                        <p class="mt-1 text-sm font-bold"><?= htmlspecialchars($headerUsername, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <label class="pixelwar-profile__item">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M2 6h3l4-3v10L5 10H2V6Zm9.1-.7 1.1-1.1A5.3 5.3 0 0 1 14 8c0 1.5-.6 2.9-1.8 3.8l-1.1-1.1c.8-.7 1.3-1.6 1.3-2.7s-.5-2-1.3-2.7Z" />
                        </svg>
                        Sound
                    </span>
                    <input id="pixelwar-sound-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle sound">
                </label>

                <label class="pixelwar-profile__item">
                    <span class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M9.8 14A6 6 0 0 1 7.4 2.5 5.8 5.8 0 0 0 12 11.8a5.9 5.9 0 0 0 2-.3A6 6 0 0 1 9.8 14Z" />
                        </svg>
                        Dark Mode
                    </span>
                    <input id="pixelwar-dark-toggle" class="pixelwar-toggle" type="checkbox" aria-label="Toggle dark mode">
                </label>

                <?php if ($isLoggedIn) : ?>
                    <a class="pixelwar-profile__item no-underline" href="./?c=settings">
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M7 1h2l.4 1.7c.4.1.8.3 1.1.5l1.5-.9 1.4 1.4-.9 1.5c.2.4.4.7.5 1.1L15 7v2l-1.7.4c-.1.4-.3.8-.5 1.1l.9 1.5-1.4 1.4-1.5-.9c-.4.2-.7.4-1.1.5L9 15H7l-.4-1.7c-.4-.1-.8-.3-1.1-.5l-1.5.9-1.4-1.4.9-1.5c-.2-.4-.4-.7-.5-1.1L1 9V7l1.7-.4c.1-.4.3-.8.5-1.1l-.9-1.5 1.4-1.4 1.5.9c.4-.2.7-.4 1.1-.5L7 1Zm1 5a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                            </svg>
                            Settings
                        </span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>

                    <a class="pixelwar-profile__item pixelwar-profile__item--danger no-underline" href="./?c=login&logout=1">
                        <span class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M2 2h6v2H4v8h4v2H2V2Zm9.3 3.3L14 8l-2.7 2.7-.9-.9L11.6 8H6V6.7h5.6l-1.2-1.2.9-.9Z" />
                            </svg>
                            Logout
                        </span>
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                <?php endif; ?>
            </div>
        </details>
    </div>
</header>

<style>
.pixelwar-profile > summary::-webkit-details-marker {
    display: none;
}

.pixelwar-profile[open] .pixelwar-profile__summary svg {
    transform: rotate(180deg);
}

.pixelwar-profile__menu {
    animation: pixelwarMenuIn 160ms ease both;
}

.pixelwar-header {
    background: transparent;
}

.pixelwar-header--guest {
    border-bottom: 0;
}

.pixelwar-header--guest .pixelwar-profile__summary {
    padding-left: 0.85rem;
}

.pixelwar-profile__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    width: 100%;
    border-radius: 0.9rem;
    padding: 0.7rem 0.75rem;
    color: #26190f;
    font-size: 0.88rem;
    font-weight: 800;
    transition: transform 160ms ease, background-color 160ms ease, color 160ms ease;
}

.pixelwar-profile__item:hover {
    transform: translateY(-1px);
    background: rgba(255, 209, 102, 0.42);
}

.pixelwar-profile__item--danger {
    color: #d94c3f;
}

.pixelwar-toggle {
    width: 2.5rem;
    height: 1.35rem;
    appearance: none;
    border: 2px solid #26190f;
    border-radius: 999px;
    background: #fff7e8;
    position: relative;
    transition: background-color 160ms ease;
}

.pixelwar-toggle::after {
    content: "";
    position: absolute;
    top: 0.12rem;
    left: 0.15rem;
    width: 0.85rem;
    height: 0.85rem;
    border: 2px solid #26190f;
    border-radius: 999px;
    background: #ff8c42;
    transition: transform 160ms ease, background-color 160ms ease;
}

.pixelwar-toggle:checked {
    background: #4cc9f0;
}

.pixelwar-toggle:checked::after {
    transform: translateX(1.05rem);
    background: #ffd166;
}

body.pixelwar-dark-mode {
    background: #120d08;
}

body.pixelwar-dark-mode .pixelwar-header {
    background: transparent;
}

body.pixelwar-dark-mode .pixelwar-profile__menu {
    border-color: #fff7e8;
    background: #1f160f;
    color: #fff7e8;
}

body.pixelwar-dark-mode .pixelwar-profile__item,
body.pixelwar-dark-mode .pixelwar-profile__menu p {
    color: #fff7e8;
}

@keyframes pixelwarMenuIn {
    from {
        opacity: 0;
        transform: translateY(-0.35rem) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>

<script>
(() => {
    const profileMenu = document.querySelector('.pixelwar-profile');
    const soundToggle = document.getElementById('pixelwar-sound-toggle');
    const darkToggle = document.getElementById('pixelwar-dark-toggle');
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
        if (!profileMenu || profileMenu.contains(event.target)) {
            return;
        }

        profileMenu.removeAttribute('open');
    });
})();
</script>
