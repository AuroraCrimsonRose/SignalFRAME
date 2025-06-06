<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Adjusted path to where db.php actually lives:
require_once __DIR__ . '/../engine/db.php';
$pdo = getDbConnection();

$userId = $_SESSION['user']['id'];
$stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ?');
$stmt->execute([$userId]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);

$username = htmlspecialchars($userRow['username']);
$email    = strtolower(trim($userRow['email']));
$hash     = md5($email);
$pfpUrl   = "https://www.gravatar.com/avatar/{$hash}?s=32&d=identicon";
?>

<div class="header">
  <div class="header-left">
    <a href="/admin/index.php" class="logo">SignalFrame Admin</a>
  </div>

  <div class="header-right">
    <div class="user-info">
      <a href="/admin/account.php">
        <img src="<?php echo $pfpUrl; ?>" alt="Profile Picture" class="avatar">
      </a>
      <span><?php echo $username; ?></span>
    </div>
    <button class="hamburger" onclick="toggleMenu()">☰</button>
  </div>
</div>

<div class="nav-menu" id="nav-menu">
  <a href="/admin/index.php">Home</a>
  <a href="/admin/account.php">Account</a>
  <a href="/admin/logout.php">Logout</a>
</div>
