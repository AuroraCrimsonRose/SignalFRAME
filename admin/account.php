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

require_once __DIR__ . '/../engine/db.php';
$pdo = getDbConnection();
$userId = $_SESSION['user']['id'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    header('Location: /admin/logout.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username'] ?? '');
    $newEmail    = trim($_POST['email'] ?? '');
    $newPassword = $_POST['password'] ?? '';

    if (empty($newUsername)) {
        $errors[] = "Username cannot be empty.";
    }
    if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $checkStmt->execute([$newEmail, $newUsername, $userId]);
    if ($checkStmt->fetch()) {
        $errors[] = "Username or email is already taken.";
    }

    if (empty($errors)) {
        $updateFields = [];
        $updateParams = [];

        if ($newUsername !== $currentUser['username']) {
            $updateFields[] = "username = ?";
            $updateParams[] = $newUsername;
        }
        if ($newEmail !== $currentUser['email']) {
            $updateFields[] = "email = ?";
            $updateParams[] = $newEmail;
        }
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $errors[] = "Password must be at least 8 characters.";
            } else {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateFields[] = "password_hash = ?";
                $updateParams[] = $hashed;
            }
        }

        if (empty($errors) && !empty($updateFields)) {
            $updateParams[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateParams);

            $_SESSION['user']['username'] = $newUsername;
            $_SESSION['user']['email']    = $newEmail;

            $_SESSION['flash'] = "Profile updated successfully.";
            header('Location: /admin/account.php');
            exit;
        }
    }
}

$pageTitle = 'Account';
ob_start();
?>

<div class="container" style="max-width: 600px; margin: 2rem auto;">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <ul style="margin: 0; padding-left: 1.2rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="profile-card" style="background: #2a2a2a; padding: 1.5rem; border-radius: 8px; display: flex; align-items: center; margin-bottom: 2rem;">
        <img
            src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($currentUser['email']))); ?>?s=80&d=identicon"
            alt="Gravatar"
            class="avatar"
            style="border-radius: 50%; width: 80px; height: 80px; margin-right: 1.25rem;"
        >
        <div>
            <h3 style="margin: 0 0 0.25rem;"><?php echo htmlspecialchars($currentUser['username']); ?></h3>
            <p style="margin: 0; color: #ccc;"><strong>Role:</strong> <?php echo htmlspecialchars($currentUser['role']); ?></p>
        </div>
    </div>

    <div class="form-card" style="background: #1e1e1e; padding: 2rem; border-radius: 8px;">
        <h2 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.5rem; color: #fff;">Account Settings</h2>

        <form method="POST" action="/admin/account.php" class="account-form">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="username" style="display: block; margin-bottom: 0.5rem; color: #aaa;">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo htmlspecialchars($currentUser['username']); ?>"
                    required
                    style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                >
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="email" style="display: block; margin-bottom: 0.5rem; color: #aaa;">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                    required
                    style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                >
                <small style="color: #666;">Gravatar will be used based on this email.</small>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; color: #aaa;">New Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    placeholder="••••••••"
                    style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                >
                <small style="color: #666;">Leave blank to keep current password.</small>
            </div>

            <button
                type="submit"
                class="btn btn-primary"
                style="width: 100%; padding: 0.75rem; background: #007bff; border: none; border-radius: 4px; color: #fff; font-size: 1rem; cursor: pointer;"
            >
                Save Changes
            </button>
        </form>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_template.php';
