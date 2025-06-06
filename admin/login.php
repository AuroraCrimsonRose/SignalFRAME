<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start(); // always start session here for login

$usersDbFile = __DIR__ . '/../config/users.sqlite';
$error = '';

if (isset($_SESSION['user'])) {
    header('Location: /admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (!file_exists($usersDbFile)) {
        $error = 'User database not found. Please run setup.';
    } else {
        try {
            $pdo = new PDO('sqlite:' . $usersDbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare('SELECT id, username, password_hash, role, api_token FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'api_token' => $user['api_token'] ?? null,
                ];
                file_put_contents('/tmp/sf_debug.txt', print_r($_SESSION, true));
                
                header('Location: /admin/index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Login error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - SignalFrame</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/admin/assets/admin-style.css">
  <style>
    .login-box {
      max-width: 400px;
      margin: 80px auto;
      background-color: #1a1a1a;
      padding: 24px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }
    .login-box h2 {
      margin-top: 0;
      text-align: center;
    }
    .login-box input {
      width: 100%;
      padding: 10px;
      margin: 12px 0;
      border: none;
      border-radius: 4px;
      background-color: #2a2a2a;
      color: white;
    }
    .login-box button {
      width: 100%;
      padding: 10px;
      background-color: #2a2a2a;
      color: white;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
    }
    .login-box button:hover {
      background-color: #444;
    }
    .error {
      color: #ff6b6b;
      text-align: center;
      margin-bottom: 12px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>SignalFrame Admin Login</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="text" name="username" placeholder="Username" autofocus required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Log In</button>
    </form>
  </div>
</body>
</html>
