<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$station = $_GET['station'] ?? 'example_station';
$logFile = __DIR__ . "/../../logs/messages/{$station}.log";

$linesPerPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$startLine = ($page - 1) * $linesPerPage;

$entries = [];

if (file_exists($logFile)) {
    $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $allLines = array_reverse($allLines);

    for ($i = $startLine; $i < min($startLine + $linesPerPage, count($allLines)); $i++) {
        $entries[] = $allLines[$i];
    }
} else {
    $entries[] = "No log file found for station: " . htmlspecialchars($station);
}

$totalPages = ceil(count($allLines ?? []) / $linesPerPage);
?>

<h3>Message Log for <?= htmlspecialchars($station) ?></h3>

<div style="max-height: 400px; overflow-y: scroll; background: #222; padding: 1rem; border-radius: 6px;">
    <?php foreach ($entries as $line): ?>
        <pre style="white-space: pre-wrap; color: #eee;"><?= htmlspecialchars($line) ?></pre>
    <?php endforeach; ?>
</div>

<div style="margin-top: 1rem;">
    <?php if ($page > 1): ?>
        <a href="?station=<?= urlencode($station) ?>&page=<?= $page - 1 ?>">« Previous</a>
    <?php endif; ?>
    &nbsp; Page <?= $page ?> of <?= $totalPages ?> &nbsp;
    <?php if ($page < $totalPages): ?>
        <a href="?station=<?= urlencode($station) ?>&page=<?= $page + 1 ?>">Next »</a>
    <?php endif; ?>
</div>
