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
    <!-- Category Hero Header -->
    <section class="category-hero-header">
        <div class="container">
            <div class="breadcrumbs" style="color: #A0AEC0;">
                <a href="index.php" style="color: #CBD5E0;">होम</a>
                <span class="separator">&gt;</span>
                <?php if ($subCategory && $category): ?>
                    <a href="category.php?cat=<?= urlencode($category['slug']) ?>" style="color: #CBD5E0;"><?= sanitize($category['name']) ?></a>
                    <span class="separator">&gt;</span>
                    <span style="color: var(--white); font-weight: 600;"><?= sanitize($subCategory['name']) ?></span>
                <?php else: ?>
                    <span style="color: var(--white); font-weight: 600;"><?= sanitize($pageHeading) ?></span>
                <?php endif; ?>
            </div>

            <h1 class="category-hero-title"><?= sanitize($pageHeading) ?></h1>
            <p style="color: #CBD5E0; font-size: 0.95rem;">
                कुल <?= number_format($totalArticles) ?> समाचार उपलब्ध
            </p>

            <!-- Subcategory Dropdown Filter (Dropdown List for Districts & Sub-categories) -->
            <?php if (!empty($category['subcategories'])): ?>
                <div class="category-dropdown-wrapper">
                    <label for="subcategorySelect" class="category-dropdown-label">
                        <i class="fas fa-map-marker-alt"></i> जिला / उप-श्रेणी चुनें:
                    </label>
                    <div class="category-select-box">
                        <select id="subcategorySelect" class="category-dropdown-select" onchange="if (this.value) window.location.href=this.value;">
                            <option value="category.php?cat=<?= urlencode($category['slug']) ?>" <?= empty($subSlug) ? 'selected' : '' ?>>
                                📁 सभी <?= sanitize($category['name']) ?> (सभी जिले)
                            </option>
                            <?php foreach ($category['subcategories'] as $sub): ?>
                                <option value="category.php?cat=<?= urlencode($category['slug']) ?>&sub=<?= urlencode($sub['slug']) ?>" 
                                        <?= $subSlug === $sub['slug'] ? 'selected' : '' ?>>
                                    📍 <?= sanitize($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down category-select-arrow"></i>
                    </div>

                    <?php if ($subCategory): ?>
                        <div class="active-subcat-indicator">
                            <span class="active-subcat-badge">
                                सक्रिय जिला: <strong><?= sanitize($subCategory['name']) ?></strong>
                                <a href="category.php?cat=<?= urlencode($category['slug']) ?>" title="हटाएं व सभी देखें"><i class="fas fa-times"></i></a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Content & Sidebar Grid -->
    <div class="main-layout">
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
