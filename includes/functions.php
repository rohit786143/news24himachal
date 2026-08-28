<?php
/**
 * Core Helper Functions
 * News 24 Himachal
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Sanitize string output / input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Get Navigation Categories (Parents with Child Subcategories)
 * Queries 'SELECT * FROM categories' without any status constraints
 */
function getNavigationCategories($pdo) {
    if (!$pdo) {
        return [];
    }

    try {
        // Direct query without WHERE status (compatible with tables having only id, name, slug)
        $stmt = $pdo->query("SELECT * FROM `categories`");
        $allCategories = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        if (empty($allCategories)) {
            return [];
        }

        $sample = $allCategories[0];
        $hasParentId = array_key_exists('parent_id', $sample);
        $hasIsNav = array_key_exists('is_nav', $sample);
        $hasDisplayOrder = array_key_exists('display_order', $sample);

        $parents = [];
        $childrenByParent = [];

        if ($hasParentId) {
            foreach ($allCategories as $cat) {
                if (empty($cat['parent_id'])) {
                    if ($hasIsNav) {
                        if ((int)$cat['is_nav'] === 1) {
                            $parents[] = $cat;
                        }
                    } else {
                        $parents[] = $cat;
                    }
                } else {
                    $childrenByParent[$cat['parent_id']][] = $cat;
                }
            }

            // Fallback: If no categories were flagged as is_nav=1, show all root parents
            if (empty($parents)) {
                foreach ($allCategories as $cat) {
                    if (empty($cat['parent_id'])) {
                        $parents[] = $cat;
                    }
                }
            }
        } else {
            $parents = $allCategories;
        }

        // Sort by display_order if column exists
        if ($hasDisplayOrder) {
            usort($parents, function($a, $b) {
                return ((int)($a['display_order'] ?? 0)) <=> ((int)($b['display_order'] ?? 0));
            });
        }

        // Attach subcategories
        foreach ($parents as &$p) {
            $p['subcategories'] = $childrenByParent[$p['id']] ?? [];
        }
        unset($p);

        return $parents;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Auto-ensure `is_nav` column exists and the 9 main navbar categories are active
 */
function ensureNavCategoriesConfigured($pdo) {
    try {
        // 1. Check if column `is_nav` exists in `categories` table
        $columns = $pdo->query("SHOW COLUMNS FROM `categories` LIKE 'is_nav'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("ALTER TABLE `categories` ADD COLUMN `is_nav` TINYINT(1) DEFAULT 0 AFTER `display_order`");
        }

        // 2. Main 9 categories definition
        $navMap = [
            'breaking-news'    => ['name' => 'ब्रेकिंग न्यूज़', 'order' => 1],
            'rajniti'          => ['name' => 'राजनीति', 'order' => 2],
            'himachal-darshan' => ['name' => 'हिमाचल दर्शन', 'order' => 3],
            'manoranjan'       => ['name' => 'मनोरंजन', 'order' => 4],
            'khel'             => ['name' => 'खेल', 'order' => 5],
            'rashiphal'        => ['name' => 'राशिफल', 'order' => 6],
            'crime'            => ['name' => 'क्राइम', 'order' => 7],
            'desh'             => ['name' => 'देश', 'order' => 8],
            'duniya'           => ['name' => 'दुनिया', 'order' => 9]
        ];

        // Also fix legacy slugs if present in DB
        $pdo->exec("UPDATE `categories` SET `slug` = 'rajniti', `name` = 'राजनीति' WHERE `slug` IN ('politics', 'siyasat')");
        $pdo->exec("UPDATE `categories` SET `slug` = 'crime', `name` = 'क्राइम' WHERE `slug` IN ('jurm', 'personalities', 'hasti')");
        $pdo->exec("UPDATE `categories` SET `slug` = 'khel', `name` = 'खेल' WHERE `slug` = 'sports'");
        $pdo->exec("UPDATE `categories` SET `slug` = 'manoranjan', `name` = 'मनोरंजन' WHERE `slug` = 'entertainment'");
        $pdo->exec("UPDATE `categories` SET `slug` = 'desh', `name` = 'देश' WHERE `slug` = 'india'");
        $pdo->exec("UPDATE `categories` SET `slug` = 'duniya', `name` = 'दुनिया' WHERE `slug` = 'world'");

        // Reset is_nav = 0 for other parent categories
        $pdo->exec("UPDATE `categories` SET `is_nav` = 0 WHERE `parent_id` IS NULL");

        // Insert or update the 9 main categories
        foreach ($navMap as $slug => $data) {
            $stmt = $pdo->prepare("SELECT id FROM `categories` WHERE `slug` = ? LIMIT 1");
            $stmt->execute([$slug]);
            $existing = $stmt->fetch();

            if ($existing) {
                $upd = $pdo->prepare("UPDATE `categories` SET `name` = ?, `display_order` = ?, `is_nav` = 1, `parent_id` = NULL WHERE `id` = ?");
                $upd->execute([$data['name'], $data['order'], $existing['id']]);
            } else {
                $ins = $pdo->prepare("INSERT INTO `categories` (`parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (NULL, ?, ?, ?, 1)");
                $ins->execute([$data['name'], $slug, $data['order']]);
            }
        }
    } catch (Exception $e) {
        // ignore
    }
}

/**
 * Get Category by Slug or ID
 */
function getCategoryBySlug($pdo, $slug) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, p.name AS parent_name, p.slug AS parent_slug 
            FROM categories c
            LEFT JOIN categories p ON c.parent_id = p.id
            WHERE c.slug = ? LIMIT 1
        ");
        $stmt->execute([$slug]);
        $category = $stmt->fetch();

        if ($category) {
            // Fetch children subcategories if this is a parent
            $subStmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY display_order ASC");
            $subStmt->execute([$category['id']]);
            $category['subcategories'] = $subStmt->fetchAll();
        }

        return $category;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get Breaking News Ticker Items
 */
function getBreakingNews($pdo, $limit = 6) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.id, n.title, n.slug, n.created_at, c.name AS category_name, c.slug AS category_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            WHERE n.is_breaking = 1
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        // Fallback to recent news if no breaking flags
        if (empty($items)) {
            $fallback = $pdo->prepare("
                SELECT n.id, n.title, n.slug, n.created_at, c.name AS category_name, c.slug AS category_slug
                FROM news n
                JOIN categories c ON n.category_id = c.id
                ORDER BY n.created_at DESC
                LIMIT ?
            ");
            $fallback->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $fallback->execute();
            return $fallback->fetchAll();
        }

        return $items;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get Featured Hero Big Article ('सबसे बड़ी खबर') - Single Item
 */
function getFeaturedHeroNews($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug, 
                   sub.name AS subcategory_name, sub.slug AS subcategory_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
            WHERE sub.slug = 'sabse-badi-khabar' OR c.slug = 'sabse-badi-khabar' OR n.is_featured = 1
            ORDER BY (sub.slug = 'sabse-badi-khabar') DESC, (n.is_featured = 1) DESC, n.created_at DESC
            LIMIT 1
        ");
        $stmt->execute();
        $article = $stmt->fetch();

        // Fallback to latest article if no featured / सबसे बड़ी खबर item
        if (!$article) {
            $fallback = $pdo->query("
                SELECT n.*, c.name AS category_name, c.slug AS category_slug, 
                       sub.name AS subcategory_name, sub.slug AS subcategory_slug
                FROM news n
                JOIN categories c ON n.category_id = c.id
                LEFT JOIN categories sub ON n.subcategory_id = sub.id
                ORDER BY n.created_at DESC
                LIMIT 1
            ");
            $article = $fallback->fetch();
        }

        return $article;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get Latest "सबसे बड़ी खबर" News Items (Top 3 for Interactive Auto-Slider)
 */
function getSabseBadiKhabarNews($pdo, $limit = 3) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug, 
                   sub.name AS subcategory_name, sub.slug AS subcategory_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
            WHERE sub.slug = 'sabse-badi-khabar' OR c.slug = 'sabse-badi-khabar' OR n.is_featured = 1
            ORDER BY (sub.slug = 'sabse-badi-khabar') DESC, (n.is_featured = 1) DESC, n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $articles = $stmt->fetchAll();

        // If less than limit, supplement with latest news to guarantee full 3-slide carousel
        if (count($articles) < $limit) {
            $excludeIds = array_column($articles, 'id');
            $placeholders = empty($excludeIds) ? '0' : implode(',', array_map('intval', $excludeIds));
            $needed = $limit - count($articles);
            
            $fallback = $pdo->prepare("
                SELECT n.*, c.name AS category_name, c.slug AS category_slug, 
                       sub.name AS subcategory_name, sub.slug AS subcategory_slug
                FROM news n
                JOIN categories c ON n.category_id = c.id
                LEFT JOIN categories sub ON n.subcategory_id = sub.id
                WHERE n.id NOT IN ($placeholders)
                ORDER BY n.is_breaking DESC, n.created_at DESC
                LIMIT ?
            ");
            $fallback->bindValue(1, (int)$needed, PDO::PARAM_INT);
            $fallback->execute();
            $moreArticles = $fallback->fetchAll();
            $articles = array_merge($articles, $moreArticles);
        }

        return $articles;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get Hero Trending News (4 Cards for Right Column)
 */
function getTrendingNews($pdo, $limit = 4, $excludeId = 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug, sub.name AS subcategory_name, sub.slug AS subcategory_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
            WHERE n.id != ?
            ORDER BY (n.is_trending * 2 + n.views) DESC, n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$excludeId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get News by Category Slug with optional Subcategory
 */
function getNewsByCategorySlug($pdo, $categorySlug, $limit = 6, $offset = 0, $subSlug = null) {
    try {
        $params = [];
        $where = [];

        if (!empty($categorySlug) && !empty($subSlug)) {
            $where[] = "(c.slug = ? OR sub.slug = ? OR c.id = (SELECT parent_id FROM categories WHERE slug = ? LIMIT 1))";
            $params[] = $categorySlug;
            $params[] = $categorySlug;
            $params[] = $subSlug;
            $where[] = "sub.slug = ?";
            $params[] = $subSlug;
        } elseif (!empty($subSlug)) {
            $where[] = "sub.slug = ?";
            $params[] = $subSlug;
        } elseif (!empty($categorySlug)) {
            $where[] = "(c.slug = ? OR sub.slug = ?)";
            $params[] = $categorySlug;
            $params[] = $categorySlug;
        }

        $sql = "
            SELECT n.*, c.name AS category_name, c.slug AS category_slug, 
                   sub.name AS subcategory_name, sub.slug AS subcategory_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY n.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count News in Category/Subcategory for Pagination
 */
function countNewsByCategorySlug($pdo, $categorySlug, $subSlug = null) {
    try {
        $params = [];
        $where = [];

        if (!empty($categorySlug) && !empty($subSlug)) {
            $where[] = "(c.slug = ? OR sub.slug = ? OR c.id = (SELECT parent_id FROM categories WHERE slug = ? LIMIT 1))";
            $params[] = $categorySlug;
            $params[] = $categorySlug;
            $params[] = $subSlug;
            $where[] = "sub.slug = ?";
            $params[] = $subSlug;
        } elseif (!empty($subSlug)) {
            $where[] = "sub.slug = ?";
            $params[] = $subSlug;
        } elseif (!empty($categorySlug)) {
            $where[] = "(c.slug = ? OR sub.slug = ?)";
            $params[] = $categorySlug;
            $params[] = $categorySlug;
        }

        $sql = "
            SELECT COUNT(*) AS total
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get Popular News (by views)
 */
function getPopularNews($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            ORDER BY n.views DESC, n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get Recent News
 */
function getRecentNews($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get Article by Slug
 */
function getArticleBySlug($pdo, $slug) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, 
                   c.name AS category_name, c.slug AS category_slug, 
                   sub.name AS subcategory_name, sub.slug AS subcategory_slug,
                   u.id AS editor_id, u.name AS editor_name, u.avatar AS editor_avatar,
                   u.designation AS editor_designation, u.bio AS editor_bio,
                   u.location AS editor_location, u.username AS editor_username
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
            LEFT JOIN users u ON n.author_id = u.id
            WHERE n.slug = ?
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get Author / Editor Profile by ID or Username
 */
function getAuthorProfile($pdo, $idOrUsername) {
    try {
        if (is_numeric($idOrUsername)) {
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ? AND `status` = 'active' LIMIT 1");
            $stmt->execute([(int)$idOrUsername]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? AND `status` = 'active' LIMIT 1");
            $stmt->execute([$idOrUsername]);
        }
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get Articles by Author ID (with pagination)
 */
function getNewsByAuthorId($pdo, $authorId, $limit = 9, $offset = 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug,
                   sub.name AS subcategory_name, sub.slug AS subcategory_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
            WHERE n.author_id = ?
            ORDER BY n.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute([(int)$authorId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count Total Articles by Author ID
 */
function countNewsByAuthorId($pdo, $authorId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `news` WHERE `author_id` = ?");
        $stmt->execute([(int)$authorId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Increment View Count with Session Lock
 */
function incrementArticleViews($pdo, $articleId) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $viewedKey = 'viewed_article_' . $articleId;
    if (!isset($_SESSION[$viewedKey])) {
        $_SESSION[$viewedKey] = true;
        try {
            $stmt = $pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
            $stmt->execute([(int)$articleId]);
        } catch (PDOException $e) {
            // Ignore
        }
    }
}

/**
 * Get Related News
 */
function getRelatedNews($pdo, $categoryId, $currentId, $limit = 3) {
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug
            FROM news n
            JOIN categories c ON n.category_id = c.id
            WHERE n.category_id = ? AND n.id != ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$currentId, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Search News Articles
 */
function searchNews($pdo, $query, $limit = 10, $offset = 0) {
    try {
        $term = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT n.*, c.name AS category_name, c.slug AS category_slug, sub.name AS subcategory_name
            FROM news n
            JOIN categories c ON n.category_id = c.id
            LEFT JOIN categories sub ON n.subcategory_id = sub.id
            WHERE n.title LIKE ? OR n.content LIKE ? OR n.excerpt LIKE ?
            ORDER BY n.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute([$term, $term, $term]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count Search Results
 */
function countSearchResults($pdo, $query) {
    try {
        $term = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM news
            WHERE title LIKE ? OR content LIKE ? OR excerpt LIKE ?
        ");
        $stmt->execute([$term, $term, $term]);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get Static CMS Page
 */
function getPageBySlug($pdo, $slug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Format Date into Hindi
 */
function formatHindiDate($dateStr) {
    $time = strtotime($dateStr);
    if (!$time) return $dateStr;

    $hindiDays = [
        'Sunday' => 'रविवार', 'Monday' => 'सोमवार', 'Tuesday' => 'मंगलवार',
        'Wednesday' => 'बुधवार', 'Thursday' => 'गुरुवार', 'Friday' => 'शुक्रवार', 'Saturday' => 'शनिवार'
    ];

    $hindiMonths = [
        1 => 'जनवरी', 2 => 'फ़रवरी', 3 => 'मार्च', 4 => 'अप्रैल',
        5 => 'मई', 6 => 'जून', 7 => 'जुलाई', 8 => 'अगस्त',
        9 => 'सितंबर', 10 => 'अक्टूबर', 11 => 'नवंबर', 12 => 'दिसंबर'
    ];

    $dayName = $hindiDays[date('l', $time)] ?? date('l', $time);
    $dayNum = date('j', $time);
    $monthName = $hindiMonths[(int)date('n', $time)] ?? date('F', $time);
    $year = date('Y', $time);

    return "$dayName, $dayNum $monthName $year";
}

/**
 * Relative Time in Hindi ("2 घंटे पहले")
 */
function timeAgoHindi($datetime) {
    $time = strtotime($datetime);
    if (!$time) return $datetime;

    $diff = time() - $time;

    if ($diff < 60) {
        return 'अभी-अभी';
    } elseif ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' मिनट पहले';
    } elseif ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' घंटे पहले';
    } elseif ($diff < 604800) {
        $days = round($diff / 86400);
        return $days . ' दिन पहले';
    } else {
        return formatHindiDate($datetime);
    }
}

/**
 * Calculate Reading Time in Minutes
 */
function estimateReadingTime($text) {
    $wordCount = mb_strlen(strip_tags($text), 'UTF-8') / 5; // Hindi word approximation
    $minutes = ceil($wordCount / 180);
    return max(1, $minutes);
}

/**
 * Get Setting value from DB
 */
function getSetting($pdo, $key, $default = '') {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $stmt = $pdo->query("SELECT `key`, `value` FROM `settings`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settingsCache[$row['key']] = $row['value'];
            }
        } catch (PDOException $e) {
            // fallback
        }
    }
    return $settingsCache[$key] ?? $default;
}

/**
 * Update Setting in DB
 */
function setSetting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        return $stmt->execute([$key, $value]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Generate clean URL slug from string (supports Hindi transliteration / clean format)
 */
function slugify($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Trim
    $text = trim($text, '-');
    // Transliterate if possible
    if (empty($text)) {
        return 'post-' . time();
    }
    return mb_strtolower($text, 'UTF-8');
}

/**
 * Handle Secure Image File Upload (Avatars, Posts, Media)
 * @param array $file $_FILES['avatar_file']
 * @param string $subDir 'avatars' or 'posts'
 * @return string|null Web-accessible path starting with /assets/images/uploads/...
 */
function handleImageUpload($file, $subDir = 'avatars') {
    if (!isset($file) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];

    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExts)) {
        return null;
    }

    // Verify MIME type if finfo is available
    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($fileTmp);
        if (!in_array($mime, $allowedMimes)) {
            return null;
        }
    }

    $uploadDir = __DIR__ . '/../assets/images/uploads/' . $subDir . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newFileName = $subDir . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $targetPath)) {
        return '/assets/images/uploads/' . $subDir . '/' . $newFileName;
    }

    return null;
}

/**
 * Get Active Live Bulletin or Latest Recorded Bulletin
 */
function getActiveLiveBulletin($pdo) {
    try {
        // Check for explicitly active live stream
        $stmt = $pdo->prepare("SELECT * FROM `live_bulletins` WHERE `is_live` = 1 ORDER BY `created_at` DESC LIMIT 1");
        $stmt->execute();
        $live = $stmt->fetch();
        if ($live) {
            return $live;
        }

        // Fallback to latest recorded bulletin video
        $fallbackStmt = $pdo->prepare("SELECT * FROM `live_bulletins` ORDER BY `created_at` DESC LIMIT 1");
        $fallbackStmt->execute();
        return $fallbackStmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get Real-Time Timeline Updates for a Bulletin
 */
function getBulletinUpdates($pdo, $bulletinId, $limit = 50) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM `bulletin_updates` 
            WHERE `bulletin_id` = ? 
            ORDER BY `created_at` DESC, `id` DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$bulletinId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get Past / Recent Video Bulletins
 */
function getPastBulletins($pdo, $excludeId = 0, $limit = 6) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM `live_bulletins` 
            WHERE `id` != ? 
            ORDER BY `created_at` DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$excludeId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Normalize any video URL (YouTube watch, live, short, embed) into responsive embed iframe src
 */
function normalizeVideoEmbedUrl($url) {
    $url = trim($url);
    if (empty($url)) {
        return 'https://www.youtube.com/embed/live_stream?channel=default';
    }

    // YouTube formats:
    // https://www.youtube.com/watch?v=VIDEO_ID
    // https://youtu.be/VIDEO_ID
    // https://www.youtube.com/live/VIDEO_ID
    // https://www.youtube.com/embed/VIDEO_ID
    if (preg_match('#(?:youtube\.com/(?:watch\?v=|live/|embed/)|youtu\.be/)([a-zA-Z0-9_-]{11})#i', $url, $matches)) {
        return 'https://www.youtube-nocookie.com/embed/' . $matches[1] . '?autoplay=1&mute=0&rel=0&enablejsapi=1';
    }

    // If it's already an embed link (e.g. Facebook plugins or iframe src)
    if (stripos($url, 'embed') !== false || stripos($url, 'plugins/video.php') !== false) {
        return $url;
    }

    return $url;
}

/**
 * Extract YouTube Thumbnail
 */
function getYouTubeThumbnail($url) {
    if (preg_match('#(?:youtube\.com/(?:watch\?v=|live/|embed/)|youtu\.be/)([a-zA-Z0-9_-]{11})#i', $url, $matches)) {
        return 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg';
    }
    return '/assets/images/video_placeholder.jpg';
}

/**
 * Track Site Visitor Pageview (For Analytics & Daily Stats)
 */
function trackSiteVisitor($pdo, $pageTitle = null) {
    if (!$pdo || php_sapi_name() === 'cli') return;
    
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/admin') !== false || strpos($requestUri, '/api') !== false) {
        return;
    }

    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Detect Device Type
    $deviceType = 'Desktop';
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
        $deviceType = 'Tablet';
    } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile|iphone)/i', $userAgent)) {
        $deviceType = 'Mobile';
    }

    // Detect Browser
    $browser = 'Other';
    if (preg_match('/edg/i', $userAgent)) $browser = 'Edge';
    elseif (preg_match('/chrome|crios/i', $userAgent)) $browser = 'Chrome';
    elseif (preg_match('/firefox|fxios/i', $userAgent)) $browser = 'Firefox';
    elseif (preg_match('/safari/i', $userAgent)) $browser = 'Safari';
    elseif (preg_match('/opera|opr/i', $userAgent)) $browser = 'Opera';

    // Detect OS
    $os = 'Other';
    if (preg_match('/windows/i', $userAgent)) $os = 'Windows';
    elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
    elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) $os = 'iOS';
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'macOS';
    elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';

    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $visitDate = date('Y-m-d');
    $title = $pageTitle ?? 'News 24 Himachal';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO `site_visitors` (`visitor_ip`, `page_url`, `page_title`, `device_type`, `browser`, `os`, `referrer`, `visit_date`, `visited_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$ip, mb_substr($requestUri, 0, 500), mb_substr($title, 0, 255), $deviceType, $browser, $os, mb_substr($referrer, 0, 500), $visitDate]);
    } catch (PDOException $e) {
        // Create table automatically if it does not exist
        if ($e->getCode() == '42S02' || strpos($e->getMessage(), "site_visitors' doesn't exist") !== false) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `site_visitors` (
                    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `visitor_ip` VARCHAR(45) NOT NULL,
                    `page_url` VARCHAR(500) NOT NULL,
                    `page_title` VARCHAR(255) NULL,
                    `device_type` VARCHAR(30) DEFAULT 'Desktop',
                    `browser` VARCHAR(50) DEFAULT 'Chrome',
                    `os` VARCHAR(50) DEFAULT 'Windows',
                    `referrer` VARCHAR(500) NULL,
                    `visit_date` DATE NOT NULL,
                    `visited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_visit_date` (`visit_date`),
                    INDEX `idx_visit_ip_date` (`visitor_ip`, `visit_date`),
                    INDEX `idx_device` (`device_type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }
    }
}



