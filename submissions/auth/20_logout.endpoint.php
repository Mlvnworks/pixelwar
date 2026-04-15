<?php
if ($requestedPage === 'login' && isset($_GET['logout'])) {
    pixelwarRedirect('home');
}

if ($requestMethod === 'POST' && $requestedPage === 'logout') {
    if (!pixelwarValidateCsrf()) {
        pixelwarFailCsrf('home');
    }

    pixelwarLogout();
    pixelwarRedirect('landing');
}
