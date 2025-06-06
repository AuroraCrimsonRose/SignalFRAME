<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * engine/logger.php
 * 
 * Provides a simple file‐based logging function for SignalFrame.
 * Each call to logEvent() appends one line to ./logs/app.log.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Append a log entry to ./logs/app.log
 *
 * @param PDO    $pdo       (unused here, but kept for compatibility)
 * @param int    $userId    ID of the user performing the action
 * @param string $level     Log level, e.g. 'info', 'warning', 'error'
 * @param string $message   Descriptive message of what happened
 */
function logEvent(PDO $pdo, int $userId, string $level, string $message): void
{
    // 1) Determine logs directory and file path
    $logsDir  = __DIR__ . '/../logs';
    $logFile  = "$logsDir/app.log";

    // 2) Ensure logs directory exists
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }

    // 3) Format timestamp
    $timestamp = date('Y-m-d H:i:s');

    // 4) Sanitize level and message to avoid stray newlines
    $cleanLevel   = strtoupper(preg_replace('/\s+/', '', $level));
    $cleanMessage = str_replace(["\r", "\n"], [' ', ' '], trim($message));

    // 5) Compose log line
    $logLine = sprintf(
        "[%s] [%s] [User: %d] %s%s",
        $timestamp,
        $cleanLevel,
        $userId,
        $cleanMessage,
        PHP_EOL
    );

    // 6) Append to file
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}
