<?php
$adminRoomViewId = max(0, (int) ($_GET['id'] ?? 0));
$adminRoomViewRoom = $roomRepository instanceof RoomRepository
    ? $roomRepository->findById($adminRoomViewId)
    : null;
$adminRoomTeacherId = (int) ($adminRoomViewRoom['user_id'] ?? 0);
$adminRoomPlayerStatusFilter = strtolower(trim((string) ($_GET['status'] ?? 'all')));
if (!in_array($adminRoomPlayerStatusFilter, ['all', 'waiting', 'solving', 'completed', 'failed'], true)) {
    $adminRoomPlayerStatusFilter = 'all';
}
$adminRoomPlayerSearch = trim((string) ($_GET['search'] ?? ''));
$adminRoomPlayerSearchNeedle = function_exists('mb_strtolower')
    ? mb_strtolower($adminRoomPlayerSearch, 'UTF-8')
    : strtolower($adminRoomPlayerSearch);

$formatTimestamp = static function (?string $value): string {
    if (!is_string($value) || trim($value) === '') {
        return 'Not set';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return 'Not set';
    }

    return date('M j, Y g:i A', $timestamp);
};

$formatDurationRange = static function (?string $startedAt, ?string $completedAt, string $statusLabel = ''): string {
    if (!is_string($startedAt) || trim($startedAt) === '') {
        return 'Not set';
    }

    if (!is_string($completedAt) || trim($completedAt) === '') {
        return $statusLabel !== '' ? $statusLabel : 'Not set';
    }

    $startedTs = strtotime($startedAt);
    $completedTs = strtotime($completedAt);

    if ($startedTs === false || $completedTs === false) {
        return 'Not set';
    }

    $seconds = max(0, $completedTs - $startedTs);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;
    $parts = [];

    if ($hours > 0) {
        $parts[] = $hours . 'h';
    }

    if ($minutes > 0) {
        $parts[] = $minutes . 'm';
    }

    if ($remainingSeconds > 0 || $parts === []) {
        $parts[] = $remainingSeconds . 's';
    }

    return implode(' ', $parts);
};

$adminRoomPlayerDisplayStatus = static function (array $player, bool $roomEnded = false, bool $strictModeEnabled = false): string {
    $baseStatus = adminPanelRoomPlayerStatusLabel($player, $roomEnded);

    if ($strictModeEnabled && $roomEnded) {
        $score = max(0, min(100, (int) ($player['strict_mode_score'] ?? 0)));
        return $score . '%';
    }

    return $baseStatus;
};

$adminRoomViewBuildQuery = static function (array $overrides = []) use ($adminRoomViewId, $adminRoomPlayerStatusFilter, $adminRoomPlayerSearch): string {
    $query = [
        'c' => 'room-view',
        'id' => $adminRoomViewId,
        'status' => $adminRoomPlayerStatusFilter,
        'search' => $adminRoomPlayerSearch,
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
            continue;
        }

        $query[$key] = $value;
    }

    return './?' . http_build_query($query);
};
?>

<main class="teacher-shell relative overflow-hidden px-4 py-6 text-arcade-ink md:py-8">
    <div class="teacher-bg absolute inset-0"></div>
    <section class="container relative grid gap-5">
        <?php if ($adminRoomViewRoom === null) : ?>
            <article class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f] md:p-6">
                <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-coral">Missing Room</p>
                <h1 class="mt-3 text-3xl font-black">Room not found.</h1>
                <p class="mt-2 text-sm font-bold leading-7 text-arcade-ink/62">The room may have been removed or the link is invalid.</p>
                <a href="./?c=teachers" class="teacher-button teacher-button--light mt-4 gap-2 no-underline">
                    <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Back to Teachers</span>
                </a>
            </article>
        <?php else : ?>
            <?php
            $difficulty = ucfirst(strtolower((string) ($adminRoomViewRoom['difficulty_name'] ?? 'Unknown')));
            $difficultyClass = 'challenge-difficulty--' . preg_replace('/[^a-z]+/', '', strtolower($difficulty));
            $author = trim((string) ($adminRoomViewRoom['teacher_firstname'] ?? '') . ' ' . (string) ($adminRoomViewRoom['teacher_lastname'] ?? ''))
                ?: (string) ($adminRoomViewRoom['teacher_username'] ?? 'Teacher');
            $strictModeEnabled = (int) ($adminRoomViewRoom['strict_mode'] ?? 0) === 1;
            $roomIsOpen = (int) ($adminRoomViewRoom['status'] ?? 1) === 1;
            $roomEnded = trim((string) ($adminRoomViewRoom['ended_at'] ?? '')) !== '';
            $roomCode = trim((string) ($adminRoomViewRoom['room_code'] ?? '')) ?: 'Not set';
            $roomDescription = trim((string) ($adminRoomViewRoom['room_description'] ?? ''));
            $htmlSourceUrl = (string) ($adminRoomViewRoom['html_source'] ?? '');
            $cssSourceUrl = (string) ($adminRoomViewRoom['css_source'] ?? '');
            $adminRoomPlayerRows = $roomPlayerRepository instanceof RoomPlayerRepository
                ? $roomPlayerRepository->listJoinedForRoom($adminRoomViewId)
                : [];
            $adminRoomPlayerRows = array_values(array_filter(
                $adminRoomPlayerRows,
                static function (array $row) use ($adminRoomPlayerStatusFilter, $adminRoomPlayerSearchNeedle, $roomEnded): bool {
                    $statusLabel = strtolower(adminPanelRoomPlayerStatusLabel($row, $roomEnded));

                    if ($adminRoomPlayerStatusFilter !== 'all' && $statusLabel !== $adminRoomPlayerStatusFilter) {
                        return false;
                    }

                    if ($adminRoomPlayerSearchNeedle === '') {
                        return true;
                    }

                    $fullName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? ''));
                    $haystack = implode(' ', [
                        $fullName,
                        (string) ($row['username'] ?? ''),
                        (string) ($row['email'] ?? ''),
                        (string) ($row['student_number'] ?? ''),
                        $statusLabel,
                    ]);
                    $haystack = function_exists('mb_strtolower')
                        ? mb_strtolower($haystack, 'UTF-8')
                        : strtolower($haystack);

                    return str_contains($haystack, $adminRoomPlayerSearchNeedle);
                }
            ));
            ?>

            <div class="admin-room-view-actions">
                <a href="./?c=teachers" class="teacher-button teacher-button--light gap-2 no-underline">
                    <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Back to Teachers</span>
                </a>
                <?php if ($adminRoomTeacherId > 0) : ?>
                    <a href="./?c=teacher-view&id=<?= (int) $adminRoomTeacherId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                        <i data-lucide="user-round-search" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Back to Teacher</span>
                    </a>
                    <a href="./?c=teacher-activity&id=<?= (int) $adminRoomTeacherId ?>&type=room" class="teacher-button teacher-button--light gap-2 no-underline">
                        <i data-lucide="history" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Back to Activity</span>
                    </a>
                <?php endif; ?>
            </div>

            <article class="teacher-hero rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f] md:p-6">
                <div class="admin-room-view-hero">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.26em] text-arcade-orange">Room Details</p>
                        <h1 class="mt-3 text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars((string) ($adminRoomViewRoom['room_name'] ?? 'Untitled Room'), ENT_QUOTES, 'UTF-8') ?></h1>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="teacher-pill bg-white"><?= htmlspecialchars($roomCode, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="teacher-pill <?= $roomIsOpen ? 'bg-arcade-mint/40' : 'bg-arcade-coral/25' ?>"><?= $roomIsOpen ? 'Open' : 'Closed' ?></span>
                            <span class="teacher-pill <?= $strictModeEnabled ? 'bg-arcade-coral/25' : 'bg-arcade-cyan/25' ?>"><?= $strictModeEnabled ? 'Strict' : 'Normal' ?></span>
                        </div>
                    </div>
                <div class="admin-room-view-hero__actions">
                    <button type="button" class="teacher-button teacher-button--primary gap-2" data-bs-toggle="modal" data-bs-target="#admin-room-challenge-modal">
                        <i data-lucide="square-arrow-out-up-right" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Open Challenge</span>
                        </button>
                    </div>
                </div>

                <div class="admin-room-view-stat-grid mt-5">
                    <article class="admin-room-view-stat-card">
                        <small>Teacher</small>
                        <strong><?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                    <article class="admin-room-view-stat-card">
                        <small>Challenge</small>
                        <strong><?= htmlspecialchars((string) ($adminRoomViewRoom['challenge_name'] ?? 'Unknown Challenge'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                    <article class="admin-room-view-stat-card">
                        <small>Timer</small>
                        <strong><?= (int) ($adminRoomViewRoom['timer_limit'] ?? 0) > 0 ? (int) ($adminRoomViewRoom['timer_limit'] ?? 0) . ' min' : 'No timer' ?></strong>
                    </article>
                    <article class="admin-room-view-stat-card">
                        <small>Mode</small>
                        <strong><?= $strictModeEnabled ? 'Strict' : 'Normal' ?></strong>
                    </article>
                    <article class="admin-room-view-stat-card">
                        <small>Created</small>
                        <strong><?= htmlspecialchars($formatTimestamp($adminRoomViewRoom['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                    <article class="admin-room-view-stat-card">
                        <small>Started</small>
                        <strong><?= htmlspecialchars($formatTimestamp($adminRoomViewRoom['started_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                    <article class="admin-room-view-stat-card">
                        <small>Ended</small>
                        <strong><?= htmlspecialchars($formatTimestamp($adminRoomViewRoom['ended_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                    <article class="admin-room-view-stat-card">
                        <small>Difficulty</small>
                        <strong class="challenge-difficulty <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8') ?></strong>
                    </article>
                </div>

                <?php if ($roomDescription !== '') : ?>
                    <article class="admin-room-view-section mt-5">
                        <p class="admin-room-view-section__eyebrow">Room Description</p>
                        <div class="admin-room-view-section__body text-sm font-bold leading-7 text-arcade-ink/70 md:text-base">
                            <?= $tools->formatRichText($roomDescription) ?>
                        </div>
                    </article>
                <?php endif; ?>
            </article>

            <section class="teacher-panel rounded-[26px] border-4 border-arcade-ink bg-arcade-panel p-5 shadow-[7px_7px_0_#26190f] md:p-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-cyan">Joined Players</p>
                        <h2 class="mt-2 text-2xl font-black">Room Players</h2>
                    </div>
                    <a href="<?= htmlspecialchars($adminRoomViewBuildQuery(['export' => 'csv']), ENT_QUOTES, 'UTF-8') ?>" class="teacher-button teacher-button--primary gap-2 no-underline">
                        <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </a>
                </div>

                <form method="get" action="./" class="admin-room-player-filter-grid mt-5">
                    <input type="hidden" name="c" value="room-view">
                    <input type="hidden" name="id" value="<?= (int) $adminRoomViewId ?>">
                    <div>
                        <label class="admin-room-player-filter-label" for="admin-room-player-search">Search</label>
                        <input
                            id="admin-room-player-search"
                            class="admin-room-player-filter-input"
                            type="search"
                            name="search"
                            value="<?= htmlspecialchars($adminRoomPlayerSearch, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Search player, username, email, or student ID"
                        >
                    </div>
                    <div>
                        <label class="admin-room-player-filter-label" for="admin-room-player-status">Filter</label>
                        <select id="admin-room-player-status" name="status" class="admin-room-player-filter-input">
                            <option value="all" <?= $adminRoomPlayerStatusFilter === 'all' ? 'selected' : '' ?>>All statuses</option>
                            <option value="waiting" <?= $adminRoomPlayerStatusFilter === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                            <option value="solving" <?= $adminRoomPlayerStatusFilter === 'solving' ? 'selected' : '' ?>>Solving</option>
                            <option value="completed" <?= $adminRoomPlayerStatusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="failed" <?= $adminRoomPlayerStatusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>
                    <div class="admin-room-player-filter-actions">
                        <button type="submit" class="teacher-button teacher-button--primary gap-2">
                            <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Apply</span>
                        </button>
                        <a href="./?c=room-view&id=<?= (int) $adminRoomViewId ?>" class="teacher-button teacher-button--light gap-2 no-underline">
                            <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                            <span>Reset</span>
                        </a>
                    </div>
                </form>

                <div class="mt-5 min-w-0 overflow-hidden rounded-2xl border border-arcade-ink/10 bg-white/80">
                    <?php if ($adminRoomPlayerRows === []) : ?>
                        <div class="px-4 py-5 text-sm font-medium text-arcade-ink/55">No joined player records matched this room view.</div>
                    <?php else : ?>
                        <div class="admin-room-player-table-wrap max-h-[38rem] overflow-x-auto overflow-y-auto">
                            <table class="min-w-[58rem] w-full text-left text-sm table-fixed">
                                <colgroup>
                                    <col class="w-[18%]">
                                    <col class="w-[15%]">
                                    <col class="w-[23%]">
                                    <col class="w-[12%]">
                                    <col class="w-[12%]">
                                    <col class="w-[20%]">
                                </colgroup>
                                <thead class="sticky top-0 bg-white/95">
                                    <tr class="border-b border-arcade-ink/10 text-xs uppercase tracking-[0.08em] text-arcade-ink/55">
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Player</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Username</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Email</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Student ID</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Status</th>
                                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($adminRoomPlayerRows as $playerRow) : ?>
                                        <?php
                                        $playerName = trim((string) ($playerRow['firstname'] ?? '') . ' ' . (string) ($playerRow['lastname'] ?? ''));
                                        $playerName = $playerName !== '' ? $playerName : (string) ($playerRow['username'] ?? 'Student');
                                        $statusLabel = $adminRoomPlayerDisplayStatus($playerRow, $roomEnded, $strictModeEnabled);
                                        $statusClass = match (true) {
                                            $strictModeEnabled && $roomEnded && $statusLabel === '100%' => 'bg-arcade-mint/35',
                                            $strictModeEnabled && $roomEnded && $statusLabel !== '0%' => 'bg-arcade-yellow/35',
                                            $statusLabel === 'Completed' => 'bg-arcade-mint/35',
                                            $statusLabel === 'Solving' => 'bg-arcade-yellow/35',
                                            $statusLabel === 'Failed' => 'bg-arcade-coral/25',
                                            default => 'bg-white',
                                        };
                                        $durationLabel = $formatDurationRange(
                                            $playerRow['started_at'] ?? null,
                                            $playerRow['completed_at'] ?? null,
                                            $statusLabel
                                        );
                                        ?>
                                        <tr class="border-b border-arcade-ink/10 align-top last:border-b-0">
                                            <td class="px-4 py-3 font-semibold text-arcade-ink break-words"><?= htmlspecialchars($playerName, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-4 py-3 font-medium text-arcade-ink/65 break-words">@<?= htmlspecialchars((string) ($playerRow['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-4 py-3 font-medium text-arcade-ink/65 break-words"><?= htmlspecialchars((string) ($playerRow['email'] ?? 'No email'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-4 py-3 font-medium text-arcade-ink/65 break-words"><?= htmlspecialchars((string) ($playerRow['student_number'] ?? 'Not set'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="px-4 py-3">
                                                <span class="teacher-pill <?= $statusClass ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td class="px-4 py-3 font-medium text-arcade-ink/65 break-words"><?= htmlspecialchars($durationLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>

<?php if ($adminRoomViewRoom !== null) : ?>
    <div class="modal fade" id="admin-room-challenge-modal" tabindex="-1" aria-labelledby="admin-room-challenge-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content admin-room-challenge-modal rounded-[28px] border-4 border-arcade-ink bg-arcade-panel text-arcade-ink shadow-[10px_10px_0_rgba(38,25,15,0.2)]">
                <div class="modal-header border-0 px-5 pb-2 pt-5">
                    <div>
                        <p class="font-arcade text-[10px] uppercase tracking-[0.24em] text-arcade-orange">Challenge Details</p>
                        <h2 class="modal-title mt-2 text-2xl font-black" id="admin-room-challenge-modal-title"><?= htmlspecialchars((string) ($adminRoomViewRoom['challenge_name'] ?? 'Unknown Challenge'), ENT_QUOTES, 'UTF-8') ?></h2>
                    </div>
                    <button type="button" class="btn-close opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-5 pb-5 pt-2">
                    <div class="admin-room-challenge-meta">
                        <span class="teacher-pill <?= htmlspecialchars($difficultyClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="teacher-pill bg-arcade-yellow/70"><?= (int) ($adminRoomViewRoom['points'] ?? 0) ?> pts</span>
                        <span class="teacher-pill bg-white">By <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="admin-room-challenge-layout mt-4">
                        <section class="admin-room-challenge-panel">
                            <p class="admin-room-view-section__eyebrow">Instruction</p>
                            <div class="admin-room-view-section__body text-sm font-bold leading-7 text-arcade-ink/70">
                                <?= $tools->formatRichText((string) ($adminRoomViewRoom['challenge_instruction'] ?? '')) ?>
                            </div>
                            <div class="admin-room-challenge-links mt-4">
                                <a href="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="teacher-mini-link">HTML Source</a>
                                <a href="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="teacher-mini-link">CSS Source</a>
                                <a href="./?c=challenge-view&id=<?= (int) ($adminRoomViewRoom['challenge_id'] ?? 0) ?>" class="teacher-mini-link">Open Full Challenge Page</a>
                            </div>
                        </section>

                        <section class="admin-room-challenge-panel">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="admin-room-view-section__eyebrow">Preview</p>
                                    <h3 class="mt-2 text-xl font-black">Target Design</h3>
                                </div>
                                <span id="admin-room-preview-status" class="teacher-pill bg-arcade-yellow">Loading</span>
                            </div>
                            <div class="admin-room-preview-frame mt-4">
                                <div id="admin-room-preview-loader" class="admin-room-preview-loader">
                                    <span class="admin-room-preview-loader__spinner" aria-hidden="true"></span>
                                    <strong>Loading preview...</strong>
                                    <small>Fetching challenge source files.</small>
                                </div>
                                <div class="admin-room-preview-stage">
                                    <iframe
                                        id="admin-room-source-preview"
                                        title="Admin room challenge preview"
                                        sandbox="allow-same-origin"
                                        data-html-source="<?= htmlspecialchars($htmlSourceUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        data-css-source="<?= htmlspecialchars($cssSourceUrl, ENT_QUOTES, 'UTF-8') ?>"></iframe>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.admin-room-view-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
}

.admin-room-player-table-wrap {
    width: 100%;
    max-width: 100%;
    overscroll-behavior-x: contain;
    -webkit-overflow-scrolling: touch;
}

.admin-room-player-filter-grid {
    display: grid;
    gap: 1rem;
}

.admin-room-player-filter-label {
    display: block;
    margin-bottom: 0.45rem;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.6);
}

.admin-room-player-filter-input {
    width: 100%;
    min-height: 3rem;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.96);
    padding: 0.8rem 0.95rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
}

.admin-room-player-filter-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: end;
}

.admin-room-view-hero {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.admin-room-view-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.admin-room-view-stat-grid {
    display: grid;
    gap: 0.85rem;
}

.admin-room-view-stat-card,
.admin-room-view-section,
.admin-room-challenge-panel {
    border: 2px solid rgba(38, 25, 15, 0.1);
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.84);
    padding: 1rem 1.05rem;
}

.admin-room-view-stat-card small,
.admin-room-view-section__eyebrow {
    display: block;
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(38, 25, 15, 0.56);
}

.admin-room-view-stat-card strong {
    display: block;
    margin-top: 0.45rem;
    font-size: 1rem;
    font-weight: 900;
    color: #26190f;
}

.admin-room-view-section__body {
    margin-top: 0.9rem;
}

.admin-room-challenge-meta,
.admin-room-challenge-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.admin-room-challenge-layout {
    display: grid;
    gap: 1rem;
}

.admin-room-preview-frame {
    position: relative;
    min-height: 28rem;
    overflow: hidden;
    border: 3px solid rgba(38, 25, 15, 0.12);
    border-radius: 1.5rem;
    background: linear-gradient(180deg, rgba(255, 247, 232, 0.96), rgba(255, 255, 255, 0.9));
}

.admin-room-preview-stage {
    position: absolute;
}

.admin-room-preview-frame iframe {
    position: absolute;
    border: 0;
    background: transparent;
}

.admin-room-preview-loader {
    position: absolute;
    inset: 0;
    z-index: 1;
    display: grid;
    place-items: center;
    align-content: center;
    gap: 0.65rem;
    text-align: center;
    padding: 1.5rem;
    color: #26190f;
}

.admin-room-preview-loader[hidden] {
    display: none;
}

.admin-room-preview-loader strong {
    font-size: 0.95rem;
    font-weight: 700;
}

.admin-room-preview-loader small {
    font-size: 0.78rem;
    color: rgba(38, 25, 15, 0.6);
}

.admin-room-preview-loader__spinner {
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 999px;
    border: 3px solid rgba(76, 201, 240, 0.2);
    border-top-color: #4cc9f0;
    animation: adminRoomPreviewSpin 0.8s linear infinite;
}

@keyframes adminRoomPreviewSpin {
    to { transform: rotate(360deg); }
}

body.pixelwar-dark-mode .admin-room-view-stat-card,
body.pixelwar-dark-mode .admin-room-view-section,
body.pixelwar-dark-mode .admin-room-challenge-panel {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(17, 24, 39, 0.72);
}

body.pixelwar-dark-mode .admin-room-view-stat-card strong,
body.pixelwar-dark-mode .admin-room-preview-loader {
    color: #f8fafc;
}

body.pixelwar-dark-mode .admin-room-view-stat-card small,
body.pixelwar-dark-mode .admin-room-view-section__eyebrow,
body.pixelwar-dark-mode .admin-room-preview-loader small,
body.pixelwar-dark-mode .admin-room-player-filter-label {
    color: rgba(248, 250, 252, 0.62);
}

body.pixelwar-dark-mode .admin-room-preview-frame {
    border-color: rgba(148, 163, 184, 0.18);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.94), rgba(17, 24, 39, 0.9));
}

body.pixelwar-dark-mode .admin-room-player-filter-input {
    border-color: rgba(148, 163, 184, 0.18);
    background: rgba(15, 23, 42, 0.92);
    color: #e2e8f0;
}

@media (min-width: 768px) {
    .admin-room-view-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-room-player-filter-grid {
        grid-template-columns: minmax(0, 1.35fr) minmax(16rem, 0.75fr) auto;
        align-items: end;
    }
}

@media (min-width: 1200px) {
    .admin-room-view-hero {
        flex-direction: row;
        align-items: end;
        justify-content: space-between;
    }

    .admin-room-challenge-layout {
        grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);
    }
}

@media (max-width: 767.98px) {
    .admin-room-player-table-wrap table {
        min-width: 54rem;
    }
}
</style>

<script>
window.addEventListener('load', () => {
    window.lucide?.createIcons();

    const preview = document.getElementById('admin-room-source-preview');
    const status = document.getElementById('admin-room-preview-status');
    const loader = document.getElementById('admin-room-preview-loader');
    const challengeModal = document.getElementById('admin-room-challenge-modal');

    if (!preview || !status) {
        return;
    }

    const disablePreviewLinks = (frame) => {
        if (!(frame instanceof HTMLIFrameElement)) {
            return;
        }

        const doc = frame.contentDocument;
        if (!doc) {
            return;
        }

        if (!doc.getElementById('pixelwar-preview-link-guard')) {
            const style = doc.createElement('style');
            style.id = 'pixelwar-preview-link-guard';
            style.textContent = 'a, area { cursor: default !important; }';
            doc.head?.appendChild(style);
        }

        doc.querySelectorAll('a, area').forEach((link) => {
            link.setAttribute('tabindex', '-1');
            link.setAttribute('aria-disabled', 'true');
        });

        if (doc.defaultView?.pixelwarPreviewLinksBlocked) {
            return;
        }

        doc.defaultView.pixelwarPreviewLinksBlocked = true;
        doc.addEventListener('click', (event) => {
            if (event.target?.closest?.('a, area')) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);
        doc.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && event.target?.closest?.('a, area')) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);
    };

    const fitPreviewFrame = (frame) => {
        if (!(frame instanceof HTMLIFrameElement)) {
            return;
        }

        const doc = frame.contentDocument;
        const body = doc?.body;
        const html = doc?.documentElement;
        const stage = frame.parentElement;
        if (!doc || !body || !html || !(stage instanceof HTMLElement)) {
            return;
        }

        const shell = stage.parentElement;
        if (!(shell instanceof HTMLElement)) {
            return;
        }

        const naturalWidth = Math.max(body.scrollWidth, body.offsetWidth, html.scrollWidth, html.offsetWidth, 1);
        const naturalHeight = Math.max(body.scrollHeight, body.offsetHeight, html.scrollHeight, html.offsetHeight, 1);
        const shellWidth = shell.clientWidth;
        const shellHeight = shell.clientHeight;
        if (shellWidth <= 0 || shellHeight <= 0) {
            return;
        }

        const scale = Math.min(1, shellWidth / naturalWidth, shellHeight / naturalHeight);
        const scaledWidth = naturalWidth * scale;
        const scaledHeight = naturalHeight * scale;
        stage.style.width = `${Math.ceil(scaledWidth)}px`;
        stage.style.height = `${Math.ceil(scaledHeight)}px`;
        stage.style.left = `${Math.max(0, (shellWidth - scaledWidth) / 2)}px`;
        stage.style.top = `${Math.max(0, (shellHeight - scaledHeight) / 2)}px`;
        frame.style.width = `${naturalWidth}px`;
        frame.style.height = `${naturalHeight}px`;
        frame.style.maxWidth = 'none';
        frame.style.maxHeight = 'none';
        frame.style.left = '0';
        frame.style.top = '0';
        frame.style.transform = `scale(${scale})`;
        frame.style.transformOrigin = 'top left';
    };

    preview.addEventListener('load', () => {
        disablePreviewLinks(preview);
        fitPreviewFrame(preview);
        status.textContent = 'Ready';
        status.classList.remove('bg-arcade-coral');
        status.classList.add('bg-arcade-cyan');
        if (loader) {
            loader.hidden = true;
        }
    });

    const buildPreviewDocument = (html, css) => `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #fff7e8; width: max-content; height: max-content; }
body { display: inline-block; font-family: Arial, sans-serif; }
a, area { cursor: default !important; }
.preview-canvas { display: inline-block; padding: 24px; }
${css}
</style>
</head>
<body>
<div class="preview-canvas">${html}</div>
</body>
</html>`;

    Promise.all([
        fetch(preview.dataset.htmlSource || '').then((response) => response.ok ? response.text() : Promise.reject(new Error('HTML source unavailable'))),
        fetch(preview.dataset.cssSource || '').then((response) => response.ok ? response.text() : Promise.reject(new Error('CSS source unavailable'))),
    ])
        .then(([html, css]) => {
            preview.srcdoc = buildPreviewDocument(html, css);
        })
        .catch(() => {
            status.textContent = 'Unavailable';
            status.classList.remove('bg-arcade-yellow');
            status.classList.add('bg-arcade-coral');
            if (loader) {
                loader.querySelector('strong')?.replaceChildren(document.createTextNode('Preview unavailable'));
                loader.querySelector('small')?.replaceChildren(document.createTextNode('The source files could not be loaded right now.'));
            }
        });

    if (challengeModal) {
        challengeModal.addEventListener('shown.bs.modal', () => {
            window.requestAnimationFrame(() => {
                fitPreviewFrame(preview);
            });
        });
    }

    window.addEventListener('resize', () => fitPreviewFrame(preview));
});
</script>
