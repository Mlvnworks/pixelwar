<?php
$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/vendor/autoload.php')) {
    require_once $rootPath . '/vendor/autoload.php';
}

require_once $rootPath . '/classes/page-meta.php';
require_once $rootPath . '/classes/tools.php';
require_once $rootPath . '/classes/challenge-catalog.php';
require_once $rootPath . '/classes/user-repository.php';
require_once $rootPath . '/classes/verification-repository.php';
require_once $rootPath . '/classes/user-account-service.php';

$pageMeta = new PageMeta();
$tools = new Tools($connection);
$userRepository = $connection instanceof mysqli ? new UserRepository($connection) : null;
$verificationRepository = $connection instanceof mysqli ? new VerificationRepository($connection) : null;
$userAccountService = $connection instanceof mysqli && $userRepository instanceof UserRepository && $verificationRepository instanceof VerificationRepository
    ? new UserAccountService($connection, $userRepository, $verificationRepository)
    : null;

$submissionFiles = glob(__DIR__ . '/submissions/*.php') ?: [];
sort($submissionFiles);

foreach ($submissionFiles as $submissionFile) {
    require $submissionFile;
}
