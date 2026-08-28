<?php
/**
 * Admin Static Page Content Editor (About Us, Disclaimer, Privacy, Terms)
 * Himachal News - Khabar 24
 */

$isEdit = false;
$pageData = null;
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

if ($pageId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM `pages` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$pageId]);
    $pageData = $stmt->fetch();
    if ($pageData) {
        $isEdit = true;
    }
}

$adminTitle = $isEdit ? 'Edit Page: ' . ($pageData['title'] ?? '') : 'Add New Page';
$adminHeading = $isEdit ? 'Edit Page Content' : 'Create New CMS Page';

$error = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $metaDescription = trim($_POST['meta_description'] ?? '');

    if (empty($title)) {
        $error = "Please enter page title.";
    } elseif (empty($content)) {
        $error = "Please enter page content.";
    } else {
        if (empty($slug)) {
            $slug = slugify($title);
        }

        try {
            if ($isEdit) {
                $stmt = $pdo->prepare("
                    UPDATE `pages` 
                    SET `title` = ?, `slug` = ?, `content` = ?, `meta_description` = ?
                    WHERE `id` = ?
                ");
                $stmt->execute([$title, $slug, $content, $metaDescription, $pageId]);
                $_SESSION['flash_message'] = "Page '{$title}' content updated successfully!";
                $_SESSION['flash_type'] = "success";
                header("Location: /admin/pages.php");
                exit;
            } else {
                $check = $pdo->prepare("SELECT COUNT(*) FROM `pages` WHERE `slug` = ?");
                $check->execute([$slug]);
                if ($check->fetchColumn() > 0) {
                    $slug .= '-' . time();
                }

                $stmt = $pdo->prepare("
                    INSERT INTO `pages` (`title`, `slug`, `content`, `meta_description`)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$title, $slug, $content, $metaDescription]);
                $_SESSION['flash_message'] = "New page '{$title}' created successfully!";
                $_SESSION['flash_type'] = "success";
                header("Location: /admin/pages.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <div><i class="fas fa-triangle-exclamation"></i> <?= sanitize($error) ?></div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<form method="POST" action="" id="pageForm">
    <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 24px;">
        
        <!-- Left: Title and Rich Text Content -->
        <div>
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-file-pen"></i> Page Details & Main Content</h2>
                </div>
                <div class="panel-body">
                    
                    <!-- Page Title -->
                    <div class="form-group">
                        <label class="form-label" for="pageTitleInput">
                            Page Title <span class="required">*</span>
                        </label>
                        <input type="text" id="pageTitleInput" name="title" class="form-control" style="font-size: 1.05rem; font-weight: 600;" 
                               placeholder="e.g. About Us" 
                               value="<?= sanitize($pageData['title'] ?? ($_POST['title'] ?? '')) ?>" required>
                    </div>

                    <!-- Meta Description -->
                    <div class="form-group">
                        <label class="form-label" for="metaDescInput">
                            Meta Description (for SEO)
                        </label>
                        <textarea id="metaDescInput" name="meta_description" class="form-control" style="min-height: 60px;" 
                                  placeholder="Short summary for search engines and social sharing..."><?= sanitize($pageData['meta_description'] ?? ($_POST['meta_description'] ?? '')) ?></textarea>
                    </div>

                    <!-- Rich Content with Quill Editor -->
                    <div class="form-group">
                        <label class="form-label">
                            Full Page Content (HTML) <span class="required">*</span>
                        </label>
                        <div class="quill-wrapper">
                            <div id="pageQuill" style="min-height: 380px;">
                                <?= $pageData['content'] ?? ($_POST['content'] ?? '') ?>
                            </div>
                        </div>
                        <input type="hidden" name="content" id="pageHiddenContent">
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: Slug & Save Button -->
        <div>
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-floppy-disk"></i> Save Page</h2>
                </div>
                <div class="panel-body">
                    
                    <!-- Slug -->
                    <div class="form-group">
                        <label class="form-label" for="pageSlugInput">
                            URL Slug <span class="required">*</span>
                        </label>
                        <input type="text" id="pageSlugInput" name="slug" class="form-control" 
                               placeholder="about, privacy-policy" 
                               value="<?= sanitize($pageData['slug'] ?? ($_POST['slug'] ?? '')) ?>" required>
                        <span class="form-hint">URL route on website root (e.g. about.php or page.php?slug=about)</span>
                    </div>

                    <button type="submit" class="topbar-btn" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem; margin-top: 10px;">
                        <i class="fas fa-check-circle"></i> <?= $isEdit ? 'Update Page' : 'Create Page' ?>
                    </button>

                    <div style="margin-top: 14px; text-align: center;">
                        <a href="/admin/pages.php" style="color: var(--text-dim); font-size: 0.85rem; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to All Pages
                        </a>
                    </div>
                </div>
            </div>

            <!-- Page Quick Preview Links -->
            <?php if ($isEdit): ?>
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title"><i class="fas fa-eye"></i> Live Link</h2>
                    </div>
                    <div class="panel-body">
                        <?php 
                        $slugMap = [
                            'about' => '/about.php',
                            'disclaimer' => '/disclaimer.php',
                            'privacy-policy' => '/privacy-policy.php',
                            'terms' => '/terms.php'
                        ];
                        $targetUrl = $slugMap[$pageData['slug']] ?? ('/index.php');
                        ?>
                        <a href="<?= $targetUrl ?>" target="_blank" class="view-site-btn">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                            <span>View Live Page (<?= sanitize($pageData['title']) ?>)</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Full Rich Toolbar Options including Justify, Bold, Italic, H1-H6, Font Sizes, Colors, Lists, Media
const toolbarOptions = [
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
    [{ 'size': ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'align': '' }, { 'align': 'center' }, { 'align': 'right' }, { 'align': 'justify' }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'indent': '-1'}, { 'indent': '+1' }],
    ['blockquote', 'code-block'],
    ['link', 'image', 'video'],
    ['clean']
];

const pageQuill = new Quill('#pageQuill', {
    modules: {
        toolbar: toolbarOptions
    },
    theme: 'snow',
    placeholder: 'Type page content here (Bold, Italic, Justify, H1-H6, Lists, Colors, Images etc)...'
});

const pageForm = document.getElementById('pageForm');
pageForm.onsubmit = function() {
    document.getElementById('pageHiddenContent').value = pageQuill.root.innerHTML;
};
</script>
