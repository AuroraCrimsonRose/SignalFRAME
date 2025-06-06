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
$pdo            = getDbConnection();
$stationsDir    = __DIR__ . '/../stations';
$commonDefault  = __DIR__ . '/../common/default';
$masterThemes   = __DIR__ . '/../themes';
$disabledDir    = "$stationsDir/disabled";

// After you’ve defined $stationsDir, $commonDefault, $masterThemes, etc.:
$allThemeDirs = [];
if (is_dir($masterThemes)) {
    foreach (scandir($masterThemes) as $d) {
        if ($d === '.' || $d === '..') continue;
        if (is_dir("$masterThemes/$d")) {
            $allThemeDirs[] = $d;
        }
    }
}

$errors = [];
$flash  = '';

if (!is_dir($disabledDir)) {
    mkdir($disabledDir, 0755, true);
}

function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = "$src/$file";
        $dstPath = "$dst/$file";
        if (is_dir($srcPath)) {
            recursiveCopy($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

function replacePlaceholdersInFile($filePath, $replacements) {
    if (!file_exists($filePath)) return;
    $content = file_get_contents($filePath);
    $content = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $content
    );
    file_put_contents($filePath, $content);
}

function readStationConfig($slug) {
    global $stationsDir;
    $cfgPath = "$stationsDir/$slug/config.json";
    if (!file_exists($cfgPath)) return null;
    return json_decode(file_get_contents($cfgPath), true);
}

function writeStationConfig($slug, $config) {
    global $stationsDir;
    $cfgPath = "$stationsDir/$slug/config.json";
    file_put_contents($cfgPath, json_encode($config, JSON_PRETTY_PRINT));
}

// ————————————————————————————————
// 1) Create New Station
// ————————————————————————————————
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_station'])) {
    // 1) Capture form inputs early
    $slug       = trim($_POST['slug'] ?? '');
    $dispName   = trim($_POST['display_name'] ?? '');
    $mountpoint = trim($_POST['mountpoint'] ?? '');
    $theme      = trim($_POST['theme'] ?? 'light');
    $enabled    = true;

    $errors = [];
    if ($slug === '' || !preg_match('/^[a-z0-9_-]+$/', $slug)) {
        $errors[] = "Slug is required and can only contain lowercase letters, numbers, hyphens, or underscores.";
    }
    if ($dispName === '') {
        $errors[] = "Display Name is required.";
    }
    if ($mountpoint === '') {
        $errors[] = "Mountpoint is required.";
    }

    $newStationPath = "$stationsDir/$slug";
    if (is_dir($newStationPath)) {
        $errors[] = "A station with slug '$slug' already exists.";
    }

    if (empty($errors)) {
        // Create the station folder
        mkdir($newStationPath, 0755, true);

        // Copy common/default (excluding themes) …
        if (is_dir($commonDefault)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($commonDefault, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $relPath = substr($item->getPathname(), strlen($commonDefault) + 1);
                if (strpos($relPath, 'themes' . DIRECTORY_SEPARATOR) === 0) {
                    continue;
                }
                $dest = "$newStationPath/$relPath";
                if ($item->isDir()) {
                    mkdir($dest, 0755, true);
                } else {
                    copy($item->getPathname(), $dest);
                }
            }
        }

        // Copy ./themes/ → stations/{slug}/themes/
        if (is_dir($masterThemes)) {
            recursiveCopy($masterThemes, "$newStationPath/themes");
        } else {
            mkdir("$newStationPath/themes", 0755, true);
        }

        // Write config.json (with slug, display_name, mountpoint, theme, enabled)
        $configArr = [
            'slug'         => $slug,
            'display_name' => $dispName,
            'mountpoint'   => $mountpoint,
            'theme'        => $theme,
            'enabled'      => true
        ];
        file_put_contents(
            "$newStationPath/config.json",
            json_encode($configArr, JSON_PRETTY_PRINT)
        );

        // Rename _station-template.php → index.php (no placeholders to replace)
        rename("$newStationPath/_station-template.php", "$newStationPath/index.php");

        // ————————
        // LOGGING: only now that $slug and $dispName are set and folder is created
        logEvent(
            $pdo,
            $_SESSION['user']['id'],
            'info',
            "Created station '$slug' (Display: '$dispName')."
        );
        // ————————

        $_SESSION['flash'] = "Station '$dispName' created successfully.";
        header('Location: /admin/station-manager.php');
        exit;
    }
}

// ————————————————————————————————
// 2) Delete Station (move to disabled/)
// ————————————————————————————————
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_station'])) {
    $slug = trim($_POST['delete_slug'] ?? '');
    $stationPath = "$stationsDir/$slug";
    if ($slug === '' || !is_dir($stationPath)) {
        $errors[] = "Invalid station slug.";
    } else {
        $timestamp    = date('Ymd_His');
        $disabledName = "{$slug}_{$timestamp}";
        $targetPath   = "$disabledDir/$disabledName";

        if (rename($stationPath, $targetPath)) {
            // LOGGING: record which station was deleted
            logEvent(
                $pdo,
                $_SESSION['user']['id'],
                'warning',
                "Deleted station '$slug'."
            );

            $_SESSION['flash'] = "Station '$slug' moved to disabled.";
            header('Location: /admin/station-manager.php');
            exit;
        } else {
            $errors[] = "Failed to disable station. Check permissions.";
        }
    }
}

// ————————————————————————————————
// 3) Toggle Enable/Disable Station
// ————————————————————————————————
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_station'])) {
    $slug = trim($_POST['toggle_slug'] ?? '');
    $cfg  = readStationConfig($slug);
    if (!$cfg) {
        $errors[] = "Station '$slug' not found.";
    } else {
        $cfg['enabled'] = !($cfg['enabled'] ?? false);
        writeStationConfig($slug, $cfg);
        $status = $cfg['enabled'] ? 'enabled' : 'disabled';

        // LOGGING: record the toggle action
        logEvent(
            $pdo,
            $_SESSION['user']['id'],
            'info',
            ucfirst($status) . " station '$slug'."
        );

        $_SESSION['flash'] = "Station '{$cfg['display_name']}' has been $status.";
        header('Location: /admin/station-manager.php');
        exit;
    }
}

// ————————————————————————————————
// 4) Edit Station (update config.json)
// ————————————————————————————————
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_station'])) {
    $slugOld    = trim($_POST['slug_old'] ?? '');
    $dispName   = trim($_POST['display_name'] ?? '');
    $mountpoint = trim($_POST['mountpoint'] ?? '');
    $theme      = trim($_POST['theme'] ?? '');
    $enabled    = isset($_POST['enabled']) ? true : false;

    $cfg = readStationConfig($slugOld);
    if (!$cfg) {
        $errors[] = "Station '$slugOld' not found.";
    } else {
        $cfg['display_name'] = $dispName;
        $cfg['mountpoint']   = $mountpoint;
        $cfg['theme']        = $theme;
        $cfg['enabled']      = $enabled;
        writeStationConfig($slugOld, $cfg);

        // LOGGING: record the updated fields
        logEvent(
            $pdo,
            $_SESSION['user']['id'],
            'info',
            "Updated station '$slugOld' to Display: '$dispName', Mountpoint: '$mountpoint', Theme: '$theme', Enabled: " . ($enabled ? 'yes' : 'no') . "."
        );

        $_SESSION['flash'] = "Station '{$dispName}' updated.";
        header('Location: /admin/station-manager.php');
        exit;
    }
}

// ————————————————————————————————
// 5) Gather station list
// ————————————————————————————————
$allStationSlugs = array_filter(scandir($stationsDir), function($d) use ($stationsDir, $disabledDir) {
    return
        $d !== '.' &&
        $d !== '..' &&
        is_dir("$stationsDir/$d") &&
        $d !== basename($disabledDir);
});

$stations = [];
foreach ($allStationSlugs as $slug) {
    $cfg = readStationConfig($slug);
    if (
        is_array($cfg) &&
        isset($cfg['slug'], $cfg['display_name'], $cfg['mountpoint'], $cfg['theme'], $cfg['enabled'])
    ) {
        $stations[] = $cfg;
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$pageTitle = 'Station Manager';
ob_start();
?>

<div class="container" style="max-width: 900px; margin: 2rem auto;">
    <?php if ($flash): ?>
        <div class="alert alert-success" style="margin-bottom: 1rem;">
            <?php echo htmlspecialchars($flash); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom: 1rem;">
            <ul style="margin: 0; padding-left: 1.2rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <h2 style="margin-bottom: 1rem;">Station Manager</h2>
    
    <div style="margin-bottom: 1rem; text-align: right;">
        <a
            href="/admin/deleted-stations.php"
            class="btn"
            style="padding: 0.5rem 1rem; background: #17a2b8; border: none; border-radius: 4px; color: #fff;"
        >
            Deleted Stations
        </a>
    </div>

    <!-- Create New Station Form -->
    <div class="form-card" style="background: #1e1e1e; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <form method="POST" action="/admin/station-manager.php" style="display: grid; gap: 1rem;">
            <h3 style="margin: 0; color: #fff;">Create New Station</h3>
            <div style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label for="slug" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Slug</label>
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        placeholder="e.g. my-station"
                        required
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                    <small style="color: #666;">Lowercase/numbers/hyphens only</small>
                </div>
                <div style="flex: 2;">
                    <label for="display_name" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Display Name</label>
                    <input
                        type="text"
                        id="display_name"
                        name="display_name"
                        placeholder="Station Display Name"
                        required
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                </div>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                <div style="flex: 2;">
                    <label for="mountpoint" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Mountpoint</label>
                    <input
                        type="text"
                        id="mountpoint"
                        name="mountpoint"
                        placeholder="e.g. /live"
                        required
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                </div>
                <div style="flex: 1;">
                    <label for="theme" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Default Theme</label>
                    <select
                        id="theme"
                        name="theme"
                        style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                    >
                        <?php foreach ($allThemeDirs as $themeDir): ?>
                        <option value="<?php echo htmlspecialchars($themeDir); ?>"><?php echo ucfirst(htmlspecialchars($themeDir)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button
                type="submit"
                name="create_station"
                class="btn btn-primary"
                style="padding: 0.6rem 1.2rem; background: #007bff; border: none; border-radius: 4px; color: #fff; margin-top: 1rem; width: 200px;"
            >
                Create Station
            </button>
        </form>
    </div>

    <!-- Station List Table -->
    <div class="table-responsive" style="background: #2a2a2a; padding: 1rem; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse; color: #fff;">
            <thead>
                <tr style="border-bottom: 1px solid #444;">
                    <th style="padding: 0.75rem; text-align: left;">Slug</th>
                    <th style="padding: 0.75rem; text-align: left;">Display Name</th>
                    <th style="padding: 0.75rem; text-align: left;">Mountpoint</th>
                    <th style="padding: 0.75rem; text-align: left;">Theme</th>
                    <th style="padding: 0.75rem; text-align: left;">Status</th>
                    <th style="padding: 0.75rem; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stations)): ?>
                    <tr>
                        <td colspan="6" style="padding: 1rem; text-align: center; color: #999;">
                            No stations found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stations as $s): ?>
                        <?php
                            $enabledVal  = $s['enabled'] ? true : false;
                            $statusLabel = $enabledVal ? 'Enabled' : 'Disabled';
                            $statusColor = $enabledVal ? '#28a745' : '#dc3545';
                        ?>
                        <tr style="border-bottom: 1px solid #333;">
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($s['slug']); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($s['display_name']); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($s['mountpoint']); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars(ucfirst($s['theme'])); ?></td>
                            <td style="padding: 0.75rem;">
                                <span style="color: <?php echo $statusColor; ?>; font-weight: bold;">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem; text-align: center;">
                                <!-- View Station -->
                                <a
                                    href="/stations/<?php echo htmlspecialchars($s['slug']); ?>/"
                                    target="_blank"
                                    class="btn"
                                    style="padding: 0.3rem 0.6rem; background: #17a2b8; border: none; border-radius: 4px; color: #fff;"
                                >
                                    View
                                </a>

                                <!-- Toggle Enable/Disable -->
                                <form method="POST" action="/admin/station-manager.php" style="display: inline; margin-left: 0.3rem;">
                                    <input type="hidden" name="toggle_slug" value="<?php echo htmlspecialchars($s['slug']); ?>">
                                    <button
                                        type="submit"
                                        name="toggle_station"
                                        class="btn"
                                        style="padding: 0.3rem 0.6rem; background: <?php echo $enabledVal ? '#ffc107' : '#28a745'; ?>; border: none; border-radius: 4px; color: #fff;"
                                    >
                                        <?php echo $enabledVal ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>

                                <!-- Edit Station -->
                                <button
                                    onclick="openEditModal('<?php echo htmlspecialchars($s['slug']); ?>')"
                                    class="btn btn-secondary"
                                    style="padding: 0.3rem 0.6rem; background: #6c757d; border: none; border-radius: 4px; color: #fff; margin-left: 0.3rem;"
                                >
                                    Edit
                                </button>

                                <!-- Delete Station -->
                                <form method="POST" action="/admin/station-manager.php"
                                    style="display: inline; margin-left: 0.3rem;"
                                    onsubmit="return confirm('Are you sure you want to delete this station?');">
                                    <input type="hidden" name="delete_slug" value="<?php echo htmlspecialchars($s['slug']); ?>">
                                    <button
                                        type="submit"
                                        name="delete_station"
                                        class="btn btn-danger"
                                        style="padding: 0.3rem 0.6rem; background: #dc3545; border: none; border-radius: 4px; color: #fff;"
                                    >
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

<!-- Edit Station Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6);">
    <div style="background: #1e1e1e; border-radius: 8px; width: 500px; max-width: 90%; margin: 5% auto; padding: 1.5rem; position: relative;">
        <button onclick="closeEditModal()" style="position: absolute; top: 0.5rem; right: 0.5rem; background: transparent; border: none; color: #fff; font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h3 style="margin-top: 0; color: #fff;">Edit Station</h3>
        <form id="editForm" method="POST" action="/admin/station-manager.php" style="display: grid; gap: 1rem;">
            <input type="hidden" id="slug_old" name="slug_old" value="">
            <div>
                <label for="display_name_edit" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Display Name</label>
                <input
                    type="text"
                    id="display_name_edit"
                    name="display_name"
                    required
                    style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                >
            </div>
            <div>
                <label for="mountpoint_edit" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Mountpoint</label>
                <input
                    type="text"
                    id="mountpoint_edit"
                    name="mountpoint"
                    required
                    style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                >
            </div>
            <div>
                <label for="theme_edit" style="display: block; margin-bottom: 0.25rem; color: #ccc;">Theme</label>
                <select
                    id="theme_edit"
                    name="theme"
                    style="width: 100%; padding: 0.5rem; background: #2c2c2c; border: 1px solid #444; border-radius: 4px; color: #fff;"
                >
                    <?php foreach ($allThemeDirs as $themeDir): ?>
                    <option value="<?php echo htmlspecialchars($themeDir); ?>"><?php echo ucfirst(htmlspecialchars($themeDir)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="enabled_edit" name="enabled" value="1">
                <label for="enabled_edit" style="color: #ccc;">Enabled</label>
            </div>
            <button
                type="submit"
                name="edit_station"
                class="btn btn-primary"
                style="padding: 0.6rem 1.2rem; background: #007bff; border: none; border-radius: 4px; color: #fff;"
            >
                Save Changes
            </button>
        </form>
    </div>
</div>

<script>
// Open the edit modal and populate fields from the station’s config.json
function openEditModal(slug) {
    fetch(`/stations/${slug}/config.json`)
        .then(response => response.json())
        .then(cfg => {
            document.getElementById('slug_old').value = cfg.slug || slug;
            document.getElementById('display_name_edit').value = cfg.display_name || '';
            document.getElementById('mountpoint_edit').value = cfg.mountpoint || '';
            document.getElementById('theme_edit').value = cfg.theme || 'light';
            document.getElementById('enabled_edit').checked = cfg.enabled ? true : false;
            document.getElementById('editModal').style.display = 'block';
        })
        .catch(err => {
            alert('Failed to load station configuration.');
            console.error(err);
        });
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_template.php';
?>
