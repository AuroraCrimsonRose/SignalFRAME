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

$user = $_SESSION['user'];

// Load all stations from folder
$stations = array_map('basename', glob(__DIR__ . '/../stations/*', GLOB_ONLYDIR));

// Get selected station from URL, fallback to first station or null
$currentStation = $_GET['station'] ?? ($stations[0] ?? null);

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SignalFrame Admin Panel</title>
  <style>
    body {
      font-family: sans-serif;
      background: #111;
      color: #eee;
      margin: 0; padding: 0;
      min-height: 100vh;
    }
    header {
      background: #222;
      padding: 1rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    label {
      margin-right: 0.5rem;
      font-weight: bold;
    }
    select, button {
      padding: 0.4rem 0.7rem;
      font-size: 1rem;
      border-radius: 4px;
      border: none;
      background: #444;
      color: #eee;
      cursor: pointer;
    }
    nav {
      position: relative;
      display: inline-block;
    }
    nav > button {
      background: #444;
    }
    nav ul {
      display: none;
      position: absolute;
      right: 0;
      background: #222;
      list-style: none;
      padding: 0;
      margin: 0;
      border-radius: 4px;
      min-width: 180px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.8);
    }
    nav ul li {
      border-bottom: 1px solid #333;
    }
    nav ul li:last-child {
      border-bottom: none;
    }
    nav ul li a {
      display: block;
      color: #eee;
      padding: 0.5rem 1rem;
      text-decoration: none;
    }
    nav ul li a:hover {
      background: #555;
    }
    nav.open ul {
      display: block;
    }
    main {
      padding: 2rem;
      max-width: 1200px;
      margin: auto;
    }
    .iframe-container {
      margin-bottom: 2rem;
    }
    iframe {
      width: 100%;
      height: 350px;
      border: none;
      background: #000;
      border-radius: 6px;
      box-shadow: 0 0 12px rgba(0,0,0,0.6);
    }
  </style>
  <script>
    function toggleSettingsDropdown() {
      document.getElementById('settings-nav').classList.toggle('open');
    }
    function onStationChange(sel) {
      const station = sel.value;
      const params = new URLSearchParams(window.location.search);
      params.set('station', station);
      window.location.search = params.toString();
    }
    document.addEventListener('click', e => {
      const nav = document.getElementById('settings-nav');
      if (!nav.contains(e.target)) {
        nav.classList.remove('open');
      }
    });
  </script>
</head>
<body>

<header>
  <div>
    <label for="station-select">Select Station:</label>
    <select id="station-select" onchange="onStationChange(this)">
      <?php foreach ($stations as $station): ?>
        <option value="<?= htmlspecialchars($station) ?>" <?= $station === $currentStation ? 'selected' : '' ?>>
          <?= htmlspecialchars(ucwords(str_replace('_', ' ', $station))) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <nav id="settings-nav">
    <button onclick="toggleSettingsDropdown()">Settings ▼</button>
    <ul>
      <li><a href="/admin/token-manager.php">Tokens</a></li>
      <li><a href="/admin/user-manager.php">User Manager</a></li>
      <li><a href="/admin/settings.php">Settings</a></li>
      <li><a href="/admin/station-manager.php">Station Manager</a></li>
      <li><a href="/admin/system-logs.php">Logs</a></li>
      <li><a href="/admin/account.php">Account</a></li>
      <li><a href="/admin/logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<main>
  <?php if ($currentStation === null): ?>
    <p>No stations found. Please add a station folder.</p>
  <?php else: ?>
    <section class="iframe-container">
      <h2>Settings for <?= htmlspecialchars(ucwords(str_replace('_', ' ', $currentStation))) ?></h2>
      <iframe src="/admin/templates/station-settings.php?station=<?= urlencode($currentStation) ?>"></iframe>
    </section>

    <section class="iframe-container">
      <h2>Logs for <?= htmlspecialchars(ucwords(str_replace('_', ' ', $currentStation))) ?></h2>
      <iframe src="/admin/templates/log-viewer.php?station=<?= urlencode($currentStation) ?>"></iframe>
    </section>
  <?php endif; ?>
</main>

</body>
</html>
