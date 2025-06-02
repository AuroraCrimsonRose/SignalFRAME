<?php
// /admin/templates/station-settings.php

require_once __DIR__ . '/../../engine/list-themes.php';
require_once __DIR__ . '/../../engine/log-message.php';

$station = $_GET['station'] ?? 'example_station';
$stationPath = __DIR__ . "/../../stations/$station/";
$configPath = $stationPath . "config.json";
$messageFile = $stationPath . "message.txt";

// Load current config and message
$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$currentTheme = $config['theme'] ?? '';
$currentMessage = file_exists($messageFile) ? file_get_contents($messageFile) : "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['theme'])) {
    $config['theme'] = $_POST['theme'];
    file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));
  }
  if (isset($_POST['message'])) {
    $newMessage = trim($_POST['message']);
    $newMessage = strip_tags($newMessage, '<b><i><u><em><strong>');
    file_put_contents($messageFile, $newMessage);
    logMessageChange($station, $newMessage);
    $currentMessage = $newMessage;
  }
  $saved = true;
}

$themes = listAvailableThemes($stationPath);
?>

<h3>Settings for <?= htmlspecialchars($station) ?></h3>
<form method="POST">
  <label for="theme">Select Theme:</label><br>
  <select name="theme" id="theme">
    <?php foreach ($themes as $theme): ?>
      <option value="<?= htmlspecialchars($theme['id']) ?>" <?= $theme['id'] === $currentTheme ? 'selected' : '' ?>>
        <?= htmlspecialchars($theme['name']) ?> (<?= $theme['source'] ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <br><br>

  <label for="message">Station Message:</label><br>
  <textarea id="message" name="message" rows="4" style="width: 100%;"><?= htmlspecialchars($currentMessage) ?></textarea>
  <br><br>

  <button type="submit">Save Settings</button>
  <?php if (!empty($saved)): ?>
    <p style="color: limegreen;">Settings updated successfully!</p>
  <?php endif; ?>
</form>
