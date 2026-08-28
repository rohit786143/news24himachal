<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../seeds.php';

$pdo = getDBConnection();
echo "Connected to DB.\n";

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("TRUNCATE TABLE news");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "News table truncated.\n";

$articles = getSeedArticles();
$insertNews = $pdo->prepare("
    INSERT INTO `news` (`category_id`, `subcategory_id`, `title`, `slug`, `excerpt`, `content`, `image_url`, `author`, `views`, `is_breaking`, `is_featured`, `is_trending`, `created_at`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL FLOOR(RAND()*72) HOUR)
");

$inserted = 0;
foreach ($articles as $art) {
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
    $inserted++;
}

echo "Successfully inserted {$inserted} news articles into news_db!\n";
