<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

$iframeApiToken = $_GET['api_token'] ?? '';
$iframeApiTokenJs = htmlspecialchars($iframeApiToken, ENT_QUOTES, 'UTF-8');

$station = $_GET['station'] ?? 'example_station';
$logFile = __DIR__ . "/../stations/$station/log.txt";

?>

<script>
  window.SIGNALFRAME_API_TOKEN = "<?= $iframeApiTokenJs ?>";
</script>

<h3>Log Viewer for <?= htmlspecialchars($station) ?></h3>

<pre style="background:#222;color:#eee;padding:1rem;max-height:300px;overflow-y:auto;">
<?php
if (file_exists($logFile)) {
    echo htmlspecialchars(file_get_contents($logFile));
} else {
    echo "No logs found.";
}
?>
</pre>
