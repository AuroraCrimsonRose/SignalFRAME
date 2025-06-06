<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// 1) Start session and guard access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /admin/login.php');
    exit;
}

// 2) Page title
$pageTitle = "Dashboard";

// 3) Include API functions
require_once __DIR__ . '/../api/getAlerts.php';
require_once __DIR__ . '/../api/getStationCount.php';
require_once __DIR__ . '/../api/getUserCount.php';
require_once __DIR__ . '/../api/getTokenCount.php';
require_once __DIR__ . '/../api/getRecentLogs.php';
require_once __DIR__ . '/../api/getHealthChecks.php';
require_once __DIR__ . '/../api/getLicenseData.php';
require_once __DIR__ . '/../api/getNewUsers.php';
require_once __DIR__ . '/../api/getDiskRamUsage.php';

// 4) Fetch data via API calls
$alerts        = getAlerts();
$totalStations = getStationCount();
$totalUsers    = getUserCount();
$tokenCount    = getTokenCount();
$recentLogs    = getRecentLogs();
$health        = getHealthChecks();
$licenseData   = getLicenseData();
$newUsers      = getNewUsers();
$diskRam       = getDiskRamUsage();

// 5) Uptime (we can leave this inline, since it’s a simple shell_exec)
$uptime = shell_exec('uptime -p') ?: 'Unavailable';

// 6) Capture page content
ob_start();
?>

<?php if (!empty($alerts)): ?>
  <div class="alert-banner">
    <?php foreach ($alerts as $alert): ?>
      <div class="alert-item">⚠️ <?= htmlspecialchars($alert) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2>Welcome, <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Admin') ?> 👋</h2>
<p>Your role: <strong><?= htmlspecialchars($_SESSION['user']['role']) ?></strong></p>

<div class="quick-actions">
  <a href="/admin/user-manager.php"      class="dash-button">User Manager</a>
  <a href="/admin/station-manager.php"   class="dash-button">Station Manager</a>
  <a href="/admin/settings.php"          class="dash-button">Settings</a>
  <a href="/admin/system-logs.php"       class="dash-button">Logs</a>
  <a href="/admin/token-manager.php"     class="dash-button">Tokens</a>
</div>

<div class="stats-grid">
  <div class="stat-box"><h3>Total Users</h3><p><?= $totalUsers ?></p></div>
  <div class="stat-box"><h3>Total Stations</h3><p><?= $totalStations ?></p></div>
  <div class="stat-box"><h3>Active API Tokens</h3><p><?= $tokenCount ?></p></div>
  <div class="stat-box"><h3>PHP Version</h3><p><?= phpversion() ?></p></div>
  <div class="stat-box"><h3>Server Uptime</h3><p><?= htmlspecialchars($uptime) ?></p></div>
</div>

<div class="health-grid">
  <div class="stat-box">
    <h3>System Health</h3>
    <?php foreach ($health as $label => $value): ?>
      <p><strong><?= htmlspecialchars($label) ?>:</strong> <?= htmlspecialchars($value) ?></p>
    <?php endforeach; ?>
  </div>

  <?php if ($licenseData): ?>
    <div class="stat-box">
      <h3>License</h3>
      <p><strong>Edition:</strong> <?= htmlspecialchars(ucfirst($licenseData['edition'] ?? 'unknown')) ?></p>
      <p><strong>Modules:</strong> <?= htmlspecialchars(implode(', ', $licenseData['enabled_modules'] ?? [])) ?></p>
      <p><strong>Branding:</strong> <?= $licenseData['branding'] ? 'Enabled' : 'Disabled' ?></p>
      <p><strong>WHMCS:</strong> <?= $licenseData['whmcs_integration'] ? 'Yes' : 'No' ?></p>
    </div>
  <?php endif; ?>

  <div class="stat-box">
    <h3>Disk & RAM Usage</h3>
    <p>
      <strong>Disk Used:</strong>
      <?= $diskRam['disk_used_mb'] ?> MB
      / <?= $diskRam['disk_total_mb'] ?> MB
      (<?= $diskRam['disk_pct'] ?>%)
    </p>
    <?php if ($diskRam['mem_pct'] !== null): ?>
      <p>
        <strong>RAM Used:</strong>
        <?= round($diskRam['mem_used_mb'], 1) ?> MB
        / <?= round($diskRam['mem_total_mb'], 1) ?> MB
        (<?= $diskRam['mem_pct'] ?>%)
      </p>
    <?php else: ?>
      <p><em>RAM data unavailable</em></p>
    <?php endif; ?>
  </div>

  <!-- <div class="stat-box">
    <h3>New Station</h3>
    <p>Create a fresh station with one click:</p>
    <a href="/admin/create-station.php"
       style="display:inline-block;margin-top:8px;padding:10px 16px;
              background:#4caf50;color:#fff;border-radius:6px;
              text-decoration:none;font-weight:bold;">
      + New Station
    </a>
  </div> -->
</div>

<div class="stats-grid" style="margin-top: 20px;">
  <div class="stat-box" style="grid-column: span 2;">
    <h3>New Registrations</h3>
    <ul style="list-style:none;padding-left:0;margin:0;">
      <?php foreach ($newUsers as $u): ?>
        <li style="margin-bottom:6px;">
          <strong><?= htmlspecialchars($u['username']) ?></strong>
          &mdash; <?= htmlspecialchars(date('M j, Y', strtotime($u['created_at']))) ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <a href="/admin/user-manager.php"
       style="display:block;margin-top:8px;font-size:13px;color:#4caf50;
              text-decoration:none;">
      Manage Users →
    </a>
  </div>
</div>

<div class="recent-logs" style="margin-top:30px;">
  <h3>Recent Activity</h3>
  <ul style="list-style:none;padding-left:0;">
    <?php foreach ($recentLogs as $log): ?>
      <li style="font-size:14px;margin-bottom:6px;">
        <code><?= htmlspecialchars($log['timestamp']) ?></code> &mdash;
        <?= htmlspecialchars($log['message']) ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_template.php';
?>
