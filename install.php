<?php
/**
 * Database Setup & Seeder Wizard (install.php)
 * News 24 Himachal
 */

$status = [];
$installed = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? '3306';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';
    $dbName = $_POST['db_name'] ?? 'news_db';

    try {
        // Step 1: Connect to MySQL Server (without database)
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        $status[] = ['title' => 'MySQL सर्वर कनेक्शन', 'status' => 'success', 'msg' => "MySQL {$dbHost}:{$dbPort} से सफलतापूर्वक कनेक्ट हुआ।"];

        // Step 2: Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");
        $status[] = ['title' => 'डेटाबेस निर्माण', 'status' => 'success', 'msg' => "डेटाबेस `{$dbName}` तैयार है।"];

        // Step 3: Read schema.sql and execute
        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql फ़ाइल नहीं मिली!");
        }

        $sql = file_get_contents($schemaFile);
        
        // Execute SQL script
        $pdo->exec($sql);
        $status[] = ['title' => 'तालिका एवं डेटा संरचना', 'status' => 'success', 'msg' => 'तालिकाएं (categories, news, pages, contacts, subscribers, manual_notifications) तैयार हो गईं।'];

        // Step 4: Insert Comprehensive News Seeds if seeds.php exists
        if (file_exists(__DIR__ . '/seeds.php')) {
            require_once __DIR__ . '/seeds.php';
            $seedArticles = getSeedArticles();
            $insertNews = $pdo->prepare("
                INSERT INTO `news` (`category_id`, `subcategory_id`, `title`, `slug`, `excerpt`, `content`, `image_url`, `author`, `views`, `is_breaking`, `is_featured`, `is_trending`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND()*72) HOUR)
                ON DUPLICATE KEY UPDATE 
                    `category_id` = VALUES(`category_id`),
                    `subcategory_id` = VALUES(`subcategory_id`),
                    `title` = VALUES(`title`),
                    `excerpt` = VALUES(`excerpt`),
                    `content` = VALUES(`content`),
                    `image_url` = VALUES(`image_url`),
                    `views` = VALUES(`views`),
                    `is_breaking` = VALUES(`is_breaking`),
                    `is_featured` = VALUES(`is_featured`),
                    `is_trending` = VALUES(`is_trending`)
            ");

            foreach ($seedArticles as $art) {
                $insertNews->execute([
                    $art['category_id'],
                    $art['subcategory_id'],
                    $art['title'],
                    $art['slug'],
                    $art['excerpt'],
                    $art['content'],
                    $art['image_url'],
                    $art['author'],
                    $art['views'],
                    $art['is_breaking'],
                    $art['is_featured'],
                    $art['is_trending']
                ]);
            }
        }

        // Step 5: Verify Category Seeds
        $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $newsCount = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
        $status[] = ['title' => 'हिंदी श्रेणियां एवं समाचार', 'status' => 'success', 'msg' => "कुल {$catCount} श्रेणियां/उप-श्रेणियां और {$newsCount} समाचार आर्टिकल्स (प्रत्येक श्रेणी और उप-श्रेणी में 5+ पोस्ट्स) सफलतापूर्वक सम्मिलित किए गए।"];

        $installed = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
        $status[] = ['title' => 'त्रुटि (Error)', 'status' => 'danger', 'msg' => $error];
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard - News 24 Himachal</title>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Hind', sans-serif;
            background-color: #121212;
            color: #E0E0E0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .installer-card {
            background-color: #1F1F1F;
            border-top: 4px solid #E50914;
            border-radius: 8px;
            max-width: 620px;
            width: 100%;
            padding: 35px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: #FFFFFF;
        }
        .logo-title span {
            background: #E50914;
            color: #fff;
            padding: 0 8px;
            border-radius: 4px;
            margin-left: 4px;
        }
        .tagline {
            color: #A0AEC0;
            font-size: 0.95rem;
            margin-top: 6px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: #CBD5E0;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: #121212;
            border: 1px solid #333333;
            border-radius: 6px;
            color: #FFFFFF;
            font-size: 0.95rem;
            outline: none;
        }
        .form-control:focus {
            border-color: #E50914;
        }
        .btn-install {
            width: 100%;
            background: #E50914;
            color: #fff;
            padding: 14px;
            font-size: 1.05rem;
            font-weight: 700;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 10px;
        }
        .btn-install:hover {
            background: #b80710;
            transform: translateY(-2px);
        }
        .btn-launch {
            display: inline-block;
            width: 100%;
            background: #10B981;
            color: #fff;
            text-align: center;
            text-decoration: none;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 6px;
            margin-top: 20px;
        }
        .status-box {
            margin: 20px 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .status-item {
            background: #282828;
            padding: 12px 16px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
        }
        .status-item.success { border-left: 4px solid #10B981; }
        .status-item.danger { border-left: 4px solid #E50914; }
        .status-item.success i { color: #10B981; }
        .status-item.danger i { color: #E50914; }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="header">
        <div class="logo-title">NEWS<span>24</span> HIMACHAL</div>
        <div class="tagline">1-Click Database Setup & Seeder Wizard</div>
    </div>

    <?php if (!empty($status)): ?>
        <div class="status-box">
            <?php foreach ($status as $s): ?>
                <div class="status-item <?= $s['status'] ?>">
                    <i class="fas <?= $s['status'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <div>
                        <strong><?= $s['title'] ?>:</strong> <?= $s['msg'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($installed): ?>
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10B981; border-radius: 6px; padding: 18px; text-align: center; color: #10B981;">
            <i class="fas fa-check-circle" style="font-size: 2.2rem; margin-bottom: 8px;"></i>
            <h3>सेटअप सफलतापूर्वक संपन्न हुआ!</h3>
            <p style="color: #CBD5E0; font-size: 0.9rem; margin-top: 4px;">डेटाबेस, 11 श्रेणियां, 20+ उप-श्रेणियां और समाचार आर्टिकल्स लोड हो चुके हैं।</p>
        </div>
        <a href="index.php" class="btn-launch">
            <i class="fas fa-globe"></i> न्यूज़ पोर्टल पर जाएं (Launch Website) &rarr;
        </a>
    <?php else: ?>
        <form method="POST">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>MySQL Host</label>
                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Port</label>
                    <input type="text" name="db_port" class="form-control" value="3306" required>
                </div>
            </div>

            <div class="form-group">
                <label>डेटाबेस नाम (Database Name)</label>
                <input type="text" name="db_name" class="form-control" value="news_db" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_user" class="form-control" value="root" required>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" class="form-control" placeholder="खाली छोड़ें यदि कोई पासवर्ड नहीं है">
                </div>
            </div>

            <button type="submit" class="btn-install">
                <i class="fas fa-database"></i> 1-Click Database Setup शुरू करें
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
