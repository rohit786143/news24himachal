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
    $_SESSION['flash_message'] = "अनुमति अस्वीकृत: यह पेज केवल मुख्य एडमिन के लिए है।";
    $_SESSION['flash_type'] = "danger";
    header("Location: /admin/index.php");
    exit;
}

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    
    // Prevent deleting own account
    if ($delId === $currentUserId) {
        $_SESSION['flash_message'] = "त्रुटि: आप अपने स्वयं के लॉगिन खाते को नहीं हटा सकते।";
        $_SESSION['flash_type'] = "danger";
    } else {
        $stmt = $pdo->prepare("DELETE FROM `users` WHERE `id` = ?");
        $stmt->execute([$delId]);
        $_SESSION['flash_message'] = "संपादक / यूज़र (ID #{$delId}) को सफलतापूर्वक हटा दिया गया है।";
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
        $_SESSION['flash_message'] = "यूज़र की स्थिति '{$newStatus}' में बदल दी गई है।";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: /admin/users.php");
    exit;
}

$adminTitle = 'संपादक एवं रिपोर्टर्स (Editors)';
$adminHeading = 'संपादक एवं रिपोर्टर प्रबंधन (Editor & User Management)';

require_once __DIR__ . '/includes/header.php';

// Fetch all users with their article count
$users = $pdo->query("
    SELECT u.*, COUNT(n.id) as article_count, COALESCE(SUM(n.views), 0) as total_views
    FROM `users` u
    LEFT JOIN `news` n ON u.id = n.author_id
    GROUP BY u.id
    ORDER BY (u.role = 'admin') DESC, u.created_at DESC
")->fetchAll();
?>

<!-- Header Action Row -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h3 style="font-size: 1.1rem; color: var(--text-heading); font-weight: 800;">
            <i class="fas fa-users-viewfinder" style="color: var(--primary);"></i> पंजीकृत संवाददाता एवं एडमिन (Total: <?= count($users) ?>)
        </h3>
        <p style="font-size: 0.84rem; color: var(--text-muted);">यहाँ से आप नए संपादकों को जोड़ सकते हैं और उनके पोस्ट्स के अधिकार प्रबंधित कर सकते हैं।</p>
    </div>
    
    <a href="/admin/user-edit.php" class="topbar-btn" style="padding: 10px 18px;">
        <i class="fas fa-user-plus"></i> नया संपादक जोड़ें (Add New Editor)
    </a>
</div>

<!-- Users Table Panel -->
<div class="panel">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>संपादक (Reporter / User)</th>
                    <th>यूज़रनेम एवं ईमेल</th>
                    <th>पदनाम (Designation) & ज़िला</th>
                    <th>भूमिका (Role)</th>
                    <th>प्रकाशित खबरें</th>
                    <th>स्थिति</th>
                    <th style="text-align: right;">कार्य</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            कोई संपादक पंजीकृत नहीं है।
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
                                                <span class="badge badge-green" style="font-size: 0.65rem; padding: 1px 4px;">आप (You)</span>
                                            <?php endif; ?>
                                        </strong>
                                        <a href="/author.php?id=<?= $u['id'] ?>" target="_blank" style="font-size: 0.76rem; color: #0284C7; text-decoration: none;">
                                            <i class="fas fa-arrow-up-right-from-square"></i> पब्लिक प्रोफाइल देखें
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
                                    <?= sanitize($u['designation'] ?: 'संवाददाता') ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    <i class="fas fa-location-dot" style="color: var(--primary);"></i> <?= sanitize($u['location'] ?: 'हिमाचल प्रदेश') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge badge-red" style="font-weight: 800;">
                                        👑 मुख्य एडमिन (Admin)
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-blue" style="font-weight: 800;">
                                        ✍️ संपादक (Editor)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--primary); font-size: 1rem;"><?= number_format($u['article_count']) ?></strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= number_format($u['total_views']) ?> व्यूज</div>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'active'): ?>
                                    <a href="/admin/users.php?action=toggle_status&id=<?= $u['id'] ?>" class="badge badge-green" style="text-decoration: none;" title="क्लिक कर निष्क्रिय करें">
                                        <i class="fas fa-check-circle"></i> सक्रिय (Active)
                                    </a>
                                <?php else: ?>
                                    <a href="/admin/users.php?action=toggle_status&id=<?= $u['id'] ?>" class="badge badge-red" style="text-decoration: none;" title="क्लिक कर सक्रिय करें">
                                        <i class="fas fa-ban"></i> निष्क्रिय (Inactive)
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <a href="/admin/user-edit.php?id=<?= $u['id'] ?>" class="btn-icon btn-icon-edit" title="एडिट करें">
                                        <i class="fas fa-pencil"></i>
                                    </a>
                                    <?php if ($u['id'] != $currentUserId): ?>
                                        <button type="button" class="btn-icon btn-icon-delete" title="हटाएं" onclick="confirmDelete('/admin/users.php?action=delete&id=<?= $u['id'] ?>', 'इस संपादक')">
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
