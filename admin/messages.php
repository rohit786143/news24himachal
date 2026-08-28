<?php
/**
 * Contact Messages Management
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

// Handle Delete Message
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM `contacts` WHERE `id` = ?")->execute([$delId]);
    $_SESSION['flash_message'] = "Message (ID #{$delId}) deleted successfully.";
    $_SESSION['flash_type'] = "success";
    header("Location: /admin/messages.php");
    exit;
}

$adminTitle = 'Contact Messages';
$adminHeading = 'Contact Messages Inbox';

require_once __DIR__ . '/includes/header.php';

// Fetch all contact inquiries
$messages = $pdo->query("SELECT * FROM `contacts` ORDER BY `created_at` DESC")->fetchAll();
?>

<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i class="fas fa-envelope-open-text"></i> Received Messages (Total: <?= count($messages) ?>)
        </h2>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Sender</th>
                    <th>Email & Phone</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Date & Time</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            No contact messages received yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td><?= $msg['id'] ?></td>
                            <td>
                                <strong style="color: var(--text-heading); font-size: 0.95rem;"><?= sanitize($msg['name']) ?></strong>
                            </td>
                            <td>
                                <div><a href="mailto:<?= sanitize($msg['email']) ?>" style="color: var(--accent-blue); text-decoration: none;"><i class="fas fa-envelope"></i> <?= sanitize($msg['email']) ?></a></div>
                                <?php if (!empty($msg['phone'])): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-dim); margin-top: 2px;"><i class="fas fa-phone"></i> <?= sanitize($msg['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-amber" style="background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A;">
                                    <?= sanitize($msg['subject']) ?>
                                </span>
                            </td>
                            <td style="max-width: 320px; font-size: 0.85rem; color: var(--text-main); line-height: 1.4;">
                                <?= nl2br(sanitize($msg['message'])) ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-dim); white-space: nowrap;">
                                <?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <a href="mailto:<?= sanitize($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn-icon btn-icon-edit" title="Reply via Email">
                                        <i class="fas fa-reply"></i>
                                    </a>
                                    <button type="button" class="btn-icon btn-icon-delete" title="Delete" onclick="confirmDelete('/admin/messages.php?action=delete&id=<?= $msg['id'] ?>', 'this message')">
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
