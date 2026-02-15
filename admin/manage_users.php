<?php
/**

 *
 * Manage users - tabular list with filter, pagination, and separate edit page.
 */

require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

if (!is_admin()) {
    redirect(SITE_URL . '/index.php');
}

require_once __DIR__ . '/../includes/db.php';

$success = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q = trim($_GET['q'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = ($page === 1) ? 10 : 20;
$offset = ($page === 1) ? 0 : (10 + (20 * ($page - 2)));

// Handle role change from dropdown
if ($action === 'change_role' && $user_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user_id === $_SESSION['user_id']) {
        $error = 'You cannot change your own role.';
    } else {
        $new_role = clean_input($_POST['role'] ?? '');
        $allowed = ['user', 'admin'];
        if (in_array($new_role, $allowed, true)) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
            $stmt->bind_param('si', $new_role, $user_id);
            if ($stmt->execute()) {
                $success = 'Role updated successfully.';
            } else {
                $error = 'Failed to update role.';
            }
            $stmt->close();
        } else {
            $error = 'Invalid role.';
        }
    }
    redirect(SITE_URL . '/admin/manage_users.php?success=' . urlencode($success) . '&error=' . urlencode($error));
}

// Handle delete
if ($action === 'delete' && $user_id > 0) {
    if ($user_id === $_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $success = 'User deleted successfully.';
            $max_id = 0;
            if ($r = $conn->query("SELECT MAX(user_id) AS max_id FROM users")) {
                $row = $r->fetch_assoc();
                $max_id = (int)($row['max_id'] ?? 0);
                $r->free();
            }
            $next_id = $max_id + 1;
            $conn->query("ALTER TABLE users AUTO_INCREMENT = " . (int)$next_id);
        } else {
            $error = 'Failed to delete user.';
        }
        $stmt->close();
    }
    redirect(SITE_URL . '/admin/manage_users.php?success=' . urlencode($success) . '&error=' . urlencode($error));
}

// Handle edit form save
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $edit_id = (int)($_POST['user_id'] ?? 0);
    $username = clean_input($_POST['username'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $role = clean_input($_POST['role'] ?? 'user');
    $new_password = $_POST['new_password'] ?? '';

    if ($edit_id <= 0) {
        $error = 'Invalid user id.';
    } elseif ($username === '' || $email === '') {
        $error = 'Username and email are required.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param('si', $email, $edit_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered by another user.';
        }
        $stmt->close();

        if ($error === '') {
            if ($new_password !== '') {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE user_id=?");
                $stmt->bind_param('ssssi', $username, $email, $role, $hashed, $edit_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
                $stmt->bind_param('sssi', $username, $email, $role, $edit_id);
            }
            if ($stmt->execute()) {
                $success = 'User updated successfully.';
            } else {
                $error = 'Failed to update user.';
            }
            $stmt->close();
        }
    }
    redirect(SITE_URL . '/admin/manage_users.php?success=' . urlencode($success) . '&error=' . urlencode($error));
}

// Get user for editing
$user = null;
if ($action === 'edit' && $user_id > 0) {
    $stmt = $conn->prepare("SELECT user_id, username, email, role, created_at FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
}

// Messages
$success = $success !== '' ? $success : (string)($_GET['success'] ?? '');
$error = $error !== '' ? $error : (string)($_GET['error'] ?? '');

// Count users
$total_users = 0;
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM users WHERE username LIKE ? OR email LIKE ? OR role LIKE ?");
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $total_users = (int)($row['c'] ?? 0);
    $stmt->close();
} else {
    $r = $conn->query("SELECT COUNT(*) AS c FROM users");
    $row = $r ? $r->fetch_assoc() : null;
    $total_users = (int)($row['c'] ?? 0);
    if ($r) { $r->free(); }
}

// Fetch users ascending
$users = [];
if ($action === 'list') {
    if ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare("SELECT user_id, username, email, role, created_at FROM users WHERE username LIKE ? OR email LIKE ? OR role LIKE ? ORDER BY user_id ASC LIMIT ? OFFSET ?");
        $stmt->bind_param('sssii', $like, $like, $like, $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $users[] = $row; }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, email, role, created_at FROM users ORDER BY user_id ASC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $per_page, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $users[] = $row; }
        $stmt->close();
    }
}

$page_title = 'Manage Users';
$active_page = 'users';
?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
            <?php if ($action === 'list'): ?>
                <div class="admin-header">
                    <h1>Manage Users</h1>
                    <div style="color: #6c757d;">Total: <?php echo (int)$total_users; ?> users</div>
                </div>

                <div class="filter-bar">
                    <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search username, email, role..." />
                        <input type="hidden" name="page" value="1" />
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="<?php echo SITE_URL; ?>/admin/manage_users.php" class="btn btn-secondary">Clear</a>
                    </form>
                </div>

                <?php if (!empty($success)): ?>
                    <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo (int)$u['user_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                    <?php if ((int)$u['user_id'] === (int)$_SESSION['user_id']): ?>
                                        <span style="font-size: 0.75rem; color: #6c757d; margin-left: 0.5rem;">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <?php $role = $u['role'] ?? 'user'; $is_admin = $role === 'admin'; ?>
                                    <?php if ((int)$u['user_id'] !== (int)$_SESSION['user_id']): ?>
                                        <form method="POST" action="<?php echo SITE_URL; ?>/admin/manage_users.php?action=change_role&id=<?php echo (int)$u['user_id']; ?>" style="display: inline;">
                                            <select name="role" onchange="this.form.submit()" class="role-select">
                                                <option value="user" <?php echo !$is_admin ? 'selected' : ''; ?>>Normal</option>
                                                <option value="admin" <?php echo $is_admin ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-<?php echo $role; ?>"><?php echo $is_admin ? 'Admin' : 'Normal'; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td class="actions">
                                    <?php if ((int)$u['user_id'] !== (int)$_SESSION['user_id']): ?>
                                        <a href="<?php echo SITE_URL; ?>/admin/manage_users.php?action=delete&id=<?php echo (int)$u['user_id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Delete this user?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                $total_pages = 1;
                if ($total_users > 10) {
                    $remaining = $total_users - 10;
                    $total_pages = 1 + (int)ceil($remaining / 20);
                }
                ?>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/admin/manage_users.php?q=<?php echo urlencode($q); ?>&page=<?php echo (int)($page - 1); ?>">← Prev</a>
                        <?php endif; ?>
                        <span style="color: #6c757d;">Page <?php echo (int)$page; ?> of <?php echo (int)$total_pages; ?></span>
                        <?php if ($page < $total_pages): ?>
                            <a class="btn btn-secondary" href="<?php echo SITE_URL; ?>/admin/manage_users.php?q=<?php echo urlencode($q); ?>&page=<?php echo (int)($page + 1); ?>">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

<?php elseif ($action === 'edit' && $user): ?>
                <div class="admin-header">
                    <h1>Edit User #<?php echo (int)$user['user_id']; ?></h1>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_users.php" class="btn btn-secondary">← Back to List</a>
                </div>

                <div class="edit-form">
                    <form method="POST" action="<?php echo SITE_URL; ?>/admin/manage_users.php?action=edit&id=<?php echo (int)$user['user_id']; ?>">
                        <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>" />
                        
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required />
                        </div>
                        
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required />
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" class="role-select">
                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Normal</option>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" placeholder="Enter new password" />
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" name="save_user" class="btn btn-success">Save Changes</button>
                            <a href="<?php echo SITE_URL; ?>/admin/manage_users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
