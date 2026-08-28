<?php
/**
 * Admin Category & Subcategory Management
 * Himachal News - Khabar 24
 */

$adminTitle = 'Categories';
$adminHeading = 'Category Management';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();
$error = null;

// Handle Delete Category / Subcategory
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM `categories` WHERE `id` = ?");
        $stmt->execute([$delId]);
        $_SESSION['flash_message'] = "Category (ID #{$delId}) deleted successfully.";
        $_SESSION['flash_type'] = "success";
    } catch (PDOException $e) {
        $_SESSION['flash_message'] = "Error: Could not delete category. " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
    header("Location: /admin/categories.php");
    exit;
}

// Handle Add or Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $catId = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;

    if (empty($name)) {
        $error = "Please enter the category name.";
    } else {
        if (empty($slug)) {
            $slug = slugify($name);
        }

        try {
            if ($catId > 0) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE `categories` 
                    SET `name` = ?, `slug` = ?, `parent_id` = ?, `display_order` = ?
                    WHERE `id` = ?
                ");
                $stmt->execute([$name, $slug, $parentId, $displayOrder, $catId]);
                $_SESSION['flash_message'] = "Category '{$name}' updated successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                // Insert New
                // Ensure unique slug
                $check = $pdo->prepare("SELECT COUNT(*) FROM `categories` WHERE `slug` = ?");
                $check->execute([$slug]);
                if ($check->fetchColumn() > 0) {
                    $slug .= '-' . time();
                }

                $stmt = $pdo->prepare("
                    INSERT INTO `categories` (`name`, `slug`, `parent_id`, `display_order`, `created_at`)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $slug, $parentId, $displayOrder]);
                $_SESSION['flash_message'] = "New category '{$name}' added successfully!";
                $_SESSION['flash_type'] = "success";
            }
            header("Location: /admin/categories.php");
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Check if editing specific category
$editCat = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ?");
    $stmt->execute([$editId]);
    $editCat = $stmt->fetch();
}

// Fetch all Parent Categories with their Subcategories & post counts
$parents = $pdo->query("
    SELECT c.*, COUNT(n.id) as post_count
    FROM `categories` c
    LEFT JOIN `news` n ON n.category_id = c.id
    WHERE c.parent_id IS NULL
    GROUP BY c.id
    ORDER BY c.display_order ASC, c.id ASC
")->fetchAll();

foreach ($parents as &$parent) {
    $subStmt = $pdo->prepare("
        SELECT sub.*, COUNT(n.id) as post_count
        FROM `categories` sub
        LEFT JOIN `news` n ON n.subcategory_id = sub.id
        WHERE sub.parent_id = ?
        GROUP BY sub.id
        ORDER BY sub.display_order ASC, sub.name ASC
    ");
    $subStmt->execute([$parent['id']]);
    $parent['subcategories'] = $subStmt->fetchAll();
}
unset($parent);

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <div><i class="fas fa-triangle-exclamation"></i> <?= sanitize($error) ?></div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1.8fr; gap: 24px;">
    
    <!-- Left Column: Add / Edit Category Form -->
    <div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i class="fas <?= $editCat ? 'fa-pen-to-square' : 'fa-folder-plus' ?>"></i>
                    <?= $editCat ? 'Edit Category' : 'Add New Category' ?>
                </h2>
                <?php if ($editCat): ?>
                    <a href="categories.php" style="color: var(--accent-blue); font-size: 0.82rem; font-weight: 600; text-decoration: none;">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                <?php endif; ?>
            </div>
            <div class="panel-body">
                <form method="POST" action="categories.php">
                    <input type="hidden" name="save_category" value="1">
                    <?php if ($editCat): ?>
                        <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?>">
                    <?php endif; ?>

                    <!-- Category Name -->
                    <div class="form-group">
                        <label class="form-label" for="catName">
                            Category Name <span class="required">*</span>
                        </label>
                        <input type="text" id="catName" name="name" class="form-control" 
                               placeholder="e.g. Politics, Sports, Shimla, Mandi..." 
                               value="<?= sanitize($editCat['name'] ?? '') ?>" required 
                               oninput="if(!document.getElementById('catSlug').getAttribute('data-edited')) document.getElementById('catSlug').value = generateSlug(this.value);">
                    </div>

                    <!-- Slug -->
                    <div class="form-group">
                        <label class="form-label" for="catSlug">
                            URL Slug <span class="required">*</span>
                        </label>
                        <input type="text" id="catSlug" name="slug" class="form-control" 
                               placeholder="politics, sports, shimla" 
                               value="<?= sanitize($editCat['slug'] ?? '') ?>" required
                               oninput="this.setAttribute('data-edited', 'true')">
                        <span class="form-hint">Used in website URL (e.g. /category.php?cat=sports)</span>
                    </div>

                    <!-- Parent Category Select -->
                    <div class="form-group">
                        <label class="form-label" for="parentSelect">
                            Parent Category
                        </label>
                        <select name="parent_id" id="parentSelect" class="form-control">
                            <option value="">-- None (Main Parent Category) --</option>
                            <?php foreach ($parents as $p): ?>
                                <?php if ($editCat && $editCat['id'] == $p['id']) continue; // Cannot be parent of itself ?>
                                <option value="<?= $p['id'] ?>" <?= (($editCat['parent_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
                                    📁 <?= sanitize($p['name']) ?> (<?= sanitize($p['slug']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">If this is a subcategory or district, select its parent category.</span>
                    </div>

                    <!-- Display Order -->
                    <div class="form-group">
                        <label class="form-label" for="displayOrder">
                            Display Order
                        </label>
                        <input type="number" id="displayOrder" name="display_order" class="form-control" 
                               value="<?= (int)($editCat['display_order'] ?? 0) ?>">
                        <span class="form-hint">Lower numbers appear first in menu (1, 2, 3...)</span>
                    </div>

                    <button type="submit" class="topbar-btn" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
                        <i class="fas fa-check-circle"></i> <?= $editCat ? 'Save Changes' : 'Save Category' ?>
                    </button>

                    <?php if ($editCat): ?>
                        <div style="text-align: center; margin-top: 12px;">
                            <a href="/admin/categories.php" style="color: var(--text-dim); font-size: 0.85rem; text-decoration: none;">
                                <i class="fas fa-xmark"></i> Cancel Edit
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Hierarchical List of Categories & Subcategories -->
    <div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i class="fas fa-list-check"></i> All Categories & Subcategories
                </h2>
            </div>
            <div class="panel-body" style="padding: 16px;">
                
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($parents as $p): ?>
                        <div style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
                            
                            <!-- Parent Item Row -->
                            <div style="padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; background: #F8FAFC; border-bottom: <?= !empty($p['subcategories']) ? '1px solid var(--border-color)' : 'none' ?>;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 1.15rem; color: var(--primary);">📁</span>
                                    <div>
                                        <strong style="font-size: 1rem; color: var(--text-heading);"><?= sanitize($p['name']) ?></strong>
                                        <span style="color: var(--text-dim); font-size: 0.8rem; margin-left: 6px;">(slug: <?= sanitize($p['slug']) ?>)</span>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <span class="badge badge-blue">
                                        <?= $p['post_count'] ?> articles
                                    </span>
                                    <span class="badge badge-gray" title="Order">
                                        Order: <?= $p['display_order'] ?>
                                    </span>
                                    <div class="action-btns">
                                        <a href="/category.php?cat=<?= urlencode($p['slug']) ?>" target="_blank" class="btn-icon" title="View on Website">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <a href="/admin/categories.php?edit=<?= $p['id'] ?>" class="btn-icon btn-icon-edit" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn-icon btn-icon-delete" title="Delete" onclick="confirmDelete('/admin/categories.php?action=delete&id=<?= $p['id'] ?>', 'this category (<?= addslashes($p['name']) ?>)')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Subcategories Child List -->
                            <?php if (!empty($p['subcategories'])): ?>
                                <div style="padding: 10px 16px 10px 36px; display: flex; flex-direction: column; gap: 8px; background: #FFFFFF;">
                                    <?php foreach ($p['subcategories'] as $sub): ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 7px 14px; background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="color: var(--accent-blue); font-size: 0.85rem;">↳</span>
                                                <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-heading);"><?= sanitize($sub['name']) ?></span>
                                                <span style="color: var(--text-dim); font-size: 0.76rem;">(<?= sanitize($sub['slug']) ?>)</span>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <span class="badge badge-gray" style="font-size: 0.68rem;">
                                                    <?= $sub['post_count'] ?> articles
                                                </span>
                                                <div class="action-btns">
                                                    <a href="/admin/categories.php?edit=<?= $sub['id'] ?>" class="btn-icon btn-icon-edit" style="width: 28px; height: 28px; font-size: 0.75rem;" title="Edit">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                    <button type="button" class="btn-icon btn-icon-delete" style="width: 28px; height: 28px; font-size: 0.75rem;" title="Delete" onclick="confirmDelete('/admin/categories.php?action=delete&id=<?= $sub['id'] ?>', 'this subcategory')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
