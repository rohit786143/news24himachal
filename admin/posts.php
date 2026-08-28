<?php
/**
 * Admin News Posts Listing & Management
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
$currentUserId = (int)$currentUser['id'];
$isEditor = ($currentUser['role'] === 'editor');
$isAdmin = ($currentUser['role'] === 'admin');

// Handle Delete Action BEFORE including header.php
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    if ($isEditor) {
        $checkStmt = $pdo->prepare("SELECT id FROM `news` WHERE `id` = ? AND `author_id` = ?");
        $checkStmt->execute([$delId, $currentUserId]);
        if (!$checkStmt->fetch()) {
            $_SESSION['flash_message'] = "Error: You can only delete your own published posts.";
            $_SESSION['flash_type'] = "danger";
            header("Location: /admin/posts.php");
            exit;
        }
    }
    $stmt = $pdo->prepare("DELETE FROM `news` WHERE `id` = ?");
    $stmt->execute([$delId]);
    $_SESSION['flash_message'] = "Article (#{$delId}) deleted successfully.";
    $_SESSION['flash_type'] = "success";
    header("Location: /admin/posts.php");
    exit;
}

$adminTitle = $isEditor ? 'My Published Posts' : 'All Posts';
$adminHeading = $isEditor ? 'My Published Articles' : 'News Management';

require_once __DIR__ . '/includes/header.php';

// Search and Filter parameters
$search = trim($_GET['q'] ?? '');
$catFilter = trim($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build Query
$sql = "
    SELECT n.*, c.name AS category_name, c.slug AS category_slug, 
           sub.name AS subcategory_name, sub.slug AS subcategory_slug
    FROM `news` n
    LEFT JOIN `categories` c ON n.category_id = c.id
    LEFT JOIN `categories` sub ON n.subcategory_id = sub.id
    WHERE 1=1
";
$countSql = "SELECT COUNT(*) FROM `news` n WHERE 1=1";
$params = [];

if ($isEditor) {
    $sql .= " AND n.author_id = ?";
    $countSql .= " AND n.author_id = ?";
    $params[] = $currentUserId;
}

if (!empty($search)) {
    $sql .= " AND (n.title LIKE ? OR n.content LIKE ? OR n.author LIKE ?)";
    $countSql .= " AND (n.title LIKE ? OR n.content LIKE ? OR n.author LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($catFilter)) {
    $sql .= " AND (n.category_id = ? OR n.subcategory_id = ?)";
    $countSql .= " AND (n.category_id = ? OR n.subcategory_id = ?)";
    $params[] = (int)$catFilter;
    $params[] = (int)$catFilter;
}

// Count Total
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalPosts / $perPage);

// Fetch Paginated Results
$sql .= " ORDER BY n.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Fetch All Categories for Filter
$allCats = $pdo->query("SELECT id, name, parent_id FROM `categories` ORDER BY parent_id ASC, display_order ASC, name ASC")->fetchAll();
?>

<!-- Action & Filter Bar -->
<div class="panel" style="margin-bottom: 20px;">
    <div class="panel-body" style="padding: 16px 20px;">
        <form method="GET" action="/admin/posts.php" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <div style="flex-grow: 1; min-width: 250px; position: relative;">
                <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Search article title or author..." class="form-control" style="padding-left: 36px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim);"></i>
            </div>

            <div style="min-width: 200px;">
                <select name="category" class="form-control" onchange="this.form.submit()">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($allCats as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>>
                            <?= $cat['parent_id'] ? '&nbsp;&nbsp;&bull; ' : '📁 ' ?><?= sanitize($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="topbar-btn" style="padding: 10px 18px;">
                <i class="fas fa-filter"></i> Filter
            </button>

            <?php if (!empty($search) || !empty($catFilter)): ?>
                <a href="/admin/posts.php" class="topbar-btn topbar-btn-secondary" style="padding: 10px 16px;">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            <?php endif; ?>

            <a href="/admin/post-edit.php" class="topbar-btn" style="margin-left: auto; background: var(--accent-green); border-color: var(--accent-green);">
                <i class="fas fa-plus"></i> Add New Post
            </a>
        </form>
    </div>
</div>

<!-- Posts Table Panel -->
<div class="panel">
    <div class="panel-header">
        <h2 class="panel-title">
            <i class="fas fa-newspaper"></i> Total <strong><?= number_format($totalPosts) ?></strong> Articles Available
        </h2>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 70px;">Image</th>
                    <th>Article Title</th>
                    <th>Category</th>
                    <?php if (!$isEditor): ?>
                        <th>Author</th>
                    <?php endif; ?>
                    <th>Views</th>
                    <th>Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="<?= $isEditor ? '6' : '7' ?>" style="text-align: center; color: var(--text-dim); padding: 40px;">
                            <i class="fas fa-newspaper" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                            No articles found. Please change filters or <a href="/admin/post-edit.php" style="color: var(--accent-blue);">add a new post</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($articles as $art): ?>
                        <tr>
                            <td>
                                <img src="<?= sanitize($art['image_url']) ?>" alt="Thumbnail" class="table-thumb" onerror="this.src='https://via.placeholder.com/60x45?text=News';">
                            </td>
                            <td>
                                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-heading); margin-bottom: 4px; max-width: 380px;">
                                    <a href="/article.php?slug=<?= urlencode($art['slug']) ?>" target="_blank" style="color: inherit; text-decoration: none;" title="View Live Website">
                                        <?= sanitize($art['title']) ?>
                                    </a>
                                </div>
                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                    <?php if ($art['is_breaking']): ?>
                                        <span class="badge badge-red"><i class="fas fa-bolt"></i> Breaking</span>
                                    <?php endif; ?>
                                    <?php if ($art['is_featured']): ?>
                                        <span class="badge badge-blue"><i class="fas fa-star"></i> Lead Story</span>
                                    <?php endif; ?>
                                    <?php if ($art['is_trending']): ?>
                                        <span class="badge badge-green"><i class="fas fa-fire"></i> Trending</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge badge-blue">
                                        📁 <?= sanitize($art['category_name']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($art['subcategory_name'])): ?>
                                    <div style="margin-top: 4px;">
                                        <span class="badge badge-gray" style="font-size: 0.68rem;">
                                            ↳ <?= sanitize($art['subcategory_name']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php if (!$isEditor): ?>
                                <td style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= sanitize($art['author'] ?: 'Editor') ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <span style="font-weight: 700; color: var(--text-heading);"><i class="far fa-eye"></i> <?= number_format($art['views']) ?></span>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-dim);">
                                <?= date('d M Y, h:i A', strtotime($art['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-btns" style="justify-content: flex-end;">
                                    <a href="/article.php?slug=<?= urlencode($art['slug']) ?>" target="_blank" class="btn-icon" title="View Live">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <a href="/admin/post-edit.php?id=<?= $art['id'] ?>" class="btn-icon btn-icon-edit" title="Edit">
                                        <i class="fas fa-pen-to-square"></i>
                                    </a>
                                    <button type="button" class="btn-icon btn-icon-delete" title="Delete" onclick="confirmDelete('/admin/posts.php?action=delete&id=<?= $art['id'] ?>', 'this article')">
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

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap;">
        <?php if ($page > 1): ?>
            <a href="/admin/posts.php?q=<?= urlencode($search) ?>&category=<?= urlencode($catFilter) ?>&page=<?= $page - 1 ?>" class="topbar-btn topbar-btn-secondary" style="padding: 6px 12px;">
                &laquo; Previous
            </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="/admin/posts.php?q=<?= urlencode($search) ?>&category=<?= urlencode($catFilter) ?>&page=<?= $i ?>" 
               class="topbar-btn <?= $page == $i ? '' : 'topbar-btn-secondary' ?>" 
               style="padding: 6px 14px; font-weight: 700;">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="/admin/posts.php?q=<?= urlencode($search) ?>&category=<?= urlencode($catFilter) ?>&page=<?= $page + 1 ?>" class="topbar-btn topbar-btn-secondary" style="padding: 6px 12px;">
                Next &raquo;
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
