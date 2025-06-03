<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

$usersDbFile = __DIR__ . '/../config/users.sqlite';

// Define your valid registration keys here (in production, store securely)
$validKeys = [
    'TEST-KEY-12345-ABCDE', // Test key for development
    // Add other valid keys here
];

$errors = [];
$success = false;

// Check if users DB and master admin exists
function usersDbExists() {
    global $usersDbFile;
    return file_exists($usersDbFile);
}

function masterAdminExists($pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

// Create SQLite DB and users table with api_token column but do NOT generate tokens here
function createUsersDb() {
    global $usersDbFile;
    $pdo = new PDO('sqlite:' . $usersDbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table including api_token column
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'admin',
            api_token TEXT UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    return $pdo;
}

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regKey = trim($_POST['reg_key'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validate registration key
    if (!in_array($regKey, $validKeys, true)) {
        $errors[] = 'Invalid registration key.';
    }

    // Validate username
    if (empty($username) || !preg_match('/^[a-zA-Z0-9_\-]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters, alphanumeric, underscore or dash.';
    }

    // Validate passwords
    if (empty($password)) {
        $errors[] = 'Password cannot be empty.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (empty($errors)) {
        // Create DB and users table if not exist
        $pdo = createUsersDb();

        // Check if master admin exists already
        if (masterAdminExists($pdo)) {
            $errors[] = 'Master admin user already exists. Setup cannot be rerun.';
        } else {
            // Insert master admin user with hashed password (no API token)
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
            $stmt->execute([$username, $hash]);
            $success = true;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>SignalFrame Setup - Create Master Admin</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 480px; margin: 2rem auto; padding: 1rem; }
    label { display: block; margin-top: 1rem; }
    input[type=text], input[type=password] { width: 100%; padding: 0.5rem; font-size: 1rem; }
    button { margin-top: 1.5rem; padding: 0.75rem 1.5rem; font-size: 1rem; }
    .error { background: #fdd; padding: 1rem; border: 1px solid #f99; margin-bottom: 1rem; }
    .success { background: #dfd; padding: 1rem; border: 1px solid #9f9; margin-bottom: 1rem; }
</style>
</head>
<body>

<h2>SignalFrame Setup</h2>

<?php if ($success): ?>
    <div class="success">
        Master admin user created successfully!<br />
        Please delete or secure this setup script now.<br />
        <a href="/admin/login.php">Go to Login</a>
    </div>
<?php else: ?>
    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Errors:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (usersDbExists() && !$success): ?>
        <div class="error">
            Setup cannot be run because user database already exists.<br />
            If you want to reset, delete <code><?= htmlspecialchars($usersDbFile) ?></code>.
        </div>
    <?php else: ?>

    <form method="POST" action="">
        <label for="reg_key">Registration Key</label>
        <input type="text" name="reg_key" id="reg_key" required autofocus />

        <label for="username">Master Admin Username</label>
        <input type="text" name="username" id="username" required />

        <label for="password">Master Admin Password</label>
        <input type="password" name="password" id="password" required />

        <label for="password_confirm">Confirm Password</label>
        <input type="password" name="password_confirm" id="password_confirm" required />

        <button type="submit">Create Master Admin</button>
    </form>

    <p><em>Test registration key: <code>TEST-KEY-12345-ABCDE</code></em></p>

    <?php endif; ?>
<?php endif; ?>

</body>
</html>
