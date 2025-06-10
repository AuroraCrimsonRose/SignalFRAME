<?php
/**
 * Station Admin Panel — SignalFrame
 * Controls only the station in this folder
 */
$configPath = __DIR__ . "/../config.json";
$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$stationSlug = basename(dirname(__DIR__));
$user = $_SESSION['user'];
if (!(
    $user['role'] === 'admin' ||
    ($user['role'] === 'station_manager' && $user['station_id'] === $stationSlug)
)) {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1><p>You don't have access to this station's admin panel.</p>";
    exit;
}

require_once __DIR__ . '/../../../engine/db.php';
require_once __DIR__ . '/../../../engine/logger.php';

// Handle updates
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_station'])) {
    $dispName   = trim($_POST['display_name'] ?? $config['display_name']);
    $mountpoint = trim($_POST['mountpoint'] ?? $config['mountpoint']);
    $theme      = trim($_POST['theme'] ?? $config['theme']);
    $enabled    = isset($_POST['enabled']);

    if ($dispName === '') $errors[] = "Display Name is required.";
    if ($mountpoint === '') $errors[] = "Mountpoint is required.";

    if (
        $user['role'] === 'station_manager' &&
        !empty($config['admin_disabled']) &&
        $enabled
    ) {
        $errors[] = "This station was disabled by an admin and cannot be re-enabled by a station manager.";
        $enabled = false;
    }

    $adminDisabled = !empty($config['admin_disabled']);
    if ($user['role'] === 'admin') {
        $adminDisabled = !$enabled ? true : false;
    }

    if (empty($errors)) {
        $newCfg = [
            'slug' => $stationSlug,
            'display_name' => $dispName,
            'mountpoint' => $mountpoint,
            'theme' => $theme,
            'enabled' => $enabled,
            'admin_disabled' => $adminDisabled
        ];
        file_put_contents($configPath, json_encode($newCfg, JSON_PRETTY_PRINT));
        logEvent(getDbConnection(), $user['id'], 'info', "Updated station '{$stationSlug}' settings.");
        $_SESSION['flash'] = "Station settings saved.";
        header("Location: /stations/{$stationSlug}/manager/");
        exit;
    }
}

// Flash
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$pageTitle = "Manage Station: {$config['display_name']}";
$adminDisabled = !empty($config['admin_disabled']);
$isStationManager = $user['role'] === 'station_manager';
ob_start();
?>
<div class="container" style="max-width: 600px; margin: 2rem auto;">
    <h2 style="margin-bottom: 1.5rem; color: #fff;">
        <span style="color: var(--accent-color);"><?= htmlspecialchars($config['display_name'] ?? 'Station') ?></span> — Settings
    </h2>

    <?php if ($flash): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem;"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-error" style="margin-bottom: 1rem;">
            <ul style="margin:0; padding-left:1.2rem;">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="form-card" style="background: #1e1e1e; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 8px #0002;">
        <form method="POST" action="">
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <label for="display_name" style="color:#ccc;">Display Name</label>
                <input type="text" id="display_name" name="display_name"
                    value="<?= htmlspecialchars($config['display_name'] ?? '') ?>"
                    required
                    style="width:100%;padding:0.5rem;background:#232323;border:1px solid #444;border-radius:4px;color:#fff;">
            </div>
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <label for="mountpoint" style="color:#ccc;">Mountpoint</label>
                <input type="text" id="mountpoint" name="mountpoint"
                    value="<?= htmlspecialchars($config['mountpoint'] ?? '') ?>"
                    required
                    style="width:100%;padding:0.5rem;background:#232323;border:1px solid #444;border-radius:4px;color:#fff;">
            </div>
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <label for="theme" style="color:#ccc;">Theme</label>
                <select id="theme" name="theme"
                    style="width:100%;padding:0.5rem;background:#232323;border:1px solid #444;border-radius:4px;color:#fff;">
                    <?php foreach (scandir(__DIR__ . '/../themes') as $t): if ($t==='.'||$t==='..') continue; ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= (($config['theme']??'')===$t ? 'selected':'')?>><?= ucfirst(htmlspecialchars($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1.2rem;">
                <?php
                $adminDisabled = !empty($config['admin_disabled']);
                $isStationManager = $user['role'] === 'station_manager';
                ?>
                <label style="color:#ccc;">
                    <input type="checkbox" name="enabled"
                        <?= !empty($config['enabled']) ? 'checked' : '' ?>
                        <?= ($adminDisabled && $isStationManager) ? 'disabled' : '' ?>
                    > Enabled
                </label>
                <?php if ($adminDisabled && $isStationManager): ?>
                    <div class="alert alert-warning" style="margin-top:8px;">
                        This station was disabled by an admin and cannot be re-enabled by a station manager.
                    </div>
                <?php endif; ?>
            </div>
            <button type="submit" name="save_station" class="btn btn-primary"
                style="padding:0.6rem 1.2rem;background:#007bff;border:none;border-radius:4px;color:#fff;">
                Save Changes
            </button>
        </form>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../../admin/_template.php';