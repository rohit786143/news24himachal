<?php
$seedsFile = __DIR__ . '/../seeds.php';
$content = file_get_contents($seedsFile);

// Let's parse and correctly map category_id and subcategory_id in seeds.php
require_once __DIR__ . '/../seeds.php';
$articles = getSeedArticles();

// Let's re-map category_id and subcategory_id based on index
for ($i = 0; $i < count($articles); $i++) {
    if ($i >= 0 && $i <= 4) { // Breaking News
        $articles[$i]['category_id'] = 1;
        $articles[$i]['subcategory_id'] = null;
    } elseif ($i >= 5 && $i <= 9) { // Duniya
        $articles[$i]['category_id'] = 9;
        $articles[$i]['subcategory_id'] = null;
    } elseif ($i >= 10 && $i <= 14) { // Desh
        $articles[$i]['category_id'] = 8;
        $articles[$i]['subcategory_id'] = null;
    } elseif ($i >= 15 && $i <= 19) { // Shimla
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 11;
    } elseif ($i >= 20 && $i <= 24) { // Kangra
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 12;
    } elseif ($i >= 25 && $i <= 29) { // Mandi
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 13;
    } elseif ($i >= 30 && $i <= 34) { // Hamirpur
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 14;
    } elseif ($i >= 35 && $i <= 39) { // Solan
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 15;
    } elseif ($i >= 40 && $i <= 44) { // Sirmaur
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 16;
    } elseif ($i >= 45 && $i <= 49) { // Chamba
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 17;
    } elseif ($i >= 50 && $i <= 54) { // Kullu
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 25;
    } elseif ($i >= 55 && $i <= 56) { // Bilaspur
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 36;
    } elseif ($i >= 57 && $i <= 59) { // Una
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 37;
    } elseif ($i >= 60 && $i <= 62) { // Kinnaur
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 38;
    } elseif ($i >= 63 && $i <= 64) { // Lahaul-Spiti
        $articles[$i]['category_id'] = 10;
        $articles[$i]['subcategory_id'] = 39;
    } elseif ($i >= 65 && $i <= 69) { // Rajniti
        $articles[$i]['category_id'] = 2;
        $articles[$i]['subcategory_id'] = null;
    } elseif ($i >= 70 && $i <= 74) { // Rashifal / Special
        $articles[$i]['category_id'] = 6;
        $articles[$i]['subcategory_id'] = null;
    } elseif ($i >= 75 && $i <= 79) { // Crime
        $articles[$i]['category_id'] = 7;
        $articles[$i]['subcategory_id'] = null;
    } elseif ($i >= 80 && $i <= 84) { // Khel
        $articles[$i]['category_id'] = 5;
        $articles[$i]['subcategory_id'] = null;
    } elseif ($i >= 85 && $i <= 89) { // Tourism
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 18;
    } elseif ($i >= 90 && $i <= 94) { // Art Culture
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 19;
    } elseif ($i >= 95 && $i <= 99) { // Fairs Festivals
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 20;
    } elseif ($i >= 100 && $i <= 104) { // Dev Lok
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 21;
    } elseif ($i >= 105 && $i <= 109) { // Temples
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 22;
    } elseif ($i >= 110 && $i <= 114) { // Deities
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 23;
    } elseif ($i >= 115 && $i <= 119) { // Traditions
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 24;
    } elseif ($i >= 120 && $i <= 124) { // Entertainment News
        $articles[$i]['category_id'] = 4;
        $articles[$i]['subcategory_id'] = 26;
    } elseif ($i >= 125 && $i <= 129) { // Our Artists
        $articles[$i]['category_id'] = 4;
        $articles[$i]['subcategory_id'] = 27;
    } elseif ($i >= 130) { // Personalities / Icons (Himachal Darshan -> Art Culture)
        $articles[$i]['category_id'] = 3;
        $articles[$i]['subcategory_id'] = 19;
    }
}

// Write generated seeds.php back
$out = "<?php\n/**\n * Seeder data generator for Himachal News\n * Generates 160+ authentic Hindi news articles for every category and subcategory\n */\n\nfunction getSeedArticles() {\n    return " . var_export($articles, true) . ";\n}\n";
file_put_contents($seedsFile, $out);
echo "seeds.php updated successfully!\n";
