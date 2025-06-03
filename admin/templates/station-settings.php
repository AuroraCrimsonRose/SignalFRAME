<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

$iframeApiToken = $_GET['api_token'] ?? '';
$iframeApiTokenJs = htmlspecialchars($iframeApiToken, ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/../../engine/list-themes.php';

$station = $_GET['station'] ?? 'example_station';
$stationPath = __DIR__ . "/../stations/$station/";
$configPath = $stationPath . "config.json";
$messageFile = $stationPath . "message.txt";

// Load current config and message
$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$currentTheme = $config['theme'] ?? '';
$currentMessage = file_exists($messageFile) ? file_get_contents($messageFile) : "";

$themes = listAvailableThemes($stationPath);
?>

<script>
  window.SIGNALFRAME_API_TOKEN = "<?= $iframeApiTokenJs ?>";
</script>

<h3>Settings for <?= htmlspecialchars($station) ?></h3>

<form id="theme-form" class="ajax-form" action="/api/update-theme.php?station=<?= urlencode($station) ?>" method="POST">
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

<hr>

<form id="message-form" class="ajax-form" action="/api/update-message.php?station=<?= urlencode($station) ?>" method="POST">
  <label for="message">Station Message:</label><br>
  <textarea id="message" name="message" rows="4" style="width: 100%;"><?= htmlspecialchars($currentMessage) ?></textarea>
  <br><br>
  <button type="submit">Save Message</button>
  <div class="response-message" aria-live="polite"></div>
</form>

<style>
  .response-message {
    margin-top: 0.5rem;
    font-weight: bold;
  }
  .response-message.success {
    color: limegreen;
  }
  .response-message.error {
    color: #ff5555;
  }
</style>
