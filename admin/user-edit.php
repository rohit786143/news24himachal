<?php
/**
 * Admin Add / Edit User & Editor
 * Himachal News - Khabar 24
 */

$isEdit = false;
$user = null;
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Fetch existing user if editing
if ($userId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user) {
        $isEdit = true;
    }
}

$adminTitle = $isEdit ? 'Edit Editor' : 'Add New Editor';
$adminHeading = $isEdit ? 'Edit Editor: ' . sanitize($user['name']) : 'Register New Editor / Reporter';

require_once __DIR__ . '/includes/header.php';

// Only Admin can access this page
if (!$isAdmin) {
    $_SESSION['flash_message'] = "Permission Denied: This page is accessible by Admin only.";
    $_SESSION['flash_type'] = "danger";
    header("Location: /admin/index.php");
    exit;
}

$error = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['admin', 'editor']) ? $_POST['role'] : 'editor';
    $designation = trim($_POST['designation'] ?? 'Reporter');
    $location = trim($_POST['location'] ?? 'Himachal Pradesh');
    $avatar = trim($_POST['avatar'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $twitter = trim($_POST['social_twitter'] ?? '');
    $facebook = trim($_POST['social_facebook'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

    // Check if image file was uploaded from device
    if (!empty($_FILES['avatar_file']['tmp_name'])) {
        $uploadedAvatar = handleImageUpload($_FILES['avatar_file'], 'avatars');
        if ($uploadedAvatar) {
            $avatar = $uploadedAvatar;
        }
    }

    if (empty($name) || empty($username) || empty($email)) {
        $error = "Please fill in Name, Username, and Email Address.";
    } elseif (!$isEdit && empty($password)) {
        $error = "Password is required for a new editor.";
    } else {
        // Check uniqueness of username and email
        $checkStmt = $pdo->prepare("SELECT id FROM `users` WHERE (`username` = ? OR `email` = ?) AND `id` != ?");
        $checkStmt->execute([$username, $email, $userId]);
        if ($checkStmt->fetch()) {
            $error = "This username or email address is already in use by another account.";
        } else {
            // Default avatar if empty
            if (empty($avatar)) {
                $avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80';
            }

            try {
                if ($isEdit) {
                    if (!empty($password)) {
                        // Update with new password
                        $passHash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("
                            UPDATE `users` SET
                                `name` = ?, `username` = ?, `email` = ?, `password` = ?,
                                `role` = ?, `designation` = ?, `location` = ?, `avatar` = ?,
                                `bio` = ?, `social_twitter` = ?, `social_facebook` = ?, `status` = ?
                            WHERE `id` = ?
                        ");
                        $stmt->execute([
                            $name, $username, $email, $passHash, $role, $designation,
                            $location, $avatar, $bio, $twitter, $facebook, $status, $userId
                        ]);
                    } else {
                        // Update without changing password
                        $stmt = $pdo->prepare("
                            UPDATE `users` SET
                                `name` = ?, `username` = ?, `email` = ?,
                                `role` = ?, `designation` = ?, `location` = ?, `avatar` = ?,
                                `bio` = ?, `social_twitter` = ?, `social_facebook` = ?, `status` = ?
                            WHERE `id` = ?
                        ");
                        $stmt->execute([
                            $name, $username, $email, $role, $designation,
                            $location, $avatar, $bio, $twitter, $facebook, $status, $userId
                        ]);
                    }

                    $_SESSION['flash_message'] = "Editor '<strong>" . sanitize($name) . "</strong>' updated successfully!";
                    $_SESSION['flash_type'] = "success";
                    header("Location: users.php");
                    exit;
                } else {
                    // Create New Editor
                    $passHash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("
                        INSERT INTO `users` (
                            `name`, `username`, `email`, `password`, `role`,
                            `designation`, `location`, `avatar`, `bio`,
                            `social_twitter`, `social_facebook`, `status`, `created_at`
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $name, $username, $email, $passHash, $role,
                        $designation, $location, $avatar, $bio,
                        $twitter, $facebook, $status
                    ]);

                    $_SESSION['flash_message'] = "New editor '<strong>" . sanitize($name) . "</strong>' registered successfully!";
                    $_SESSION['flash_type'] = "success";
                    header("Location: users.php");
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <div><i class="fas fa-triangle-exclamation"></i> <?= sanitize($error) ?></div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        
        <!-- Left: Account & Profile Details -->
        <div>
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-id-card"></i> Editor & Reporter Details</h2>
                </div>
                <div class="panel-body">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label class="form-label" for="userName">Full Name / Pen Name <span class="required">*</span></label>
                            <input type="text" id="userName" name="name" class="form-control" 
                                   value="<?= sanitize($user['name'] ?? ($_POST['name'] ?? '')) ?>" 
                                   placeholder="e.g. Keylong Reporter or Rohit Kumar" required>
                        </div>

                        <!-- Designation -->
                        <div class="form-group">
                            <label class="form-label" for="userDesignation">Designation / Bureau</label>
                            <input type="text" id="userDesignation" name="designation" class="form-control" 
                                   value="<?= sanitize($user['designation'] ?? ($_POST['designation'] ?? 'Editorial Desk • News 24 Himachal')) ?>" 
                                   placeholder="e.g. Keylong Reporter • News 24 Himachal">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <!-- Username -->
                        <div class="form-group">
                            <label class="form-label" for="userUsername">Login Username <span class="required">*</span></label>
                            <input type="text" id="userUsername" name="username" class="form-control" 
                                   value="<?= sanitize($user['username'] ?? ($_POST['username'] ?? '')) ?>" 
                                   placeholder="e.g. kelang_editor" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label class="form-label" for="userEmail">Email Address <span class="required">*</span></label>
                            <input type="email" id="userEmail" name="email" class="form-control" 
                                   value="<?= sanitize($user['email'] ?? ($_POST['email'] ?? '')) ?>" 
                                   placeholder="reporter@himachalnews24.com" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <!-- Password -->
                        <div class="form-group">
                            <label class="form-label" for="userPass">
                                Password <?= $isEdit ? '<span style="color:var(--text-muted); font-size:0.75rem;">(Leave blank to keep current)</span>' : '<span class="required">*</span>' ?>
                            </label>
                            <input type="password" id="userPass" name="password" class="form-control" 
                                   placeholder="<?= $isEdit ? 'Enter new password or leave blank' : 'Secure password' ?>" 
                                   <?= $isEdit ? '' : 'required' ?>>
                        </div>

                        <!-- Location -->
                        <div class="form-group">
                            <label class="form-label" for="userLocation">District / Location</label>
                            <input type="text" id="userLocation" name="location" class="form-control" 
                                   value="<?= sanitize($user['location'] ?? ($_POST['location'] ?? 'Lahaul-Spiti, Himachal Pradesh')) ?>" 
                                   placeholder="e.g. Lahaul-Spiti / Shimla / Kangra">
                        </div>
                    </div>

                    <!-- Bio -->
                    <div class="form-group">
                        <label class="form-label" for="userBio">Author Bio / About</label>
                        <textarea id="userBio" name="bio" rows="4" class="form-control" placeholder="Brief journalism background and experience..."><?= sanitize($user['bio'] ?? ($_POST['bio'] ?? '')) ?></textarea>
                        <span class="form-hint">This description will be visible to readers on the author public profile page.</span>
                    </div>

                    <!-- Social Links -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-x-twitter"></i> X / Twitter Profile URL</label>
                            <input type="url" name="social_twitter" class="form-control" 
                                   value="<?= sanitize($user['social_twitter'] ?? ($_POST['social_twitter'] ?? '')) ?>" 
                                   placeholder="https://twitter.com/username">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fab fa-facebook"></i> Facebook Profile URL</label>
                            <input type="url" name="social_facebook" class="form-control" 
                                   value="<?= sanitize($user['social_facebook'] ?? ($_POST['social_facebook'] ?? '')) ?>" 
                                   placeholder="https://facebook.com/username">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right: Avatar Preview, Role & Status -->
        <div>
            <!-- Save Button Card -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-floppy-disk"></i> Actions</h2>
                </div>
                <div class="panel-body">
                    <button type="submit" class="topbar-btn" style="width: 100%; justify-content: center; padding: 12px; font-size: 1rem;">
                        <i class="fas fa-check-circle"></i> <?= $isEdit ? 'Save Changes' : 'Create Editor' ?>
                    </button>
                    <div style="margin-top: 14px; text-align: center;">
                        <a href="/admin/users.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Back to Editors List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Role & Status Box -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-shield-halved"></i> Role & Status</h2>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="form-label" for="userRole">Account Role</label>
                        <select name="role" id="userRole" class="form-control">
                            <option value="editor" <?= (($user['role'] ?? ($_POST['role'] ?? 'editor')) === 'editor') ? 'selected' : '' ?>>
                                ✍️ Editor / Reporter
                            </option>
                            <option value="admin" <?= (($user['role'] ?? ($_POST['role'] ?? 'editor')) === 'admin') ? 'selected' : '' ?>>
                                👑 Chief Admin
                            </option>
                        </select>
                        <span class="form-hint" style="margin-top: 4px;">
                            <strong>Editor:</strong> Can add/edit their own articles.<br>
                            <strong>Admin:</strong> Has full control over settings, categories, and users.
                        </span>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="userStatus">Account Status</label>
                        <select name="status" id="userStatus" class="form-control">
                            <option value="active" <?= (($user['status'] ?? ($_POST['status'] ?? 'active')) === 'active') ? 'selected' : '' ?>>
                                🟢 Active
                            </option>
                            <option value="inactive" <?= (($user['status'] ?? ($_POST['status'] ?? 'active')) === 'inactive') ? 'selected' : '' ?>>
                                🔴 Inactive (Login disabled)
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Avatar Upload & Live Preview Box -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title"><i class="fas fa-image"></i> Profile Avatar</h2>
                </div>
                <div class="panel-body text-center">
                    <?php 
                    $currentAvatar = $user['avatar'] ?? ($_POST['avatar'] ?? 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80');
                    ?>
                    <div style="margin-bottom: 14px; display: inline-block;">
                        <img src="<?= sanitize($currentAvatar) ?>" id="avatarImgPreview" 
                             alt="Avatar Preview" 
                             style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); box-shadow: 0 6px 16px rgba(0,0,0,0.15); background: #F1F5F9;">
                    </div>

                    <!-- Direct Device File Upload Input -->
                    <div class="form-group" style="text-align: left; margin-bottom: 16px;">
                        <label class="form-label" for="avatarFileInput" style="color: var(--text-heading); font-weight: 800;">
                            <i class="fas fa-cloud-arrow-up" style="color: var(--primary);"></i> Upload Photo from Device
                        </label>
                        <input type="file" id="avatarFileInput" name="avatar_file" accept="image/*" class="form-control" 
                               style="padding: 8px 12px; cursor: pointer; border: 1.5px dashed var(--primary); background: #FEF2F2;"
                               onchange="previewEditorLocalImage(this, 'avatarImgPreview')">
                        <span class="form-hint" style="color: var(--text-muted);">Select JPG or PNG image file from device.</span>
                    </div>

                    <!-- Optional URL Alternative -->
                    <div class="form-group" style="text-align: left; margin-bottom: 0; border-top: 1px dashed var(--border-color); padding-top: 12px;">
                        <label class="form-label" for="avatarInput">Or Image URL</label>
                        <input type="url" id="avatarInput" name="avatar" class="form-control" 
                               value="<?= sanitize($currentAvatar) ?>" 
                               placeholder="https://..." oninput="document.getElementById('avatarImgPreview').src=this.value">
                    </div>
                </div>
            </div>

        </div>

    </div>
</form>

<script>
function previewEditorLocalImage(input, previewId) {
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
