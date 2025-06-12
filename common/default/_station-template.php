<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 *
 * This template loads config.json dynamically so that changes to theme/display_name in
 * config.json (via the Edit modal) take effect immediately.
 */

// Determine path to this station’s directory
// __DIR__ here is /path/to/project/stations/{slug}
$stationDir = __DIR__;

// Path to config.json
$configPath = "$stationDir/config.json";

// Load config.json
if (!file_exists($configPath)) {
    // If config.json is missing, show an error
    echo "<pre style='color:red;'>Error: config.json not found.</pre>";
    exit;
}

$config = json_decode(file_get_contents($configPath), true);
if (!$config) {
    echo "<pre style='color:red;'>Error: Invalid config.json.</pre>";
    exit;
}

// Extract values, with defaults
$stationName = $config['display_name'] ?? 'Unnamed Station';
$theme       = $config['theme']        ?? 'light';
$mountpoint  = $config['mountpoint']   ?? '/live';
$enabled     = $config['enabled']      ?? false;

// If station is disabled, you could show a “Station disabled” message instead of the player
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($stationName); ?> — SignalFrame</title>

  <?php
    // Path to this theme’s manifest
    $manifestPath = "$stationDir/themes/{$theme}/theme-manifest.json";
    $manifest     = file_exists($manifestPath)
                    ? json_decode(file_get_contents($manifestPath), true)
                    : null;

    // Build CSS variable string from manifest colors
    $cssVars = '';
    if ($manifest && isset($manifest['colors'])) {
        foreach ($manifest['colors'] as $key => $val) {
            $cssVars .= "--{$key}: {$val}; ";
        }
    }
  ?>

  <?php if ($manifest && isset($manifest['description'])): ?>
    <meta name="description" content="<?php echo htmlspecialchars($manifest['description']); ?>" />
  <?php endif; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Link to the station’s current theme CSS (relative path) -->
  <link rel="stylesheet" href="themes/<?php echo htmlspecialchars($theme); ?>/style.css" />

  <!-- Inject CSS variables from manifest colors -->
  <?php if ($cssVars !== ''): ?>
    <style>:root { <?php echo $cssVars; ?> }</style>
  <?php endif; ?>
</head>
<body>

  <header class="header">
    <h1><?php echo htmlspecialchars($stationName); ?></h1>
    <?php if ($manifest && isset($manifest['type'])): ?>
      <small class="theme-info">
        Theme: <?php echo htmlspecialchars($manifest['name']); ?> (<?php echo htmlspecialchars($manifest['type']); ?>)
      </small>
    <?php endif; ?>
  </header>

  <main class="main-content">
    <?php if (!$enabled): ?>
      <div class="disabled-message">
        <p>This station is currently <strong>disabled</strong>.</p>
      </div>
    <?php else: ?>
      <div class="audio-widget">
        <h2>Listen Live</h2>

        <!-- Audio element (hidden native controls) -->
        <audio id="audio-player">
          <source id="source-low" src="/livelow" type="audio/mpeg" />
          <source id="source-medium" src="/livemedium" type="audio/mpeg" />
          <source id="source-high" src="/livehigh" type="audio/mpeg" />
          Your browser does not support the audio element.
        </audio>

        <button class="play-btn" id="play-btn">Play</button>

        <div class="quality-selector">
          <label for="quality-select">Stream Quality:</label><br/>
          <select id="quality-select">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>

        <div class="now-playing">
          Now Playing: <span id="np-track">Loading…</span>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <footer class="footer">
    <small>© <?php echo date('Y'); ?> SignalFrame — Powered by CATALYSTS LABS</small>
  </footer>

  <?php if ($manifest && !empty($manifest['hasVisualizer'])): ?>
    <script src="themes/<?php echo htmlspecialchars($theme); ?>/visualizer.js"></script>
  <?php endif; ?>

  <script>
    (function() {
      <?php if ($enabled): ?>
      const audio       = document.getElementById('audio-player');
      const playBtn     = document.getElementById('play-btn');
      const qualitySel  = document.getElementById('quality-select');
      const nowPlaying  = document.getElementById('np-track');
      const mount       = '<?php echo htmlspecialchars($mountpoint); ?>';

      function setDefaultQuality() {
        const isMobile = window.innerWidth < 768;
        qualitySel.value = isMobile ? 'low' : 'medium';
        updateAudioSource(qualitySel.value);
      }

      function updateAudioSource(q) {
        // Remove any trailing slash from mountpoint
        let base = mount.replace(/\/$/, '');
        let srcUrl = base + q; // e.g., "/live" + "low" = "/livelow"
        audio.src = srcUrl;
        if (!audio.paused) {
          audio.play().catch(() => {});
        }
      }

      function fetchNowPlaying() {
        // Replace with real API call to get current track if available
        setTimeout(() => {
          nowPlaying.textContent = 'Sample Track Title';
        }, 1000);
      }

      playBtn.addEventListener('click', () => {
        if (audio.paused) {
          audio.play().then(() => { playBtn.textContent = 'Pause'; }).catch(() => {});
        } else {
          audio.pause();
          playBtn.textContent = 'Play';
        }
      });

      qualitySel.addEventListener('change', (e) => {
        const wasPlaying = !audio.paused;
        updateAudioSource(e.target.value);
        if (wasPlaying) audio.play().catch(() => {});
      });

      window.addEventListener('load', () => {
        setDefaultQuality();
        fetchNowPlaying();
      });
      <?php endif; ?>
    })();
  </script>

</body>
</html>
