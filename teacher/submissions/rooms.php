<?php
$teacherRoomEndpointFiles = glob(__DIR__ . '/rooms/*.endpoint.php') ?: [];
sort($teacherRoomEndpointFiles, SORT_NATURAL);

foreach ($teacherRoomEndpointFiles as $teacherRoomEndpointFile) {
    require $teacherRoomEndpointFile;
}
