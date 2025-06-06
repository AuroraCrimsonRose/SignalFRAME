<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns an array of alert strings (e.g. missing config.json, flash messages, etc.).
 */

// Only start the session if one isn’t already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAlerts(): array
{
    $alerts      = [];
    $stationsDir = __DIR__ . '/../stations';
    $allDirs     = glob($stationsDir . '/*', GLOB_ONLYDIR);

    foreach ($allDirs as $dir) {
        if (basename($dir) === 'disabled') {
            continue;
        }
        if (!file_exists($dir . '/config.json')) {
            $alerts[] = basename($dir) . " is missing config.json";
        }
    }

    if (!empty($_SESSION['flash'])) {
        $alerts[] = $_SESSION['flash'];
        unset($_SESSION['flash']);
    }

    return $alerts;
}
