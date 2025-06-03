<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Get API token from session user data
$apiToken = $_SESSION['user']['api_token'] ?? '';

// Sanitize token for JS and URL
$apiTokenJs = htmlspecialchars($apiToken, ENT_QUOTES, 'UTF-8');
$apiTokenUrl = urlencode($apiToken);

$stationDirs = glob(__DIR__ . '/../stations/*', GLOB_ONLYDIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>SignalFrame Admin Panel</title>
  <header style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1>SignalFrame Admin Panel</h1>
    <form action="/admin/logout.php" method="POST" style="margin:0;">
      <button type="submit" style="padding:0.5rem 1rem; font-size:1rem; cursor:pointer;">Logout</button>
    </form>
  </header>
  <style>
    body {
      font-family: sans-serif;
      background: #111;
      color: #eee;
      padding: 2rem;
    }
    h1 {
      text-align: center;
      margin-bottom: 2rem;
    }
    .station {
      background: #1e1e1e;
      padding: 1rem;
      margin: 1rem auto;
      max-width: 900px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    }
    iframe {
      width: 100%;
      height: 250px;
      border: none;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
  <h1>SignalFrame Admin Panel</h1>
  <?php foreach ($stationDirs as $dir): 
    $station = basename($dir);
  ?>
    <div class="station">
      <h2><?= htmlspecialchars(ucwords(str_replace('_', ' ', $station))) ?></h2>
      <iframe src="templates/station-settings.php?station=<?= urlencode($station) ?>&api_token=<?= $apiTokenUrl ?>"></iframe>
      <iframe src="templates/log-viewer.php?station=<?= urlencode($station) ?>&api_token=<?= $apiTokenUrl ?>"></iframe>
    </div>
  <?php endforeach; ?>

  <script>
    window.SIGNALFRAME_API_TOKEN = "<?= $apiTokenJs ?>";
  </script>
  <script src="/admin/assets/ajax-forms.js"></script>
</body>
</html>
