<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$usersDbFile = __DIR__ . '/../config/users.sqlite';
$stationsPath = __DIR__ . '/../stations';

$pdo = new PDO('sqlite:' . $usersDbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = (int)($_GET['id'] ?? 0);
$loggedInUserId = $_SESSION['user']['id'];

$errors = [];
$success = false;

$stations = array_map('basename', glob($stationsPath . '/*', GLOB_ONLYDIR));
$validRoles = ['dj', 'webmaster', 'station_manager', 'admin'];

// Load user data
$stmt = $pdo->prepare('SELECT id, username, role, station_id FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $stationId = $_POST['station_id'] ?? $user['station_id'];
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Basic validations
    if (!preg_match('/^[a-zA-Z0-9_\-]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters, alphanumeric, underscore or dash.";
    }

    if (!in_array($role, $validRoles, true)) {
        $errors[] = "Invalid role selected.";
    }

    if ($role !== 'admin' && !in_array($stationId, $stations, true)) {
        $errors[] = "Invalid or missing station for role $role.";
    }
    if ($role === 'admin') {
        $stationId = null;
    }

    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }
        if ($password !== $passwordConfirm) {
            $errors[] = "Passwords do not match.";
        }
    }

    // Prevent changing own role for safety
    if ($userId === $loggedInUserId && $role !== $user['role']) {
        $errors[] = "You cannot change your own role.";
    }

    if (empty($errors)) {
        // Update username, role, station
        $stmtUpdate = $pdo->prepare('UPDATE users SET username = ?, role = ?, station_id = ? WHERE id = ?');
        $stmtUpdate->execute([$username, $role, $stationId, $userId]);

        // Update password if provided
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtPass = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmtPass->execute([$hash, $userId]);
        }

        $success = true;

        // Reload user data
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit User - <?= htmlspecialchars($user['username']) ?></title>
<style>
  body { font-family: Arial, sans-serif; max-width: 480px; margin: 2rem auto; padding: 1rem; background: #111; color: #eee; }
  label { display: block; margin-top: 1rem; font-weight: bold; }
  input[type=text], input[type=password], select { width: 100%; padding: 0.5rem; font-size: 1rem; }
  button { margin-top: 1.5rem; padding: 0.75rem 1.5rem; font-size: 1rem; cursor: pointer; }
  .error { background: #fdd; padding: 1rem; border: 1px solid #f99; margin-bottom: 1rem; }
  .success { background: #dfd; padding: 1rem; border: 1px solid #9f9; margin-bottom: 1rem; }
  a { color: #88f; text-decoration: none; }
</style>
</head>
<body>

<h1>Edit User: <?= htmlspecialchars($user['username']) ?></h1>

<?php if ($success): ?>
  <div class="success">User updated successfully.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="error">
    <strong>Errors:</strong>
    <ul>
      <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="">
  <label for="username">Username</label>
  <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required autofocus />

  <label for="role">Role</label>
  <select id="role" name="role" onchange="toggleStationSelect()" required>
    <?php foreach ($validRoles as $r): ?>
      <option value="<?= $r ?>" <?= $r === $user['role'] ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $r)) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="station_id">Assigned Station</label>
  <select id="station_id" name="station_id" required>
    <option value="">-- Select Station --</option>
    <?php foreach ($stations as $station): ?>
      <option value="<?= htmlspecialchars($station) ?>" <?= $station === $user['station_id'] ? 'selected' : '' ?>><?= htmlspecialchars($station) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="password">New Password (leave blank to keep current)</label>
  <input type="password" id="password" name="password" />

  <label for="password_confirm">Confirm New Password</label>
  <input type="password" id="password_confirm" name="password_confirm" />

  <button type="submit">Save Changes</button>
</form>

<script>
  function toggleStationSelect() {
    const roleSelect = document.getElementById('role');
    const stationSelect = document.getElementById('station_id');
    if (roleSelect.value === 'admin') {
      stationSelect.disabled = true;
      stationSelect.value = '';
      stationSelect.required = false;
    } else {
      stationSelect.disabled = false;
      stationSelect.required = true;
    }
  }
  toggleStationSelect();
</script>

<p><a href="user-manager.php">← Back to User List</a></p>

</body>
</html>
