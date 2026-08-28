<?php
/**
 * Daily Site Visitors & Analytics Dashboard
 * News 24 Himachal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$adminTitle = 'दैनिक पाठक एवं विज़िटर एनालिटिक्स';
$adminHeading = 'दैनिक पाठक एवं विज़िटर एनालिटिक्स (Site Visitors & Traffic)';

require_once __DIR__ . '/includes/header.php';

// Ensure table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `site_visitors` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `visitor_ip` VARCHAR(45) NOT NULL,
        `page_url` VARCHAR(500) NOT NULL,
        `page_title` VARCHAR(255) NULL,
        `device_type` VARCHAR(30) DEFAULT 'Desktop',
        `browser` VARCHAR(50) DEFAULT 'Chrome',
        `os` VARCHAR(50) DEFAULT 'Windows',
        `referrer` VARCHAR(500) NULL,
        `visit_date` DATE NOT NULL,
        `visited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_visit_date` (`visit_date`),
        INDEX `idx_visit_ip_date` (`visitor_ip`, `visit_date`),
        INDEX `idx_device` (`device_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Determine Date Range
$preset = $_GET['preset'] ?? '';
$today = date('Y-m-d');

if ($preset === 'today') {
    $startDate = $today;
    $endDate = $today;
} elseif ($preset === 'yesterday') {
    $startDate = date('Y-m-d', strtotime('-1 day'));
    $endDate = date('Y-m-d', strtotime('-1 day'));
} elseif ($preset === '7days') {
    $startDate = date('Y-m-d', strtotime('-6 days'));
    $endDate = $today;
} elseif ($preset === '30days') {
    $startDate = date('Y-m-d', strtotime('-29 days'));
    $endDate = $today;
} elseif ($preset === 'this_month') {
    $startDate = date('Y-m-01');
    $endDate = $today;
} else {
    // Custom date range or default to last 7 days
    $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 days'));
    $endDate = !empty($_GET['end_date']) ? $_GET['end_date'] : $today;
}

// 1. Total Metrics in Range
$stmtSummary = $pdo->prepare("
    SELECT 
        COUNT(*) as total_pageviews,
        COUNT(DISTINCT visitor_ip) as unique_visitors,
        SUM(CASE WHEN device_type = 'Mobile' THEN 1 ELSE 0 END) as mobile_views,
        SUM(CASE WHEN device_type = 'Desktop' THEN 1 ELSE 0 END) as desktop_views,
        SUM(CASE WHEN device_type = 'Tablet' THEN 1 ELSE 0 END) as tablet_views
    FROM `site_visitors`
    WHERE `visit_date` BETWEEN ? AND ?
");
$stmtSummary->execute([$startDate, $endDate]);
$summary = $stmtSummary->fetch() ?: ['total_pageviews' => 0, 'unique_visitors' => 0, 'mobile_views' => 0, 'desktop_views' => 0, 'tablet_views' => 0];

$totalViews = (int)$summary['total_pageviews'];
$uniqueVisitors = (int)$summary['unique_visitors'];
$mobileViews = (int)$summary['mobile_views'];
$desktopViews = (int)$summary['desktop_views'];

$mobilePercent = $totalViews > 0 ? round(($mobileViews / $totalViews) * 100, 1) : 0;
$desktopPercent = $totalViews > 0 ? round(($desktopViews / $totalViews) * 100, 1) : 0;

// 2. Day-by-Day Breakdown in Range
$stmtDaily = $pdo->prepare("
    SELECT 
        `visit_date`,
        COUNT(*) as day_views,
        COUNT(DISTINCT visitor_ip) as day_uniques,
        SUM(CASE WHEN device_type = 'Mobile' THEN 1 ELSE 0 END) as day_mobile,
        SUM(CASE WHEN device_type = 'Desktop' THEN 1 ELSE 0 END) as day_desktop
    FROM `site_visitors`
    WHERE `visit_date` BETWEEN ? AND ?
    GROUP BY `visit_date`
    ORDER BY `visit_date` DESC
");
$stmtDaily->execute([$startDate, $endDate]);
$dailyStats = $stmtDaily->fetchAll();

// 3. Top Visited Pages & Articles
$stmtTopPages = $pdo->prepare("
    SELECT `page_title`, `page_url`, COUNT(*) as views_count, COUNT(DISTINCT visitor_ip) as unique_readers
    FROM `site_visitors`
    WHERE `visit_date` BETWEEN ? AND ?
    GROUP BY `page_url`, `page_title`
    ORDER BY views_count DESC
    LIMIT 10
");
$stmtTopPages->execute([$startDate, $endDate]);
$topPages = $stmtTopPages->fetchAll();

// 4. Device & Browser Breakdown
$stmtBrowsers = $pdo->prepare("
    SELECT `browser`, COUNT(*) as cnt
    FROM `site_visitors`
    WHERE `visit_date` BETWEEN ? AND ?
    GROUP BY `browser`
    ORDER BY cnt DESC
    LIMIT 5
");
$stmtBrowsers->execute([$startDate, $endDate]);
$topBrowsers = $stmtBrowsers->fetchAll();

// 5. Recent 25 Live Visitor Logs
$recentVisitors = $pdo->query("
    SELECT `visitor_ip`, `page_title`, `page_url`, `device_type`, `browser`, `os`, `visited_at`
    FROM `site_visitors`
    ORDER BY `visited_at` DESC
    LIMIT 25
")->fetchAll();
?>

<!-- Filter & Date Range Bar -->
<div class="panel" style="margin-bottom: 24px; padding: 18px 20px;">
    <form method="GET" action="visitors.php" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        
        <!-- Preset Shortcuts -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-heading); margin-right: 4px;">
                <i class="fas fa-calendar-days" style="color: var(--primary);"></i> समय सीमा:
            </span>
            <a href="visitors.php?preset=today" class="topbar-btn <?= $preset === 'today' ? '' : 'topbar-btn-secondary' ?>" style="padding: 6px 12px; font-size: 0.82rem;">आज (Today)</a>
            <a href="visitors.php?preset=yesterday" class="topbar-btn <?= $preset === 'yesterday' ? '' : 'topbar-btn-secondary' ?>" style="padding: 6px 12px; font-size: 0.82rem;">कल (Yesterday)</a>
            <a href="visitors.php?preset=7days" class="topbar-btn <?= ($preset === '7days' || (!$preset && empty($_GET['start_date']))) ? '' : 'topbar-btn-secondary' ?>" style="padding: 6px 12px; font-size: 0.82rem;">पिछले 7 दिन</a>
            <a href="visitors.php?preset=30days" class="topbar-btn <?= $preset === '30days' ? '' : 'topbar-btn-secondary' ?>" style="padding: 6px 12px; font-size: 0.82rem;">पिछले 30 दिन</a>
            <a href="visitors.php?preset=this_month" class="topbar-btn <?= $preset === 'this_month' ? '' : 'topbar-btn-secondary' ?>" style="padding: 6px 12px; font-size: 0.82rem;">इस महीने</a>
        </div>

        <!-- Custom Date Range Form Inputs -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">से:</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>" style="padding: 6px 10px; font-size: 0.85rem; width: 140px;">
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">तक:</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>" style="padding: 6px 10px; font-size: 0.85rem; width: 140px;">
            </div>
            <button type="submit" class="topbar-btn" style="padding: 6px 14px; font-size: 0.85rem;">
                <i class="fas fa-filter"></i> फ़िल्टर लागू करें
            </button>
        </div>

    </form>
</div>

<!-- Summary Stat Cards for Selected Date Range -->
<div class="stats-grid" style="margin-bottom: 24px;">
    
    <div class="stat-card">
        <div class="stat-info">
            <h4>कुल पेज व्यूज (Page Views)</h4>
            <div class="stat-number" style="color: var(--primary);"><?= number_format($totalViews) ?></div>
            <span style="font-size: 0.78rem; color: var(--text-muted);">चुनी गई अवधि में कुल देखे गए पृष्ठ</span>
        </div>
        <div class="stat-icon-wrap stat-icon-red">
            <i class="fas fa-eye"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h4>अद्वितीय विज़िटर्स (Unique Visitors)</h4>
            <div class="stat-number" style="color: #0284C7;"><?= number_format($uniqueVisitors) ?></div>
            <span style="font-size: 0.78rem; color: var(--text-muted);">अलग-अलग IP / डिवाइस से आए पाठक</span>
        </div>
        <div class="stat-icon-wrap stat-icon-blue">
            <i class="fas fa-users"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h4>मोबाइल ट्रैफ़िक (Mobile Share)</h4>
            <div class="stat-number" style="color: #16A34A;"><?= $mobilePercent ?>%</div>
            <span style="font-size: 0.78rem; color: var(--text-muted);"><?= number_format($mobileViews) ?> मोबाइल विज़िट्स</span>
        </div>
        <div class="stat-icon-wrap stat-icon-green">
            <i class="fas fa-mobile-screen-button"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h4>डेस्कटॉप ट्रैफ़िक (Desktop Share)</h4>
            <div class="stat-number" style="color: #D97706;"><?= $desktopPercent ?>%</div>
            <span style="font-size: 0.78rem; color: var(--text-muted);"><?= number_format($desktopViews) ?> कंप्यूटर विज़िट्स</span>
        </div>
        <div class="stat-icon-wrap stat-icon-amber">
            <i class="fas fa-desktop"></i>
        </div>
    </div>

</div>

<!-- 2-Column Layout: Daily Table & Breakdown -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
    
    <!-- Left: Day-by-Day Traffic Breakdown Table -->
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">
                <i class="fas fa-calendar-day" style="color: var(--primary);"></i> दैनिक विज़िटर्स रिपोर्ट (<?= date('d M Y', strtotime($startDate)) ?> से <?= date('d M Y', strtotime($endDate)) ?>)
            </h2>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>दिनांक (Date)</th>
                        <th>अद्वितीय पाठक (Unique)</th>
                        <th>कुल पेज व्यूज (Views)</th>
                        <th>मोबाइल / डेस्कटॉप</th>
                        <th style="width: 25%;">ट्रैफ़िक ग्राफ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dailyStats)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 35px;">
                                <i class="fas fa-chart-line" style="font-size: 2.2rem; color: #CBD5E1; margin-bottom: 8px; display: block;"></i>
                                इस दिनांक सीमा में अभी तक कोई विज़िट डेटा दर्ज नहीं हुआ है।<br>
                                <small>जैसे ही विज़िटर्स लाइव साइट खोलेंगे, यहाँ तुरंत लाइव आंकड़े दिखने लगेंगे।</small>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $maxDayViews = max(array_column($dailyStats, 'day_views'));
                        if ($maxDayViews <= 0) $maxDayViews = 1;
                        ?>
                        <?php foreach ($dailyStats as $row): ?>
                            <?php 
                            $barWidth = round(($row['day_views'] / $maxDayViews) * 100);
                            $dayDateStr = date('d M Y (D)', strtotime($row['visit_date']));
                            $isTodayRow = ($row['visit_date'] === $today);
                            ?>
                            <tr <?= $isTodayRow ? 'style="background: rgba(227, 27, 35, 0.04); font-weight: 600;"' : '' ?>>
                                <td>
                                    <strong><?= $dayDateStr ?></strong>
                                    <?php if ($isTodayRow): ?>
                                        <span class="badge badge-red" style="font-size: 0.65rem; margin-left: 4px;">आज (Today)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: #0284C7; font-weight: 700;"><?= number_format($row['day_uniques']) ?></span>
                                </td>
                                <td>
                                    <span style="color: var(--primary); font-weight: 800; font-size: 0.95rem;"><?= number_format($row['day_views']) ?></span>
                                </td>
                                <td style="font-size: 0.8rem; color: var(--text-muted);">
                                    <span><i class="fas fa-mobile-screen"></i> <?= $row['day_mobile'] ?></span> • 
                                    <span><i class="fas fa-desktop"></i> <?= $row['day_desktop'] ?></span>
                                </td>
                                <td>
                                    <div style="background: #E2E8F0; border-radius: 4px; height: 8px; overflow: hidden; width: 100%;">
                                        <div style="background: var(--primary); height: 100%; width: <?= max(5, $barWidth) ?>%; border-radius: 4px;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Column: Top Browsers & Platform -->
    <div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-compass" style="color: #0284C7;"></i> शीर्ष ब्राउज़र (Top Browsers)</h2>
            </div>
            <div class="panel-body">
                <?php if (empty($topBrowsers)): ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 20px;">डेटा उपलब्ध नहीं है।</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($topBrowsers as $b): ?>
                            <?php $bPercent = $totalViews > 0 ? round(($b['cnt'] / $totalViews) * 100, 1) : 0; ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                                    <span style="font-weight: 700; color: var(--text-heading);">
                                        <i class="fab fa-<?= strtolower($b['browser']) === 'chrome' ? 'chrome' : (strtolower($b['browser']) === 'safari' ? 'safari' : (strtolower($b['browser']) === 'edge' ? 'edge' : 'firefox')) ?>"></i> <?= sanitize($b['browser']) ?>
                                    </span>
                                    <span style="color: var(--text-muted); font-weight: 600;"><?= number_format($b['cnt']) ?> (<?= $bPercent ?>%)</span>
                                </div>
                                <div style="background: #E2E8F0; border-radius: 4px; height: 6px; overflow: hidden;">
                                    <div style="background: #0284C7; height: 100%; width: <?= $bPercent ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Summary Box -->
        <div class="panel" style="margin-top: 20px; background: #F8FAFC; border: 1.5px solid var(--border-color);">
            <div class="panel-body" style="font-size: 0.84rem; color: var(--text-main); line-height: 1.6;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-weight: 700; color: var(--text-heading);">
                    <i class="fas fa-shield-halved" style="color: #16A34A;"></i> लाइव ट्रैकिंग सक्रिय (Active)
                </div>
                <p style="color: var(--text-muted);">जब भी कोई पाठक वेबसाइट, होमपेज या किसी भी खबर को पढ़ेगा, उसका विज़िट स्वतः दिनांक और डिवाइस के अनुसार यहाँ दर्ज हो जाएगा।</p>
            </div>
        </div>
    </div>

</div>

<!-- Top Visited News Articles in Selected Date Range -->
<div class="panel" style="margin-bottom: 24px;">
    <div class="panel-header">
        <h2 class="panel-title"><i class="fas fa-fire" style="color: #D97706;"></i> सबसे अधिक पढ़ी गई खबरें (Most Popular Articles in Range)</h2>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>पेज / खबर का शीर्षक (Article Title & Link)</th>
                    <th>अद्वितीय पाठक (Unique Visitors)</th>
                    <th>कुल पेज व्यूज (Pageviews)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topPages)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            इस अवधि में अभी तक कोई पेज व्यू डेटा नहीं है।
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $rank = 1; foreach ($topPages as $tp): ?>
                        <tr>
                            <td>
                                <span style="font-weight: 800; font-size: 0.95rem; color: <?= $rank <= 3 ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                                    #<?= $rank++ ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--text-heading); font-size: 0.92rem; display: block;">
                                    <?= sanitize($tp['page_title'] ?: 'News 24 Himachal') ?>
                                </strong>
                                <a href="<?= htmlspecialchars($tp['page_url']) ?>" target="_blank" style="font-size: 0.78rem; color: #0284C7; text-decoration: none;">
                                    <i class="fas fa-arrow-up-right-from-square"></i> <?= htmlspecialchars(mb_substr($tp['page_url'], 0, 70)) ?>
                                </a>
                            </td>
                            <td>
                                <span style="color: #0284C7; font-weight: 700;"><?= number_format($tp['unique_readers']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-red" style="font-weight: 800; font-size: 0.88rem;">
                                    <?= number_format($tp['views_count']) ?> व्यूज
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Real-time Live Visitors Activity Log -->
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title"><i class="fas fa-tower-broadcast" style="color: var(--primary);"></i> हाल के लाइव विज़िटर्स (Recent 25 Live Visits)</h2>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>समय (Time)</th>
                    <th>IP पता</th>
                    <th>देखा गया पेज / खबर</th>
                    <th>डिवाइस</th>
                    <th>ब्राउज़र व OS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentVisitors)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            कोई हाल की विज़िट गतिविधि नहीं है।
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentVisitors as $rv): ?>
                        <?php 
                        // Mask IP for privacy (e.g. 192.168.1.***)
                        $ipParts = explode('.', $rv['visitor_ip']);
                        $maskedIp = count($ipParts) === 4 ? $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.***' : $rv['visitor_ip'];
                        ?>
                        <tr>
                            <td style="font-size: 0.8rem; color: var(--text-muted);">
                                <i class="far fa-clock"></i> <?= date('d M, h:i:s A', strtotime($rv['visited_at'])) ?>
                            </td>
                            <td>
                                <code style="background: #F1F5F9; border: 1px solid var(--border-color); padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;"><?= htmlspecialchars($maskedIp) ?></code>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.86rem; color: var(--text-heading); max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= sanitize($rv['page_title'] ?: 'News 24 Himachal') ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($rv['page_url']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $rv['device_type'] === 'Mobile' ? 'badge-green' : ($rv['device_type'] === 'Tablet' ? 'badge-amber' : 'badge-blue') ?>" style="font-size: 0.72rem;">
                                    <i class="fas fa-<?= $rv['device_type'] === 'Mobile' ? 'mobile-screen' : ($rv['device_type'] === 'Tablet' ? 'tablet-screen-button' : 'desktop') ?>"></i> <?= sanitize($rv['device_type']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-main);">
                                <?= sanitize($rv['browser']) ?> (<?= sanitize($rv['os']) ?>)
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
