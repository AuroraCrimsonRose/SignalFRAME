Write-Host "Watching for restart flag. Press Ctrl+C to exit."
while ($true) {
    $flag = "F:\Users\Aurora\Documents\GitHub\SignalFRAME\shared\restart.flag"
    if (Test-Path $flag) {
        Write-Host "Flag detected! Restarting containers..."
        Remove-Item $flag
        Start-Process "F:\Users\Aurora\Documents\GitHub\SignalFRAME\shared\restart-containers.bat"
        Write-Host "Restart command sent. Waiting for next flag..."
    }
    Start-Sleep -Seconds 2
}