<?php
require __DIR__ . '/auth/00_admin_auth_helpers.php';

$adminEndpointFiles = glob(__DIR__ . '/auth/*.endpoint.php') ?: [];
sort($adminEndpointFiles, SORT_NATURAL);

foreach ($adminEndpointFiles as $adminEndpointFile) {
    require $adminEndpointFile;
}
