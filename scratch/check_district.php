<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

echo "=== CATEGORIES ===\n";
$stmt = $pdo->query("SELECT id, parent_id, name, slug, is_nav FROM categories ORDER BY id ASC");
while ($r = $stmt->fetch()) {
    echo "ID: {$r['id']}, Parent: " . ($r['parent_id'] ?? 'NULL') . ", Name: {$r['name']}, Slug: {$r['slug']}, is_nav: {$r['is_nav']}\n";
}

echo "\n=== NEWS BY CATEGORY & SUBCATEGORY ===\n";
$stmt = $pdo->query("
    SELECT c.name as cat_name, c.slug as cat_slug, sub.name as sub_name, sub.slug as sub_slug, COUNT(n.id) as cnt
    FROM news n
    LEFT JOIN categories c ON n.category_id = c.id
    LEFT JOIN categories sub ON n.subcategory_id = sub.id
    GROUP BY n.category_id, n.subcategory_id
");
while ($r = $stmt->fetch()) {
    echo "Cat: [{$r['cat_slug']}] {$r['cat_name']} | Sub: [{$r['sub_slug']}] {$r['sub_name']} => Count: {$r['cnt']}\n";
}

echo "\n=== TEST DISTRICT QUERIES ===\n";
foreach (['shimla', 'mandi', 'kullu', 'kangra'] as $dist) {
    $res = getNewsByCategorySlug($pdo, 'himachal-news', 4, 0, $dist);
    echo "District '$dist' with 'himachal-news': " . count($res) . " articles\n";
    
    // Also test by just subslug or direct search
    $stmt = $pdo->prepare("SELECT n.id, n.title, c.slug as cslug, sub.slug as subslug FROM news n JOIN categories c ON n.category_id = c.id LEFT JOIN categories sub ON n.subcategory_id = sub.id WHERE sub.slug = ?");
    $stmt->execute([$dist]);
    $items = $stmt->fetchAll();
    echo "Direct sub.slug = '$dist': " . count($items) . " articles\n";
}
