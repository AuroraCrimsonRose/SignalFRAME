<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

$iframeApiToken = $_GET['api_token'] ?? '';
$iframeApiTokenJs = htmlspecialchars($iframeApiToken, ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/../engine/list-themes.php';

$station = $_GET['station'] ?? 'example_station';
$stationPath = __DIR__ . "/../stations/$station/";
$configPath = $stationPath . "config.json";

$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$currentTheme = $config['theme'] ?? '';

$themes = listAvailableThemes($stationPath);
?>

<script>
  window.SIGNALFRAME_API_TOKEN = "<?= $iframeApiTokenJs ?>";
</script>

<form id="theme-selector-form" class="ajax-form" action="/api/update-theme.php?station=<?= urlencode($station) ?>" method="POST">
  <label for="theme">Select Theme:</label><br>
  <select name="theme" id="theme">
    <?php foreach ($themes as $theme): ?>
      <option value="<?= htmlspecialchars($theme['id']) ?>" <?= $theme['id'] === $currentTheme ? 'selected' : '' ?>>
        <?= htmlspecialchars($theme['name']) ?> (<?= $theme['source'] ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <br><br>
  <button type="submit">Save Theme</button>
  <div class="response-message" aria-live="polite"></div>
</form>
