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
    $_SESSION['flash_message'] = "सब्सक्राइबर (ID #{$delId}) को सफलतापूर्वक हटा दिया गया है।";
    $_SESSION['flash_type'] = "success";
    header("Location: /admin/subscribers.php");
    exit;
}

$adminTitle = 'सब्सक्राइबर्स (Subscribers)';
$adminHeading = 'सब्सक्राइबर्स डिवाइस सूची (Device Subscribers)';

require_once __DIR__ . '/includes/header.php';

$subscribers = $pdo->query("SELECT * FROM `subscribers` ORDER BY `created_at` DESC")->fetchAll();
?>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i class="fas fa-users"></i> पंजीकृत डिवाइस सब्सक्राइबर्स (Total: <?= count($subscribers) ?>)
        </h2>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>डिवाइस आईडी</th>
                    <th>प्रकार (Type)</th>
                    <th>ब्राउज़र एवं OS</th>
                    <th>IP पता</th>
                    <th>स्थिति (Status)</th>
                    <th>पंजीकरण दिनांक</th>
                    <th style="text-align: right;">कार्य</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-dim); padding: 40px;">
                            <i class="fas fa-users-slash" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            अभी कोई सब्सक्राइबर पंजीकृत नहीं है।
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
                                    <?= $sub['status'] === 'active' ? 'सक्रिय (Active)' : 'निष्क्रिय' ?>
                                </span>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-dim);">
                                <?= date('d M Y, h:i A', strtotime($sub['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <button type="button" class="btn-icon btn-icon-delete" title="हटाएं" onclick="confirmDelete('/admin/subscribers.php?action=delete&id=<?= $sub['id'] ?>', 'इस सब्सक्राइबर')">
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
