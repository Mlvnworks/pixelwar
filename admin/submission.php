<?php
$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/vendor/autoload.php')) {
    require_once $rootPath . '/vendor/autoload.php';
}

require_once $rootPath . '/classes/page-meta.php';
require_once $rootPath . '/classes/tools.php';
require_once $rootPath . '/classes/challenge-catalog.php';
require_once $rootPath . '/classes/challenge-repository.php';
require_once $rootPath . '/classes/activity-log-repository.php';
require_once $rootPath . '/classes/teacher-account-service.php';
require_once $rootPath . '/classes/user-repository.php';
require_once $rootPath . '/classes/user-challenge-repository.php';
require_once $rootPath . '/classes/verification-repository.php';
require_once $rootPath . '/classes/user-account-service.php';

$pageMeta = new PageMeta();
$tools = new Tools($connection);
$activityLogRepository = $connection instanceof mysqli ? new ActivityLogRepository($connection) : null;
$challengeRepository = $connection instanceof mysqli ? new ChallengeRepository($connection) : null;
$userRepository = $connection instanceof mysqli ? new UserRepository($connection) : null;
$userChallengeRepository = $connection instanceof mysqli ? new UserChallengeRepository($connection) : null;
$teacherAccountService = $connection instanceof mysqli && $userRepository instanceof UserRepository
    ? new TeacherAccountService($connection, $userRepository, $tools)
    : null;
$verificationRepository = $connection instanceof mysqli ? new VerificationRepository($connection) : null;
$userAccountService = $connection instanceof mysqli && $userRepository instanceof UserRepository && $verificationRepository instanceof VerificationRepository
    ? new UserAccountService($connection, $userRepository, $verificationRepository)
    : null;

$submissionFiles = glob(__DIR__ . '/submissions/*.php') ?: [];
sort($submissionFiles);

foreach ($submissionFiles as $submissionFile) {
    require $submissionFile;
}
