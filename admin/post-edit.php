<?php
/**
 * Admin Add / Edit News Article
 * Himachal News - Khabar 24
 */

$isEdit = false;
$post = null;
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// If Editing, fetch existing post
if ($postId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM `news` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    if ($post) {
        $isEdit = true;
        // Verify editor ownership
        if (!empty($_SESSION['admin_user']) && $_SESSION['admin_user']['role'] === 'editor') {
            if ((int)$post['author_id'] !== (int)$_SESSION['admin_user']['id']) {
                $_SESSION['flash_message'] = "त्रुटि: आप केवल अपनी प्रकाशित खबर ही संपादित (Edit) कर सकते हैं।";
                $_SESSION['flash_type'] = "danger";
                header("Location: /admin/posts.php");
                exit;
            }
        }
    }
}

$adminTitle = $isEdit ? 'खबर संपादित करें (Edit Post)' : 'नई खबर जोड़ें (Add Post)';
$adminHeading = $isEdit ? 'खबर संपादित करें (Edit Article #' . $postId . ')' : 'नई खबर प्रकाशित करें (Publish Article)';

$error = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    
    // Check if user uploaded an image file from device
    if (!empty($_FILES['image_file']['tmp_name'])) {
        $uploadedNewsImg = handleImageUpload($_FILES['image_file'], 'posts');
        if ($uploadedNewsImg) {
            $imageUrl = $uploadedNewsImg;
        }
    }

    // Determine Author and Author ID
    $loggedInUser = $_SESSION['admin_user'] ?? ['id' => 1, 'name' => 'संपादकीय प्रमुख', 'role' => 'admin'];
    if ($loggedInUser['role'] === 'editor') {
        $author = $loggedInUser['name'];
        $authorId = (int)$loggedInUser['id'];
    } else {
        $authorId = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : (int)$loggedInUser['id'];
        $author = trim($_POST['author'] ?? $loggedInUser['name']);
        // If author_id selected, fetch exact name from users table
        if (!empty($_POST['author_id'])) {
            $uStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
            $uStmt->execute([$authorId]);
            $uName = $uStmt->fetchColumn();
            if ($uName) $author = $uName;
        }
    }

    $views = isset($_POST['views']) ? (int)$_POST['views'] : 0;
    $isBreaking = isset($_POST['is_breaking']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isTrending = isset($_POST['is_trending']) ? 1 : 0;

    // Basic Validation
    if (empty($title)) {
        $error = "कृपया खबर का शीर्षक (Title) दर्ज करें।";
    } elseif (empty($categoryId)) {
        $error = "कृपया मुख्य श्रेणी (Category) का चयन करें।";
    } elseif (empty($content)) {
        $error = "कृपया खबर का पूरा विवरण (Content) दर्ज करें।";
    } else {
        // Auto-generate slug if empty
        if (empty($slug)) {
            $slug = slugify($title);
        }

        // Default placeholder image if empty
        if (empty($imageUrl)) {
            $imageUrl = 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=800&q=80';
        }

        // Auto-generate excerpt if empty
        if (empty($excerpt)) {
            $plainText = strip_tags($content);
            $excerpt = mb_substr($plainText, 0, 160, 'UTF-8') . '...';
        }

        // Ensure author_id column exists in news table safely
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM `news` LIKE 'author_id'")->fetch();
            if (!$colCheck) {
                $pdo->exec("ALTER TABLE `news` ADD COLUMN `author_id` INT NULL AFTER `author`");
            }
        } catch (Exception $eCol) {}

        try {
            if ($isEdit) {
                // Update
                $updateStmt = $pdo->prepare("
                    UPDATE `news` SET
                        `category_id` = ?,
                        `subcategory_id` = ?,
                        `title` = ?,
                        `slug` = ?,
                        `excerpt` = ?,
                        `content` = ?,
                        `image_url` = ?,
                        `author` = ?,
                        `author_id` = ?,
                        `views` = ?,
                        `is_breaking` = ?,
                        `is_featured` = ?,
                        `is_trending` = ?
                    WHERE `id` = ?
                ");
                $updateStmt->execute([
                    $categoryId,
                    $subcategoryId,
                    $title,
                    $slug,
                    $excerpt,
                    $content,
                    $imageUrl,
                    $author,
                    $authorId,
                    $views,
                    $isBreaking,
                    $isFeatured,
                    $isTrending,
                    $postId
                ]);

                $_SESSION['flash_message'] = "खबर सफलतापूर्वक अपडेट कर दी गई है!";
                $_SESSION['flash_type'] = "success";
                header("Location: posts.php");
                exit;
            } else {
                // Insert New
                // Ensure slug uniqueness
                $checkSlug = $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `slug` = ?");
                $checkSlug->execute([$slug]);
                if ($checkSlug->fetchColumn() > 0) {
                    $slug .= '-' . time();
                }

                $insertStmt = $pdo->prepare("
                    INSERT INTO `news` (
                        `category_id`, `subcategory_id`, `title`, `slug`, `excerpt`, 
                        `content`, `image_url`, `author`, `author_id`, `views`, `is_breaking`, 
                        `is_featured`, `is_trending`, `created_at`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([
                    $categoryId,
                    $subcategoryId,
                    $title,
                    $slug,
                    $excerpt,
                    $content,
                    $imageUrl,
                    $author,
                    $authorId,
                    $views,
                    $isBreaking,
                    $isFeatured,
                    $isTrending
                ]);

                $newId = $pdo->lastInsertId();
                $_SESSION['flash_message'] = "नई खबर (ID: #{$newId}) सफलतापूर्वक प्रकाशित कर दी गई है!";
                $_SESSION['flash_type'] = "success";
                header("Location: posts.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "डेटाबेस त्रुटि: " . $e->getMessage();
        }
    }
}

// Fetch all Parent Categories & Subcategories
$parents = $pdo->query("SELECT id, name, slug FROM `categories` WHERE `parent_id` IS NULL ORDER BY `display_order` ASC, `name` ASC")->fetchAll();
$allSubcategories = $pdo->query("SELECT id, parent_id, name, slug FROM `categories` WHERE `parent_id` IS NOT NULL ORDER BY `parent_id` ASC, `display_order` ASC, `name` ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <div><i class="fas fa-triangle-exclamation"></i> <?= sanitize($error) ?></div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<form method="POST" action="" id="postForm" enctype="multipart/form-data">
    <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 24px;">
        
        <!-- Left Column: Main Article Body & Details -->
        <div>
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-newspaper"></i> खबर का विवरण (Article Information)</h2>
                </div>
                <div class="panel-body">
                    
                    <!-- Title -->
                    <div class="form-group">
                        <label class="form-label" for="postTitle">
                            खबर का मुख्य शीर्षक (Headline / Title) <span class="required">*</span>
                        </label>
                        <input type="text" id="postTitle" name="title" class="form-control" style="font-size: 1.05rem; font-weight: 600;" 
                               placeholder="उदा: हिमाचल कैबिनेट का बड़ा फैसला: युवाओं के लिए नई रोजगार नीति..." 
                               value="<?= sanitize($post['title'] ?? ($_POST['title'] ?? '')) ?>" required>
                    </div>

                    <!-- Slug -->
                    <div class="form-group">
                        <label class="form-label" for="postSlug">
                            URL स्लग (Permalink Slug) <span class="form-hint">(खाली छोड़ने पर शीर्षक से स्वतः बन जाएगा)</span>
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="postSlug" name="slug" class="form-control" 
                                   placeholder="himachal-cabinet-decision-employment-policy" 
                                   value="<?= sanitize($post['slug'] ?? ($_POST['slug'] ?? '')) ?>">
                            <button type="button" class="topbar-btn topbar-btn-secondary" onclick="document.getElementById('postSlug').value = generateSlug(document.getElementById('postTitle').value);" title="शीर्षक से स्वतः स्लग बनाएं">
                                <i class="fas fa-wand-magic-sparkles"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Excerpt -->
                    <div class="form-group">
                        <label class="form-label" for="postExcerpt">
                            संक्षिप्त सारांश (Short Excerpt) <span class="form-hint">(होमपेज और सोशल मीडिया कार्ड्स के लिए)</span>
                        </label>
                        <textarea id="postExcerpt" name="excerpt" class="form-control" style="min-height: 70px;" 
                                  placeholder="खबर का 1-2 पंक्तियों में मुख्य सार..."><?= sanitize($post['excerpt'] ?? ($_POST['excerpt'] ?? '')) ?></textarea>
                    </div>

                    <!-- Full Content with WYSIWYG Editor -->
                    <div class="form-group">
                        <label class="form-label">
                            खबर का पूरा विवरण (Full Article Content) <span class="required">*</span>
                        </label>
                        <div class="quill-wrapper">
                            <div id="quillEditor" style="min-height: 380px;">
                                <?= $post['content'] ?? ($_POST['content'] ?? '') ?>
                            </div>
                        </div>
                        <input type="hidden" name="content" id="hiddenContent">
                    </div>

                </div>
            </div>
        </div>

        <!-- Right Column: Category, Subcategory, Image, Flags & Save -->
        <div>
            
            <!-- Publish / Save Action Box -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-paper-plane"></i> प्रकाशन (Publish)</h2>
                </div>
                <div class="panel-body">
                    <button type="submit" class="topbar-btn" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
                        <i class="fas fa-check-circle"></i> <?= $isEdit ? 'अपडेट सुरक्षित करें (Save Changes)' : 'खबर प्रकाशित करें (Publish News)' ?>
                    </button>

                    <div style="margin-top: 14px; text-align: center;">
                        <a href="/admin/posts.php" style="color: var(--text-dim); font-size: 0.85rem; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> वापस सूची पर जाएं
                        </a>
                    </div>
                </div>
            </div>

            <!-- Categories Selection Box -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-folder-tree"></i> श्रेणी चयन (Categories)</h2>
                </div>
                <div class="panel-body">
                    
                    <!-- Parent Category -->
                    <div class="form-group">
                        <label class="form-label" for="categorySelect">
                            मुख्य श्रेणी (Parent Category) <span class="required">*</span>
                        </label>
                        <select name="category_id" id="categorySelect" class="form-control" required onchange="filterSubcategories()">
                            <option value="">-- श्रेणी चुनें --</option>
                            <?php foreach ($parents as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (($post['category_id'] ?? ($_POST['category_id'] ?? 0)) == $p['id']) ? 'selected' : '' ?>>
                                    📁 <?= sanitize($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Subcategory (Cascading Filter) -->
                    <div class="form-group">
                        <label class="form-label" for="subcategorySelect">
                            उप-श्रेणी / जिला (Subcategory / District)
                        </label>
                        <select name="subcategory_id" id="subcategorySelect" class="form-control">
                            <option value="">-- कोई उप-श्रेणी नहीं (None) --</option>
                            <?php foreach ($allSubcategories as $sub): ?>
                                <option value="<?= $sub['id'] ?>" data-parent="<?= $sub['parent_id'] ?>" 
                                        <?= (($post['subcategory_id'] ?? ($_POST['subcategory_id'] ?? 0)) == $sub['id']) ? 'selected' : '' ?>>
                                    ↳ <?= sanitize($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-hint">मुख्य श्रेणी बदलते ही उप-श्रेणियां स्वतः फ़िल्टर हो जाएंगी।</span>
                    </div>

                </div>
            </div>

            <!-- Featured Image Box -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-image"></i> मुख्य तस्वीर (Featured Image)</h2>
                </div>
                <div class="panel-body">
                    
                    <!-- Direct Device File Upload Input -->
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label" for="newsImageFile" style="color: #FFFFFF; font-weight: 700;">
                            <i class="fas fa-cloud-arrow-up" style="color: var(--primary);"></i> डिवाइस से तस्वीर अपलोड करें
                        </label>
                        <input type="file" id="newsImageFile" name="image_file" accept="image/*" class="form-control" 
                               style="padding: 8px 12px; cursor: pointer; border: 1.5px dashed var(--primary); background: rgba(229,9,20,0.05);"
                               onchange="previewNewsImage(this)">
                        <span class="form-hint" style="color: var(--text-dim);">कंप्यूटर / मोबाइल से JPG, PNG, WEBP तस्वीर चुनें।</span>
                    </div>

                    <div class="form-group" style="border-top: 1px dashed var(--border-color); padding-top: 12px; margin-bottom: 12px;">
                        <label class="form-label" for="imageUrlInput">
                            अथवा तस्वीर का वेब लिंक (Image URL)
                        </label>
                        <input type="url" id="imageUrlInput" name="image_url" class="form-control" 
                               placeholder="https://images.unsplash.com/..." 
                               value="<?= sanitize($post['image_url'] ?? ($_POST['image_url'] ?? '')) ?>" 
                               oninput="updateImagePreview(this.value)">
                    </div>

                    <!-- Live Image Preview -->
                    <div style="margin-top: 10px; border-radius: var(--radius-sm); overflow: hidden; border: 1px dashed var(--border-color); background: var(--bg-input); height: 160px; display: flex; align-items: center; justify-content: center;">
                        <img id="imagePreview" src="<?= sanitize($post['image_url'] ?? '') ?>" alt="Preview" 
                             style="max-width: 100%; max-height: 100%; object-fit: cover; <?= empty($post['image_url']) ? 'display:none;' : '' ?>">
                        <span id="imagePlaceholderText" style="color: var(--text-dim); font-size: 0.8rem; <?= !empty($post['image_url']) ? 'display:none;' : '' ?>">
                            <i class="far fa-image" style="font-size: 1.5rem; display: block; margin-bottom: 4px; text-align: center;"></i>
                            तस्वीर का प्रीव्यू यहाँ दिखेगा
                        </span>
                    </div>
                </div>
            </div>

            <!-- Display Badges & Meta -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-tags"></i> प्राथमिकता एवं सेटिंग्स</h2>
                </div>
                <div class="panel-body" style="display: flex; flex-direction: column; gap: 12px;">
                    
                    <label class="form-check">
                        <input type="checkbox" name="is_breaking" value="1" <?= (!empty($post['is_breaking']) || !empty($_POST['is_breaking'])) ? 'checked' : '' ?>>
                        <div>
                            <strong style="color: #fff; font-size: 0.88rem;"><i class="fas fa-bolt" style="color: var(--primary);"></i> ब्रेकिंग न्यूज़ (Breaking)</strong>
                            <div style="font-size: 0.75rem; color: var(--text-dim);">टॉप रेड टिकर में फ्लैश होगी</div>
                        </div>
                    </label>

                    <label class="form-check">
                        <input type="checkbox" name="is_featured" value="1" <?= (!empty($post['is_featured']) || !empty($_POST['is_featured'])) ? 'checked' : '' ?>>
                        <div>
                            <strong style="color: #fff; font-size: 0.88rem;"><i class="fas fa-star" style="color: var(--accent-amber);"></i> सबसे बड़ी खबर (Hero Slider)</strong>
                            <div style="font-size: 0.75rem; color: var(--text-dim);">होमपेज मुख्य स्लाइडर पर दिखेगी</div>
                        </div>
                    </label>

                    <label class="form-check">
                        <input type="checkbox" name="is_trending" value="1" <?= (!empty($post['is_trending']) || !empty($_POST['is_trending'])) ? 'checked' : '' ?>>
                        <div>
                            <strong style="color: #fff; font-size: 0.88rem;"><i class="fas fa-fire" style="color: #f97316;"></i> ट्रेंडिंग खबर (Trending)</strong>
                            <div style="font-size: 0.75rem; color: var(--text-dim);">ट्रेंडिंग लिस्ट में ऊपर आएगी</div>
                        </div>
                    </label>

                    <?php if (!empty($isEditor) && $isEditor): ?>
                        <div class="form-group" style="margin-top: 14px; margin-bottom: 0;">
                            <label class="form-label">लेखक / संवाददाता (Author)</label>
                            <div style="background: var(--bg-body); border: 1.5px solid var(--border-color); border-radius: 8px; padding: 10px 12px; display: flex; align-items: center; gap: 10px;">
                                <img src="<?= sanitize($currentUser['avatar']) ?>" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid #38BDF8;">
                                <div>
                                    <strong style="color: var(--text-heading); font-size: 0.9rem; display: block;"><?= sanitize($currentUser['name']) ?></strong>
                                    <span style="color: var(--text-muted); font-size: 0.76rem;"><?= sanitize($currentUser['designation'] ?? 'संवाददाता • News 24 Himachal') ?></span>
                                </div>
                            </div>
                            <input type="hidden" name="author" value="<?= sanitize($currentUser['name']) ?>">
                            <input type="hidden" name="author_id" value="<?= $currentUserId ?>">
                        </div>
                    <?php else: ?>
                        <?php 
                        $allUsersList = $pdo->query("SELECT id, name, designation, role FROM `users` WHERE `status` = 'active' ORDER BY `role` ASC, `name` ASC")->fetchAll(); 
                        $currentAuthorId = (int)($post['author_id'] ?? ($currentUser['id'] ?? 1));
                        ?>
                        <div class="form-group" style="margin-top: 14px; margin-bottom: 0;">
                            <label class="form-label" for="authorSelect">संवाददाता / लेखक चुनें (Assign Author)</label>
                            <select name="author_id" id="authorSelect" class="form-control">
                                <?php foreach ($allUsersList as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($currentAuthorId === (int)$u['id']) ? 'selected' : '' ?>>
                                        <?= ($u['role'] === 'admin') ? '👑 ' : '✍️ ' ?><?= sanitize($u['name']) ?> (<?= sanitize($u['designation'] ?: $u['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group" style="margin-top: 14px; margin-bottom: 0;">
                        <label class="form-label" for="postViews">व्यूज काउंट (Views)</label>
                        <input type="number" id="postViews" name="views" class="form-control" 
                               value="<?= (int)($post['views'] ?? ($_POST['views'] ?? 0)) ?>">
                    </div>

                </div>
            </div>

        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Custom Image Upload Handler for Quill (Local File Picker)
function imageHandler() {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();

    input.onchange = function() {
        const file = input.files[0];
        if (file && /^image\//.test(file.type)) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', e.target.result);
                quill.setSelection(range.index + 1);
            };
            reader.readAsDataURL(file);
        }
    };
}

// Full Rich Toolbar with All Requested Options (Bold, Align, H1-H6, Font Sizes, Colors, Lists, Media, Image Upload)
const toolbarOptions = [
    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
    [{ 'size': ['small', false, 'large', 'huge'] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'color': [] }, { 'background': [] }],
    [{ 'align': '' }, { 'align': 'center' }, { 'align': 'right' }, { 'align': 'justify' }],
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'script': 'sub'}, { 'script': 'super' }],
    [{ 'indent': '-1'}, { 'indent': '+1' }],
    ['blockquote', 'code-block'],
    ['link', 'image', 'video'],
    ['clean']
];

const quill = new Quill('#quillEditor', {
    modules: {
        toolbar: {
            container: toolbarOptions,
            handlers: {
                'image': imageHandler
            }
        }
    },
    theme: 'snow',
    placeholder: 'खबर का पूरा विवरण यहाँ लिखें या पेस्ट करें (Bold, Italic, Align Justify, H1-H6, लिस्ट, कलर, इमेज आदि)...'
});

// Sync Quill HTML to hidden input before form submit
const form = document.getElementById('postForm');
form.onsubmit = function() {
    const hiddenContent = document.getElementById('hiddenContent');
    hiddenContent.value = quill.root.innerHTML;
};

// Filter Subcategories by Selected Parent Category
function filterSubcategories() {
    const parentSelect = document.getElementById('categorySelect');
    const subSelect = document.getElementById('subcategorySelect');
    if (!parentSelect || !subSelect) return;

    const selectedParentId = parentSelect.value;
    const currentSubValue = subSelect.value;
    let hasVisibleMatch = false;

    for (let i = 0; i < subSelect.options.length; i++) {
        const option = subSelect.options[i];
        const optParent = option.getAttribute('data-parent');

        if (!optParent) {
            // "None" option is always visible
            option.style.display = 'block';
            continue;
        }

        if (selectedParentId && optParent === selectedParentId) {
            option.style.display = 'block';
            if (option.value === currentSubValue) {
                hasVisibleMatch = true;
            }
        } else {
            option.style.display = 'none';
        }
    }

    if (!hasVisibleMatch && subSelect.value !== '') {
        subSelect.value = '';
    }
}

// Local Image File Preview Handler
function previewNewsImage(input) {
    const img = document.getElementById('imagePreview');
    const placeholder = document.getElementById('imagePlaceholderText');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Live Image Preview Handler (URL)
function updateImagePreview(url) {
    const img = document.getElementById('imagePreview');
    const placeholder = document.getElementById('imagePlaceholderText');
    if (url && url.trim() !== '') {
        img.src = url;
        img.style.display = 'block';
        placeholder.style.display = 'none';
        img.onerror = function() {
            img.style.display = 'none';
            placeholder.style.display = 'block';
        };
    } else {
        img.style.display = 'none';
        placeholder.style.display = 'block';
    }
}

// Run initial subcategory filter on page load
document.addEventListener('DOMContentLoaded', function() {
    filterSubcategories();
});
</script>
