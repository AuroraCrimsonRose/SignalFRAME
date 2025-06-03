<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$usersDbFile = __DIR__ . '/../config/users.sqlite';
$userId = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];

$errors = [];
$successMsg = '';

// Directory for profile pictures
$pfpDir = __DIR__ . '/../uploads/pfps/';
if (!is_dir($pfpDir)) {
    mkdir($pfpDir, 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . $usersDbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Load current user data including pfp filename
    $stmt = $pdo->prepare('SELECT pfp_filename FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentPfp = $row['pfp_filename'] ?? null;

    // Handle profile picture upload
    if (isset($_POST['upload_pfp']) && isset($_FILES['pfp']) && $_FILES['pfp']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['pfp']['tmp_name'];
        $fileName = basename($_FILES['pfp']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExt, $allowedExts, true)) {
            $errors[] = 'Invalid file type. Allowed: jpg, jpeg, png, gif.';
        } else {
            $newFileName = "user_{$userId}." . $fileExt;
            $destPath = $pfpDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destPath)) {
                // Update DB with new pfp filename
                $stmt = $pdo->prepare('UPDATE users SET pfp_filename = ? WHERE id = ?');
                $stmt->execute([$newFileName, $userId]);
                $currentPfp = $newFileName;
                $successMsg = 'Profile picture updated successfully.';
            } else {
                $errors[] = 'Failed to move uploaded file.';
            }
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        // Validate new password
        if (strlen($newPass) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPass !== $confirmPass) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (empty($errors)) {
            // Verify current password
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userRow || !password_verify($currentPass, $userRow['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                // Update with new password hash
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([$newHash, $userId]);
                $successMsg = 'Password changed successfully.';
            }
        }
    }

} catch (Exception $e) {
    $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
}

$pfpUrl = $currentPfp ? "/uploads/pfps/" . htmlspecialchars($currentPfp) : "/admin/assets/default-pfp.png"; // fallback pfp
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Account - SignalFrame Admin</title>
<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 480px;
        margin: 2rem auto;
        padding: 1rem;
        background: #111;
        color: #eee;
    }
    h1 {
        margin-bottom: 1.5rem;
    }
    label {
        display: block;
        margin-top: 1rem;
        font-weight: bold;
    }
    input[type=password], input[type=file] {
        width: 100%;
        padding: 0.5rem;
        font-size: 1rem;
    }
    button {
        margin-top: 1.5rem;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        cursor: pointer;
    }
    .error {
        background: #fdd;
        padding: 1rem;
        border: 1px solid #f99;
        margin-bottom: 1rem;
    }
    .success {
        background: #dfd;
        padding: 1rem;
        border: 1px solid #9f9;
        margin-bottom: 1rem;
    }
    .pfp-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .pfp-container img {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #666;
    }
</style>
</head>
<body>

<h1>Account Settings</h1>

<div class="pfp-container">
    <img src="<?= $pfpUrl ?>" alt="Profile Picture" />
    <div><strong><?= htmlspecialchars($username) ?></strong></div>
</div>

<?php if (!empty($errors)): ?>
    <div class="error">
        <strong>Errors:</strong>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($successMsg): ?>
    <div class="success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<!-- Profile Picture Upload -->
<form method="POST" enctype="multipart/form-data">
    <label for="pfp">Update Profile Picture</label>
    <input type="file" name="pfp" id="pfp" accept="image/png, image/jpeg, image/gif" required />
    <button type="submit" name="upload_pfp">Upload Picture</button>
</form>

<!-- Change Password -->
<form method="POST">
    <label for="current_password">Current Password</label>
    <input type="password" name="current_password" id="current_password" required />

    <label for="new_password">New Password</label>
    <input type="password" name="new_password" id="new_password" required />

    <label for="confirm_password">Confirm New Password</label>
    <input type="password" name="confirm_password" id="confirm_password" required />

    <button type="submit" name="change_password">Change Password</button>
</form>

</body>
</html>
