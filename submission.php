<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/classes/page-meta.php';
require_once __DIR__ . '/classes/tools.php';
require_once __DIR__ . '/classes/teacher-account-service.php';
require_once __DIR__ . '/classes/user-repository.php';
require_once __DIR__ . '/classes/verification-repository.php';
require_once __DIR__ . '/classes/user-account-service.php';
require_once __DIR__ . '/classes/challenge-repository.php';
require_once __DIR__ . '/classes/user-challenge-repository.php';
require_once __DIR__ . '/classes/activity-log-repository.php';
require_once __DIR__ . '/classes/gameplay-completion-service.php';

// ==================== INITIALIZATION ====================
$pageMeta = new PageMeta();
$tools = new Tools($connection);
$userRepository = $connection instanceof mysqli ? new UserRepository($connection) : null;
$teacherAccountService = $connection instanceof mysqli && $userRepository instanceof UserRepository
    ? new TeacherAccountService($connection, $userRepository, $tools)
    : null;
$verificationRepository = $connection instanceof mysqli ? new VerificationRepository($connection) : null;
$userAccountService = $connection instanceof mysqli && $userRepository instanceof UserRepository && $verificationRepository instanceof VerificationRepository
    ? new UserAccountService($connection, $userRepository, $verificationRepository)
    : null;
$challengeRepository = $connection instanceof mysqli ? new ChallengeRepository($connection) : null;
$userChallengeRepository = $connection instanceof mysqli ? new UserChallengeRepository($connection) : null;
$activityLogRepository = $connection instanceof mysqli ? new ActivityLogRepository($connection) : null;
$gameplayCompletionService = $connection instanceof mysqli
    && $userChallengeRepository instanceof UserChallengeRepository
    && $challengeRepository instanceof ChallengeRepository
    && $activityLogRepository instanceof ActivityLogRepository
        ? new GameplayCompletionService($connection, $userChallengeRepository, $challengeRepository, $activityLogRepository)
        : null;

// ==================== SUBMISSION HANDLERS ====================
$submissionFiles = glob(__DIR__ . '/submissions/*.php') ?: [];
sort($submissionFiles);

foreach ($submissionFiles as $submissionFile) {
    require $submissionFile;
}
