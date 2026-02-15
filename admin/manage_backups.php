<?php
/**
 * admin/manage_backups.php
 * 
 * Database backup management page
 */

$page_title = 'Database Backups';
$active_page = 'backups';

require_once __DIR__ . '/../includes/functions.php';

// Check admin access
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!is_admin()) {
    redirect(SITE_URL . '/index.php');
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/DatabaseBackup.php';

// Initialize backup class
$backup = new DatabaseBackup($servername, $username, $password, $dbname);

$message = '';
$message_type = '';

// Handle actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'create':
            $result = $backup->createBackup();
            if ($result) {
                $message = 'Backup created successfully: ' . basename($result);
                $message_type = 'success';
            } else {
                $message = 'Failed to create backup';
                $message_type = 'error';
            }
            break;
            
        case 'download':
            if (isset($_GET['file'])) {
                $backup->downloadBackup($_GET['file']);
            }
            break;
            
        case 'delete':
            if (isset($_GET['file'])) {
                if ($backup->deleteBackup($_GET['file'])) {
                    $message = 'Backup deleted successfully';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to delete backup';
                    $message_type = 'error';
                }
            }
            break;
            
        case 'clean':
            $deleted = $backup->cleanOldBackups(7);
            $message = "Deleted $deleted old backup(s). Keeping last 7 backups.";
            $message_type = 'success';
            break;
    }
}

// Get all backups
$backups = $backup->getBackups();
$totalSize = $backup->getBackupDirSize();

require_once __DIR__ . '/partials/header.php';
?>

<div class="admin-section">
    <h1><i class="fas fa-database"></i> Database Backups</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="color: #7f8c8d; font-size: 0.875rem; margin-bottom: 0.5rem;">Total Backups</div>
            <div style="font-size: 2rem; font-weight: 700; color: #2c3e50;"><?php echo count($backups); ?></div>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="color: #7f8c8d; font-size: 0.875rem; margin-bottom: 0.5rem;">Storage Used</div>
            <div style="font-size: 2rem; font-weight: 700; color: #2c3e50;"><?php echo DatabaseBackup::formatBytes($totalSize); ?></div>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="color: #7f8c8d; font-size: 0.875rem; margin-bottom: 0.5rem;">Latest Backup</div>
            <div style="font-size: 1.25rem; font-weight: 700; color: #2c3e50;">
                <?php echo count($backups) > 0 ? date('M d, Y', $backups[0]['created']) : 'None'; ?>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div style="margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <a href="?action=create" class="btn btn-primary" onclick="return confirm('Create new database backup?')">
            <i class="fas fa-plus-circle"></i> Create Backup Now
        </a>
        <a href="?action=clean" class="btn btn-secondary" onclick="return confirm('Delete old backups and keep only last 7?')">
            <i class="fas fa-broom"></i> Clean Old Backups
        </a>
    </div>
    
    <!-- Backups Table -->
    <div class="table-container" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden;">
        <?php if (count($backups) > 0): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 1px solid #dee2e6;">Filename</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 1px solid #dee2e6;">Date Created</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #2c3e50; border-bottom: 1px solid #dee2e6;">Size</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; color: #2c3e50; border-bottom: 1px solid #dee2e6;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $index => $b): ?>
                        <tr style="border-bottom: 1px solid #f1f3f4;">
                            <td style="padding: 1rem;">
                                <i class="fas fa-file-code" style="color: #3498db; margin-right: 0.5rem;"></i>
                                <?php echo htmlspecialchars($b['filename']); ?>
                                <?php if ($index === 0): ?>
                                    <span style="background: #27ae60; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">Latest</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; color: #6c757d;">
                                <?php echo $b['date_formatted']; ?>
                            </td>
                            <td style="padding: 1rem; color: #6c757d;">
                                <?php echo DatabaseBackup::formatBytes($b['size']); ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <a href="?action=download&file=<?php echo urlencode($b['filename']); ?>" 
                                   class="btn btn-small" 
                                   style="background: #3498db; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; margin-right: 0.5rem;">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <a href="?action=delete&file=<?php echo urlencode($b['filename']); ?>" 
                                   class="btn btn-small" 
                                   style="background: #e74c3c; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none;"
                                   onclick="return confirm('Delete this backup?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="padding: 3rem; text-align: center; color: #6c757d;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                <p>No backups found. Create your first backup now!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Auto Backup Info -->
    <div style="margin-top: 2rem; background: #e3f2fd; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #2196f3;">
        <h3 style="margin-top: 0; color: #1565c0;"><i class="fas fa-info-circle"></i> Automated Backups</h3>
        <p style="margin-bottom: 0.5rem;">To enable daily automated backups, add this cron job to your server:</p>
        <code style="display: block; background: #263238; color: #aed581; padding: 1rem; border-radius: 4px; font-family: monospace; margin-top: 0.5rem;">
            0 2 * * * php <?php echo realpath(__DIR__ . '/../cron_backup.php'); ?> >> /dev/null 2>&1
        </code>
        <p style="margin-top: 1rem; font-size: 0.875rem; color: #546e7a;">
            This will run daily at 2:00 AM and create a backup automatically.
        </p>
    </div>
</div>

<style>
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}
.btn-primary {
    background: #3498db;
    color: white;
}
.btn-primary:hover {
    background: #2980b9;
}
.btn-secondary {
    background: #95a5a6;
    color: white;
}
.btn-secondary:hover {
    background: #7f8c8d;
}
.alert {
    padding: 1rem 1.5rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
