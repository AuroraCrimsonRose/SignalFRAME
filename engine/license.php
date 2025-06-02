<?php
// engine/license.php

function loadLicenseConfig($file = __DIR__ . '/../license.json') {
    if (!file_exists($file)) {
        die("License file missing. Please contact support.");
    }

    $json = file_get_contents($file);
    $config = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Invalid license configuration.");
    }

    return $config;
}

function checkModuleEnabled($moduleName, $license) {
    return in_array($moduleName, $license['enabled_modules']);
}

function isCommercialEdition($license) {
    return ($license['edition'] === 'commercial');
}

// Load and store license globally
$LICENSE = loadLicenseConfig();
?>
