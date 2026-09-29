<?php
$accountReportUserId = max(0, (int) ($_GET['id'] ?? 0));
$accountReportStudent = $userRepository instanceof UserRepository
    ? $userRepository->findSessionUser($accountReportUserId)
    : null;

if ($accountReportStudent === null || (int) ($accountReportStudent['role_id'] ?? 0) !== 3) {
    http_response_code(404);
    require dirname(__DIR__, 2) . '/components/404.php';
    return;
}

$accountReportRootPrefix = '../';
$accountReportBackUrl = './?c=student-view&id=' . $accountReportUserId;
$accountReportBackLabel = 'Back to Student Profile';
require dirname(__DIR__, 2) . '/pages/account-report.php';
