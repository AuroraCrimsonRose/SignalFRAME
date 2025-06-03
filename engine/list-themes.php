<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// /engine/list-themes.php

function listAvailableThemes($stationPath = null) {
    $themes = [];

    // First: global themes
    $globalThemePath = __DIR__ . '/../themes/';
    if (is_dir($globalThemePath)) {
        foreach (scandir($globalThemePath) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            $manifest = "$globalThemePath$dir/theme-manifest.json";
            if (file_exists($manifest)) {
                $themeData = json_decode(file_get_contents($manifest), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $themeData['source'] = 'global';
                    $themes[] = $themeData;
                }
            }
        }
    }

    // Then: station-local themes (override)
    if ($stationPath) {
        $localThemePath = "$stationPath/themes/";
        if (is_dir($localThemePath)) {
            foreach (scandir($localThemePath) as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                $manifest = "$localThemePath$dir/theme-manifest.json";
                if (file_exists($manifest)) {
                    $themeData = json_decode(file_get_contents($manifest), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $themeData['source'] = 'local';
                        $themes[] = $themeData;
                    }
                }
            }
        }
    }

    return $themes;
}

// Example usage:
// $themes = listAvailableThemes(__DIR__ . '/../stations/example_station/');
// header('Content-Type: application/json');
// echo json_encode($themes, JSON_PRETTY_PRINT);
