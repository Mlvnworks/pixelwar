<?php
require __DIR__ . '/auth/00_teacher_auth_helpers.php';

$teacherEndpointFiles = glob(__DIR__ . '/auth/*.endpoint.php') ?: [];
sort($teacherEndpointFiles, SORT_NATURAL);

foreach ($teacherEndpointFiles as $teacherEndpointFile) {
    require $teacherEndpointFile;
}
