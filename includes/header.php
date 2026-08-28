<?php
/**
 * Header Component
 * News 24 Himachal
 */
require_once __DIR__ . '/functions.php';

$pdo = getDBConnection();
$pageTitle = $pageTitle ?? 'News 24 Himachal - हिमाचल प्रदेश का प्रमुख हिंदी समाचार पोर्टल';
$pageDescription = $pageDescription ?? 'News 24 Himachal - ब्रेकिंग न्यूज़, शिमला, कांगड़ा, मंडी, देवभूमि दर्शन, राजनीति, संस्कृति और ताज़ा खबरें।';

// Track unique visitor & pageview
trackSiteVisitor($pdo, $pageTitle);
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <meta name="description" content="<?= sanitize($pageDescription) ?>">
    
    <!-- Open Graph / Meta -->
    <meta property="og:type" content="news">
    <meta property="og:title" content="<?= sanitize($pageTitle) ?>">
    <meta property="og:description" content="<?= sanitize($pageDescription) ?>">
    <meta property="og:site_name" content="News 24 Himachal">
    
    <!-- Google Fonts: Hind (Devanagari) & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time() ?>">
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container top-bar-inner">
            <div class="top-bar-left">
                <span class="top-bar-badge">LIVE</span>
                <div class="top-bar-date">
                    <i class="far fa-calendar-alt"></i>
                    <span><?= formatHindiDate(date('Y-m-d')) ?></span>
                </div>
                <div class="top-bar-clock">
                    <i class="far fa-clock"></i>
                    <span id="live-clock"><?= date('h:i:s A') ?></span>
                </div>
            </div>
            <div class="top-bar-right">
                <div class="top-socials">
                    <a href="<?= sanitize(getSetting($pdo, 'social_facebook', '#')) ?>" target="_blank" title="Facebook" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?= sanitize(getSetting($pdo, 'social_twitter', '#')) ?>" target="_blank" title="Twitter" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
                    <a href="<?= sanitize(getSetting($pdo, 'social_youtube', '#')) ?>" target="_blank" title="YouTube" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="<?= sanitize(getSetting($pdo, 'social_instagram', '#')) ?>" target="_blank" title="Instagram" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="<?= sanitize(getSetting($pdo, 'social_telegram', '#')) ?>" target="_blank" title="Telegram" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
                </div>
                <div class="top-links">
                    <a href="about.php">About Us</a>
                    <a href="contact.php">Contact Us</a>
                </div>
                <div class="top-subscribe-wrap">
                    <button type="button" class="global-subscribe-btn not-subscribed" aria-label="Subscribe to News 24 Himachal">
                        <i class="fas fa-bell"></i>
                        <span class="btn-text">SUBSCRIBE</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header (Logo Left, Search Box Center) -->
    <header class="main-header">
        <div class="container header-main-wrap">
            <div class="header-logo-col">
                <a href="index.php" class="brand-logo-left" title="News 24 Himachal - मुख्य पृष्ठ">
                    <img src="assets/images/logo.png" alt="News 24 Himachal" class="site-main-logo">
                </a>
            </div>
            <div class="header-search-col">
                <div class="header-search-bar">
                    <form action="search.php" method="GET">
                        <i class="fas fa-search search-icon-left"></i>
                        <input type="text" name="q" placeholder="हिमाचल, शिमला, कांगड़ा, मंडी या कोई भी खबर खोजें..." value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>" required>
                        <button type="submit" aria-label="Search">खोजें</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Sticky Navigation -->
    <?php require_once __DIR__ . '/navbar.php'; ?>
