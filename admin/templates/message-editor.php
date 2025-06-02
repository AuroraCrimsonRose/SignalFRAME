<!-- /admin/templates/message-editor.php -->
<?php
$station = $_GET['station'] ?? 'example_station';
$stationPath = __DIR__ . "/../../stations/$station/";
$messageFile = $stationPath . "message.txt";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newMessage = trim($_POST['message'] ?? '');
  $newMessage = strip_tags($newMessage, '<b><i><u><em><strong>'); // Allow basic formatting
  file_put_contents($messageFile, $newMessage);
  $saved = true;
}

$currentMessage = file_exists($messageFile) ? file_get_contents($messageFile) : "";
?>

<form method="POST">
  <label for="message">Station Message (visible to listeners):</label><br>
  <textarea id="message" name="message" rows="4" style="width: 100%;"><?= htmlspecialchars($currentMessage) ?></textarea>
  <br><button type="submit">Save Message</button>
  <?php if (!empty($saved)): ?>
    <p style="color: limegreen;">Message updated!</p>
  <?php endif; ?>
</form>
