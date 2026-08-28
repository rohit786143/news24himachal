<?php
require_once __DIR__ . '/../seeds.php';

$articles = getSeedArticles();
echo "Total seed articles: " . count($articles) . "\n";

// Print summary of titles and current category_id / subcategory_id
foreach ($articles as $i => $a) {
    if ($i < 20 || $i % 5 === 0) {
        echo "[$i] Cat: {$a['category_id']}, Sub: " . ($a['subcategory_id'] ?? 'null') . " => Title: " . mb_substr($a['title'], 0, 45) . "...\n";
    }
}
