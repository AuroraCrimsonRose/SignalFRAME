<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns an associative array of health‐check labels → values.
 */

function getHealthChecks(): array
{
    return [
        'PHP Version'    => phpversion(),
        'PDO Enabled'    => extension_loaded('pdo')        ? 'Yes' : 'No',
        'SQLite Support' => extension_loaded('pdo_sqlite') ? 'Yes' : 'No',
        'Disk Free'      => round(disk_free_space("/") / 1024 / 1024, 2) . ' MB',
    ];
}
