<?php
/**
 * Users / Editors Management
 * News 24 Himachal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Check auth
if (empty($_SESSION['admin_user'])) {
    header("Location: /admin/login.php");
    exit;
}

$currentUser = $_SESSION['admin_user'];
$currentUserId = (int)$currentUser['id'];
$isAdmin = ($currentUser['role'] === 'admin');

// Only Admin can access this page
if (!$isAdmin) {
    $_SESSION['flash_message'] = "Permission Denied: This page is accessible by Admin only.";
    $_SESSION['flash_type'] = "danger";
    header("Location: /admin/index.php");
    exit;
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    
    // Prevent deleting own account
    if ($delId === $currentUserId) {
        $_SESSION['flash_message'] = "Error: You cannot delete your own account.";
        $_SESSION['flash_type'] = "danger";
    } else {
        $stmt = $pdo->prepare("DELETE FROM `users` WHERE `id` = ?");
        $stmt->execute([$delId]);
        $_SESSION['flash_message'] = "Editor / User (ID #{$delId}) deleted successfully.";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: /admin/users.php");
    exit;
}

// Handle Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $toggleId = (int)$_GET['id'];
    if ($toggleId !== $currentUserId) {
        $currStatus = $pdo->query("SELECT `status` FROM `users` WHERE `id` = {$toggleId}")->fetchColumn();
        $newStatus = ($currStatus === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE `users` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $toggleId]);
        $_SESSION['flash_message'] = "User status updated to '{$newStatus}'.";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: users.php");
    exit;
}

$adminTitle = 'Editors & Users';
$adminHeading = 'Editor & User Management';

require_once __DIR__ . '/includes/header.php';

// Fetch all users safely
$users = $pdo->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM `news` WHERE `author` = u.name OR `author` = u.username) as article_count,
        (SELECT COALESCE(SUM(views), 0) FROM `news` WHERE `author` = u.name OR `author` = u.username) as total_views
    FROM `users` u
    ORDER BY (u.role = 'admin') DESC, u.created_at DESC
")->fetchAll();
?>

<!-- Header Action Row -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h3 style="font-size: 1.1rem; color: var(--text-heading); font-weight: 800;">
            <i class="fas fa-users-viewfinder" style="color: var(--primary);"></i> Registered Reporters & Admins (Total: <?= count($users) ?>)
        </h3>
        <p style="font-size: 0.84rem; color: var(--text-muted);">Add new editors, reset passwords, and manage permissions.</p>
    </div>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="profile.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 16px;">
            <i class="fas fa-key"></i> Change My Password / Profile
        </a>
        <a href="user-edit.php" class="topbar-btn" style="padding: 10px 18px;">
            <i class="fas fa-user-plus"></i> + Add New Editor
        </a>
    </div>
</div>

<!-- Users Table Panel -->
<div class="panel">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Reporter / User</th>
                    <th>Username & Email</th>
                    <th>Designation & Location</th>
                    <th>Role</th>
                    <th>Published Articles</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No editors registered yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="<?= sanitize($u['avatar'] ?: 'https://via.placeholder.com/40') ?>" 
                                         alt="<?= sanitize($u['name']) ?>" 
                                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $u['role'] === 'admin' ? 'var(--primary)' : '#38BDF8' ?>;">
                                    <div>
                                        <strong style="color: var(--text-heading); font-size: 0.95rem; display: block;">
                                            <?= sanitize($u['name']) ?>
                                            <?php if ($u['id'] == $currentUserId): ?>
                                                <span class="badge badge-green" style="font-size: 0.65rem; padding: 1px 4px;">You</span>
                                            <?php endif; ?>
                                        </strong>
                                        <a href="/author.php?id=<?= $u['id'] ?>" target="_blank" style="font-size: 0.76rem; color: #0284C7; text-decoration: none;">
                                            <i class="fas fa-arrow-up-right-from-square"></i> View Public Profile
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><code style="background: #F1F5F9; border: 1px solid var(--border-color); padding: 2px 6px; border-radius: 4px; color: var(--text-heading); font-weight: 700;">@<?= sanitize($u['username']) ?></code></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 3px;">
                                    <i class="fas fa-envelope"></i> <?= sanitize($u['email']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-main); font-size: 0.88rem;">
                                    <?= sanitize($u['designation'] ?: 'Reporter') ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    <i class="fas fa-location-dot" style="color: var(--primary);"></i> <?= sanitize($u['location'] ?: 'Himachal Pradesh') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge badge-red" style="font-weight: 800;">
                                        👑 Chief Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-blue" style="font-weight: 800;">
                                        ✍️ Editor
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--primary); font-size: 1rem;"><?= number_format($u['article_count']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= number_format($u['total_views']) ?> views</div>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <a href="users.php?action=toggle_status&id=<?= $u['id'] ?>" class="badge badge-green" style="text-decoration: none;" title="Click to Deactivate">
                                        <i class="fas fa-check-circle"></i> Active
                                    </a>
                                <?php else: ?>
                                    <a href="users.php?action=toggle_status&id=<?= $u['id'] ?>" class="badge badge-red" style="text-decoration: none;" title="Click to Activate">
                                        <i class="fas fa-ban"></i> Inactive
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <a href="user-edit.php?id=<?= $u['id'] ?>" class="btn-icon btn-icon-edit" title="Edit & Change Password">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                    <?php if ($u['id'] != $currentUserId): ?>
                                        <button type="button" class="btn-icon btn-icon-delete" title="Delete" onclick="confirmDelete('users.php?action=delete&id=<?= $u['id'] ?>', 'this editor')">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
