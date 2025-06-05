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

try {
    $pdo = new PDO('sqlite:' . $usersDbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, username, role, station_id, created_at FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

$stations = array_map('basename', glob($stationsPath . '/*', GLOB_ONLYDIR));
$validRoles = ['dj', 'webmaster', 'station_manager', 'admin'];

$errors = [];
$success = null;

// Handle create user form submission (modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
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
    if (!in_array($role, $validRoles, true)) {
        $errors[] = "Invalid role selected.";
    }
    if ($role !== 'admin') {
        if (!in_array($stationId, $stations, true)) {
            $errors[] = "Invalid or missing station for role $role.";
        }
    } else {
        $stationId = null; // admins have no station
    }

    if (empty($errors)) {
        // Check username unique
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetchColumn() > 0) {
            $errors[] = "Username already exists.";
        } else {
            // Insert user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtInsert = $pdo->prepare('INSERT INTO users (username, password_hash, role, station_id) VALUES (?, ?, ?, ?)');
            $stmtInsert->execute([$username, $hash, $role, $stationId]);
            $success = "User <strong>" . htmlspecialchars($username) . "</strong> created successfully.";
            // Refresh users list after insert
            $stmt = $pdo->query("SELECT id, username, role, station_id, created_at FROM users ORDER BY username ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Manager - SignalFrame Admin</title>
<style>
  body {
    font-family: Arial, sans-serif;
    max-width: 900px;
    margin: 2rem auto;
    padding: 1rem;
    background: #111;
    color: #eee;
  }
  h1 {
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  a.button-primary {
    background-color: #4caf50;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
  }
  a.button-primary:hover {
    background-color: #45a049;
  }
  table {
    border-collapse: collapse;
    width: 100%;
  }
  th, td {
    border: 1px solid #444;
    padding: 0.6rem 1rem;
    text-align: left;
  }
  th {
    background: #222;
  }
  tr:nth-child(even) {
    background: #1a1a1a;
  }
  a.button {
    display: inline-block;
    padding: 0.3rem 0.8rem;
    background: #444;
    color: #eee;
    text-decoration: none;
    border-radius: 4px;
    margin-right: 0.3rem;
    font-size: 0.9rem;
  }
  a.button:hover {
    background: #666;
  }
  .flash-message {
    background-color: #4caf50;
    color: white;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }
  .error-message {
    background-color: #f44336;
    color: white;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
  }

  /* Modal Styles */
  #createUserModal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.8);
  }
  #createUserModal .modal-content {
    background-color: #222;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 8px;
    max-width: 480px;
    color: #eee;
    position: relative;
  }
  #createUserModal .close-btn {
    position: absolute;
    top: 10px; right: 15px;
    font-size: 28px;
    font-weight: bold;
    color: #fff;
    cursor: pointer;
  }
  #createUserModal label {
    display: block;
    margin-top: 1rem;
    font-weight: bold;
  }
  #createUserModal input[type=text],
  #createUserModal input[type=password],
  #createUserModal select {
    width: 100%;
    padding: 0.5rem;
    font-size: 1rem;
  }
  #createUserModal button[type=submit] {
    margin-top: 1.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    cursor: pointer;
    background-color: #4caf50;
    border: none;
    color: #fff;
    border-radius: 4px;
  }
  #createUserModal button[type=submit]:hover {
    background-color: #45a049;
  }
</style>
<script>
  function openCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'block';
  }
  function closeCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'none';
  }
  window.onclick = function(event) {
    const modal = document.getElementById('createUserModal');
    if (event.target === modal) {
      closeCreateUserModal();
    }
  };
  function onRoleChange() {
    const role = document.getElementById('role').value;
    const stationSelect = document.getElementById('station_id');
    if (role === 'admin') {
      stationSelect.disabled = true;
      stationSelect.value = '';
      stationSelect.required = false;
    } else {
      stationSelect.disabled = false;
      stationSelect.required = true;
    }
  }
</script>
</head>
<body>

<h1>
  User Manager
  <a class="button-primary" onclick="openCreateUserModal()">+ Create New User</a>
</h1>

<?php if ($success): ?>
  <div class="flash-message"><?= $success ?></div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="error-message">
    <ul>
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Username</th>
      <th>Role</th>
      <th>Station</th>
      <th>Created At</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (empty($users)): ?>
    <tr><td colspan="5"><em>No users found.</em></td></tr>
  <?php else: ?>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= htmlspecialchars($user['username']) ?></td>
        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))) ?></td>
        <td><?= htmlspecialchars($user['station_id'] ?? '-') ?></td>
        <td><?= htmlspecialchars($user['created_at']) ?></td>
        <td>
          <a class="button" href="user-edit.php?id=<?= (int)$user['id'] ?>">Edit</a>
          <?php if ($user['id'] !== $_SESSION['user']['id']): ?>
            <a class="button" href="user-delete.php?id=<?= (int)$user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
          <?php else: ?>
            <em>(You)</em>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>
  </tbody>
</table>

<!-- Create User Modal -->
<div id="createUserModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeCreateUserModal()">&times;</span>
    <h2>Create New User</h2>
    <form method="POST" action="" onsubmit="return confirm('Create this user?');">
      <input type="hidden" name="create_user" value="1" />

      <label for="username">Username</label>
      <input type="text" id="username" name="username" required autofocus />

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />

      <label for="password_confirm">Confirm Password</label>
      <input type="password" id="password_confirm" name="password_confirm" required />

      <label for="role">Role</label>
      <select id="role" name="role" onchange="onRoleChange()" required>
        <?php foreach ($validRoles as $role): ?>
          <option value="<?= $role ?>"><?= ucwords(str_replace('_', ' ', $role)) ?></option>
        <?php endforeach; ?>
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
  </div>
</div>

<script>
  onRoleChange();
</script>

</body>
</html>
