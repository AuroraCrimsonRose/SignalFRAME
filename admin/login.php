<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

$usersDbFile = __DIR__ . '/../config/users.sqlite';
$error = '';

if (isset($_SESSION['user'])) {
    // Already logged in
    header('Location: /admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } elseif (!file_exists($usersDbFile)) {
        $error = 'User database not found. Please run setup first.';
    } else {
        try {
            error_log('Looking for DB at: ' . realpath($usersDbFile));
            $pdo = new PDO('sqlite:' . $usersDbFile);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare('SELECT id, username, password_hash, role, api_token FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Successful login
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'api_token' => $user['api_token'] ?? null,
                ];
                header('Location: /admin/index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            error_log('PDO Exception: ' . $e->getMessage());
            $error = 'Error accessing user database.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>SignalFrame Admin Login</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 400px; margin: 3rem auto; padding: 1rem; }
  label { display: block; margin-top: 1rem; }
  input[type=text], input[type=password] { width: 100%; padding: 0.5rem; font-size: 1rem; }
  button { margin-top: 1.5rem; padding: 0.75rem 1.5rem; font-size: 1rem; }
  .error { color: #f55; margin-top: 1rem; }
</style>
</head>
<body>

<h2>SignalFrame Admin Login</h2>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <label for="username">Username</label>
  <input type="text" name="username" id="username" autofocus required />

  <label for="password">Password</label>
  <input type="password" name="password" id="password" required />

  <button type="submit">Log In</button>
</form>

</body>
</html>
