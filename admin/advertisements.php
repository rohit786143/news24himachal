<?php
/**
 * Admin Advertisement Banner Management
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
$isAdmin = ($currentUser['role'] === 'admin');

if (!$isAdmin) {
    $_SESSION['flash_message'] = "Permission Denied: Ad management is accessible by Admin only.";
    $_SESSION['flash_type'] = "danger";
    header("Location: /admin/index.php");
    exit;
}

// Handle Save Advertisement Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ad'])) {
    $status = in_array($_POST['ad_banner_status'] ?? '', ['active', 'inactive']) ? $_POST['ad_banner_status'] : 'active';
    $title = trim($_POST['ad_banner_title'] ?? 'This Space is Available for Advertisement');
    $link = trim($_POST['ad_banner_link'] ?? 'contact.php');
    $image = trim($_POST['ad_banner_image'] ?? '/assets/images/ad_banner.jpg');

    // Handle Direct File Upload from Device
    if (!empty($_FILES['ad_banner_file']['tmp_name'])) {
        $uploaded = handleImageUpload($_FILES['ad_banner_file'], 'ads');
        if ($uploaded) {
            $image = $uploaded;
        }
    }

    // Restore Default Dummy Poster
    if (isset($_POST['restore_default'])) {
        $image = '/assets/images/ad_banner.jpg';
        $title = 'This Space is Available for Advertisement';
        $link = 'contact.php';
        $status = 'active';
    }

    try {
        setSetting($pdo, 'ad_banner_status', $status);
        setSetting($pdo, 'ad_banner_title', $title);
        setSetting($pdo, 'ad_banner_link', $link);
        setSetting($pdo, 'ad_banner_image', $image);

        $_SESSION['flash_message'] = "Ad banner settings updated successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: /admin/advertisements.php");
        exit;
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$adStatus = getSetting($pdo, 'ad_banner_status', 'active');
$adImage = getSetting($pdo, 'ad_banner_image', '/assets/images/ad_banner.jpg');
$adLink = getSetting($pdo, 'ad_banner_link', 'contact.php');
$adTitle = getSetting($pdo, 'ad_banner_title', 'This Space is Available for Advertisement');

$adminTitle = 'Ads Management';
$adminHeading = 'Sidebar Ad Banner Management';

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 24px;">
    
    <!-- Left Column: Ad Configuration Form -->
    <div>
        <form method="POST" action="/admin/advertisements.php" enctype="multipart/form-data">
            <input type="hidden" name="save_ad" value="1">

            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-rectangle-ad" style="color: var(--primary);"></i> Ad Banner Details & Upload</h2>
                </div>
                <div class="panel-body">
                    
                    <!-- Ad Status Toggle -->
                    <div class="form-group">
                        <label class="form-label" for="adStatusSelect">Display Status</label>
                        <select name="ad_banner_status" id="adStatusSelect" class="form-control">
                            <option value="active" <?= $adStatus === 'active' ? 'selected' : '' ?>>🟢 Active (Show on Website)</option>
                            <option value="inactive" <?= $adStatus === 'inactive' ? 'selected' : '' ?>>🔴 Inactive (Hide)</option>
                        </select>
                        <span class="form-hint">When active, this advertisement banner will be shown in the sidebar above newsletter.</span>
                    </div>

                    <!-- Direct Device File Upload Input -->
                    <div class="form-group" style="background: #FEF2F2; border: 1.5px dashed var(--primary); padding: 16px; border-radius: var(--radius-sm); margin-bottom: 18px;">
                        <label class="form-label" for="adBannerFile" style="font-weight: 800; color: var(--text-heading); font-size: 0.95rem;">
                            <i class="fas fa-cloud-arrow-up" style="color: var(--primary);"></i> Upload Ad Banner from Device
                        </label>
                        <input type="file" id="adBannerFile" name="ad_banner_file" accept="image/*" class="form-control" 
                               style="padding: 8px 12px; cursor: pointer; background: #FFFFFF;"
                               onchange="previewAdLocalImage(this)">
                        <span class="form-hint" style="color: var(--text-muted);">Select JPG, PNG, or WEBP image from device (Recommended size: 1:1 ratio or 600x600 px).</span>
                    </div>

                    <!-- Alternative Image URL Link -->
                    <div class="form-group">
                        <label class="form-label" for="adImageInput">Or Image URL</label>
                        <input type="url" id="adImageInput" name="ad_banner_image" class="form-control" 
                               value="<?= sanitize($adImage) ?>" 
                               placeholder="https://..." oninput="document.getElementById('adLivePreviewImg').src=this.value">
                    </div>

                    <!-- Ad Target Redirect Link -->
                    <div class="form-group">
                        <label class="form-label" for="adLinkInput">Target Destination URL <span class="required">*</span></label>
                        <input type="text" id="adLinkInput" name="ad_banner_link" class="form-control" 
                               value="<?= sanitize($adLink) ?>" 
                               placeholder="e.g. contact.php or https://advertiser-website.com" required>
                        <span class="form-hint">Clicking the ad will open this link in a new tab.</span>
                    </div>

                    <!-- Ad Title / Tagline -->
                    <div class="form-group">
                        <label class="form-label" for="adTitleInput">Ad Title / Alt Text</label>
                        <input type="text" id="adTitleInput" name="ad_banner_title" class="form-control" 
                               value="<?= sanitize($adTitle) ?>" 
                               placeholder="e.g. This Space is Available for Advertisement">
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap;">
                        <button type="submit" class="topbar-btn" style="padding: 12px 24px; font-size: 1rem;">
                            <i class="fas fa-check-circle"></i> Save Ad Settings
                        </button>

                        <button type="submit" name="restore_default" value="1" class="topbar-btn topbar-btn-secondary" style="padding: 12px 18px;" onclick="return confirm('Do you want to restore default ad banner?');">
                            <i class="fas fa-rotate-left"></i> Restore Default Banner
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- Right Column: Live Visual Preview in Sidebar Scale -->
    <div>
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fas fa-eye"></i> Live Sidebar Preview</h2>
            </div>
            <div class="panel-body" style="background: #F1F5F9; padding: 20px;">
                <p style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 12px; text-align: center;">
                    The ad banner will appear in the website sidebar at this size:
                </p>

                <!-- Mock Sidebar Ad Card -->
                <div style="background: #FFFFFF; border: 1.5px solid #CBD5E1; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); max-width: 320px; margin: 0 auto;">
                    <div style="padding: 7px 12px; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: var(--text-dim); letter-spacing: 0.5px; display: flex; align-items: center; gap: 4px;">
                            <i class="fas fa-rectangle-ad" style="color: var(--primary);"></i> Sponsored Ad
                        </span>
                        <span style="font-size: 0.68rem; font-weight: 700; color: #0284C7;">Advertise &rarr;</span>
                    </div>
                    <div style="padding: 8px; text-align: center; background: #0F172A;">
                        <img src="<?= sanitize($adImage) ?>" id="adLivePreviewImg" alt="Ad Preview" 
                             style="width: 100%; height: auto; aspect-ratio: 1/1; object-fit: cover; display: block; border-radius: 6px;">
                    </div>
                    <div style="padding: 8px 12px; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">
                            <i class="fas fa-bullhorn" style="color: var(--primary);"></i> Ad Contact
                        </span>
                        <span class="badge badge-red" style="font-size: 0.65rem; padding: 2px 6px;">Book Now &rarr;</span>
                    </div>
                </div>

                <div style="margin-top: 16px; text-align: center;">
                    <a href="/" target="_blank" style="color: #0284C7; font-size: 0.85rem; font-weight: 700; text-decoration: none;">
                        <i class="fas fa-arrow-up-right-from-square"></i> View Live on Main Website
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function previewAdLocalImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('adLivePreviewImg').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
