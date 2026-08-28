<?php
/**
 * Admin Live Bulletin & Timeline Management
 * Himachal News - Khabar 24
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
$isAdmin = ($currentUser['role'] === 'admin');

if (!$isAdmin) {
    $_SESSION['flash_message'] = "Permission Denied: Live bulletin management is accessible by Admin only.";
    $_SESSION['flash_type'] = "danger";
    header("Location: /admin/index.php");
    exit;
}

// Fetch active or latest bulletin
$activeBulletin = getActiveLiveBulletin($pdo);
if (!$activeBulletin) {
    $pdo->exec("
        INSERT INTO `live_bulletins` (`title`, `video_url`, `is_live`, `description`, `created_at`)
        VALUES ('Himachal Live Breaking Bulletin: Latest Major Updates', '', 1, 'Fastest and most accurate live bulletin of Himachal Pradesh.', NOW())
    ");
    $activeBulletin = getActiveLiveBulletin($pdo);
}

$bulletinId = (int)$activeBulletin['id'];

// Handle Actions BEFORE including header.php

// 1. Save Main Bulletin Header Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bulletin'])) {
    $title = trim($_POST['title'] ?? '');
    $isLive = !empty($_POST['is_live']) ? 1 : 0;
    $description = trim($_POST['description'] ?? '');

    if (!empty($title)) {
        $stmt = $pdo->prepare("
            UPDATE `live_bulletins`
            SET `title` = ?, `is_live` = ?, `description` = ?
            WHERE `id` = ?
        ");
        $stmt->execute([$title, $isLive, $description, $bulletinId]);

        $_SESSION['flash_message'] = "Live bulletin header settings updated successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: /admin/live-bulletins.php");
        exit;
    }
}

// 2. Add New Quick Timeline Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_update'])) {
    $timeLabel = trim($_POST['timestamp_label'] ?? date('h:i A'));
    $headline = trim($_POST['headline'] ?? '');
    $badgeType = trim($_POST['badge_type'] ?? 'Update');

    if (!empty($headline)) {
        $stmt = $pdo->prepare("
            INSERT INTO `bulletin_updates` (`bulletin_id`, `timestamp_label`, `headline`, `badge_type`, `created_at`)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$bulletinId, $timeLabel, $headline, $badgeType]);

        $_SESSION['flash_message'] = "New live timeline update added to top of feed!";
        $_SESSION['flash_type'] = "success";
        header("Location: /admin/live-bulletins.php");
        exit;
    }
}

// 3. Edit Existing Timeline Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit_update'])) {
    $uId = (int)($_POST['update_id'] ?? 0);
    $timeLabel = trim($_POST['edit_timestamp_label'] ?? date('h:i A'));
    $headline = trim($_POST['edit_headline'] ?? '');
    $badgeType = trim($_POST['edit_badge_type'] ?? 'Update');

    if ($uId > 0 && !empty($headline)) {
        $stmt = $pdo->prepare("
            UPDATE `bulletin_updates`
            SET `timestamp_label` = ?, `headline` = ?, `badge_type` = ?
            WHERE `id` = ? AND `bulletin_id` = ?
        ");
        $stmt->execute([$timeLabel, $headline, $badgeType, $uId, $bulletinId]);

        $_SESSION['flash_message'] = "Timeline update modified successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: /admin/live-bulletins.php");
        exit;
    }
}

// 4. Delete Timeline Update
if (isset($_GET['action']) && $_GET['action'] === 'delete_update' && isset($_GET['update_id'])) {
    $uId = (int)$_GET['update_id'];
    $pdo->prepare("DELETE FROM `bulletin_updates` WHERE `id` = ? AND `bulletin_id` = ?")->execute([$uId, $bulletinId]);
    $_SESSION['flash_message'] = "Timeline update deleted.";
    $_SESSION['flash_type'] = "success";
    header("Location: /admin/live-bulletins.php");
    exit;
}

// 5. Clear All Timeline Updates
if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    $pdo->prepare("DELETE FROM `bulletin_updates` WHERE `bulletin_id` = ?")->execute([$bulletinId]);
    $_SESSION['flash_message'] = "All timeline updates cleared.";
    $_SESSION['flash_type'] = "success";
    header("Location: /admin/live-bulletins.php");
    exit;
}

// Fetch update for editing if requested
$editingUpdate = null;
if (isset($_GET['edit_id'])) {
    $eId = (int)$_GET['edit_id'];
    $eStmt = $pdo->prepare("SELECT * FROM `bulletin_updates` WHERE `id` = ? AND `bulletin_id` = ?");
    $eStmt->execute([$eId, $bulletinId]);
    $editingUpdate = $eStmt->fetch();
}

$updates = getBulletinUpdates($pdo, $bulletinId, 100);

$adminTitle = 'Live Bulletins';
$adminHeading = '🔴 Live Bulletin & Timeline Management';

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1.1fr 1.9fr; gap: 24px;">
    
    <!-- Column 1: Live Bulletin Title & Settings -->
    <div>
        <!-- Active Bulletin Header Settings -->
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-tower-broadcast" style="color: var(--primary-red);"></i> 1. Live Bulletin Header Settings</h2>
                <a href="/live-bulletin.php" target="_blank" style="color: #0284C7; font-size: 0.85rem; font-weight: 700; text-decoration: none;">
                    <i class="fas fa-arrow-up-right-from-square"></i> View Live Page
                </a>
            </div>
            <div class="panel-body">
                <form method="POST" action="/admin/live-bulletins.php">
                    <input type="hidden" name="save_bulletin" value="1">

                    <div class="form-group">
                        <label class="form-label" for="bulletinTitle">Bulletin Headline Title <span class="required">*</span></label>
                        <input type="text" id="bulletinTitle" name="title" class="form-control" 
                               value="<?= sanitize($activeBulletin['title']) ?>" required>
                    </div>

                    <div class="form-group" style="background: #FEF2F2; border: 1.5px solid #FECACA; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 800; color: #991B1B; margin: 0;">
                            <input type="checkbox" name="is_live" value="1" <?= !empty($activeBulletin['is_live']) ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                            <span>🔴 Keep 'LIVE BULLETIN' Active</span>
                        </label>
                        <span class="form-hint" style="color: #7F1D1D; margin-top: 4px; display: block;">
                            '🔴 LIVE BULLETIN' blinking badge will be displayed on the website.
                        </span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bulletinDesc">Short Description / Intro</label>
                        <textarea id="bulletinDesc" name="description" class="form-control" rows="4"><?= sanitize($activeBulletin['description'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="topbar-btn" style="width: 100%; justify-content: center; padding: 12px;">
                        <i class="fas fa-floppy-disk"></i> Save Header Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Timeline Reset Box -->
        <div class="panel" style="margin-top: 24px;">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-arrows-rotate"></i> Timeline Management</h2>
            </div>
            <div class="panel-body">
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 14px;">
                    Clear all previous timeline updates with a single click to start a new live coverage.
                </p>
                <button type="button" class="topbar-btn topbar-btn-secondary" 
                        style="width: 100%; justify-content: center; color: #DC2626; border-color: #FECACA; background: #FEF2F2;"
                        onclick="confirmDelete('/admin/live-bulletins.php?action=clear_all', 'all timeline updates')">
                    <i class="fas fa-trash-can"></i> Clear Entire Timeline (Reset)
                </button>
            </div>
        </div>
    </div>

    <!-- Column 2: Fast Timeline Poster & Live Entries -->
    <div>
        
        <?php if ($editingUpdate): ?>
            <!-- Edit Specific Timeline Update Box -->
            <div class="panel" style="border: 2px solid #0284C7; box-shadow: 0 0 15px rgba(2, 132, 199, 0.15);">
                <div class="panel-header" style="background: #E0F2FE;">
                    <h2 class="panel-title" style="color: #0369A1;">
                        <i class="fas fa-pen-to-square"></i> Edit Timeline Update #<?= $editingUpdate['id'] ?>
                    </h2>
                    <a href="/admin/live-bulletins.php" class="topbar-btn topbar-btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
                <div class="panel-body">
                    <form method="POST" action="/admin/live-bulletins.php">
                        <input type="hidden" name="save_edit_update" value="1">
                        <input type="hidden" name="update_id" value="<?= $editingUpdate['id'] ?>">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="editTimeInput">Time / Timestamp</label>
                                <input type="text" id="editTimeInput" name="edit_timestamp_label" class="form-control" 
                                       value="<?= sanitize($editingUpdate['timestamp_label']) ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="editBadgeSelect">Category Badge</label>
                                <select id="editBadgeSelect" name="edit_badge_type" class="form-control">
                                    <option value="Breaking News" <?= in_array($editingUpdate['badge_type'], ['Breaking News', 'बड़ी खबर']) ? 'selected' : '' ?>>🔴 Breaking News</option>
                                    <option value="Latest Update" <?= in_array($editingUpdate['badge_type'], ['Latest Update', 'अपडेट']) ? 'selected' : '' ?>>🔵 Latest Update</option>
                                    <option value="Weather Alert" <?= in_array($editingUpdate['badge_type'], ['Weather Alert', 'मौसम']) ? 'selected' : '' ?>>🟡 Weather Alert</option>
                                    <option value="Sports News" <?= in_array($editingUpdate['badge_type'], ['Sports News', 'खेल']) ? 'selected' : '' ?>>🟢 Sports News</option>
                                    <option value="Politics" <?= in_array($editingUpdate['badge_type'], ['Politics', 'सियासत']) ? 'selected' : '' ?>>🟣 Politics</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="editHeadlineInput">Headline Details <span class="required">*</span></label>
                            <textarea id="editHeadlineInput" name="edit_headline" class="form-control" rows="3" required><?= sanitize($editingUpdate['headline']) ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="topbar-btn" style="padding: 10px 24px;">
                                <i class="fas fa-check"></i> Save Changes
                            </button>
                            <a href="/admin/live-bulletins.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Fast Instant Timeline Poster -->
            <div class="panel">
                <div class="panel-header" style="background: linear-gradient(135deg, #1E1B4B, #0F172A); color: #FFF;">
                    <h2 class="panel-title" style="color: #FFF;"><i class="fas fa-bolt" style="color: #FDE047;"></i> 2. Post New Live Timeline Update (Top-Of-Feed)</h2>
                </div>
                <div class="panel-body">
                    <form method="POST" action="/admin/live-bulletins.php">
                        <input type="hidden" name="add_update" value="1">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="timeLabelInput">Time / Timestamp</label>
                                <input type="text" id="timeLabelInput" name="timestamp_label" class="form-control" 
                                       value="<?= date('h:i A') ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="badgeTypeSelect">Category Badge</label>
                                <select id="badgeTypeSelect" name="badge_type" class="form-control">
                                    <option value="Breaking News" selected>🔴 Breaking News</option>
                                    <option value="Latest Update">🔵 Latest Update</option>
                                    <option value="Weather Alert">🟡 Weather Alert</option>
                                    <option value="Sports News">🟢 Sports News</option>
                                    <option value="Politics">🟣 Politics</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="headlineInput">Live Headline / Bullet Details <span class="required">*</span></label>
                            <textarea id="headlineInput" name="headline" class="form-control" rows="3" 
                                      placeholder="e.g. Administration issues fresh advisory following heavy snowfall on Shimla-Manali Highway..." required></textarea>
                        </div>

                        <button type="submit" class="topbar-btn" style="padding: 10px 24px; font-size: 0.95rem;">
                            <i class="fas fa-paper-plane"></i> Post to Timeline Now &rarr;
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Real-Time Timeline List Panel -->
        <div class="panel" style="margin-top: 24px;">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i class="fas fa-list-ol"></i> 3. Current Timeline Updates (Total: <?= count($updates) ?>)
                </h2>
                <span class="badge" style="background: #E0F2FE; color: #0284C7; font-size: 0.74rem;">Newest On Top</span>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 85px;">Time</th>
                            <th style="width: 100px;">Badge</th>
                            <th>Headline Details</th>
                            <th style="width: 80px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($updates)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4" style="color: var(--text-muted);">
                                    No timeline updates present. Post the first update from the form above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($updates as $u): ?>
                                <tr>
                                    <td><strong><?= sanitize($u['timestamp_label']) ?></strong></td>
                                    <td>
                                        <span class="badge" style="font-size: 0.72rem; padding: 2px 6px;">
                                            <?= sanitize($u['badge_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem; line-height: 1.45; color: var(--text-heading); font-weight: 600;">
                                            <?= sanitize($u['headline']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div style="display: flex; gap: 4px; justify-content: center;">
                                            <a href="/admin/live-bulletins.php?edit_id=<?= $u['id'] ?>" class="btn-icon btn-icon-edit" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button type="button" class="btn-icon btn-icon-delete" 
                                                    title="Delete" 
                                                    onclick="confirmDelete('/admin/live-bulletins.php?action=delete_update&update_id=<?= $u['id'] ?>', 'this timeline update')">
                                                <i class="fas fa-trash"></i>
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

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
