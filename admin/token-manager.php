<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../engine/db.php';

$pdo = getDbConnection();
$message = '';

// Handle new token creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_user'])) {
    $username = trim($_POST['new_user']);
    $role = trim($_POST['role'] ?? 'user');

    if ($username === '') {
        $message = 'Username cannot be empty.';
    } else {
        // Generate random token
        $token = bin2hex(random_bytes(16));
        // Insert user and token
        $stmt = $pdo->prepare("INSERT INTO users (username, api_token, role) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$username, $token, $role]);
            $message = "User '$username' created with token: $token";
        } catch (PDOException $e) {
            $message = "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Handle token deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_token'])) {
    $id = (int)$_POST['delete_token'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Deleted token ID $id.";
}

// Fetch all users
$users = $pdo->query("SELECT id, username, api_token, role FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>API Token Manager - SignalFrame</title>
  <style>
    body { font-family: Arial, sans-serif; background: #222; color: #eee; padding: 2rem; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 2rem; }
    th, td { border: 1px solid #555; padding: 0.5rem; text-align: left; }
    th { background: #333; }
    input, select { padding: 0.3rem; margin-right: 1rem; }
    button { padding: 0.4rem 1rem; }
    .message { margin-bottom: 1rem; color: #8f8; }
  </style>
</head>
<body>
  <p>Logged in as: <?= htmlspecialchars($_SESSION['user']) ?> | <a href="logout.php">Logout</a></p>
  <h1>API Token Manager</h1>

  <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="POST" style="margin-bottom:2rem;">
    <input type="text" name="new_user" placeholder="New username" required />
    <select name="role">
      <option value="user">User</option>
      <option value="admin">Admin</option>
    </select>
    <button type="submit">Create Token</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>API Token</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><code><?= $u['api_token'] ?></code></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete token for <?= htmlspecialchars($u['username']) ?>?');">
              <input type="hidden" name="delete_token" value="<?= $u['id'] ?>" />
              <button type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</body>
</html>
