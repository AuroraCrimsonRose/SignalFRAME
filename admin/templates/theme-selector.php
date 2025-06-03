<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

<!-- /admin/templates/theme-selector.php -->

require_once __DIR__ . '/../../engine/list-themes.php';
$station = $_GET['station'] ?? 'example_station';
$themes = listAvailableThemes(__DIR__ . "/../../stations/$station");
$configPath = __DIR__ . "/../../stations/$station/config.json";
$config = json_decode(file_get_contents($configPath), true);
$currentTheme = $config['theme'] ?? '';
?>

<form id="theme-switcher">
  <label for="theme">Select Theme for <?= htmlspecialchars($station) ?>:</label>
  <select id="theme" name="theme">
    <?php foreach ($themes as $theme): ?>
      <option value="<?= htmlspecialchars($theme['id']) ?>" <?= $theme['id'] === $currentTheme ? 'selected' : '' ?>>
        <?= htmlspecialchars($theme['name']) ?> (<?= $theme['source'] ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <button type="submit">Apply Theme</button>
</form>

<script>
document.getElementById('theme-switcher').addEventListener('submit', async function (e) {
  e.preventDefault();
  const station = "<?= htmlspecialchars($station) ?>";
  const theme = document.getElementById('theme').value;

  const response = await fetch(`/api/update-theme.php?station=${station}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: `theme=${encodeURIComponent(theme)}`
  });

  const result = await response.json();
  if (result.success) {
    alert(`Theme changed to ${result.theme}`);
    location.reload();
  } else {
    alert(`Error: ${result.error}`);
  }
});
</script>
