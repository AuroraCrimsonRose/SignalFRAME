<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// 1) Start session and guard access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /admin/login.php');
    exit;
}

// 2) Page title and DB setup
$pageTitle   = 'System Logs';
$usersDbFile = __DIR__ . '/../config/users.sqlite';
$pdo         = new PDO('sqlite:' . $usersDbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 3) Fetch logs (with user names if available)
try {
    $stmt = $pdo->query("
        SELECT 
            logs.id,
            logs.level,
            logs.message,
            logs.timestamp,
            users.username AS user_name
        FROM logs
        LEFT JOIN users ON logs.user_id = users.id
        ORDER BY logs.timestamp DESC
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
}

// 4) Capture page content
ob_start();
?>

<div class="container" style="max-width: 900px; margin: 2rem auto;">
    <h2 style="margin-bottom: 1rem;">System Logs</h2>

    <?php if (empty($logs)): ?>
        <div style="padding: 1rem; background: #2a2a2a; border-radius: 4px; color: #ccc; text-align: center;">
            No log entries found.
        </div>
    <?php else: ?>
        <div class="table-responsive" style="background: #2a2a2a; padding: 1rem; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; color: #fff;">
                <thead>
                    <tr style="border-bottom: 1px solid #444;">
                        <th style="padding: 0.75rem; text-align: left;">Timestamp</th>
                        <th style="padding: 0.75rem; text-align: left;">Level</th>
                        <th style="padding: 0.75rem; text-align: left;">User</th>
                        <th style="padding: 0.75rem; text-align: left;">Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            // Color‐code level
                            switch (strtolower($log['level'])) {
                                case 'error':
                                    $levelColor = '#dc3545';
                                    break;
                                case 'warning':
                                    $levelColor = '#ffc107';
                                    break;
                                default:
                                    $levelColor = '#28a745';
                                    break;
                            }
                        ?>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="padding: 0.75rem; font-family: monospace;">
                                <?= htmlspecialchars($log['timestamp']) ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <span style="color: <?= $levelColor ?>; font-weight: bold;">
                                    <?= strtoupper(htmlspecialchars($log['level'])) ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?= htmlspecialchars($log['user_name'] ?? 'System') ?>
                            </td>
                            <td style="padding: 0.75rem;">
                                <?= htmlspecialchars($log['message']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_template.php';
?>
