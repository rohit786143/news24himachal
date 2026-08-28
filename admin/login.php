<?php
/**
 * Admin & Editor Login Page
 * Himachal News - Khabar 24
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// If already logged in, redirect to admin dashboard
if (!empty($_SESSION['admin_user'])) {
    header("Location: index.php");
    exit;
}

$siteName = getSetting($pdo, 'site_name', 'News 24 Himachal');
$error = '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($loginInput) || empty($password)) {
        $error = 'कृपया यूज़रनेम / ईमेल और पासवर्ड दर्ज करें।';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM `users` 
                WHERE (`username` = ? OR `email` = ?) AND `status` = 'active' 
                LIMIT 1
            ");
            $stmt->execute([$loginInput, $loginInput]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful Login
                $displayName = !empty($user['full_name']) ? $user['full_name'] : (!empty($user['name']) ? $user['name'] : $user['username']);
                
                $_SESSION['admin_user'] = [
                    'id' => (int)$user['id'],
                    'name' => $displayName,
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'avatar' => $user['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
                    'designation' => $user['designation'] ?? 'संपादक'
                ];

                $_SESSION['flash_message'] = "स्वागत है, <strong>" . sanitize($displayName) . "</strong>! आप सफलतापूर्वक लॉगिन हो चुके हैं।";
                $_SESSION['flash_type'] = "success";

                header("Location: index.php");
                exit;
            } else {
                $error = 'गलत यूज़रनेम या पासवर्ड! कृपया दोबारा जांचें।';
            }
        } catch (PDOException $e) {
            $error = 'डेटाबेस त्रुटि: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <base href="/admin/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>लॉगिन | एडमिन व रिपोर्टर पोर्टल - <?= sanitize($siteName) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #F8FAFC;
            --primary: #E31B23;
            --primary-hover: #C41219;
            --primary-blue: #2F3E9E;
            --text-heading: #0F172A;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --radius-md: 12px;
            --shadow-lg: 0 14px 34px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', 'Hind', -apple-system, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-image: radial-gradient(#E2E8F0 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .login-card {
            background: #FFFFFF;
            width: 100%;
            max-width: 440px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .login-header {
            background: #101935;
            padding: 28px 24px;
            text-align: center;
            border-bottom: 3px solid var(--primary);
        }

        .login-brand-logo {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: #FFFFFF;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 12px;
            box-shadow: 0 4px 14px rgba(229, 9, 20, 0.5);
        }

        .login-header h1 {
            color: #FFFFFF;
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .login-header p {
            color: #94A3B8;
            font-size: 0.82rem;
            margin-top: 4px;
        }

        .login-body {
            padding: 28px 26px;
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 6px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px 11px 40px;
            background: #FFFFFF;
            border: 1.5px solid #CBD5E1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-heading);
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.12);
        }

        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: #FFFFFF;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(229, 9, 20, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(229, 9, 20, 0.4);
        }

        .login-footer {
            padding: 14px 26px 20px;
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border-color);
            background: #F8FAFC;
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-brand-logo">
            <i class="fas fa-newspaper"></i>
        </div>
        <h1><?= sanitize($siteName) ?></h1>
        <p>एडमिन एवं संवाददाता कंट्रोल पैनल (Editor Portal)</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <div><?= sanitize($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="usernameInput">यूज़रनेम अथवा ईमेल आईडी</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="usernameInput" name="username" class="form-control" 
                           placeholder="उदा: admin" required autofocus 
                           value="<?= isset($_POST['username']) ? sanitize($_POST['username']) : '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="passwordInput">पासवर्ड</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="passwordInput" name="password" class="form-control" 
                           placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-right-to-bracket"></i> लॉगिन करें (Login)
            </button>
        </form>
    </div>

    <div class="login-footer">
        <a href="/index.php" target="_blank">
            <i class="fas fa-arrow-left"></i> मुख्य न्यूज़ वेबसाइट पर जाएं
        </a>
    </div>
</div>

</body>
</html>