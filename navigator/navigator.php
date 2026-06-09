<?php
$allowedPages = [];
$pageFiles = glob(__DIR__ . '/../pages/*.php') ?: [];
$normalizedContent = preg_match('/^[a-z0-9\-]+$/i', $content) === 1 ? $content : 'landing';

$allowedPages = array_map(function ($file) {
    return basename($file, '.php');
}, $pageFiles);

$isAllowedPage = in_array($normalizedContent, $allowedPages, true);
$pageStyleFile = __DIR__ . '/../styling/page/' . $normalizedContent . '.css';
$appName = isset($pageMeta) ? $pageMeta->titleFor($normalizedContent) : APP_NAME;
$appDescription = isset($pageMeta) ? $pageMeta->descriptionFor($normalizedContent) : 'Gamified CSS game for students';
$headerlessPages = ['landing', 'pixelwar', 'matching'];
$footerlessPages = ['landing', 'login', 'forgot-password', 'update-pass', 'signup', 'email-verification', 'profile-setup', 'review-pending', 'review-rejected', 'pixelwar', 'matching'];
$hidesHeader = in_array($normalizedContent, $headerlessPages, true);
$hidesFooter = in_array($normalizedContent, $footerlessPages, true);
$globalVersusInviteEnabled = isset($_SESSION['user_id'])
    && $normalizedContent !== 'logout'
    && $normalizedContent !== 'pixelwar'
    && defined('PUSHER_KEY')
    && defined('PUSHER_CLUSTER')
    && trim((string) PUSHER_KEY) !== ''
    && trim((string) PUSHER_CLUSTER) !== ''
    && !str_starts_with(trim((string) PUSHER_KEY), 'your-pusher-')
    && !str_starts_with(trim((string) PUSHER_CLUSTER), 'your-pusher-');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($appDescription, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        arcade: {
                            cream: '#fff7e8',
                            peach: '#ffd9a8',
                            yellow: '#ffd166',
                            orange: '#ff8c42',
                            coral: '#f97373',
                            cyan: '#4cc9f0',
                            mint: '#8bd3c7',
                            ink: '#26190f',
                            panel: '#fffdf6'
                        }
                    },
                    fontFamily: {
                        arcade: ['"Press Start 2P"', 'cursive'],
                        body: ['"Space Grotesk"', 'sans-serif']
                    },
                    boxShadow: {
                        arcade: '0 24px 60px rgba(38, 25, 15, 0.14)'
                    }
                }
            }
        };
    </script>

    <link rel="stylesheet" href="./styling/style.css">

    <?php if ($isAllowedPage && is_file($pageStyleFile)) : ?>
        <link rel="stylesheet" href="./styling/page/<?= htmlspecialchars($normalizedContent, ENT_QUOTES, 'UTF-8') ?>.css">
    <?php endif; ?>
    <link rel="stylesheet" href="./styling/theme.css">
    <link rel="stylesheet" href="./styling/responsive.css">

    <link rel="shortcut icon" href="./assets/img/icon.png" type="image/x-icon">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
</head>

<body class="bg-arcade-cream font-body text-arcade-ink antialiased">
    <script>
        try {
            if (localStorage.getItem('pixelwarDarkMode') === 'on') {
                document.body.classList.add('pixelwar-dark-mode');
            }
        } catch (error) {
            document.body.classList.remove('pixelwar-dark-mode');
        }
    </script>
    <?php
    if ($isAllowedPage) {
        if (!$hidesHeader) {
            include __DIR__ . '/../components/navbar.php';
        }
        require __DIR__ . '/../pages/' . $normalizedContent . '.php';
        if (!$hidesFooter) {
            include __DIR__ . '/../components/footer.php';
        }
        $tools->alert();
    } else {
        http_response_code(404);
        require __DIR__ . '/../components/404.php';
    }
    ?>
    <?php if ($globalVersusInviteEnabled) : ?>
        <div class="modal fade" id="global-versus-invite-modal" tabindex="-1" aria-labelledby="global-versus-invite-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-[26px] border-4 border-arcade-ink bg-arcade-panel shadow-[10px_10px_0_#26190f]">
                    <div class="modal-body p-5 md:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-arcade text-[10px] uppercase tracking-[0.22em] text-arcade-cyan">Duel Invite</p>
                                <h2 id="global-versus-invite-modal-title" class="mt-3 text-2xl font-bold text-arcade-ink">Player Invitation</h2>
                            </div>
                            <button type="button" class="rounded-xl border-2 border-arcade-ink bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-arcade-ink" data-bs-dismiss="modal" aria-label="Close">Close</button>
                        </div>

                        <div class="mt-5 flex items-center gap-4 rounded-[22px] border-4 border-arcade-ink bg-white p-4 shadow-[6px_6px_0_rgba(38,25,15,0.18)]">
                            <span id="global-versus-invite-avatar" class="relative grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl border-4 border-arcade-ink bg-arcade-yellow font-arcade text-lg text-arcade-ink">PW</span>
                            <div class="min-w-0">
                                <p class="text-xs font-black uppercase tracking-[0.14em] text-arcade-ink/48">Invited by</p>
                                <p id="global-versus-invite-name" class="mt-1 truncate text-lg font-black text-arcade-ink">Player</p>
                                <p id="global-versus-invite-username" class="truncate text-sm font-bold text-arcade-ink/60">@player</p>
                            </div>
                        </div>

                        <p id="global-versus-invite-copy" class="mt-4 text-sm font-bold leading-7 text-arcade-ink/68">
                            A live 1v1 invite just reached your arena.
                        </p>

                        <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
                            <button type="button" class="rounded-xl border-2 border-arcade-ink bg-white px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow" data-bs-dismiss="modal">
                                Dismiss
                            </button>
                            <button type="button" id="global-versus-invite-accept" class="rounded-xl border-2 border-arcade-ink bg-arcade-cyan px-4 py-2 text-sm font-bold text-arcade-ink shadow-[0_4px_0_#26190f] transition hover:-translate-y-0.5 hover:bg-arcade-yellow">
                                Accept
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
            <div id="global-versus-decline-toast" class="toast align-items-center border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[8px_8px_0_#26190f]" role="status" aria-live="polite" aria-atomic="true">
                <div class="d-flex align-items-start">
                    <div class="toast-body pe-2">
                        <p class="mb-1 font-arcade text-[10px] uppercase tracking-[0.2em] text-arcade-coral">Invite Declined</p>
                        <p id="global-versus-decline-toast-text" class="mb-0 text-sm font-bold leading-6 text-arcade-ink">The player declined your invitation.</p>
                    </div>
                    <button type="button" class="btn-close me-2 mt-2" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id']) && $normalizedContent !== 'logout') : ?>
        <script>
            (() => {
                const csrfToken = <?= json_encode(pixelwarCsrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const heartbeatUrl = window.location.pathname + window.location.search;
                const sendHeartbeat = () => {
                    if (!csrfToken) {
                        return;
                    }

                    const body = new URLSearchParams({
                        presence_action: 'heartbeat',
                        _csrf_token: csrfToken,
                    });

                    fetch(heartbeatUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: body.toString(),
                    }).catch(() => {});
                };

                sendHeartbeat();
                window.setInterval(sendHeartbeat, 30000);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'visible') {
                        sendHeartbeat();
                    }
                });
            })();
        </script>
    <?php endif; ?>
    <?php if ($globalVersusInviteEnabled) : ?>
        <script>
            (() => {
                const inviteModalElement = document.getElementById('global-versus-invite-modal');
                const inviteAvatar = document.getElementById('global-versus-invite-avatar');
                const inviteName = document.getElementById('global-versus-invite-name');
                const inviteUsername = document.getElementById('global-versus-invite-username');
                const inviteCopy = document.getElementById('global-versus-invite-copy');
                const inviteAcceptButton = document.getElementById('global-versus-invite-accept');
                const declineToastElement = document.getElementById('global-versus-decline-toast');
                const declineToastText = document.getElementById('global-versus-decline-toast-text');
                const csrfToken = <?= json_encode(pixelwarCsrfToken(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const currentUserId = <?= (int) ($_SESSION['user_id'] ?? 0) ?>;
                const pusherKey = <?= json_encode((string) PUSHER_KEY, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                const pusherCluster = <?= json_encode((string) PUSHER_CLUSTER, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
                let inviteModal = null;
                let declineToast = null;
                let activeInviterUserId = 0;
                let inviteResponded = false;

                const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#039;',
                    '"': '&quot;',
                }[character]));

                const showInviteModal = (payload) => {
                    if (!inviteModalElement || !window.bootstrap?.Modal) {
                        return;
                    }

                    const inviterName = String(payload?.inviter_name || 'Player');
                    const inviterUsername = String(payload?.inviter_username || '');
                    const inviterAvatarUrl = String(payload?.inviter_avatar_url || '').trim();
                    const inviterInitials = String(payload?.inviter_initials || 'PW');

                    if (inviteAvatar) {
                        if (inviterAvatarUrl !== '') {
                            inviteAvatar.innerHTML = `<img src="${escapeHtml(inviterAvatarUrl)}" alt="" class="h-full w-full object-cover">`;
                        } else {
                            inviteAvatar.textContent = inviterInitials;
                        }
                    }
                    if (inviteName) {
                        inviteName.textContent = inviterName;
                    }
                    if (inviteUsername) {
                        inviteUsername.textContent = inviterUsername !== '' ? `@${inviterUsername}` : '@player';
                    }
                    if (inviteCopy) {
                        inviteCopy.textContent = `${inviterName} invited you to a live 1v1 duel.`;
                    }

                    activeInviterUserId = Number(payload?.inviter_user_id || 0);
                    inviteResponded = false;

                    inviteModal = inviteModal || new window.bootstrap.Modal(inviteModalElement);
                    inviteModal.show();
                };

                const showDeclineToast = (payload) => {
                    if (!declineToastElement || !window.bootstrap?.Toast) {
                        return;
                    }

                    const declinerName = String(payload?.decliner_name || 'The player');
                    if (declineToastText) {
                        declineToastText.textContent = `${declinerName} declined your invitation.`;
                    }

                    declineToast = declineToast || new window.bootstrap.Toast(declineToastElement, {
                        delay: 5000,
                    });
                    declineToast.show();
                };

                const notifyInviteDeclined = () => {
                    if (!activeInviterUserId || inviteResponded || !csrfToken) {
                        return;
                    }

                    inviteResponded = true;
                    const body = new URLSearchParams({
                        versus_action: 'decline_invite',
                        inviter_user_id: String(activeInviterUserId),
                        _csrf_token: csrfToken,
                    });

                    fetch('./?c=versus', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: body.toString(),
                    }).catch(() => {});
                };

                const acceptInvite = () => {
                    if (!activeInviterUserId || !csrfToken || inviteResponded) {
                        return;
                    }

                    inviteResponded = true;
                    if (inviteAcceptButton instanceof HTMLButtonElement) {
                        inviteAcceptButton.disabled = true;
                        inviteAcceptButton.textContent = 'Accepting...';
                    }

                    const body = new URLSearchParams({
                        versus_action: 'accept_invite',
                        inviter_user_id: String(activeInviterUserId),
                        _csrf_token: csrfToken,
                    });

                    fetch('./?c=versus', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: body.toString(),
                    })
                        .then((response) => response.ok ? response.json() : Promise.reject(new Error('Accept failed')))
                        .then((payload) => {
                            if (!payload?.success || !payload?.redirect_url) {
                                throw new Error(payload?.message || 'Accept failed');
                            }
                            window.location.href = payload.redirect_url;
                        })
                        .catch(() => {
                            inviteResponded = false;
                            if (inviteAcceptButton instanceof HTMLButtonElement) {
                                inviteAcceptButton.disabled = false;
                                inviteAcceptButton.textContent = 'Accept';
                            }
                        });
                };

                inviteAcceptButton?.addEventListener('click', acceptInvite);
                inviteModalElement?.addEventListener('hidden.bs.modal', () => {
                    notifyInviteDeclined();
                    activeInviterUserId = 0;
                    if (inviteAcceptButton instanceof HTMLButtonElement) {
                        inviteAcceptButton.disabled = false;
                        inviteAcceptButton.textContent = 'Accept';
                    }
                });

                if (currentUserId > 0 && pusherKey && pusherCluster && window.Pusher) {
                    const pusher = new window.Pusher(pusherKey, {
                        cluster: pusherCluster,
                    });
                    const channel = pusher.subscribe(`user-${currentUserId}`);

                    channel.bind('versus-invite', (payload) => {
                        showInviteModal(payload || {});
                    });
                    channel.bind('versus-invite-declined', (payload) => {
                        showDeclineToast(payload || {});
                    });
                    channel.bind('versus-invite-accepted', (payload) => {
                        if (payload?.redirect_url) {
                            window.location.href = payload.redirect_url;
                        }
                    });
                }
            })();
        </script>
    <?php endif; ?>
    <script>
        (() => {
            const applyThemeAwareWidgets = () => {
                const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
                const textColor = isDarkMode ? '#fff7e8' : '#26190f';
                const mutedColor = isDarkMode ? 'rgba(255, 247, 232, 0.62)' : 'rgba(38, 25, 15, 0.62)';
                const gridColor = isDarkMode ? 'rgba(255, 247, 232, 0.14)' : 'rgba(38, 25, 15, 0.12)';

                if (window.Chart) {
                    window.Chart.defaults.color = textColor;
                    window.Chart.defaults.borderColor = gridColor;
                    const instances = window.Chart.instances || {};
                    Object.keys(instances).forEach((key) => {
                        const chart = instances[key];
                        if (!chart?.options) {
                            return;
                        }

                        chart.options.color = textColor;
                        Object.values(chart.options.scales || {}).forEach((scale) => {
                            scale.ticks = { ...(scale.ticks || {}), color: mutedColor };
                            scale.grid = { ...(scale.grid || {}), color: gridColor };
                        });
                        chart.update('none');
                    });
                }

                if (window.lucide?.createIcons) {
                    window.lucide.createIcons();
                }
            };

            window.addEventListener('pixelwar:theme-change', applyThemeAwareWidgets);
            document.addEventListener('DOMContentLoaded', applyThemeAwareWidgets);
        })();
    </script>
</body>

</html>
