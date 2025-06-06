<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns decoded JSON from config/retail-config.json, or null if missing/invalid.
 */

function getLicenseData(): ?array
{
    $configFile = __DIR__ . '/../config/retail-config.json';
    if (!file_exists($configFile)) {
        return null;
    }
    $json = json_decode(file_get_contents($configFile), true);
    return is_array($json) ? $json : null;
}
