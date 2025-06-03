<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// engine/license.php

function loadLicenseConfig($file = __DIR__ . '/../config/retail-config.json') {
    if (!file_exists($file)) {
        die("License configuration file missing at $file. Please contact support.");
    }

    $json = file_get_contents($file);
    $config = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Invalid license configuration format in $file.");
    }

    return $config;
}

function checkModuleEnabled($moduleName, $license) {
    return in_array($moduleName, $license['enabled_modules']);
}

function isCommercialEdition($license) {
    return ($license['edition'] === 'commercial');
}

// Load and store license globally for easy access
$LICENSE = loadLicenseConfig();
