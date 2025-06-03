<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

function unauthorized() {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user is authenticated via session
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
} else {
    // Otherwise, authenticate via API token
    $token = null;

    // Check for Bearer token in Authorization header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
    // Fallback to api_token in POST data
    elseif (isset($_POST['api_token'])) {
        $token = $_POST['api_token'];
    }

    if (!$token) {
        unauthorized();
    }

    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../config/users.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT users.id, users.username, users.role FROM users JOIN api_tokens ON users.id = api_tokens.user_id WHERE api_tokens.token = ? AND api_tokens.revoked = 0');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            unauthorized();
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
}

$iframeApiToken = $_GET['api_token'] ?? '';
$iframeApiTokenJs = htmlspecialchars($iframeApiToken, ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/../engine/list-themes.php';

$station = $_GET['station'] ?? 'example_station';
$stationPath = __DIR__ . "/../stations/$station/";
$configPath = $stationPath . "config.json";

$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$currentTheme = $config['theme'] ?? '';

$themes = listAvailableThemes($stationPath);
?>

<script>
  window.SIGNALFRAME_API_TOKEN = "<?= $iframeApiTokenJs ?>";
</script>

<form id="theme-selector-form" class="ajax-form" action="/api/update-theme.php?station=<?= urlencode($station) ?>" method="POST">
  <label for="theme">Select Theme:</label><br>
  <select name="theme" id="theme">
    <?php foreach ($themes as $theme): ?>
      <option value="<?= htmlspecialchars($theme['id']) ?>" <?= $theme['id'] === $currentTheme ? 'selected' : '' ?>>
        <?= htmlspecialchars($theme['name']) ?> (<?= $theme['source'] ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <br><br>
  <button type="submit">Save Theme</button>
  <div class="response-message" aria-live="polite"></div>
</form>
