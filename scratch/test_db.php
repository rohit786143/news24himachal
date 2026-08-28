<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $pdo = getDBConnection();
    echo "Database connected successfully.\n";
    $c = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $n = $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
    echo "Categories count: $c\n";
    echo "News count: $n\n";

    $cats = getNavigationCategories($pdo);
    echo "Nav categories count: " . count($cats) . "\n";
    foreach ($cats as $cat) {
        echo " - " . $cat['name'] . " (" . $cat['slug'] . ")\n";
    }

    $sabseBadi = getSabseBadiKhabarNews($pdo, 3);
    echo "Sabse Badi Khabar count: " . count($sabseBadi) . "\n";
    foreach ($sabseBadi as $sb) {
        echo " - " . $sb['title'] . " | img: " . $sb['image_url'] . "\n";
    }

    $trending = getTrendingNews($pdo, 4);
    echo "Trending count: " . count($trending) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
