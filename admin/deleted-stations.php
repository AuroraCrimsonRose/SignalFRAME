<?php
/**
 * Deleted Stations — SignalFrame Admin
 * Lists stations in stations/disabled/ and allows restoring or permanently deleting them.
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

$pdo           = getDbConnection();
$stationsDir   = __DIR__ . '/../stations';
$disabledDir   = "$stationsDir/disabled";
$errors        = [];
$flash         = '';

if (!is_dir($disabledDir)) {
    mkdir($disabledDir, 0755, true);
}

/**
 * Recursively delete a directory and its contents.
 */
function rrmdir(string $dir): bool {
    if (!is_dir($dir)) return false;
    $objects = scandir($dir);
    foreach ($objects as $obj) {
        if ($obj === '.' || $obj === '..') continue;
        $path = "$dir/$obj";
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}

/**
 * Attempt to restore a station: move disabled folder back to stations/
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Restore one station
    if (isset($_POST['restore_slug'])) {
        $folderName = trim($_POST['restore_slug']);
        $srcPath    = "$disabledDir/$folderName";

        if (!preg_match('/^(.+)_\d{8}_\d{6}$/', $folderName, $matches)) {
            $errors[] = "Invalid folder name: $folderName.";
        } else {
            $origSlug = $matches[1];
            $destPath = "$stationsDir/$origSlug";

            if (is_dir($destPath)) {
                $errors[] = "Cannot restore: station '$origSlug' already exists.";
            } else {
                if (rename($srcPath, $destPath)) {
                    logEvent(
                        $pdo,
                        $_SESSION['user']['id'],
                        'info',
                        "Restored station '$origSlug' from disabled."
                    );
                    $_SESSION['flash'] = "Station '$origSlug' restored successfully.";
                    header('Location: /admin/deleted-stations.php');
                    exit;
                } else {
                    $errors[] = "Failed to restore station '$origSlug'. Check permissions.";
                }
            }
        }
    }

    // Permanently delete one station
    if (isset($_POST['permanent_delete'])) {
        $folderName = trim($_POST['delete_folder']);
        $srcPath    = "$disabledDir/$folderName";

        if (!preg_match('/^(.+)_\d{8}_\d{6}$/', $folderName, $matches)) {
            $errors[] = "Invalid folder name: $folderName.";
        } else {
            $origSlug = $matches[1];

            if (is_dir($srcPath)) {
                if (rrmdir($srcPath)) {
                    logEvent(
                        $pdo,
                        $_SESSION['user']['id'],
                        'warning',
                        "Permanently deleted station '$origSlug'."
                    );
                    $_SESSION['flash'] = "Station '$origSlug' permanently deleted.";
                    header('Location: /admin/deleted-stations.php');
                    exit;
                } else {
                    $errors[] = "Failed to permanently delete '$origSlug'. Check permissions.";
                }
            } else {
                $errors[] = "Folder not found: $folderName.";
            }
        }
    }

    // Permanently delete all stations
    if (isset($_POST['delete_all'])) {
        $deleted = [];
        foreach (scandir($disabledDir) as $d) {
            if ($d === '.' || $d === '..') continue;
            $path = "$disabledDir/$d";
            if (is_dir($path) && preg_match('/^(.+)_\d{8}_\d{6}$/', $d, $m)) {
                $origSlug = $m[1];
                if (rrmdir($path)) {
                    logEvent(
                        $pdo,
                        $_SESSION['user']['id'],
                        'warning',
                        "Permanently deleted station '$origSlug'."
                    );
                    $deleted[] = $origSlug;
                }
            }
        }
        if ($deleted) {
            $_SESSION['flash'] = "Permanently deleted: " . implode(', ', $deleted) . '.';
        } else {
            $_SESSION['flash'] = "No disabled stations to delete.";
        }
        header('Location: /admin/deleted-stations.php');
        exit;
    }
}

// Gather folders under stations/disabled/
$deletedStations = [];
foreach (scandir($disabledDir) as $d) {
    if ($d === '.' || $d === '..') continue;
    $fullPath = "$disabledDir/$d";
    if (is_dir($fullPath)) {
        // Try reading config.json inside this folder
        $configPath  = "$fullPath/config.json";
        $displayName = '[Unknown]';
        if (file_exists($configPath)) {
            $cfg = json_decode(file_get_contents($configPath), true);
            if (is_array($cfg) && isset($cfg['display_name'])) {
                $displayName = $cfg['display_name'];
            }
        }
        // Parse timestamp from folder name
        if (preg_match('/^.+_(\d{8}_\d{6})$/', $d, $tsMatches)) {
            $tsRaw       = $tsMatches[1];
            $tsFormatted = DateTime::createFromFormat('Ymd_His', $tsRaw);
            $deletedAt   = $tsFormatted ? $tsFormatted->format('Y-m-d H:i:s') : $tsRaw;
        } else {
            $deletedAt = '[Unknown]';
        }

        $deletedStations[] = [
            'folder'      => $d,
            'orig_slug'   => preg_match('/^(.+)_\d{8}_\d{6}$/', $d, $m) ? $m[1] : $d,
            'displayName' => $displayName,
            'deletedAt'   => $deletedAt,
        ];
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$pageTitle = 'Deleted Stations';
ob_start();
?>

<div class="container" style="max-width: 900px; margin: 2rem auto;">
    <?php if ($flash): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem;">
            <?= htmlspecialchars($flash) ?>
        </div>
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

    <h2 style="margin-bottom: 1rem;">Deleted Stations</h2>
    <p style="margin-bottom: 1rem;">
        These stations were previously disabled. You can restore or permanently delete them.
    </p>

    <?php if (empty($deletedStations)): ?>
        <div style="padding: 1rem; background: #2a2a2a; border-radius: 4px; color: #ccc; text-align: center;">
            No deleted stations found.
        </div>
    <?php else: ?>
        <!-- Delete All Button -->
        <form method="POST" action="/admin/deleted-stations.php" style="text-align: right; margin-bottom: 1rem;">
            <button
                type="submit"
                name="delete_all"
                class="btn btn-danger"
                style="padding: 0.5rem 1rem; background: #dc3545; border: none; border-radius: 4px; color: #fff;"
                onclick="return confirm('Are you sure you want to permanently delete ALL disabled stations?');"
            >
                Delete All
            </button>
        </form>

        <div class="table-responsive" style="background: #2a2a2a; padding: 1rem; border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; color: #fff;">
                <thead>
                    <tr style="border-bottom: 1px solid #444;">
                        <th style="padding: 0.75rem; text-align: left;">Slug</th>
                        <th style="padding: 0.75rem; text-align: left;">Display Name</th>
                        <th style="padding: 0.75rem; text-align: left;">Deleted At</th>
                        <th style="padding: 0.75rem; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deletedStations as $ds): ?>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($ds['orig_slug']) ?></td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($ds['displayName']) ?></td>
                            <td style="padding: 0.75rem;"><?= htmlspecialchars($ds['deletedAt']) ?></td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <!-- Restore Station -->
                                <form method="POST" action="/admin/deleted-stations.php" style="display: inline;" onsubmit="return confirm('Restore station <?= htmlspecialchars($ds['orig_slug']) ?>?');">
                                    <input type="hidden" name="restore_slug" value="<?= htmlspecialchars($ds['folder']) ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-success"
                                        style="padding: 0.3rem 0.6rem; background: #28a745; border: none; border-radius: 4px; color: #fff;"
                                    >
                                        Restore
                                    </button>
                                </form>

                                <!-- Permanent Delete -->
                                <form method="POST" action="/admin/deleted-stations.php" style="display: inline; margin-left: 0.3rem;" onsubmit="return confirm('Permanently delete station <?= htmlspecialchars($ds['orig_slug']) ?>?');">
                                    <input type="hidden" name="delete_folder" value="<?= htmlspecialchars($ds['folder']) ?>">
                                    <button
                                        type="submit"
                                        name="permanent_delete"
                                        class="btn btn-danger"
                                        style="padding: 0.3rem 0.6rem; background: #dc3545; border: none; border-radius: 4px; color: #fff;"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div style="margin-top: 1.5rem; text-align: right;">
        <a
            href="/admin/station-manager.php"
            class="btn"
            style="padding: 0.5rem 1rem; background: #6c757d; border: none; border-radius: 4px; color: #fff;"
        >
            Back to Station Manager
        </a>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_template.php';
?>
