<?php
/**
 * Author / Editor Profile Page (author.php)
 * News 24 Himachal
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

$authorIdOrUser = $_GET['id'] ?? ($_GET['u'] ?? 1);
$author = getAuthorProfile($pdo, $authorIdOrUser);

if (!$author) {
    header("HTTP/1.0 404 Not Found");
    $pageTitle = 'लेखक प्रोफाइल नहीं मिली - News 24 Himachal';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding: 80px 20px; text-align: center;">
            <i class="fas fa-user-slash" style="font-size: 3.5rem; color: #E50914; margin-bottom: 20px;"></i>
            <h1 style="font-size: 2rem; margin-bottom: 12px;">क्षमा करें, यह लेखक प्रोफाइल उपलब्ध नहीं है।</h1>
            <p style="color: #666; margin-bottom: 25px;">हो सकता है कि यह खाता निष्क्रिय हो या लिंक टूट गया हो।</p>
            <a href="index.php" class="btn-primary">होम पेज पर लौटें &rarr;</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$totalArticles = countNewsByAuthorId($pdo, $author['id']);
$totalPages = ceil($totalArticles / $perPage);
$articles = getNewsByAuthorId($pdo, $author['id'], $perPage, $offset);

// Calculate total views for this author
$totalViewsStmt = $pdo->prepare("SELECT COALESCE(SUM(views), 0) FROM news WHERE author_id = ?");
$totalViewsStmt->execute([$author['id']]);
$authorTotalViews = (int)$totalViewsStmt->fetchColumn();

$pageTitle = sanitize($author['name']) . ' - संवाददाता प्रोफाइल | News 24 Himachal';
$pageDescription = sanitize($author['bio'] ?: ($author['name'] . ' द्वारा प्रकाशित हिमाचल प्रदेश की ताज़ा खबरें'));

require_once __DIR__ . '/includes/header.php';
?>

<main class="main-layout">
    <div class="container">
        
        <!-- Breadcrumb -->
        <nav class="breadcrumb" style="margin-bottom: 18px;">
            <a href="index.php"><i class="fas fa-home"></i> होम</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span>संपादक एवं संवाददाता</span>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="active"><?= sanitize($author['name']) ?></span>
        </nav>

        <!-- Author Hero Profile Banner -->
        <div style="background: linear-gradient(135deg, #18181B 0%, #0F172A 100%); border-radius: 16px; padding: 32px 28px; color: #FFFFFF; margin-bottom: 30px; border-bottom: 4px solid var(--primary-red); box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div style="display: flex; gap: 28px; align-items: center; flex-wrap: wrap;">
                
                <!-- Avatar -->
                <div style="position: relative; flex-shrink: 0;">
                    <img src="<?= sanitize(!empty($author['avatar']) ? $author['avatar'] : getAdminAvatar($pdo)) ?>" 
                         alt="<?= sanitize($author['name']) ?>" 
                         style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-red); box-shadow: 0 8px 24px rgba(229, 9, 20, 0.4);">
                    <span style="position: absolute; bottom: 4px; right: 4px; background: #10B981; width: 18px; height: 18px; border-radius: 50%; border: 3px solid #18181B;" title="Active Reporter"></span>
                </div>

                <!-- Bio & Info -->
                <div style="flex-grow: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px;">
                        <h1 style="font-size: 1.8rem; font-weight: 800; font-family: var(--font-heading); margin: 0; color: #FFFFFF;">
                            <?= sanitize($author['name']) ?>
                        </h1>
                        <span class="badge" style="background: rgba(229, 9, 20, 0.25); color: #FF4D58; border: 1px solid rgba(229, 9, 20, 0.4); font-size: 0.8rem; padding: 4px 10px; border-radius: 6px;">
                            <?= ($author['role'] === 'admin') ? '👑 मुख्य संपादक' : '✍️ संवाददाता' ?>
                        </span>
                    </div>

                    <div style="font-size: 0.95rem; color: #94A3B8; margin-bottom: 12px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <span><i class="fas fa-id-badge" style="color: var(--primary-red);"></i> <?= sanitize($author['designation'] ?: 'संपादकीय डेस्क • News 24 Himachal') ?></span>
                        <?php if (!empty($author['location'])): ?>
                            <span><i class="fas fa-location-dot" style="color: var(--primary-red);"></i> <?= sanitize($author['location']) ?></span>
                        <?php endif; ?>
                    </div>

                    <p style="font-size: 0.95rem; color: #E2E8F0; line-height: 1.6; max-width: 800px; margin-bottom: 16px;">
                        <?= nl2br(sanitize($author['bio'] ?: 'हिमाचल प्रदेश की राजनीति, मौसम, जनहित एवं विकास से जुड़े मुद्दों पर पिछले कई वर्षों से लगातार निष्पक्ष व सटीक रिपोर्टिंग।')) ?>
                    </p>

                    <!-- Stats Badges & Social Links -->
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 14px;">
                        <div style="display: flex; gap: 20px;">
                            <div>
                                <span style="font-size: 1.25rem; font-weight: 800; color: var(--primary-red);"><?= number_format($totalArticles) ?></span>
                                <span style="font-size: 0.82rem; color: #94A3B8; margin-left: 4px;">प्रकाशित खबरें</span>
                            </div>
                            <div>
                                <span style="font-size: 1.25rem; font-weight: 800; color: #38BDF8;"><?= number_format($authorTotalViews) ?></span>
                                <span style="font-size: 0.82rem; color: #94A3B8; margin-left: 4px;">कुल पाठक व्यूज</span>
                            </div>
                        </div>

                        <!-- Social Handles -->
                        <div style="display: flex; gap: 10px;">
                            <?php if (!empty($author['social_twitter'])): ?>
                                <a href="<?= sanitize($author['social_twitter']) ?>" target="_blank" rel="noopener" style="width: 34px; height: 34px; background: rgba(255,255,255,0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #FFFFFF; text-decoration: none; transition: background 0.2s ease;">
                                    <i class="fab fa-x-twitter"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($author['social_facebook'])): ?>
                                <a href="<?= sanitize($author['social_facebook']) ?>" target="_blank" rel="noopener" style="width: 34px; height: 34px; background: rgba(255,255,255,0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #FFFFFF; text-decoration: none; transition: background 0.2s ease;">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            <?php endif; ?>
                            <a href="mailto:<?= sanitize($author['email']) ?>" style="width: 34px; height: 34px; background: rgba(255,255,255,0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #FFFFFF; text-decoration: none; transition: background 0.2s ease;" title="ईमेल भेजें">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Layout Grid: Articles List + Sidebar -->
        <div class="content-grid">
            
            <!-- Left Main Column: Articles by this Author -->
            <div class="main-content-col">
                <div class="section-header" style="margin-bottom: 20px;">
                    <h2 class="section-title">
                        <i class="fas fa-newspaper" style="color: var(--primary-red);"></i> <?= sanitize($author['name']) ?> द्वारा प्रकाशित खबरें (<?= $totalArticles ?>)
                    </h2>
                </div>

                <?php if (empty($articles)): ?>
                    <div style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: 12px; padding: 50px 20px; text-align: center; color: var(--text-muted);">
                        <i class="fas fa-file-pen" style="font-size: 2.5rem; color: #CBD5E1; margin-bottom: 12px; display: block;"></i>
                        <h3 style="font-size: 1.1rem; color: var(--text-heading); margin-bottom: 4px;">अभी तक कोई खबर प्रकाशित नहीं हुई है।</h3>
                        <p style="font-size: 0.88rem;">इस संवाददाता द्वारा जल्द ही नई खबरें अपडेट की जाएंगी।</p>
                    </div>
                <?php else: ?>
                    <div class="cat-grid-3" style="margin-bottom: 30px;">
                        <?php foreach ($articles as $art): ?>
                            <article class="cat-news-card">
                                <div class="cat-card-img">
                                    <img src="<?= sanitize($art['image_url']) ?>" alt="<?= sanitize($art['title']) ?>" loading="lazy">
                                    <span class="cat-card-badge">
                                        <i class="fas fa-tag"></i> <?= sanitize($art['subcategory_name'] ?? $art['category_name']) ?>
                                    </span>
                                </div>
                                <div class="cat-card-body">
                                    <h3 class="cat-card-title">
                                        <a href="article.php?slug=<?= urlencode($art['slug']) ?>">
                                            <?= sanitize($art['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="cat-card-meta">
                                        <span><i class="far fa-calendar-alt"></i> <?= formatHindiDate($art['created_at']) ?></span>
                                        <span><i class="far fa-eye"></i> <?= number_format($art['views']) ?> व्यूज</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div style="display: flex; justify-content: center; gap: 8px; margin: 30px 0; flex-wrap: wrap;">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="author.php?id=<?= $author['id'] ?>&page=<?= $i ?>" 
                                   style="padding: 8px 16px; border-radius: 6px; font-weight: 700; text-decoration: none; transition: all 0.2s ease; <?= $page == $i ? 'background: var(--primary-red); color: #FFF;' : 'background: #FFF; color: var(--text-primary); border: 1px solid var(--border-color);' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Right Sidebar -->
            <aside class="sidebar-col">
                <?php require __DIR__ . '/includes/sidebar.php'; ?>
            </aside>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
