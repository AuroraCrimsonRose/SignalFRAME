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
$stationDirs = glob(__DIR__ . '/../stations/*', GLOB_ONLYDIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SignalFrame Admin Panel</title>
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
      max-width: 800px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    }
    iframe {
      width: 100%;
      height: 400px;
      border: none;
    }
  </style>
</head>
<body>
  <p>Logged in as: <?= htmlspecialchars($_SESSION['user']) ?> | <a href="logout.php">Logout</a></p>
  <h1>SignalFrame Admin Panel</h1>
  <?php foreach ($stationDirs as $dir): 
    $station = basename($dir);
  ?>
    <div class="station">
      <h2><?= htmlspecialchars(ucwords(str_replace('_', ' ', $station))) ?></h2>
      <iframe src="templates/station-settings.php?station=<?= urlencode($station) ?>"></iframe>
    </div>
  <?php endforeach; ?>
</body>
</html>
