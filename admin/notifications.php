<?php
/**
 * Admin Push Notification Dispatcher & History
 * Himachal News - Khabar 24
 */

$adminTitle = 'Push Notifications';
$adminHeading = 'Push Notification Center';

require_once __DIR__ . '/includes/header.php';

// Handle Dispatching Notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    $title = trim($_POST['title'] ?? '');
    $msgBody = trim($_POST['message'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $badgeText = trim($_POST['badge_text'] ?? 'Latest News');
    $newsId = !empty($_POST['news_id']) ? (int)$_POST['news_id'] : null;

    if (!empty($title) && !empty($msgBody)) {
        if (empty($url)) {
            $url = defined('APP_URL') ? APP_URL : 'https://news24hp.com';
        }

        $cntStmt = $pdo->query("SELECT COUNT(*) FROM `subscribers` WHERE `status` = 'active'");
        $recipientCount = (int)$cntStmt->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO `manual_notifications` (`news_id`, `title`, `message`, `url`, `image_url`, `badge_text`, `sent_by`, `recipient_count`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, 'Admin', ?, NOW())
        ");
        $stmt->execute([$newsId, $title, $msgBody, $url, $imageUrl, $badgeText, $recipientCount]);
        
        $_SESSION['flash_message'] = "Notification sent successfully to <strong>{$recipientCount} active device(s)</strong>!";
        $_SESSION['flash_type'] = "success";
        header("Location: /admin/notifications.php");
        exit;
    } else {
        $error = "Please fill in both Title and Message.";
    }
}

// Fetch articles for fast auto-fill dropdown
$recentNews = $pdo->query("
    SELECT n.`id`, n.`title`, n.`slug`, n.`excerpt`, n.`image_url`, c.`name` as category_name
    FROM `news` n
    LEFT JOIN `categories` c ON n.`category_id` = c.`id`
    ORDER BY n.`created_at` DESC
    LIMIT 30
")->fetchAll();

// Fetch notification history
$history = $pdo->query("
    SELECT n.*, COUNT(d.`id`) as delivered_count, COUNT(d.`clicked_at`) as click_count
    FROM `manual_notifications` n
    LEFT JOIN `notification_deliveries` d ON n.`id` = d.`notification_id`
    GROUP BY n.`id`
    ORDER BY n.`created_at` DESC
    LIMIT 20
")->fetchAll();
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <div><i class="fas fa-triangle-exclamation"></i> <?= sanitize($error) ?></div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    
    <!-- Left Column: Send Notification Form -->
    <div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-paper-plane"></i> Dispatch Push Alert</h2>
            </div>
            <div class="panel-body">
                <form method="POST" action="notifications.php">
                    <input type="hidden" name="action" value="send_notification">

                    <!-- Quick Auto-Fill from Article -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-bolt" style="color: var(--accent-amber);"></i> Quick Fill from Published Post
                        </label>
                        <select id="newsAutoSelect" class="form-control" onchange="autoFillNotification(this)">
                            <option value="">-- Select an article to auto-fill details --</option>
                            <?php foreach ($recentNews as $rn): ?>
                                <option value="<?= $rn['id'] ?>" 
                                        data-title="<?= htmlspecialchars($rn['title'], ENT_QUOTES) ?>"
                                        data-excerpt="<?= htmlspecialchars($rn['excerpt'] ?? '', ENT_QUOTES) ?>"
                                        data-url="<?= (defined('APP_URL') ? APP_URL : 'https://news24hp.com') ?>/article.php?slug=<?= urlencode($rn['slug']) ?>"
                                        data-image="<?= htmlspecialchars($rn['image_url'], ENT_QUOTES) ?>"
                                        data-cat="<?= htmlspecialchars($rn['category_name'] ?? 'Breaking', ENT_QUOTES) ?>">
                                    [<?= sanitize($rn['category_name']) ?>] <?= sanitize(mb_substr($rn['title'], 0, 55)) ?>...
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="news_id" id="notifNewsId">

                    <!-- Title -->
                    <div class="form-group">
                        <label class="form-label" for="notifTitle">
                            Notification Title <span class="required">*</span>
                        </label>
                        <input type="text" id="notifTitle" name="title" class="form-control" required placeholder="e.g. Heavy snowfall in Shimla, Kufri-Narkanda road reopened...">
                    </div>

                    <!-- Message Body -->
                    <div class="form-group">
                        <label class="form-label" for="notifMsg">
                            Short Message Body <span class="required">*</span>
                        </label>
                        <textarea id="notifMsg" name="message" class="form-control" style="min-height: 80px;" required placeholder="e.g. Administration urges tourists to exercise caution..."></textarea>
                    </div>

                    <!-- Target URL -->
                    <div class="form-group">
                        <label class="form-label" for="notifUrl">
                            Target Destination URL (Link opened on click)
                        </label>
                        <input type="url" id="notifUrl" name="url" class="form-control" placeholder="<?= (defined('APP_URL') ? APP_URL : 'https://news24hp.com') ?>/article.php?slug=..." value="<?= (defined('APP_URL') ? APP_URL : 'https://news24hp.com') ?>">
                    </div>

                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                        <!-- Image URL -->
                        <div class="form-group">
                            <label class="form-label" for="notifImg">Image URL</label>
                            <input type="url" id="notifImg" name="image_url" class="form-control" placeholder="https://images.unsplash.com/...">
                        </div>

                        <!-- Badge Text -->
                        <div class="form-group">
                            <label class="form-label" for="notifBadge">Badge Text</label>
                            <input type="text" id="notifBadge" name="badge_text" class="form-control" value="Latest News">
                        </div>
                    </div>

                    <button type="submit" class="topbar-btn" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem; margin-top: 10px;">
                        <i class="fas fa-paper-plane"></i> Dispatch Push Alert Now
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Push Notification History -->
    <div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-history"></i> Notification History</h2>
            </div>
            <div class="panel-body" style="padding: 16px;">
                <?php if (empty($history)): ?>
                    <p style="text-align: center; color: var(--text-dim); padding: 30px;">No notifications dispatched yet.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($history as $h): ?>
                            <div style="background: var(--bg-input); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 6px;">
                                    <span class="badge badge-red" style="font-size: 0.7rem;">
                                        <?= sanitize($h['badge_text']) ?>
                                    </span>
                                    <span style="font-size: 0.72rem; color: var(--text-dim);">
                                        <?= date('d M Y, h:i A', strtotime($h['created_at'])) ?>
                                    </span>
                                </div>
                                <h4 style="font-size: 0.92rem; color: #fff; margin-bottom: 4px; line-height: 1.35;">
                                    <?= sanitize($h['title']) ?>
                                </h4>
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">
                                    <?= sanitize($h['message']) ?>
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-dim); border-top: 1px solid var(--border-color); padding-top: 8px;">
                                    <span><i class="fas fa-users"></i> Recipients: <strong><?= $h['recipient_count'] ?> devices</strong></span>
                                    <span><i class="fas fa-user-shield"></i> <?= sanitize($h['sent_by']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function autoFillNotification(selectElem) {
    const opt = selectElem.options[selectElem.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('notifNewsId').value = opt.value;
        document.getElementById('notifTitle').value = opt.getAttribute('data-title') || '';
        document.getElementById('notifMsg').value = opt.getAttribute('data-excerpt') || '';
        document.getElementById('notifUrl').value = opt.getAttribute('data-url') || '';
        document.getElementById('notifImg').value = opt.getAttribute('data-image') || '';
        document.getElementById('notifBadge').value = opt.getAttribute('data-cat') || 'Latest News';
    }
}
</script>
