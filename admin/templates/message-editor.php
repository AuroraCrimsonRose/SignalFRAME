<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

$iframeApiToken = $_GET['api_token'] ?? '';
$iframeApiTokenJs = htmlspecialchars($iframeApiToken, ENT_QUOTES, 'UTF-8');

$station = $_GET['station'] ?? 'example_station';
$messageFile = __DIR__ . "/../stations/$station/message.txt";

$currentMessage = file_exists($messageFile) ? file_get_contents($messageFile) : "";
?>

<script>
  window.SIGNALFRAME_API_TOKEN = "<?= $iframeApiTokenJs ?>";
</script>

<form id="message-editor-form" class="ajax-form" action="/api/update-message.php?station=<?= urlencode($station) ?>" method="POST">
  <label for="message">Edit Station Message:</label><br>
  <textarea id="message" name="message" rows="6" style="width: 100%;"><?= htmlspecialchars($currentMessage) ?></textarea>
  <br><br>
  <button type="submit">Save Message</button>
  <div class="response-message" aria-live="polite"></div>
</form>
