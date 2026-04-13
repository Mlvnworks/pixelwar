<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/classes/page-meta.php';
require_once __DIR__ . '/classes/tools.php';

// ==================== INITIALIZATION ====================
$pageMeta = new PageMeta();
$tools = new Tools($connection);

// ==================== SUBMISSION HANDLERS ====================
$submissionFiles = glob(__DIR__ . '/submissions/*.php') ?: [];
sort($submissionFiles);

foreach ($submissionFiles as $submissionFile) {
    require $submissionFile;
}
