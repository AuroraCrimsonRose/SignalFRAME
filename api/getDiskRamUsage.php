<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns an associative array with:
 *  - disk_total_mb
 *  - disk_used_mb
 *  - disk_pct
 *  - (if proc/meminfo available) mem_total_mb, mem_used_mb, mem_pct
 */

function getDiskRamUsage(): array
{
    $totalSpace = disk_total_space("/");
    $freeSpace  = disk_free_space("/");
    $usedSpace  = $totalSpace - $freeSpace;
    $diskPct    = $totalSpace
                  ? round(($usedSpace / $totalSpace) * 100, 1)
                  : 0;

    // RAM
    $memInfo = @file_get_contents("/proc/meminfo");
    if ($memInfo !== false
        && preg_match('/MemTotal:\s+(\d+) kB/', $memInfo, $mTotal)
        && preg_match('/MemAvailable:\s+(\d+) kB/', $memInfo, $mAvail)
    ) {
        $memTotalMb = $mTotal[1] / 1024;
        $memAvailMb = $mAvail[1] / 1024;
        $memUsedMb  = $memTotalMb - $memAvailMb;
        $memPct     = round(($memUsedMb / $memTotalMb) * 100, 1);
    } else {
        $memTotalMb = null;
        $memUsedMb  = null;
        $memPct     = null;
    }

    return [
        'disk_total_mb' => round($totalSpace / 1024 / 1024, 1),
        'disk_used_mb'  => round($usedSpace  / 1024 / 1024, 1),
        'disk_pct'      => $diskPct,
        'mem_total_mb'  => $memTotalMb,
        'mem_used_mb'   => $memUsedMb,
        'mem_pct'       => $memPct,
    ];
}
