<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// /stations/example_station/index.php

require_once __DIR__ . '/../../engine/license.php';

$stationPath = __DIR__;
$stationName = basename($stationPath);

$configFile = "$stationPath/config.json";
$messageFile = "$stationPath/message.txt";

// Load config
if (!file_exists($configFile)) {
  die("Missing config.json for $stationName.");
}
$config = json_decode(file_get_contents($configFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
  die("Invalid config.json format.");
}

// Load station message
$message = file_exists($messageFile) ? trim(file_get_contents($messageFile)) : "";

// Determine theme path with fallback
$activeTheme = $config['theme'] ?? 'default';
$localThemePath = "$stationPath/themes/$activeTheme/style.css";
$globalThemePath = "/themes/$activeTheme/style.css";
$themeCss = file_exists($localThemePath) ? "themes/$activeTheme/style.css" : $globalThemePath;

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($config['title'] ?? $stationName) ?></title>
  <link rel="stylesheet" href="<?= $themeCss ?>">
</head>
<body>
  <header>
    <h1><?= htmlspecialchars($config['title'] ?? $stationName) ?></h1>
    <?php if ($LICENSE['branding']): ?>
      <p class="branding">Powered by Catalyst Labs – Personal Edition</p>
    <?php endif; ?>
  </header>

  <main>
    <audio controls autoplay>
      <source src="<?= htmlspecialchars($config['stream_url'] ?? '') ?>" type="audio/mpeg">
      Your browser does not support the audio element.
    </audio>
    <p><?= nl2br(htmlspecialchars($message)) ?></p>
  </main>

  <footer>
    <p>&copy; 2025 <?= htmlspecialchars($stationName) ?> Station</p>
  </footer>
</body>
</html>
