<?php
$reportUserId = isset($accountReportUserId)
    ? max(0, (int) $accountReportUserId)
    : (int) ($_SESSION['user_id'] ?? 0);
$accountReportRootPrefix = isset($accountReportRootPrefix) ? (string) $accountReportRootPrefix : './';
$accountReportBackUrl = isset($accountReportBackUrl) ? (string) $accountReportBackUrl : './?c=settings';
$accountReportBackLabel = isset($accountReportBackLabel) ? (string) $accountReportBackLabel : 'Back to Settings';
$reportProfile = [];
$reportCompletedChallenges = 0;
$reportTotalPoints = 0;
$reportModeTotals = ['solo' => 0, 'pvp' => 0, 'room' => 0];
$reportMonthlyRows = [];
$reportSeasonProgress = [];
$reportHighestRank = [
    'name' => 'Unranked',
    'season' => 'No season record',
    'points' => 0,
    'requirement' => 0,
];
$reportBestLeaderboard = [
    'position' => null,
    'season' => 'No season record',
    'points' => 0,
];

if ($reportUserId > 0 && isset($connection) && $connection instanceof mysqli) {
    $profileStatement = $connection->prepare(
        'SELECT
            users.user_id,
            users.username,
            users.email,
            users.acc_type,
            users.is_verified,
            users.is_active,
            users.last_seen_at,
            users.registration_date,
            user_details.firstname,
            user_details.lastname,
            user_details.student_number,
            user_details.section,
            images.source AS avatar_url
         FROM users
         LEFT JOIN user_details ON user_details.user_id = users.user_id
         LEFT JOIN images ON images.img_id = user_details.image_id
         WHERE users.user_id = ?
            AND users.date_deleted IS NULL
         LIMIT 1'
    );
    $profileStatement->bind_param('i', $reportUserId);
    $profileStatement->execute();
    $reportProfile = $profileStatement->get_result()->fetch_assoc() ?: [];
    $profileStatement->close();

    $reportCompletedChallenges = $userChallengeRepository instanceof UserChallengeRepository
        ? $userChallengeRepository->countCompletedForUser($reportUserId)
        : 0;
    $reportTotalPoints = $userRepository instanceof UserRepository
        ? $userRepository->totalPlayerProgressPointsForUser($reportUserId)
        : 0;

    $modeStatement = $connection->prepare(
        'SELECT
            DATE_FORMAT(completed_at, "%Y-%m") AS month_key,
            SUM(CASE WHEN pvp_id IS NULL AND room_id IS NULL THEN 1 ELSE 0 END) AS solo_total,
            SUM(CASE WHEN pvp_id IS NOT NULL THEN 1 ELSE 0 END) AS pvp_total,
            SUM(CASE WHEN pvp_id IS NULL AND room_id IS NOT NULL THEN 1 ELSE 0 END) AS room_total
         FROM user_challenge
         WHERE user_id = ?
            AND completed_at IS NOT NULL
         GROUP BY DATE_FORMAT(completed_at, "%Y-%m")
         ORDER BY month_key ASC'
    );
    $modeStatement->bind_param('i', $reportUserId);
    $modeStatement->execute();
    $reportMonthlyRows = $modeStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $modeStatement->close();

    foreach ($reportMonthlyRows as $modeRow) {
        $reportModeTotals['solo'] += (int) ($modeRow['solo_total'] ?? 0);
        $reportModeTotals['pvp'] += (int) ($modeRow['pvp_total'] ?? 0);
        $reportModeTotals['room'] += (int) ($modeRow['room_total'] ?? 0);
    }

    $seasonRows = $seasonRepository instanceof SeasonRepository ? $seasonRepository->listAll() : [];
    $seasonLookup = [];
    $firstSeasonId = 0;
    foreach ($seasonRows as $seasonRow) {
        $seasonId = (int) ($seasonRow['season_id'] ?? 0);
        if ($seasonId <= 0) {
            continue;
        }
        $seasonLookup[$seasonId] = $seasonRow;
        $firstSeasonId = $seasonId;
    }

    $progressStatement = $connection->prepare(
        'SELECT season_id, SUM(points) AS points
         FROM player_progress
         WHERE user_id = ?
         GROUP BY season_id'
    );
    $progressStatement->bind_param('i', $reportUserId);
    $progressStatement->execute();
    $progressRows = $progressStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $progressStatement->close();

    foreach ($progressRows as $progressRow) {
        $seasonId = $progressRow['season_id'] === null ? $firstSeasonId : (int) $progressRow['season_id'];
        $points = max(0, (int) ($progressRow['points'] ?? 0));
        if (!isset($reportSeasonProgress[$seasonId])) {
            $reportSeasonProgress[$seasonId] = 0;
        }
        $reportSeasonProgress[$seasonId] += $points;
    }

    $bestRankRequirement = -1;
    foreach ($reportSeasonProgress as $seasonId => $points) {
        $rankMeta = $rankRepository instanceof RankRepository
            ? $rankRepository->progressForPoints($points)
            : ['current_name' => 'Unranked', 'current_requirement' => 0];
        $rankRequirement = (int) ($rankMeta['current_requirement'] ?? 0);
        if ($rankRequirement > $bestRankRequirement || ($rankRequirement === $bestRankRequirement && $points > $reportHighestRank['points'])) {
            $bestRankRequirement = $rankRequirement;
            $reportHighestRank = [
                'name' => (string) ($rankMeta['current_name'] ?? 'Unranked'),
                'season' => (string) ($seasonLookup[$seasonId]['name'] ?? 'Unassigned Season'),
                'points' => $points,
                'requirement' => $rankRequirement,
            ];
        }

        if ($seasonId <= 0 || !($userRepository instanceof UserRepository)) {
            continue;
        }
        $seasonStanding = $userRepository->leaderboardRankForUserForSeason($reportUserId, $seasonId);
        if ($seasonStanding !== null) {
            $position = (int) ($seasonStanding['rank_position'] ?? 0);
            if ($position > 0 && ($reportBestLeaderboard['position'] === null || $position < $reportBestLeaderboard['position'])) {
                $reportBestLeaderboard = [
                    'position' => $position,
                    'season' => (string) ($seasonLookup[$seasonId]['name'] ?? 'Unassigned Season'),
                    'points' => (int) ($seasonStanding['points'] ?? $points),
                ];
            }
        }
    }
}

$reportFirstname = trim((string) ($reportProfile['firstname'] ?? $_SESSION['firstname'] ?? ''));
$reportLastname = trim((string) ($reportProfile['lastname'] ?? $_SESSION['lastname'] ?? ''));
$reportFullName = trim($reportFirstname . ' ' . $reportLastname);
$reportUsername = trim((string) ($reportProfile['username'] ?? $_SESSION['username'] ?? 'Player')) ?: 'Player';
$reportFullName = $reportFullName !== '' ? $reportFullName : $reportUsername;
$reportAvatarUrl = function_exists('pixelwarAvatarUrl')
    ? pixelwarAvatarUrl(trim((string) ($reportProfile['avatar_url'] ?? '')), 192)
    : trim((string) ($reportProfile['avatar_url'] ?? ''));
$reportInitials = strtoupper(substr($reportFirstname, 0, 1) . substr($reportLastname, 0, 1));
$reportInitials = $reportInitials !== '' ? $reportInitials : strtoupper(substr($reportUsername, 0, 2));
$reportCurrentRank = $rankRepository instanceof RankRepository
    ? $rankRepository->progressForPoints($reportTotalPoints)
    : ['current_name' => 'Unranked', 'badge_initial' => 'U'];
$reportCurrentRankName = trim((string) ($reportCurrentRank['current_name'] ?? 'Unranked')) ?: 'Unranked';
$reportCurrentRankInitial = strtoupper(substr(trim((string) ($reportCurrentRank['badge_initial'] ?? $reportCurrentRankName)), 0, 1)) ?: 'U';
$reportGeneratedAt = new DateTimeImmutable('now');
$reportFilenamePart = static function (string $value, string $fallback): string {
    $normalized = preg_replace('/[^a-z0-9]+/i', '_', trim($value));
    return trim((string) $normalized, '_') ?: $fallback;
};
$reportPdfFilename = sprintf(
    '%s_%s_%s-report.pdf',
    $reportFilenamePart($reportFirstname, 'Player'),
    $reportFilenamePart($reportLastname, 'Account'),
    $reportGeneratedAt->format('Ymd_His')
);

$chartLabels = [];
$chartSolo = [];
$chartPvp = [];
$chartRoom = [];
$monthlyLookup = [];
foreach ($reportMonthlyRows as $monthlyRow) {
    $monthlyLookup[(string) ($monthlyRow['month_key'] ?? '')] = $monthlyRow;
}

$firstMonthKey = $reportMonthlyRows !== [] ? (string) ($reportMonthlyRows[0]['month_key'] ?? '') : $reportGeneratedAt->format('Y-m');
try {
    $chartMonth = new DateTimeImmutable($firstMonthKey . '-01');
} catch (Throwable) {
    $chartMonth = new DateTimeImmutable('first day of this month');
}
$chartEndMonth = new DateTimeImmutable('first day of this month');
while ($chartMonth <= $chartEndMonth) {
    $monthKey = $chartMonth->format('Y-m');
    $monthRow = $monthlyLookup[$monthKey] ?? [];
    $chartLabels[] = $chartMonth->format('M Y');
    $chartSolo[] = (int) ($monthRow['solo_total'] ?? 0);
    $chartPvp[] = (int) ($monthRow['pvp_total'] ?? 0);
    $chartRoom[] = (int) ($monthRow['room_total'] ?? 0);
    $chartMonth = $chartMonth->modify('+1 month');
}

$formatReportDate = static function ($value, string $fallback = 'Not available'): string {
    $timestamp = strtotime((string) $value);
    return $timestamp !== false ? date('M j, Y g:i A', $timestamp) : $fallback;
};
?>

<main class="account-report-page px-4 py-7 text-arcade-ink md:py-10">
    <div class="account-report-bg" aria-hidden="true"></div>
    <div class="account-report-toolbar mx-auto flex max-w-[1120px] flex-wrap items-center justify-between gap-3">
        <a href="<?= htmlspecialchars($accountReportBackUrl, ENT_QUOTES, 'UTF-8') ?>" class="account-report-action account-report-action--light">&larr; <?= htmlspecialchars($accountReportBackLabel, ENT_QUOTES, 'UTF-8') ?></a>
        <button type="button" class="account-report-action account-report-action--print" data-print-account-report>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
            Print / Save PDF
        </button>
    </div>

    <div class="account-report-preview" data-account-report-preview>
    <article class="account-report-sheet" id="account-report-sheet">
        <header class="account-report-header">
            <div class="account-report-brand">
                <span class="account-report-logo"><img src="<?= htmlspecialchars($accountReportRootPrefix, ENT_QUOTES, 'UTF-8') ?>assets/img/pixelwar-braces-logo.svg" alt="PixelWar logo"></span>
                <div>
                    <p>Official Player Record</p>
                    <h1>PixelWar Account Report</h1>
                </div>
            </div>
            <div class="account-report-reference">
                <small>Generated <?= htmlspecialchars($reportGeneratedAt->format('M j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </header>

        <section class="account-report-profile">
            <div class="account-report-avatar" aria-hidden="true">
                <?php if ($reportAvatarUrl !== '') : ?>
                    <img src="<?= htmlspecialchars($reportAvatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="">
                <?php else : ?>
                    <span><?= htmlspecialchars($reportInitials, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <div class="account-report-player">
                <p>Player Account</p>
                <h2><?= htmlspecialchars($reportFullName, ENT_QUOTES, 'UTF-8') ?></h2>
                <span><?= htmlspecialchars($reportUsername, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="account-report-rank-badge" aria-label="Current rank: <?= htmlspecialchars($reportCurrentRankName, ENT_QUOTES, 'UTF-8') ?>">
                <span class="account-report-rank-badge__emblem" aria-hidden="true"><?= htmlspecialchars($reportCurrentRankInitial, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="account-report-rank-badge__copy">
                    <small>Current Rank</small>
                    <strong><?= htmlspecialchars($reportCurrentRankName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <b><?= (int) $reportTotalPoints ?> pts</b>
                </span>
            </div>
        </section>

        <section class="account-report-section">
            <div class="account-report-section-title">
                <span>01</span>
                <div><p>Account Intel</p><h2>Player information</h2></div>
            </div>
            <div class="account-report-info-grid">
                <div><span>User ID</span><strong><?= (int) $reportUserId ?></strong></div>
                <div><span>Username</span><strong><?= htmlspecialchars($reportUsername, ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div><span>Email Address</span><strong><?= htmlspecialchars((string) ($reportProfile['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div><span>Student Number</span><strong><?= htmlspecialchars(trim((string) ($reportProfile['student_number'] ?? '')) ?: 'Not assigned', ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div><span>Section</span><strong><?= htmlspecialchars(trim((string) ($reportProfile['section'] ?? '')) ?: 'Not assigned', ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div><span>Account Type</span><strong><?= htmlspecialchars(ucfirst((string) ($reportProfile['acc_type'] ?? 'manual')), ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div><span>Email Status</span><strong><?= (int) ($reportProfile['is_verified'] ?? 0) === 1 ? 'Verified' : 'Unverified' ?></strong></div>
                <div><span>Member Since</span><strong><?= htmlspecialchars($formatReportDate($reportProfile['registration_date'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div><span>Last Seen</span><strong><?= htmlspecialchars($formatReportDate($reportProfile['last_seen_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong></div>
            </div>
        </section>

        <section class="account-report-section">
            <div class="account-report-section-title">
                <span>02</span>
                <div><p>Career Snapshot</p><h2>All-time achievements</h2></div>
            </div>
            <div class="account-report-achievement-grid">
                <article class="account-report-stat account-report-stat--yellow">
                    <div class="account-report-stat__heading">
                        <span class="account-report-stat__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v1a4 4 0 0 0 4 4M17 6h3v1a4 4 0 0 1-4 4M12 13v4M8 21h8M9 17h6"/></svg></span>
                        <span class="account-report-stat__label">Completed Challenges</span>
                    </div>
                    <strong><?= (int) $reportCompletedChallenges ?></strong>
                    <small>Recorded finishes</small>
                </article>
                <article class="account-report-stat account-report-stat--cyan">
                    <div class="account-report-stat__heading">
                        <span class="account-report-stat__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m12 3 2.5 5.1 5.6.8-4.1 4 1 5.6-5-2.6-5 2.6 1-5.6-4.1-4 5.6-.8L12 3Z"/></svg></span>
                        <span class="account-report-stat__label">Total Game Points</span>
                    </div>
                    <strong><?= (int) $reportTotalPoints ?></strong>
                    <small>Across all seasons</small>
                </article>
                <article class="account-report-stat account-report-stat--orange">
                    <div class="account-report-stat__heading">
                        <span class="account-report-stat__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m12 3 7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                        <span class="account-report-stat__label">Highest Rank</span>
                    </div>
                    <strong><?= htmlspecialchars((string) $reportHighestRank['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars((string) $reportHighestRank['season'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $reportHighestRank['points'] ?> pts</small>
                </article>
                <article class="account-report-stat account-report-stat--mint">
                    <div class="account-report-stat__heading">
                        <span class="account-report-stat__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M4 19h16M6 19v-5h4v5M10 19V9h4v10M14 19V4h4v15"/></svg></span>
                        <span class="account-report-stat__label">Best Leaderboard</span>
                    </div>
                    <strong><?= $reportBestLeaderboard['position'] !== null ? '#' . (int) $reportBestLeaderboard['position'] : 'N/A' ?></strong>
                    <small><?= htmlspecialchars((string) $reportBestLeaderboard['season'], ENT_QUOTES, 'UTF-8') ?><?= $reportBestLeaderboard['position'] !== null ? ' · ' . (int) $reportBestLeaderboard['points'] . ' pts' : '' ?></small>
                </article>
            </div>
        </section>

        <section class="account-report-section account-report-chart-section">
            <div class="account-report-section-title account-report-section-title--chart">
                <span>03</span>
                <div><p>Submission Pattern</p><h2>All-time game activity</h2></div>
                <div class="account-report-mode-summary">
                    <b><i class="is-solo"></i>Solo Practice <?= (int) $reportModeTotals['solo'] ?></b>
                    <b><i class="is-pvp"></i>1v1 <?= (int) $reportModeTotals['pvp'] ?></b>
                    <b><i class="is-room"></i>Room <?= (int) $reportModeTotals['room'] ?></b>
                </div>
            </div>
            <div class="account-report-chart-wrap">
                <canvas id="account-report-chart" width="1020" height="310" aria-label="All-time submission pattern by game mode"></canvas>
            </div>
        </section>

        <footer class="account-report-footer">
            <p>This report is generated from the player records stored by PixelWar.</p>
            <strong><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?> · Learn CSS Through Play</strong>
        </footer>
    </article>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.0/dist/chart.umd.min.js"></script>
<script>
(() => {
    const printButton = document.querySelector('[data-print-account-report]');
    const reportPdfFilename = <?= json_encode($reportPdfFilename, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const reportPdfTitle = reportPdfFilename.replace(/\.pdf$/i, '');
    const originalDocumentTitle = document.title;
    const preparePdfFilename = () => {
        document.title = reportPdfTitle;
    };
    const restoreDocumentTitle = () => {
        document.title = originalDocumentTitle;
    };
    window.addEventListener('beforeprint', preparePdfFilename);
    window.addEventListener('afterprint', restoreDocumentTitle);

    const reportPreview = document.querySelector('[data-account-report-preview]');
    const reportSheet = document.getElementById('account-report-sheet');
    const sizeReportPreview = () => {
        if (!(reportPreview instanceof HTMLElement) || !(reportSheet instanceof HTMLElement)) {
            return;
        }

        const availableWidth = Math.max(280, document.documentElement.clientWidth - 32);
        const scale = Math.min(1, availableWidth / 1120);
        reportPreview.style.setProperty('--account-report-scale', scale.toFixed(5));
        reportPreview.style.width = `${1120 * scale}px`;
        reportPreview.style.height = `${1400 * scale}px`;
    };

    sizeReportPreview();
    window.addEventListener('resize', sizeReportPreview, { passive: true });

    const canvas = document.getElementById('account-report-chart');
    if (!(canvas instanceof HTMLCanvasElement) || typeof window.Chart === 'undefined') {
        printButton?.addEventListener('click', () => window.print());
        return;
    }

    const isDarkMode = document.body.classList.contains('pixelwar-dark-mode');
    const reportChart = new window.Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                { label: 'Solo Practice', data: <?= json_encode($chartSolo) ?>, borderColor: '#ff8c42', backgroundColor: 'rgba(255,140,66,.14)', pointBackgroundColor: '#ffd166' },
                { label: '1v1', data: <?= json_encode($chartPvp) ?>, borderColor: '#4cc9f0', backgroundColor: 'rgba(76,201,240,.14)', pointBackgroundColor: '#4cc9f0' },
                { label: 'Room', data: <?= json_encode($chartRoom) ?>, borderColor: '#2f9e88', backgroundColor: 'rgba(139,211,199,.16)', pointBackgroundColor: '#8bd3c7' },
            ].map((dataset) => ({ ...dataset, borderWidth: 3, pointRadius: 3, pointHoverRadius: 6, pointBorderWidth: 2, pointBorderColor: '#26190f', tension: .34, fill: false })),
        },
        options: {
            responsive: false,
            maintainAspectRatio: true,
            animation: false,
            layout: { padding: { left: 4, right: 4, bottom: 14 } },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#26190f', titleColor: '#fff7e8', bodyColor: '#fff7e8', padding: 12, cornerRadius: 10 },
            },
            scales: {
                x: { ticks: { color: isDarkMode ? '#fff7e8' : '#5b4637', padding: 8, font: { weight: '700' }, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0, color: isDarkMode ? '#fff7e8' : '#5b4637', font: { weight: '700' } }, grid: { color: isDarkMode ? 'rgba(255,247,232,.1)' : 'rgba(38,25,15,.09)' } },
            },
        },
    });

    printButton?.addEventListener('click', () => {
        reportChart.update('none');
        window.requestAnimationFrame(() => window.print());
    });
})();
</script>
