<?php
/**
 * includes/DatabaseBackup.php
 * 
 * Database backup class for generating SQL dumps
 */

class DatabaseBackup {
    private $host;
    private $username;
    private $password;
    private $database;
    private $backupDir;
    
    public function __construct($host, $username, $password, $database) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->backupDir = __DIR__ . '/../backups';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Generate SQL backup of the database
     * @return string|bool Path to backup file or false on failure
     */
    public function createBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$this->database}_{$timestamp}.sql";
        $filepath = $this->backupDir . '/' . $filename;
        
        // Connect to database
        $conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($conn->connect_error) {
            return false;
        }
        
        // Start output buffer
        $output = "-- BookHub Database Backup\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Database: {$this->database}\n";
        $output .= "-- Host: {$this->host}\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Get all tables
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        foreach ($tables as $table) {
            // Drop table if exists
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n\n";
            
            // Get create table statement
            $result = $conn->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch_assoc();
            $output .= $row['Create Table'] . ";\n\n";
            
            // Get table data
            $result = $conn->query("SELECT * FROM `{$table}`");
            if ($result->num_rows > 0) {
                $output .= "INSERT INTO `{$table}` VALUES\n";
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    $rows[] = "(" . implode(", ", $values) . ")";
                }
                $output .= implode(",\n", $rows) . ";\n\n";
            }
        }
        
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Write to file
        if (file_put_contents($filepath, $output) === false) {
            $conn->close();
            return false;
        }
        
        $conn->close();
        return $filepath;
    }
    
    /**
     * Get list of all backup files
     * @return array List of backup files with metadata
     */
    public function getBackups() {
        $backups = [];
        
        if (!is_dir($this->backupDir)) {
            return $backups;
        }
        
        $files = glob($this->backupDir . '/*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'created' => filemtime($file),
                'date_formatted' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by date descending
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    /**
     * Delete a backup file
     * @param string $filename Name of backup file
     * @return bool True on success, false on failure
     */
    public function deleteBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Download a backup file
     * @param string $filename Name of backup file
     */
    public function downloadBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($filepath);
        exit;
    }
    
    /**
     * Clean old backups (keep only last N backups)
     * @param int $keep Number of backups to keep
     * @return int Number of deleted backups
     */
    public function cleanOldBackups($keep = 7) {
        $backups = $this->getBackups();
        $deleted = 0;
        
        if (count($backups) > $keep) {
            $toDelete = array_slice($backups, $keep);
            foreach ($toDelete as $backup) {
                if ($this->deleteBackup($backup['filename'])) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get backup directory size
     * @return int Total size in bytes
     */
    public function getBackupDirSize() {
        $size = 0;
        $files = glob($this->backupDir . '/*.sql');
        
        foreach ($files as $file) {
            $size += filesize($file);
        }
        
        return $size;
    }
    
    /**
     * Format bytes to human readable
     * @param int $bytes
     * @return string
     */
    public static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
