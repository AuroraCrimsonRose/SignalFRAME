<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /admin/login.php');
    exit;
}

// Use the new helpers under engine/
require_once __DIR__ . '/../engine/db.php';
require_once __DIR__ . '/../engine/logger.php';
$pdo = getDbConnection();

$errors = [];
$flash  = '';

// Handle "Create New User" form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['new_username'] ?? '');
    $email    = trim($_POST['new_email'] ?? '');
    $password = $_POST['new_password'] ?? '';
    $role     = $_POST['new_role'] ?? 'user';

    // Basic validation
    if ($username === '') {
        $errors[] = "Username is required.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    // Check for existing username/email
    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetch()) {
            $errors[] = "Username or email already taken.";
        }
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role)
            VALUES (:u, :e, :ph, :r)
        ");
        $insert->execute([
            ':u'  => $username,
            ':e'  => $email,
            ':ph' => $hashed,
            ':r'  => $role
        ]);

        // Get the new user's ID
        $newUserId = (int)$pdo->lastInsertId();

        // Log creation without storing the password
        logEvent(
            $pdo,
            $_SESSION['user']['id'],
            'info',
            "Created user '{$username}' (ID {$newUserId}, Role {$role})."
        );

        $_SESSION['flash'] = "User '{$username}' created.";
        header('Location: /admin/user-manager.php');
        exit;
    }
}

// Handle "Delete User" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $delUserId = (int) ($_POST['delete_id'] ?? 0);
    if ($delUserId > 0) {
        // Fetch username before deleting
        $stmtFetch = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmtFetch->execute([$delUserId]);
        $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);
        $deletedUsername = $row['username'] ?? '(unknown)';

        $delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete->execute([$delUserId]);

        // Log deletion
        logEvent(
            $pdo,
            $_SESSION['user']['id'],
            'warning',
            "Deleted user '{$deletedUsername}' (ID {$delUserId})."
        );

        $_SESSION['flash'] = "User deleted.";
        header('Location: /admin/user-manager.php');
        exit;
    }
}

// Fetch all users
$usersStmt = $pdo->query("
    SELECT id, username, email, role, station_id, created_at
    FROM users
    ORDER BY created_at DESC
");
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch list of stations (if you want to allow assigning a station)
$stations = [];
try {
    $stationDirs = array_filter(scandir(__DIR__ . '/../stations'), function($d) {
        return $d !== '.' && $d !== '..' && is_dir(__DIR__ . "/../stations/$d");
    });
    foreach ($stationDirs as $slug) {
        // Attempt to read station display name from config.json
        $cfgPath = __DIR__ . "/../stations/$slug/config.json";
        if (file_exists($cfgPath)) {
            $data = json_decode(file_get_contents($cfgPath), true);
            $stations[] = ['slug' => $slug, 'name' => $data['display_name'] ?? $slug];
        }
    }
} catch (Exception $e) {
    // If /stations/ isn’t readable, just leave stations empty
}

$pageTitle = 'User Manager';
ob_start();
?>
<div class="container" style="max-width: 1000px; margin: 2rem auto;">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem;">
            <?= htmlspecialchars($_SESSION['flash']) ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.2rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <h2 style="margin-bottom: 1rem;">User Manager</h2>

    <!-- Create New User Form -->
    <div class="form-card" style="background: #1e1e1e; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <form method="POST" action="/admin/user-manager.php" style="display: grid; gap: 1rem;">
            <div style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label for="new_username" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Username</label>
                    <input
                        type="text"
                        id="new_username"
                        name="new_username"
                        required
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                </div>
                <div style="flex: 1;">
                    <label for="new_email" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Email</label>
                    <input
                        type="email"
                        id="new_email"
                        name="new_email"
                        required
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                </div>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label for="new_password" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Password</label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        placeholder="••••••••"
                        required
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                </div>
                <div style="flex: 1;">
                    <label for="new_role" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Role</label>
                    <select
                        id="new_role"
                        name="new_role"
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                        <option value="dj">DJ</option>
                        <option value="webmaster">Webmaster</option>
                        <option value="station_manager">Station Manager</option>
                        <option value="admin">Admin</option>
                        <option value="user" selected>User</option>
                    </select>
                </div>
            </div>
            <button
                type="submit"
                name="create_user"
                class="btn btn-primary"
                style="padding: 0.6rem 1.2rem; background: #007bff; border: none; border-radius: 4px; color: #fff;"
            >
                Create User
            </button>
        </form>
    </div>

    <!-- User List Table -->
    <div class="table-responsive" style="background: #2a2a2a; padding: 1rem; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse; color: #fff;">
            <thead>
                <tr style="border-bottom: 1px solid #444;">
                    <th style="padding: 0.75rem; text-align: left;">Avatar</th>
                    <th style="padding: 0.75rem; text-align: left;">Username</th>
                    <th style="padding: 0.75rem; text-align: left;">Email</th>
                    <th style="padding: 0.75rem; text-align: left;">Role</th>
                    <th style="padding: 0.75rem; text-align: left;">Station</th>
                    <th style="padding: 0.75rem; text-align: left;">Created At</th>
                    <th style="padding: 0.75rem; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allUsers)): ?>
                    <tr>
                        <td colspan="7" style="padding: 1rem; text-align: center; color: #999;">
                            No users found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allUsers as $u): ?>
                        <?php
                            $hash = md5(strtolower(trim($u['email'])));
                            // Find station display name if assigned
                            $stationName = '';
                            if ($u['station_id']) {
                                foreach ($stations as $s) {
                                    if ($s['slug'] === $u['station_id']) {
                                        $stationName = $s['name'];
                                        break;
                                    }
                                }
                            }
                        ?>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="padding: 0.75rem;">
                                <img
                                    src="https://www.gravatar.com/avatar/<?= $hash ?>?s=32&d=identicon"
                                    alt="Avatar"
                                    style="border-radius: 50%;"
                                >
                            </td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($u['username']) ?></td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($u['role']) ?></td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($stationName) ?></td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($u['created_at']) ?></td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <form method="POST" action="/admin/user-manager.php"
                                      onsubmit="return confirm('Delete this user?');"
                                      style="display: inline;">
                                    <input type="hidden" name="delete_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger"
                                            style="padding: 0.3rem 0.6rem; background: #dc3545; border: none; border-radius: 4px; color: #fff;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_template.php';
