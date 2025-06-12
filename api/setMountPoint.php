<?php
$xmlFile = realpath(__DIR__ . '/../icecast-config/icecast.xml');
$liqFile = realpath(__DIR__ . '/../liquidsoap-config/radio.liq');

function addIcecastMounts($baseMount) {
    if (empty($baseMount)) return [];
    global $xmlFile;
    if (!$xmlFile || !file_exists($xmlFile)) {
        error_log("Icecast config file not found: $xmlFile");
        return [];
    }
    $xml = file_get_contents($xmlFile);

    $baseMount = '/' . ltrim((string)$baseMount, '/');
    $baseMount = rtrim($baseMount, '/');
    $mounts = [
        $baseMount,
        $baseMount . 'low',
        $baseMount . 'medium',
        $baseMount . 'high'
    ];

    $added = [];
    foreach ($mounts as $mount) {
        if (strpos($xml, "<mount-name>$mount</mount-name>") === false) {
            $mountBlock = "  <mount>\n    <mount-name>$mount</mount-name>\n    <max-listeners>100</max-listeners>\n  </mount>\n";
            $xml = preg_replace('/<\/icecast>/', $mountBlock . '</icecast>', $xml, 1);
            $added[] = $mount;
        }
    }
    if (file_put_contents($xmlFile, $xml) === false) {
        error_log("Failed to write to $xmlFile");
    }
    return $added;
}

function removeIcecastMounts($baseMount) {
    if (empty($baseMount)) return [];
    global $xmlFile;
    if (!$xmlFile || !file_exists($xmlFile)) return [];
    $xml = file_get_contents($xmlFile);

    $baseMount = '/' . ltrim((string)$baseMount, '/');
    $baseMount = rtrim($baseMount, '/');
    $mounts = [
        $baseMount,
        $baseMount . 'low',
        $baseMount . 'medium',
        $baseMount . 'high'
    ];

    $removed = [];
    foreach ($mounts as $mount) {
        if (strpos($xml, "<mount-name>$mount</mount-name>") !== false) {
            $xml = preg_replace(
                '#\s*<mount>\s*<mount-name>' . preg_quote($mount, '#') . '</mount-name>.*?</mount>\s*#s',
                '',
                $xml
            );
            $removed[] = $mount;
        }
    }
    file_put_contents($xmlFile, $xml);
    return $removed;
}

function addLiquidsoapOutput($slug, $mountpoint) {
    if (empty($slug) || empty($mountpoint)) return false;
    global $liqFile;
    if (!$liqFile || !file_exists($liqFile)) return false;
    $liq = file_get_contents($liqFile);

    // Ensure trailing slash for playlist directory
    $musicDir = "/stations/$slug/music/";
    // Use fallback to default radio for reliability
    $block = "\n# --- $slug ---\n" .
        "radio_$slug = fallback([playlist(mode=\"random\", \"$musicDir\"), radio])\n" .
        "output.icecast(%mp3(bitrate=64), host=\"icecast\", port=8000, password=\"SignalFRAME\", mount=\"$mountpoint" . "low\", name=\"$slug (Low)\", description=\"$slug (Low)\", radio_$slug)\n" .
        "output.icecast(%mp3(bitrate=128), host=\"icecast\", port=8000, password=\"SignalFRAME\", mount=\"$mountpoint" . "medium\", name=\"$slug (Medium)\", description=\"$slug (Medium)\", radio_$slug)\n" .
        "output.icecast(%mp3(bitrate=128), host=\"icecast\", port=8000, password=\"SignalFRAME\", mount=\"$mountpoint\", name=\"$slug\", description=\"$slug\", radio_$slug)\n" .
        "output.icecast(%mp3(bitrate=192), host=\"icecast\", port=8000, password=\"SignalFRAME\", mount=\"$mountpoint" . "high\", name=\"$slug (High)\", description=\"$slug (High)\", radio_$slug)\n";

    // Prevent duplicate
    if (strpos($liq, "# --- $slug ---") === false) {
        file_put_contents($liqFile, $liq . $block);
        return true;
    }
    return false;
}

function removeLiquidsoapOutput($slug) {
    if (empty($slug)) return false;
    global $liqFile;
    if (!$liqFile || !file_exists($liqFile)) return false;
    $liq = file_get_contents($liqFile);

    // Remove the block for this slug
    $liq = preg_replace('/\n# --- ' . preg_quote($slug, '/') . ' ---.*?(?=\n# --- |\z)/s', '', $liq);
    file_put_contents($liqFile, $liq);
    return true;
}

$mountpoint = $cfg['mountpoint'] ?? '';
if (!empty($mountpoint)) {
    removeIcecastMounts($mountpoint);
    $addedMounts = addIcecastMounts($mountpoint);
    $liqAdded = addLiquidsoapOutput($slug, $mountpoint);
}

shell_exec('start /B "" "F:\\Users\\Aurora\\Documents\\GitHub\\SignalFRAME\\restart-containers.bat"');