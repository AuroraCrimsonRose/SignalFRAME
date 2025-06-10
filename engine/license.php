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

function getLicenseConfigPath() {
    return __DIR__ . '/../config/retail-config.json';
}

function getLicenseCachePath() {
    return __DIR__ . '/../config/license-cache.json';
}

function getLocalLicenseKey() {
    $configPath = getLicenseConfigPath();
    if (!file_exists($configPath)) return null;
    $data = json_decode(file_get_contents($configPath), true);
    return $data['license_key'] ?? null;
}

function verifyLicenseKeyRemote($key) {
    $url = 'https://catalystslabs.com/SignalFrame/admin/license-check.php'; // <-- updated URL
    $data = ['license_key' => $key];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'timeout' => 5
        ]
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        return ['status' => 'error', 'message' => 'Could not contact license server.'];
    }

    $response = json_decode($result, true);
    return $response;
}

function checkAndCacheLicense() {
    $key = getLocalLicenseKey();
    if (!$key) return ['status' => 'invalid', 'message' => 'No license key found.'];

    $result = verifyLicenseKeyRemote($key);

    // Cache result
    file_put_contents(getLicenseCachePath(), json_encode([
        'checked_at' => date('c'),
        'result' => $result
    ], JSON_PRETTY_PRINT));

    return $result;
}

function getCachedLicenseStatus() {
    $cachePath = getLicenseCachePath();
    if (!file_exists($cachePath)) return null;
    $cache = json_decode(file_get_contents($cachePath), true);
    return $cache['result'] ?? null;
}

function requireValidLicense() {
    $status = getCachedLicenseStatus();
    if (!$status || $status['status'] !== 'valid') {
        http_response_code(403);
        echo "<h1>License Invalid</h1><p>This installation is not licensed. Please check your license key.</p>";
        exit;
    }
}

// Load and store license globally for easy access
$LICENSE = loadLicenseConfig();
