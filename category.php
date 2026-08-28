<?php
/**
 * Category & Subcategory Listing Page
 * News 24 Himachal
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

$categorySlug = isset($_GET['cat']) ? sanitize($_GET['cat']) : '';
$subSlug = isset($_GET['sub']) ? sanitize($_GET['sub']) : null;

// Route Rashifal category directly to the rich 12-Rashi Daily Horoscope Hub
if ($categorySlug === 'rashiphal' || $categorySlug === 'rashifal') {
    require_once __DIR__ . '/rashifal.php';
    exit;
}
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

// Fetch Category Details
$category = getCategoryBySlug($pdo, $categorySlug);

// If subcategory is requested, fetch subcategory info
$subCategory = null;
if ($subSlug) {
    $subCategory = getCategoryBySlug($pdo, $subSlug);
}

// Fallback if category not found
if (!$category && !$subCategory) {
    // Default to latest news
    $pageTitle = 'सभी समाचार - News 24 Himachal';
    $pageHeading = 'ताज़ा समाचार';
    $articles = getNewsByCategorySlug($pdo, '', $perPage, $offset);
    $totalArticles = countNewsByCategorySlug($pdo, '');
} else {
    $currentCat = $subCategory ?: $category;
    $pageTitle = sanitize($currentCat['name']) . ' - समाचार | News 24 Himachal';
    $pageHeading = sanitize($currentCat['name']);
    $articles = getNewsByCategorySlug($pdo, $categorySlug, $perPage, $offset, $subSlug);
    $totalArticles = countNewsByCategorySlug($pdo, $categorySlug, $subSlug);
}

$totalPages = ceil($totalArticles / $perPage);

require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="main-layout" style="padding-top: 15px;">
        <div class="container">
            <!-- Compact Space-Saving Category Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 2px solid #E2E8F0; padding-bottom: 12px;">
                <div>
                    <div class="breadcrumbs" style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 4px;">
                        <a href="index.php" style="color: var(--text-muted); text-decoration: none;">होम</a>
                        <span class="separator" style="margin: 0 4px;">&rsaquo;</span>
                        <?php if ($subCategory && $category): ?>
                            <a href="category.php?cat=<?= urlencode($category['slug']) ?>" style="color: var(--text-muted); text-decoration: none;"><?= sanitize($category['name']) ?></a>
                            <span class="separator" style="margin: 0 4px;">&rsaquo;</span>
                            <span style="color: var(--primary); font-weight: 700;"><?= sanitize($subCategory['name']) ?></span>
                        <?php else: ?>
                            <span style="color: var(--primary); font-weight: 700;"><?= sanitize($pageHeading) ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 style="font-size: 1.55rem; font-weight: 900; color: var(--text-heading); margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 4px; height: 22px; background: var(--primary); border-radius: 2px;"></span>
                        <?= sanitize($pageHeading) ?>
                        <span style="font-size: 0.82rem; font-weight: normal; color: var(--text-muted); margin-left: 6px;">(कुल <?= number_format($totalArticles) ?> खबरें)</span>
                    </h1>
                </div>

                <!-- Subcategory / District Dropdown Filter -->
                <?php if (!empty($category['subcategories'])): ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="subcategorySelect" style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); white-space: nowrap;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> जिला / उप-श्रेणी:
                        </label>
                        <select id="subcategorySelect" class="form-control" onchange="if (this.value) window.location.href=this.value;" style="padding: 6px 12px; font-size: 0.85rem; height: auto; border-radius: 6px; width: auto; min-width: 180px;">
                            <option value="category.php?cat=<?= urlencode($category['slug']) ?>" <?= empty($subSlug) ? 'selected' : '' ?>>
                                📁 सभी <?= sanitize($category['name']) ?>
                            </option>
                            <?php foreach ($category['subcategories'] as $sub): ?>
                                <option value="category.php?cat=<?= urlencode($category['slug']) ?>&sub=<?= urlencode($sub['slug']) ?>" 
                                        <?= $subSlug === $sub['slug'] ? 'selected' : '' ?>>
                                    📍 <?= sanitize($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Content & Sidebar Grid -->
        <div class="container content-grid">
            
            <div class="main-content-column">
                <?php if (!empty($articles)): ?>
                    <div class="news-cards-grid">
                        <?php foreach ($articles as $article): ?>
                            <article class="news-card">
                                <div class="news-card-img">
                                    <img src="<?= sanitize($article['image_url']) ?>" alt="<?= sanitize($article['title']) ?>" loading="lazy">
                                </div>
                                <div class="news-card-body">
                                    <span class="category-tag">
                                        <?= sanitize($article['subcategory_name'] ?? $article['category_name']) ?>
                                    </span>
                                    <h2 class="news-card-title">
                                        <a href="article.php?slug=<?= urlencode($article['slug']) ?>">
                                            <?= sanitize($article['title']) ?>
                                        </a>
                                    </h2>
                                    <p class="news-card-excerpt"><?= sanitize($article['excerpt']) ?></p>
                                    <div class="news-card-footer">
                                        <span><i class="far fa-clock"></i> <?= timeAgoHindi($article['created_at']) ?></span>
                                        <span><i class="far fa-eye"></i> <?= number_format($article['views']) ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?cat=<?= urlencode($categorySlug) ?><?= $subSlug ? '&sub='.urlencode($subSlug) : '' ?>&page=<?= $page - 1 ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?cat=<?= urlencode($categorySlug) ?><?= $subSlug ? '&sub='.urlencode($subSlug) : '' ?>&page=<?= $i ?>" 
                                   class="page-link <?= $page == $i ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?cat=<?= urlencode($categorySlug) ?><?= $subSlug ? '&sub='.urlencode($subSlug) : '' ?>&page=<?= $page + 1 ?>" class="page-link">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div style="background: var(--white); padding: 40px; text-align: center; border-radius: var(--radius); border: 1px solid var(--border-color);">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: #CBD5E0; margin-bottom: 15px;"></i>
                        <h3>इस श्रेणी में अभी कोई समाचार उपलब्ध नहीं है।</h3>
                        <p style="color: var(--text-muted); margin-top: 8px;">कृपया अन्य श्रेणियों या मुख्य पृष्ठ पर समाचार देखें।</p>
                        <a href="index.php" class="btn-primary" style="margin-top: 18px;">होम पेज पर जाएं</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="sidebar-column">
                <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
