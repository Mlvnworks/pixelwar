<?php

$teacherChallengeEndpointFiles = glob(__DIR__ . '/challenges/*.endpoint.php') ?: [];
sort($teacherChallengeEndpointFiles, SORT_NATURAL);

foreach ($teacherChallengeEndpointFiles as $teacherChallengeEndpointFile) {
    require $teacherChallengeEndpointFile;
}
