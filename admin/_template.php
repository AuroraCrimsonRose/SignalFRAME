<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: /admin/login.php");
    exit;
}

$alerts = [];
if (!empty($_SESSION['flash'])) {
    $alerts[] = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($pageTitle ?? 'SignalFrame Admin') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/admin/assets/admin-style.css" />
</head>
<body>

<?php include __DIR__ . '/_nav.php'; ?>

<div class="dashboard">
  <?php if (!empty($alerts)): ?>
    <div class="alert-banner">
      <?php foreach ($alerts as $alert): ?>
        <div class="alert-item">⚠️ <?= htmlspecialchars($alert) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <main>
    <?= $pageContent ?? '' ?>
  </main>
</div>

<footer class="branding-footer fixed-footer">
  Powered by <strong>SignalFrame</strong> — a CATALYSTS LABS project
</footer>

<script>
function toggleMenu() {
  const menu = document.getElementById('nav-menu');
  menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
}
window.addEventListener('click', function(e) {
  const menu = document.getElementById('nav-menu');
  const button = document.querySelector('.hamburger');
  if (!menu.contains(e.target) && !button.contains(e.target)) {
    menu.style.display = 'none';
  }
});
</script>
</body>
</html>
