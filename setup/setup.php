<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path to SQLite file under config/
$dbPath = __DIR__ . '/../config/users.sqlite';

// Whitelisted registration keys
$validKeys = [
    'TEST-KEY-12345-ABCDE',
    // …add more keys here if needed
];

$errors  = [];
$success = '';

// If the file already exists and there's an admin, prevent re‐setup
if (file_exists($dbPath)) {
    try {
        $pdoCheck   = new PDO('sqlite:' . $dbPath);
        $pdoCheck->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check for existing admin
        $stmtCheck  = $pdoCheck->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = (int) $stmtCheck->fetchColumn();
        if ($adminCount > 0) {
            $errors[] = "Setup has already been completed. An admin account exists.";
        }
    } catch (Exception $e) {
        // If users table doesn’t exist yet, ignore and let form run
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $regKey   = trim($_POST['reg_key'] ?? '');

    // Basic validation
    if ($username === '') {
        $errors[] = "Username is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!in_array($regKey, $validKeys, true)) {
        $errors[] = "Invalid registration key.";
    }

    if (empty($errors)) {
        // Ensure config/ directory exists
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create or open the SQLite database under config/
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create users table
        $createUsers = "
            CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                username      TEXT    NOT NULL UNIQUE,
                email         TEXT    NOT NULL UNIQUE,
                password_hash TEXT    NOT NULL,
                role          TEXT    NOT NULL,
                station_id    INTEGER DEFAULT NULL,
                api_token     TEXT    DEFAULT NULL,
                created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ";
        $pdo->exec($createUsers);

        // Create api_tokens table
        $createTokens = "
            CREATE TABLE IF NOT EXISTS api_tokens (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL,
                token      TEXT    NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ";
        $pdo->exec($createTokens);

        // Create logs table
        $createLogs = "
            CREATE TABLE IF NOT EXISTS logs (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id   INTEGER,
                level     TEXT    NOT NULL,
                message   TEXT    NOT NULL,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES users(id)
            );
        ";
        $pdo->exec($createLogs);

        // Insert master‐admin user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insert       = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role)
            VALUES (:username, :email, :pw, 'admin')
        ");
        $insert->execute([
            ':username' => $username,
            ':email'    => $email,
            ':pw'       => $passwordHash,
        ]);

        $_SESSION['flash'] = "Master admin created. You can now log in.";
        header('Location: /admin/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SignalFrame Setup</title>
    <link rel="stylesheet" href="/admin/assets/admin-style.css">
    <style>
        /* Minimal inline styling for the setup form */
        .setup-box {
            max-width: 420px;
            margin: 3rem auto;
            background: #1e1e1e;
            padding: 2rem;
            border-radius: 8px;
            color: #fff;
        }
        .setup-box h2 {
            margin-bottom: 1rem;
        }
        .setup-box label {
            display: block;
            margin-top: 0.75rem;
        }
        .setup-box input {
            width: 100%;
            padding: 0.5rem;
            background: #2c2c2c;
            border: 1px solid #444;
            border-radius: 4px;
            color: #fff;
        }
        .setup-box .btn {
            margin-top: 1rem;
            width: 100%;
        }
        .alert-error {
            background: #8b0000;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success {
            background: #006400;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="setup-box">
        <h2>SignalFrame Setup</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/setup/setup.php">
            <label for="username">Admin Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                required
            >

            <label for="email">Admin Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                required
            >

            <label for="password">Password (min 8 characters)</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <label for="reg_key">Registration Key</label>
            <input
                type="text"
                id="reg_key"
                name="reg_key"
                value="<?php echo isset($regKey) ? htmlspecialchars($regKey) : ''; ?>"
                required
            >

            <button type="submit" class="btn btn-primary">Create Master Admin</button>
        </form>
    </div>
</body>
</html>
