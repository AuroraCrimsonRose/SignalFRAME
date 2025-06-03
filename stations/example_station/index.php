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

// Bitrate options and default mount
$bitrateOptions = $config['bitrate_options'] ?? [];
$defaultMount = '';
foreach ($bitrateOptions as $opt) {
    if (!empty($opt['default'])) {
        $defaultMount = $opt['mount'];
        break;
    }
}
if (!$defaultMount && !empty($bitrateOptions)) {
    $defaultMount = $bitrateOptions[0]['mount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($config['title'] ?? $stationName) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($themeCss) ?>" />
  <style>
    #now-playing, #station-message {
      margin-top: 1rem;
      font-style: italic;
    }
    .branding {
      font-size: 0.9rem;
      opacity: 0.7;
    }
  </style>
</head>
<body>
  <header>
    <h1><?= htmlspecialchars($config['title'] ?? $stationName) ?></h1>
    <?php if ($LICENSE['branding']): ?>
      <p class="branding">Powered by Catalyst Labs – Personal Edition</p>
    <?php endif; ?>
  </header>

  <main>
    <label for="bitrate-select">Select Quality:</label>
    <select id="bitrate-select" aria-label="Select stream bitrate">
      <?php foreach ($bitrateOptions as $opt): ?>
        <option value="<?= htmlspecialchars($opt['mount']) ?>" <?= ($opt['mount'] === $defaultMount) ? 'selected' : '' ?>>
          <?= htmlspecialchars($opt['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <audio id="stream-player" controls autoplay>
      <source src="https://stream.catalystslabs.com<?= htmlspecialchars($defaultMount) ?>" type="audio/mpeg" />
      Your browser does not support the audio element.
    </audio>

    <div id="now-playing">Loading current track...</div>
    <div id="station-message"><?= nl2br(htmlspecialchars($message)) ?></div>
  </main>

  <footer>
    <p>&copy; 2025 <?= htmlspecialchars($stationName) ?> Station</p>
  </footer>

  <script>
    const player = document.getElementById('stream-player');
    const bitrateSelect = document.getElementById('bitrate-select');
    const nowPlayingDiv = document.getElementById('now-playing');
    const stationMessageDiv = document.getElementById('station-message');

    // Switch stream source on bitrate select change
    bitrateSelect.addEventListener('change', () => {
      const selectedMount = bitrateSelect.value;
      player.pause();
      player.src = 'https://stream.catalystslabs.com' + selectedMount;
      player.load();
      player.play();
      localStorage.setItem('preferred_bitrate', selectedMount);
    });

    // Load preferred bitrate from localStorage
    window.addEventListener('load', () => {
      const preferred = localStorage.getItem('preferred_bitrate');
      if (preferred && [...bitrateSelect.options].some(opt => opt.value === preferred)) {
        bitrateSelect.value = preferred;
        player.src = 'https://stream.catalystslabs.com' + preferred;
        player.load();
        player.play();
      }
    });

    // Fetch current track info every 10s
    async function fetchNowPlaying() {
      try {
        const res = await fetch('https://stream.catalystslabs.com/status-json.xsl');
        const data = await res.json();
        const mount = bitrateSelect.value;
        const sources = data.icestats.source;
        let nowPlaying = null;

        if (Array.isArray(sources)) {
          nowPlaying = sources.find(src => src.listenurl.includes(mount));
        } else if (sources.listenurl && sources.listenurl.includes(mount)) {
          nowPlaying = sources;
        }

        if (nowPlaying && nowPlaying.title) {
          nowPlayingDiv.textContent = 'Now Playing: ' + nowPlaying.title;
        } else {
          nowPlayingDiv.textContent = 'Now Playing: Not Available';
        }
      } catch (e) {
        nowPlayingDiv.textContent = 'Now Playing: Error fetching data';
        console.error(e);
      }
    }

    // Fetch station message every 30s
    async function fetchStationMessage() {
      try {
        const res = await fetch('message.txt?cache=' + Date.now());
        const text = await res.text();
        stationMessageDiv.innerHTML = text.replace(/\n/g, '<br>');
      } catch (e) {
        stationMessageDiv.textContent = 'Message: Not Available';
        console.error(e);
      }
    }

    // Initial fetch and intervals
    fetchNowPlaying();
    fetchStationMessage();
    setInterval(fetchNowPlaying, 10000);
    setInterval(fetchStationMessage, 30000);
  </script>
</body>
</html>
