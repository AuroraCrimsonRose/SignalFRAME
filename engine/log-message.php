<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// /engine/log-message.php

function logMessageChange($station, $newMessage, $user = 'admin') {
    $logDir = __DIR__ . '/../logs/messages';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $stationLogFile = "$logDir/" . basename($station) . ".log";
    $timestamp = date('Y-m-d H:i:s');

    $entry = "[$timestamp] by [$user]:\n" . str_replace("\n", " ", $newMessage) . "\n\n";
    file_put_contents($stationLogFile, $entry, FILE_APPEND);
}
