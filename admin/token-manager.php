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
require_once __DIR__ . '/../engine/logger.php';
$pdo = getDbConnection();

// Handle “Create New Token” form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_token'])) {
    $userIdForToken = (int) ($_POST['user_id'] ?? 0);
    if ($userIdForToken > 0) {
        // Generate a random API token (e.g. 40-character hex)
        $newToken = bin2hex(random_bytes(20));

        $insert = $pdo->prepare("
            INSERT INTO api_tokens (user_id, token)
            VALUES (:uid, :tok)
        ");
        $insert->execute([
            ':uid' => $userIdForToken,
            ':tok' => $newToken,
        ]);

        // Fetch username for logging
        $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmtUser->execute([$userIdForToken]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $username = $userRow['username'] ?? '(unknown)';

        // Log creation without revealing the token itself
        logEvent(
            $pdo,
            $_SESSION['user']['id'],
            'info',
            "Generated new API token for user '{$username}' (ID {$userIdForToken})."
        );

        $_SESSION['flash'] = "New API token generated.";
        header('Location: /admin/token-manager.php');
        exit;
    }
}

// Handle “Revoke” action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_token'])) {
    $tokenId = (int) ($_POST['token_id'] ?? 0);
    if ($tokenId > 0) {
        // Fetch user_id and username before deletion
        $stmtFetch = $pdo->prepare("
            SELECT t.user_id, u.username
            FROM api_tokens AS t
            LEFT JOIN users AS u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmtFetch->execute([$tokenId]);
        $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        $del = $pdo->prepare("DELETE FROM api_tokens WHERE id = ?");
        $del->execute([$tokenId]);

        $userId = $row['user_id'] ?? 0;
        $username = $row['username'] ?? '(unknown)';

        // Log revocation without showing the token
        logEvent(
            $pdo,
            $_SESSION['user']['id'],
            'warning',
            "Revoked API token (ID {$tokenId}) for user '{$username}' (ID {$userId})."
        );

        $_SESSION['flash'] = "API token revoked.";
        header('Location: /admin/token-manager.php');
        exit;
    }
}

// Fetch all tokens and their associated usernames
$tokensStmt = $pdo->query("
    SELECT t.id, t.user_id, t.token, t.created_at, u.username
    FROM api_tokens AS t
    LEFT JOIN users AS u ON t.user_id = u.id
    ORDER BY t.created_at DESC
");
$allTokens = $tokensStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users for the “Create New Token” dropdown
$usersStmt = $pdo->query("SELECT id, username, email FROM users ORDER BY username ASC");
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Grab and clear any flash message
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$pageTitle = 'Token Manager';
ob_start();
?>
<div class="container" style="max-width: 800px; margin: 2rem auto;">
    <?php if ($flash): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem;">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <h2 style="margin-bottom: 1rem;">API Token Manager</h2>

    <!-- Create New Token Form -->
    <div class="form-card" style="background: #1e1e1e; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <form method="POST" action="/admin/token-manager.php" style="display: flex; align-items: center; gap: 1rem;">
            <label for="user_id" style="color: #ccc;">Select User</label>
            <select name="user_id" id="user_id" required style="padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;">
                <option value="">— Choose User —</option>
                <?php foreach ($allUsers as $u): ?>
                    <option value="<?= (int)$u['id'] ?>">
                        <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="create_token" class="btn btn-primary" style="padding: 0.6rem 1.2rem; background: #007bff; border: none; border-radius: 4px; color: #fff;">
                Generate Token
            </button>
        </form>
    </div>

    <!-- Token List -->
    <div class="table-responsive" style="background: #2a2a2a; padding: 1rem; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse; color: #fff;">
            <thead>
                <tr style="border-bottom: 1px solid #444;">
                    <th style="padding: 0.75rem; text-align: left;">User</th>
                    <th style="padding: 0.75rem; text-align: left;">Token (masked)</th>
                    <th style="padding: 0.75rem; text-align: left;">Created At</th>
                    <th style="padding: 0.75rem; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allTokens)): ?>
                    <tr>
                        <td colspan="4" style="padding: 1rem; text-align: center; color: #999;">
                            No tokens found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allTokens as $tok): ?>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="padding: 0.75rem;">
                                <?= htmlspecialchars($tok['username'] ?: '(unknown)') ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?= substr(htmlspecialchars($tok['token']), 0, 8) . '…' ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?= htmlspecialchars($tok['created_at']) ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <form method="POST" action="/admin/token-manager.php" onsubmit="return confirm('Revoke this token?');">
                                    <input type="hidden" name="token_id" value="<?= (int)$tok['id'] ?>">
                                    <button type="submit" name="revoke_token" class="btn btn-danger" style="padding: 0.3rem 0.6rem; background: #dc3545; border: none; border-radius: 4px; color: #fff;">
                                        Revoke
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
?>
