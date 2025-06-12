<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns an integer: total number of “active” stations (excludes “disabled”).
 */

function getStationCount(): int
{
    $stationsDir = __DIR__ . '/../stations';
    $allDirs     = glob($stationsDir . '/*', GLOB_ONLYDIR);
    $count       = 0;

    foreach ($allDirs as $dir) {
        if (basename($dir) !== 'disabled') {
            $count++;
        }
        else if (basename($dir) !== 'music') {
            $count++;
        }
    }

    return $count;
}
