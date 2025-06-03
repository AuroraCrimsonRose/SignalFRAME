<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

// Require logged-in admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$usersDbFile = __DIR__ . '/../config/users.sqlite';
$stationsPath = __DIR__ . '/../stations';

$errors = [];
$success = false;

// Get list of stations (folder names)
$stations = array_map('basename', glob($stationsPath . '/*', GLOB_ONLYDIR));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'dj';
    $stationId = $_POST['station_id'] ?? null;

    // Validation
    if (!preg_match('/^[a-zA-Z0-9_\-]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters, alphanumeric, underscore or dash.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $passwordConfirm) {
        $errors[] = "Passwords do not match.";
    }
    $validRoles = ['dj', 'webmaster', 'station_manager', 'admin'];
    if (!in_array($role, $validRoles, true)) {
        $errors[] = "Invalid role selected.";
    }
    // station_id must be null for admin or one of existing stations for others
    if ($role !== 'admin') {
        if (!in_array($stationId, $stations, true)) {
            $errors[] = "Invalid or missing station for role $role.";
        }
    } else {
        $stationId = null; // admins have no station
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO('sqlite:' . $usersDbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if username exists
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Username already exists.";
            } else {
                // Insert new user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role, station_id) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, $hash, $role, $stationId]);
                $success = true;
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Create New User - SignalFrame Admin</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 480px; margin: 2rem auto; padding: 1rem; background: #111; color: #eee; }
    label { display: block; margin-top: 1rem; font-weight: bold; }
    input[type=text], input[type=password], select { width: 100%; padding: 0.5rem; font-size: 1rem; }
    button { margin-top: 1.5rem; padding: 0.75rem 1.5rem; font-size: 1rem; cursor: pointer; }
    .error { background: #fdd; padding: 1rem; border: 1px solid #f99; margin-bottom: 1rem; }
    .success { background: #dfd; padding: 1rem; border: 1px solid #9f9; margin-bottom: 1rem; }
</style>
<script>
  function onRoleChange() {
    const role = document.getElementById('role').value;
    const stationSelect = document.getElementById('station_id');
    if (role === 'admin') {
      stationSelect.disabled = true;
      stationSelect.value = '';
    } else {
      stationSelect.disabled = false;
    }
  }
</script>
</head>
<body>

<h1>Create New User</h1>

<?php if ($success): ?>
    <div class="success">
        User <strong><?= htmlspecialchars($username) ?></strong> created successfully!<br />
        <a href="user-create.php">Create another user</a> | <a href="index.php">Back to Admin Panel</a>
    </div>
<?php else: ?>
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
        <input type="text" id="username" name="username" required autofocus />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />

        <label for="password_confirm">Confirm Password</label>
        <input type="password" id="password_confirm" name="password_confirm" required />

        <label for="role">Role</label>
        <select id="role" name="role" onchange="onRoleChange()">
            <option value="dj" selected>DJ</option>
            <option value="webmaster">Webmaster</option>
            <option value="station_manager">Station Manager</option>
            <option value="admin">Admin</option>
        </select>

        <label for="station_id">Assigned Station</label>
        <select id="station_id" name="station_id" required>
            <option value="">-- Select Station --</option>
            <?php foreach ($stations as $station): ?>
                <option value="<?= htmlspecialchars($station) ?>"><?= htmlspecialchars($station) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Create User</button>
    </form>

    <script>onRoleChange();</script>
<?php endif; ?>

</body>
</html>
