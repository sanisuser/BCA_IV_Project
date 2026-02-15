<?php
/**
 * cron_backup.php
 * 
 * Automated daily backup script for cron job
 * Run this script using cron job for automated backups
 * 
 * Cron job example (runs daily at 2:00 AM):
 * 0 2 * * * php /path/to/bookhub/cron_backup.php >> /var/log/bookhub_backup.log 2>&1
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/DatabaseBackup.php';

// Initialize backup class
$backup = new DatabaseBackup($servername, $username, $password, $dbname);

echo "[" . date('Y-m-d H:i:s') . "] Starting automated backup...\n";

// Create backup
$result = $backup->createBackup();

if ($result) {
    echo "[" . date('Y-m-d H:i:s') . "] Backup created successfully: " . basename($result) . "\n";
    
    // Clean old backups (keep last 7 days)
    $deleted = $backup->cleanOldBackups(7);
    if ($deleted > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Cleaned up $deleted old backup(s)\n";
    }
    
    $totalSize = $backup->getBackupDirSize();
    echo "[" . date('Y-m-d H:i:s') . "] Total backup storage: " . DatabaseBackup::formatBytes($totalSize) . "\n";
    
    echo "[" . date('Y-m-d H:i:s') . "] Backup process completed successfully.\n";
    exit(0);
} else {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to create backup!\n";
    exit(1);
}
