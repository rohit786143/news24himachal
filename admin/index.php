<?php
/**
 * Admin Dashboard Main Page
 * Himachal News - Khabar 24
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isEditor = (!empty($_SESSION['admin_user']) && $_SESSION['admin_user']['role'] === 'editor');
$adminTitle = $isEditor ? 'मेरा डैशबोर्ड' : 'डैशबोर्ड';
$adminHeading = $isEditor ? 'संवाददाता डैशबोर्ड (Reporter Overview)' : 'एडमिन डैशबोर्ड (Dashboard)';

require_once __DIR__ . '/includes/header.php';

if ($isEditor) {
    // Editor Specific Stats
    $myPostsCount = (int)$pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `author_id` = ?")->execute([$currentUserId]) ? $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `author_id` = ?")->fetchColumn() : 0;
    
    // Exact fetches
    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `author_id` = ?");
    $stmt1->execute([$currentUserId]);
    $totalPosts = (int)$stmt1->fetchColumn();

    $stmt2 = $pdo->prepare("SELECT COALESCE(SUM(`views`), 0) FROM `news` WHERE `author_id` = ?");
    $stmt2->execute([$currentUserId]);
    $totalViews = (int)$stmt2->fetchColumn();

    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `author_id` = ? AND `is_breaking` = 1");
    $stmt3->execute([$currentUserId]);
    $totalBreaking = (int)$stmt3->fetchColumn();

    $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `author_id` = ? AND `is_trending` = 1");
    $stmt4->execute([$currentUserId]);
    $totalTrending = (int)$stmt4->fetchColumn();

    // Editor's Recent Articles
    $recentNewsStmt = $pdo->prepare("
        SELECT n.*, c.name AS category_name, sub.name AS subcategory_name
        FROM `news` n
        LEFT JOIN `categories` c ON n.category_id = c.id
        LEFT JOIN `categories` sub ON n.subcategory_id = sub.id
        WHERE n.author_id = ?
        ORDER BY n.created_at DESC
        LIMIT 8
    ");
    $recentNewsStmt->execute([$currentUserId]);
    $recentNews = $recentNewsStmt->fetchAll();
    $recentMessages = [];
} else {
    // Admin Core Stats
    $totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM `news`")->fetchColumn();
    $totalViews = (int)$pdo->query("SELECT COALESCE(SUM(`views`), 0) FROM `news`")->fetchColumn();
    $totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM `categories` WHERE `parent_id` IS NULL")->fetchColumn();
    $totalSubcategories = (int)$pdo->query("SELECT COUNT(*) FROM `categories` WHERE `parent_id` IS NOT NULL")->fetchColumn();
    $totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM `contacts`")->fetchColumn();
    $totalSubscribers = (int)$pdo->query("SELECT COUNT(*) FROM `subscribers` WHERE `status` = 'active'")->fetchColumn();
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();

    // Fetch 8 Most Recent Articles
    $recentNewsStmt = $pdo->query("
        SELECT n.*, c.name AS category_name, sub.name AS subcategory_name
        FROM `news` n
        LEFT JOIN `categories` c ON n.category_id = c.id
        LEFT JOIN `categories` sub ON n.subcategory_id = sub.id
        ORDER BY n.created_at DESC
        LIMIT 8
    ");
    $recentNews = $recentNewsStmt->fetchAll();

    // Fetch 5 Recent Messages
    $recentMessagesStmt = $pdo->query("SELECT * FROM `contacts` ORDER BY `created_at` DESC LIMIT 5");
    $recentMessages = $recentMessagesStmt->fetchAll();
}
?>

<?php if ($isEditor): ?>
    <!-- =========================================================================
         EDITOR / REPORTER DEDICATED WORKSPACE
         ========================================================================= -->
    
    <!-- Reporter Welcome Banner Card -->
    <div style="background: linear-gradient(135deg, #18181B 0%, #1E293B 100%); border-radius: 14px; padding: 24px; color: #FFFFFF; margin-bottom: 24px; border-left: 5px solid #38BDF8; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <img src="<?= sanitize($currentUser['avatar']) ?>" alt="<?= sanitize($currentUser['name']) ?>" 
                 style="width: 68px; height: 68px; border-radius: 50%; object-fit: cover; border: 3px solid #38BDF8; box-shadow: 0 4px 14px rgba(56, 189, 248, 0.4);">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: #FFFFFF; margin: 0;">
                        नमस्ते, <?= sanitize($currentUser['name']) ?>!
                    </h2>
                    <span class="badge badge-blue" style="font-size: 0.75rem; padding: 3px 8px;">
                        ✍️ अधिकृत संवाददाता
                    </span>
                </div>
                <p style="font-size: 0.85rem; color: #94A3B8; margin-top: 4px;">
                    <i class="fas fa-id-card" style="color: #38BDF8;"></i> <?= sanitize($currentUser['designation'] ?: 'संवाददाता • News 24 Himachal') ?>
                    &nbsp;|&nbsp; 
                    <a href="/author.php?id=<?= $currentUserId ?>" target="_blank" style="color: #38BDF8; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-arrow-up-right-from-square"></i> आपकी लाइव पब्लिक प्रोफाइल
                    </a>
                </p>
            </div>
        </div>

        <div>
            <a href="/admin/post-edit.php" class="topbar-btn" style="background: var(--primary); padding: 12px 22px; font-size: 1rem;">
                <i class="fas fa-pen-nib"></i> नई खबर लिखें (Add New Post)
            </a>
        </div>
    </div>

    <!-- Reporter Performance Stats Grid -->
    <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
            <div class="stat-info">
                <h4>मेरी कुल प्रकाशित खबरें</h4>
                <div class="stat-number"><?= number_format($totalPosts) ?></div>
            </div>
            <div class="stat-icon-wrap stat-icon-red">
                <i class="fas fa-file-lines"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>मेरी खबरों के कुल व्यूज</h4>
                <div class="stat-number"><?= number_format($totalViews) ?></div>
            </div>
            <div class="stat-icon-wrap stat-icon-blue">
                <i class="fas fa-eye"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>ब्रेकिंग न्यूज़ अलर्ट्स</h4>
                <div class="stat-number"><?= number_format($totalBreaking) ?></div>
            </div>
            <div class="stat-icon-wrap stat-icon-amber">
                <i class="fas fa-bolt"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>ट्रेंडिंग खबरें</h4>
                <div class="stat-number"><?= number_format($totalTrending) ?></div>
            </div>
            <div class="stat-icon-wrap stat-icon-green">
                <i class="fas fa-fire"></i>
            </div>
        </div>
    </div>

    <!-- Quick Action Navigation for Editor -->
    <div style="display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap;">
        <a href="/admin/post-edit.php" class="topbar-btn" style="padding: 10px 18px;">
            <i class="fas fa-plus-circle"></i> नई खबर लिखें
        </a>
        <a href="/admin/posts.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-list-check"></i> मेरी सभी प्रकाशित खबरें (<?= $totalPosts ?>)
        </a>
        <a href="/admin/profile.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-user-gear"></i> मेरी प्रोफाइल व पासवर्ड
        </a>
        <a href="/author.php?id=<?= $currentUserId ?>" target="_blank" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px; color: #0284C7;">
            <i class="fas fa-id-badge"></i> पब्लिक लेखक प्रोफाइल देखें
        </a>
    </div>

    <!-- Editor Main 2 Columns: My Recent Articles + Guidelines -->
    <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 24px;">
        
        <!-- Left: Editor's Recent Articles -->
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-newspaper" style="color: var(--primary);"></i> आपके द्वारा हाल ही में प्रकाशित खबरें</h2>
                <a href="/admin/posts.php" style="color: #0284C7; font-size: 0.85rem; font-weight: 700; text-decoration: none;">
                    मेरी सभी खबरें देखें (<?= $totalPosts ?>) &rarr;
                </a>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>थंबनेल</th>
                            <th>शीर्षक (Title)</th>
                            <th>श्रेणी</th>
                            <th>व्यूज</th>
                            <th>दिनांक</th>
                            <th style="text-align: right;">कार्य (Actions)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentNews)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                    <i class="fas fa-feather-pointed" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 10px; display: block;"></i>
                                    आपने अभी तक कोई खबर प्रकाशित नहीं की है।<br>
                                    <a href="/admin/post-edit.php" class="topbar-btn" style="display: inline-flex; margin-top: 14px; padding: 8px 16px;">
                                        <i class="fas fa-plus"></i> अपनी पहली खबर लिखें
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentNews as $news): ?>
                                <tr>
                                    <td>
                                        <img src="<?= sanitize($news['image_url']) ?>" alt="Thumb" class="table-thumb" onerror="this.src='https://via.placeholder.com/60x45?text=News';">
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <a href="/article.php?slug=<?= urlencode($news['slug']) ?>" target="_blank" style="color: var(--text-main); text-decoration: none;">
                                                <?= sanitize($news['title']) ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                            <?php if ($news['is_breaking']): ?>
                                                <span class="badge badge-red" style="font-size: 0.65rem;">⚡ ब्रेकिंग</span>
                                            <?php endif; ?>
                                            <?php if ($news['is_featured']): ?>
                                                <span class="badge badge-amber" style="font-size: 0.65rem;">⭐ लीड स्लाइडर</span>
                                            <?php endif; ?>
                                            <?php if ($news['is_trending']): ?>
                                                <span class="badge badge-green" style="font-size: 0.65rem;">🔥 ट्रेंडिंग</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-blue">
                                            <?= sanitize($news['subcategory_name'] ?? $news['category_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-heading);"><i class="far fa-eye" style="color: #0284C7;"></i> <?= number_format($news['views']) ?></strong>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                                        <?= date('d M Y', strtotime($news['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-btns" style="justify-content: flex-end;">
                                            <a href="/article.php?slug=<?= urlencode($news['slug']) ?>" target="_blank" class="btn-icon" style="background: #F1F5F9; color: #0284C7; border: 1px solid #CBD5E1;" title="वेबसाइट पर लाइव देखें">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/admin/post-edit.php?id=<?= $news['id'] ?>" class="btn-icon btn-icon-edit" title="संपादित करें (Edit)">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button type="button" class="btn-icon btn-icon-delete" title="हटाएं (Delete)" onclick="confirmDelete('/admin/posts.php?action=delete&id=<?= $news['id'] ?>', 'इस खबर')">
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

        <!-- Right: Reporter Guidelines & Profile Snippet -->
        <div>
            <!-- Quick Bio Card -->
            <div class="panel" style="margin-bottom: 20px;">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-user-check"></i> आपका रिपोर्टर विवरण</h2>
                </div>
                <div class="panel-body text-center">
                    <img src="<?= sanitize($currentUser['avatar']) ?>" alt="Avatar" 
                         style="width: 76px; height: 76px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); margin-bottom: 10px;">
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-heading); margin-bottom: 2px;">
                        <?= sanitize($currentUser['name']) ?>
                    </h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px;">
                        <?= sanitize($currentUser['designation'] ?: 'संवाददाता • News 24 Himachal') ?>
                    </p>
                    <a href="/admin/profile.php" class="topbar-btn topbar-btn-secondary" style="width: 100%; justify-content: center; font-size: 0.85rem;">
                        <i class="fas fa-user-pen"></i> प्रोफाइल व पासवर्ड अपडेट करें
                    </a>
                </div>
            </div>

            <!-- News Publishing Guidelines -->
            <div class="panel" style="background: linear-gradient(135deg, #F0F9FF 0%, #FFFFFF 100%); border: 1.5px solid #BAE6FD;">
                <div class="panel-header" style="background: transparent; border-bottom: 1px solid #E0F2FE;">
                    <h2 class="panel-title" style="color: #0369A1;"><i class="fas fa-lightbulb"></i> खबर लेखन दिशा-निर्देश</h2>
                </div>
                <div class="panel-body" style="font-size: 0.85rem; color: #334155; line-height: 1.6;">
                    <div style="margin-bottom: 10px; display: flex; gap: 8px;">
                        <i class="fas fa-check-circle" style="color: #0284C7; margin-top: 3px;"></i>
                        <span>खबर का शीर्षक आकर्षक, स्पष्ट और तथ्यात्मक रखें।</span>
                    </div>
                    <div style="margin-bottom: 10px; display: flex; gap: 8px;">
                        <i class="fas fa-check-circle" style="color: #0284C7; margin-top: 3px;"></i>
                        <span>उच्च गुणवत्ता वाली वास्तविक तस्वीर (Landscape Format) का लिंक लगाएं।</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <i class="fas fa-check-circle" style="color: #0284C7; margin-top: 3px;"></i>
                        <span>यदि खबर अत्यंत महत्वपूर्ण या ताज़ा है, तो <strong>'ब्रेकिंग न्यूज़'</strong> का चयन करें।</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php else: ?>
    <!-- =========================================================================
         MAIN ADMINISTRATOR MASTER DASHBOARD
         ========================================================================= -->

    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h4>कुल समाचार (Articles)</h4>
                <div class="stat-number"><?= number_format($totalPosts) ?></div>
            </div>
            <div class="stat-icon-wrap stat-icon-red">
                <i class="fas fa-newspaper"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>कुल पाठक व्यूज (Views)</h4>
                <div class="stat-number"><?= number_format($totalViews) ?></div>
            </div>
            <div class="stat-icon-wrap stat-icon-blue">
                <i class="fas fa-eye"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>श्रेणियां (Categories)</h4>
                <div class="stat-number"><?= $totalCategories ?> <span style="font-size: 0.85rem; font-weight: normal; color: var(--text-muted);">(+<?= $totalSubcategories ?> उप)</span></div>
            </div>
            <div class="stat-icon-wrap stat-icon-amber">
                <i class="fas fa-folder-tree"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>संपादक / यूज़र्स (Editors)</h4>
                <div class="stat-number"><?= number_format($totalUsers) ?></div>
            </div>
            <div class="stat-icon-wrap stat-icon-green">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Quick Action Shortcuts for Admin -->
    <div style="display: flex; gap: 12px; margin-bottom: 30px; flex-wrap: wrap;">
        <a href="post-edit.php" class="topbar-btn" style="padding: 10px 18px;">
            <i class="fas fa-plus-circle"></i> नई खबर लिखें (Add Post)
        </a>
        <a href="posts.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-file-lines"></i> सभी खबरें प्रबंधित करें
        </a>
        <a href="users.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-users-gear"></i> संपादक / यूज़र्स
        </a>
        <a href="/admin/categories.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-tags"></i> श्रेणियां
        </a>
        <a href="/admin/settings.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-sliders"></i> साइट सेटिंग्स
        </a>
        <a href="/admin/advertisements.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-rectangle-ad"></i> विज्ञापन प्रबंधन (Ads)
        </a>
        <a href="/admin/live-bulletins.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-tower-broadcast" style="color: var(--primary-red);"></i> लाइव बुलेटिन
        </a>
        <a href="/admin/notifications.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 18px;">
            <i class="fas fa-paper-plane"></i> पुश अलर्ट भेजें
        </a>
    </div>

    <!-- Main Two Columns: Recent News + Quick Messages -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        
        <!-- Recent Posts Table Panel -->
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-clock-rotate-left"></i> हाल ही में प्रकाशित खबरें (Recent Articles)</h2>
                <a href="/admin/posts.php" style="color: #0284C7; font-size: 0.85rem; font-weight: 600; text-decoration: none;">सभी देखें &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>थंबनेल</th>
                            <th>शीर्षक (Title)</th>
                            <th>श्रेणी</th>
                            <th>व्यूज</th>
                            <th>दिनांक</th>
                            <th>कार्य (Actions)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentNews)): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-dim); padding: 30px;">कोई खबर उपलब्ध नहीं है।</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentNews as $news): ?>
                                <tr>
                                    <td>
                                        <img src="<?= sanitize($news['image_url']) ?>" alt="Thumb" class="table-thumb" onerror="this.src='https://via.placeholder.com/60x45?text=News';">
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <a href="/article.php?slug=<?= urlencode($news['slug']) ?>" target="_blank" style="color: var(--text-main); text-decoration: none;">
                                                <?= sanitize($news['title']) ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-dim);">
                                            <?= sanitize($news['author']) ?>
                                            <?php if ($news['is_breaking']): ?>
                                                <span class="badge badge-red" style="font-size: 0.65rem;">ब्रेकिंग</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-blue">
                                            <?= sanitize($news['subcategory_name'] ?? $news['category_name']) ?>
                                        </span>
                                    </td>
                                    <td><i class="far fa-eye"></i> <?= number_format($news['views']) ?></td>
                                    <td style="font-size: 0.8rem; color: var(--text-muted);">
                                        <?= date('d M Y', strtotime($news['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="/admin/post-edit.php?id=<?= $news['id'] ?>" class="btn-icon btn-icon-edit" title="संपादित करें (Edit)">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button type="button" class="btn-icon btn-icon-delete" title="हटाएं (Delete)" onclick="confirmDelete('/admin/posts.php?action=delete&id=<?= $news['id'] ?>', 'इस खबर')">
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

        <!-- Right Side: Recent Contact Form Messages -->
        <div>
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-inbox"></i> ताज़ा संदेश (Messages)</h2>
                    <a href="/admin/messages.php" style="color: #0284C7; font-size: 0.85rem; font-weight: 600; text-decoration: none;">सभी (<?= $totalMessages ?>) &rarr;</a>
                </div>
                <div class="panel-body" style="padding: 16px;">
                    <?php if (empty($recentMessages)): ?>
                        <p style="color: var(--text-dim); text-align: center; padding: 20px 0;">कोई संदेश प्राप्त नहीं हुआ।</p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php foreach ($recentMessages as $msg): ?>
                                <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <strong style="font-size: 0.88rem; color: var(--text-heading);"><?= sanitize($msg['name']) ?></strong>
                                        <span style="font-size: 0.72rem; color: var(--text-dim);"><?= timeAgoHindi($msg['created_at']) ?></span>
                                    </div>
                                    <div style="font-size: 0.82rem; color: #0284C7; margin-bottom: 4px; font-weight: 600;">
                                        <?= sanitize($msg['subject']) ?>
                                    </div>
                                    <p style="font-size: 0.8rem; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= sanitize($msg['message']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Info Box -->
            <div class="panel" style="background: linear-gradient(135deg, #FEF2F2 0%, #FFFFFF 100%); border: 1px solid #FECACA;">
                <div class="panel-body">
                    <h3 style="font-size: 1rem; color: var(--primary); margin-bottom: 8px; display: flex; align-items: center; gap: 8px; font-weight: 700;">
                        <i class="fas fa-circle-check" style="color: var(--accent-green);"></i> लाइव सिस्टम सक्रिय है
                    </h3>
                    <p style="font-size: 0.84rem; color: var(--text-muted); line-height: 1.5;">
                        आपका पोर्टल और डेटाबेस पूरी तरह सक्रिय हैं। यहां से जोड़ा गया कोई भी लेख, श्रेणी या पेज तुरंत मुख्य वेबसाइट पर दिखने लगेगा।
                    </p>
                </div>
            </div>
        </div>

    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

