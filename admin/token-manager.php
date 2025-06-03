<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

// Require user to be logged in and admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$usersDbFile = __DIR__ . '/../config/users.sqlite';

try {
    $pdo = new PDO('sqlite:' . $usersDbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all users for user selector
    $stmtUsers = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
    $allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Handle new token creation
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_token_user'])) {
        $userId = (int)$_POST['create_token_user'];
        if ($userId === 0) {
            $message = "Please select a valid user.";
        } else {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO api_tokens (user_id, token) VALUES (?, ?)");
            $stmt->execute([$userId, $token]);
            $message = "New token generated for user ID $userId.";
        }
    }

    // Handle token revocation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_token_id'])) {
        $tokenId = (int)$_POST['revoke_token_id'];
        $stmt = $pdo->prepare("UPDATE api_tokens SET revoked = 1 WHERE id = ?");
        $stmt->execute([$tokenId]);
        $message = "Token ID $tokenId revoked.";
    }

    // Fetch users and their tokens
    $stmt = $pdo->query("
        SELECT users.id as user_id, users.username, users.role, 
               api_tokens.id as token_id, api_tokens.token, api_tokens.created_at, 
               api_tokens.last_used_at, api_tokens.usage_count, api_tokens.revoked
        FROM users
        LEFT JOIN api_tokens ON users.id = api_tokens.user_id
        ORDER BY users.username, api_tokens.created_at DESC
    ");

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $uid = $row['user_id'];
        if (!isset($data[$uid])) {
            $data[$uid] = [
                'username' => $row['username'],
                'role' => $row['role'],
                'tokens' => [],
            ];
        }
        if ($row['token_id']) {
            $data[$uid]['tokens'][] = [
                'id' => $row['token_id'],
                'token' => $row['token'],
                'created_at' => $row['created_at'],
                'last_used_at' => $row['last_used_at'],
                'usage_count' => $row['usage_count'],
                'revoked' => $row['revoked'],
            ];
        }
    }
} catch (Exception $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>SignalFrame Token Manager</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; padding: 1rem; background: #111; color: #eee; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 2rem; }
    th, td { border: 1px solid #444; padding: 0.5rem 1rem; }
    th { background: #222; }
    tr:nth-child(even) { background: #1a1a1a; }
    button { padding: 0.25rem 0.75rem; cursor: pointer; }
    .revoked { color: #f55; font-weight: bold; }
    .message { background: #282; padding: 1rem; margin-bottom: 1rem; border-radius: 5px; }
    form.inline { display: inline; }
    label, select { font-weight: bold; }
</style>
</head>
<body>

<h1>Token Manager</h1>

<?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<section>
    <h2>Generate New Token</h2>
    <form method="POST" onsubmit="return confirm('Generate new token for selected user?');">
        <label for="create_token_user">Select User:</label>
        <select name="create_token_user" id="create_token_user" required>
            <option value="">-- Select a User --</option>
            <?php foreach ($allUsers as $user): ?>
                <option value="<?= (int)$user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Generate New Token</button>
    </form>
</section>

<?php foreach ($data as $userId => $user): ?>
    <section>
        <h2><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</h2>

        <?php if (count($user['tokens']) === 0): ?>
            <p><em>No tokens yet.</em></p>
        <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>Token</th>
                  <th>Owner</th>
                  <th>Created At</th>
                  <th>Last Used</th>
                  <th>Usage Count</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($user['tokens'] as $token): ?>
                  <tr>
                    <td><code><?= htmlspecialchars($token['token']) ?></code></td>
                    <td><?= htmlspecialchars($user['username']) ?></td> <!-- Added owner column -->
                    <td><?= htmlspecialchars($token['created_at']) ?></td>
                    <td><?= htmlspecialchars($token['last_used_at'] ?? '-') ?></td>
                    <td><?= (int)$token['usage_count'] ?></td>
                    <td><?= $token['revoked'] ? '<span class="revoked">Revoked</span>' : 'Active' ?></td>
                    <td>
                      <?php if (!$token['revoked']): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Revoke this token?');">
                          <input type="hidden" name="revoke_token_id" value="<?= (int)$token['id'] ?>" />
                          <button type="submit">Revoke</button>
                        </form>
                      <?php else: ?>
                        <em>None</em>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

</body>
</html>
