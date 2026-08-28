<?php
/**
 * Subscribers Management
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

// Handle Delete Subscriber
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM `subscribers` WHERE `id` = ?")->execute([$delId]);
    $_SESSION['flash_message'] = "Subscriber (ID #{$delId}) deleted successfully.";
    $_SESSION['flash_type'] = "success";
    header("Location: /admin/subscribers.php");
    exit;
}

$adminTitle = 'Subscribers';
$adminHeading = 'Device Subscribers';

require_once __DIR__ . '/includes/header.php';

$subscribers = $pdo->query("SELECT * FROM `subscribers` ORDER BY `created_at` DESC")->fetchAll();
?>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i class="fas fa-users"></i> Registered Device Subscribers (Total: <?= count($subscribers) ?>)
        </h2>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Device ID</th>
                    <th>Device Type</th>
                    <th>Browser & OS</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Registration Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-dim); padding: 40px;">
                            <i class="fas fa-users-slash" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            No subscribers registered yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td><?= $sub['id'] ?></td>
                            <td>
                                <code style="background: #F1F5F9; border: 1px solid var(--border-color); padding: 2px 6px; border-radius: 4px; color: var(--accent-blue); font-size: 0.8rem;">
                                    <?= sanitize(substr($sub['device_id'], 0, 16)) ?>...
                                </code>
                            </td>
                            <td>
                                <span class="badge <?= $sub['device_type'] === 'Mobile' ? 'badge-blue' : 'badge-gray' ?>">
                                    <i class="fas <?= $sub['device_type'] === 'Mobile' ? 'fa-mobile-screen' : 'fa-desktop' ?>"></i> <?= sanitize($sub['device_type']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-main);">
                                <?= sanitize($sub['browser'] ?? 'Unknown') ?> / <?= sanitize($sub['os'] ?? 'Unknown') ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-dim);">
                                <?= sanitize($sub['ip_address'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <span class="badge <?= $sub['status'] === 'active' ? 'badge-green' : 'badge-red' ?>">
                                    <?= $sub['status'] === 'active' ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-dim);">
                                <?= date('d M Y, h:i A', strtotime($sub['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <button type="button" class="btn-icon btn-icon-delete" title="Delete" onclick="confirmDelete('/admin/subscribers.php?action=delete&id=<?= $sub['id'] ?>', 'this subscriber')">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
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
