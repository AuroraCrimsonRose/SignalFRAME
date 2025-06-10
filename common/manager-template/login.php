<?php
session_start();

$usersDbFile = __DIR__ . '/../../../config/users.sqlite';
$error = '';
$stationSlug = basename(dirname(__DIR__));

if (isset($_SESSION['user'])) {
    // Already logged in, redirect to manager index
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (!file_exists($usersDbFile)) {
        $error = 'User database not found. Please contact the administrator.';
    } else {
        try {
            $pdo = new PDO('sqlite:' . $usersDbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->prepare('SELECT id, username, password_hash, role, station_id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Only allow admin or correct station_manager
                if (
                    $user['role'] === 'admin' ||
                    ($user['role'] === 'station_manager' && $user['station_id'] === $stationSlug)
                ) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'station_id' => $user['station_id'] ?? null,
                    ];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'You do not have access to this station manager.';
                }
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
  <title>Station Manager Login</title>
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
    <h2>Station Manager Login</h2>
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