<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

// Require admin access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$usersDbFile = __DIR__ . '/../config/users.sqlite';

try {
    $pdo = new PDO('sqlite:' . $usersDbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, username, role, station_id, created_at FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>SignalFrame Admin - User List</title>
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
</style>
</head>
<body>

<h1>User List</h1>

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

</body>
</html>
