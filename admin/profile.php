<?php
/**
 * Admin / Editor Self-Service Profile Page
 * Himachal News - Khabar 24
 */

$adminTitle = 'मेरी प्रोफाइल (My Profile)';
$adminHeading = 'मेरी प्रोफाइल एवं खाता सेटिंग्स (My Account Profile)';

require_once __DIR__ . '/includes/header.php';

$error = null;

// Fetch fresh details of logged-in user
$userStmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
$userStmt->execute([$currentUserId]);
$user = $userStmt->fetch();

if (!$user) {
    echo '<div class="alert alert-danger">उपयोगकर्ता खाता नहीं मिला।</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $designation = trim($_POST['designation'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $avatar = trim($_POST['avatar'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $twitter = trim($_POST['social_twitter'] ?? '');
    $facebook = trim($_POST['social_facebook'] ?? '');
    
    // Check if user uploaded a photo file from device
    if (!empty($_FILES['avatar_file']['tmp_name'])) {
        $uploadedAvatar = handleImageUpload($_FILES['avatar_file'], 'avatars');
        if ($uploadedAvatar) {
            $avatar = $uploadedAvatar;
        }
    }

    $currentPass = trim($_POST['current_password'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');

    if (empty($name) || empty($username) || empty($email)) {
        $error = "कृपया नाम, यूज़रनेम और ईमेल आईडी दर्ज करें।";
    } else {
        // Check username and email uniqueness
        $dupCheck = $pdo->prepare("SELECT id FROM `users` WHERE (`email` = ? OR `username` = ?) AND `id` != ?");
        $dupCheck->execute([$email, $username, $currentUserId]);
        if ($dupCheck->fetch()) {
            $error = "यह यूज़रनेम या ईमेल आईडी पहले से किसी अन्य खाते से जुड़ी है।";
        } else {
            try {
                // If user wants to change password
                if (!empty($newPass)) {
                    if (empty($currentPass) || !password_verify($currentPass, $user['password'])) {
                        $error = "वर्तमान पासवर्ड (Current Password) गलत है। पासवर्ड नहीं बदला गया।";
                    } else {
                        $passHash = password_hash($newPass, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("
                            UPDATE `users` SET
                                `name` = ?, `username` = ?, `email` = ?, `designation` = ?, `location` = ?,
                                `avatar` = ?, `bio` = ?, `social_twitter` = ?, `social_facebook` = ?,
                                `password` = ?
                            WHERE `id` = ?
                        ");
                        $stmt->execute([
                            $name, $username, $email, $designation, $location,
                            $avatar, $bio, $twitter, $facebook,
                            $passHash, $currentUserId
                        ]);

                        // Update session
                        $_SESSION['admin_user']['name'] = $name;
                        $_SESSION['admin_user']['username'] = $username;
                        $_SESSION['admin_user']['email'] = $email;
                        $_SESSION['admin_user']['avatar'] = $avatar;
                        $_SESSION['admin_user']['designation'] = $designation;

                        $_SESSION['flash_message'] = "आपकी प्रोफाइल, यूज़रनेम और पासवर्ड सफलतापूर्वक अपडेट कर दिए गए हैं!";
                        $_SESSION['flash_type'] = "success";
                        header("Location: profile.php");
                        exit;
                    }
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("
                        UPDATE `users` SET
                            `name` = ?, `username` = ?, `email` = ?, `designation` = ?, `location` = ?,
                            `avatar` = ?, `bio` = ?, `social_twitter` = ?, `social_facebook` = ?
                        WHERE `id` = ?
                    ");
                    $stmt->execute([
                        $name, $username, $email, $designation, $location,
                        $avatar, $bio, $twitter, $facebook,
                        $currentUserId
                    ]);

                    // Update session
                    $_SESSION['admin_user']['name'] = $name;
                    $_SESSION['admin_user']['username'] = $username;
                    $_SESSION['admin_user']['email'] = $email;
                    $_SESSION['admin_user']['avatar'] = $avatar;
                    $_SESSION['admin_user']['designation'] = $designation;

                    $_SESSION['flash_message'] = "आपकी प्रोफाइल एवं यूज़रनेम सफलतापूर्वक अपडेट कर दिया गया है!";
                    $_SESSION['flash_type'] = "success";
                    header("Location: profile.php");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "डेटाबेस त्रुटि: " . $e->getMessage();
            }
        }
    }
}
?>

<?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <i class="fas fa-triangle-exclamation"></i> <?= sanitize($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="profile.php" enctype="multipart/form-data">
    <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 24px;">
        
        <!-- Left: Profile Info -->
        <div>
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-user-pen"></i> व्यक्तिगत एवं पत्रकारिता प्रोफ़ाइल</h2>
                </div>
                <div class="panel-body">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="profileName">पूरा नाम (Author Name) <span class="required">*</span></label>
                            <input type="text" id="profileName" name="name" class="form-control" 
                                   value="<?= sanitize($user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="profileDesignation">पदनाम (Designation / Bureau)</label>
                            <input type="text" id="profileDesignation" name="designation" class="form-control" 
                                   value="<?= sanitize($user['designation']) ?>" 
                                   placeholder="उदा: मुख्य संपादक • News 24 Himachal">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="profileUsername">लॉगिन यूज़रनेम (Username) <span class="required">*</span></label>
                            <input type="text" id="profileUsername" name="username" class="form-control" 
                                   value="<?= sanitize($user['username']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="profileEmail">ईमेल आईडी (Email) <span class="required">*</span></label>
                            <input type="email" id="profileEmail" name="email" class="form-control" 
                                   value="<?= sanitize($user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profileLocation">स्थान / ज़िला (Location)</label>
                        <input type="text" id="profileLocation" name="location" class="form-control" 
                               value="<?= sanitize($user['location']) ?>" 
                               placeholder="उदा: लाहौल-स्पीति, हिमाचल प्रदेश">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profileBio">संपादक परिचय (Biography)</label>
                        <textarea id="profileBio" name="bio" rows="4" class="form-control" placeholder="अपने बारे में संक्षिप्त परिचय..."><?= sanitize($user['bio']) ?></textarea>
                        <span class="form-hint">यह विवरण आपकी प्रकाशित खबरों में लेखक बॉक्स एवं आपकी प्रोफाइल पर पाठकों को दिखेगा।</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-x-twitter"></i> X / Twitter URL</label>
                            <input type="url" name="social_twitter" class="form-control" 
                                   value="<?= sanitize($user['social_twitter'] ?? '') ?>" 
                                   placeholder="https://twitter.com/username">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-facebook"></i> Facebook URL</label>
                            <input type="url" name="social_facebook" class="form-control" 
                                   value="<?= sanitize($user['social_facebook'] ?? '') ?>" 
                                   placeholder="https://facebook.com/username">
                        </div>
                    </div>

                    <!-- Password Change Section -->
                    <div style="border-top: 1.5px dashed var(--border-color); padding-top: 20px; margin-top: 10px;">
                        <h3 style="font-size: 1rem; color: var(--text-heading); margin-bottom: 12px; font-weight: 700;">
                            <i class="fas fa-key" style="color: var(--primary);"></i> पासवर्ड बदलें (Change Password)
                        </h3>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label" for="currPass">वर्तमान पासवर्ड (Current Password)</label>
                                <input type="password" id="currPass" name="current_password" class="form-control" placeholder="••••••••">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="newPass">नया पासवर्ड (New Password)</label>
                                <input type="password" id="newPass" name="new_password" class="form-control" placeholder="नया पासवर्ड">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: Avatar Upload, Preview & Submit -->
        <div>
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-floppy-disk"></i> सुरक्षित करें</h2>
                </div>
                <div class="panel-body">
                    <button type="submit" class="topbar-btn" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
                        <i class="fas fa-check-circle"></i> प्रोफाइल अपडेट करें
                    </button>
                    <div style="margin-top: 14px; text-align: center;">
                        <a href="/author.php?id=<?= $user['id'] ?>" target="_blank" style="color: #0284C7; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-eye"></i> मेरी लाइव पब्लिक प्रोफाइल देखें
                        </a>
                    </div>
                </div>
            </div>

            <!-- Avatar Upload & Live Preview Card -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-camera"></i> प्रोफ़ाइल फ़ोटो (Photo)</h2>
                </div>
                <div class="panel-body text-center">
                    <div style="margin-bottom: 16px; position: relative; display: inline-block;">
                        <img src="<?= sanitize($user['avatar'] ?: 'https://via.placeholder.com/120') ?>" id="profileAvatarPreview" 
                             alt="Avatar" 
                             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); box-shadow: 0 6px 18px rgba(0,0,0,0.15); background: #F1F5F9;">
                    </div>

                    <!-- Direct Device File Upload Input -->
                    <div class="form-group" style="text-align: left; margin-bottom: 16px;">
                        <label class="form-label" for="avatarFileInput" style="color: var(--text-heading); font-weight: 800;">
                            <i class="fas fa-cloud-arrow-up" style="color: var(--primary);"></i> डिवाइस से फ़ोटो अपलोड करें
                        </label>
                        <input type="file" id="avatarFileInput" name="avatar_file" accept="image/*" class="form-control" 
                               style="padding: 8px 12px; cursor: pointer; border: 1.5px dashed var(--primary); background: #FEF2F2;"
                               onchange="previewLocalImage(this, 'profileAvatarPreview')">
                        <span class="form-hint" style="color: var(--text-muted);">कंप्यूटर / मोबाइल से JPG, PNG, WEBP फ़ोटो चुनें।</span>
                    </div>

                    <!-- Optional URL Alternative -->
                    <div class="form-group" style="text-align: left; margin-bottom: 0; border-top: 1px dashed var(--border-color); padding-top: 12px;">
                        <label class="form-label" for="profileAvatar">अथवा फ़ोटो URL (Image Link)</label>
                        <input type="url" id="profileAvatar" name="avatar" class="form-control" 
                               value="<?= sanitize($user['avatar']) ?>" 
                               placeholder="https://..." oninput="document.getElementById('profileAvatarPreview').src=this.value">
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
function previewLocalImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
