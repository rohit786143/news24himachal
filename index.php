<?php
/**
 * Homepage (index.php)
 * News 24 Himachal
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'News 24 Himachal - शिमला, कांगड़ा, मंडी और देवभूमि की ताज़ा ख़बरें';
$pageDescription = 'News 24 Himachal - हिमाचल प्रदेश का नंबर 1 हिंदी न्यूज़ पोर्टल। ब्रेकिंग न्यूज़, शिमला, कांगड़ा, मंडी, देवभूमि दर्शन, राजनीति, संस्कृति और पर्यटन की हर ख़बर सबसे पहले।';

require_once __DIR__ . '/includes/header.php';

// Fetch Hero Data (Top 3 "Latest News" for Auto-Slider + 4 Trending + Live TV)
$latestNewsList = getSabseBadiKhabarNews($pdo, 3);
$trendingNews = getTrendingNews($pdo, 4, array_column($latestNewsList, 'id'));

// Fetch Category Blocks Data (4 Major Districts: Shimla, Mandi, Kullu, Kangra - 4 items each)
$shimlaNews = getNewsByCategorySlug($pdo, 'himachal-news', 4, 0, 'shimla');
$mandiNews = getNewsByCategorySlug($pdo, 'himachal-news', 4, 0, 'mandi');
$kulluNews = getNewsByCategorySlug($pdo, 'himachal-news', 4, 0, 'kullu');
$kangraNews = getNewsByCategorySlug($pdo, 'himachal-news', 4, 0, 'kangra');

$darshanNews = getNewsByCategorySlug($pdo, 'himachal-darshan', 3);
$rajnitiNews = getNewsByCategorySlug($pdo, 'rajniti', 3);
$crimeNews = getNewsByCategorySlug($pdo, 'crime', 3);
$sportsNews = getNewsByCategorySlug($pdo, 'khel', 3);
$entertainmentNews = getNewsByCategorySlug($pdo, 'manoranjan', 3);
?>

<main>
    <!-- Hero Section (Split Layout: 1 Interactive Slider 'Latest News' + 4 Trending + Live TV) -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-grid">
                
                <!-- Left: Big Lead Carousel ("Latest News" - Top 3 Latest Published Articles) -->
                <?php if (!empty($latestNewsList)): ?>
                <div class="lead-col-container">
                    <div class="hero-block-heading">
                        <span class="hero-block-badge"><i class="fas fa-bolt"></i> Latest News</span>
                        <div class="lead-slider-controls-top">
                            <button type="button" class="lead-slider-arrow prev" id="leadPrevBtn" aria-label="पिछली खबर" title="पिछली खबर">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="lead-slider-counter"><span id="leadSlideCurrent">1</span>/<?= count($latestNewsList) ?></span>
                            <button type="button" class="lead-slider-arrow next" id="leadNextBtn" aria-label="अगली खबर" title="अगली खबर">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="lead-slider-container" id="sabseBadiKhabarSlider" data-autoplay-interval="4500">
                        <div class="lead-slider-track">
                            <?php foreach ($latestNewsList as $index => $item): ?>
                            <div class="lead-slide <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                                <div class="lead-story-card">
                                    <!-- Clean Thumbnail Image (With District Badge on top-right) -->
                                    <div class="lead-img-wrap">
                                        <span class="lead-slide-district-badge">
                                            <i class="fas fa-map-marker-alt"></i> <?= sanitize($item['subcategory_name'] ?? $item['category_name']) ?>
                                        </span>
                                        <a href="article.php?slug=<?= urlencode($item['slug']) ?>" title="<?= sanitize($item['title']) ?>">
                                            <img src="<?= sanitize($item['image_url']) ?>" alt="<?= sanitize($item['title']) ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>">
                                        </a>
                                    </div>

                                    <!-- Content Below Image (Headline & Meta) -->
                                    <div class="lead-story-body">
                                        <h1 class="lead-story-title">
                                            <a href="article.php?slug=<?= urlencode($item['slug']) ?>">
                                                <?= sanitize($item['title']) ?>
                                            </a>
                                        </h1>
                                        <div class="lead-story-meta">
                                            <span><i class="far fa-user"></i> <?= sanitize($item['author']) ?></span>
                                            <span><i class="far fa-clock"></i> <?= timeAgoHindi($item['created_at']) ?></span>
                                            <span><i class="far fa-eye"></i> <?= number_format($item['views']) ?> व्यूज</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Progress / Pagination Indicator Dots -->
                        <div class="lead-slider-dots">
                            <?php foreach ($latestNewsList as $index => $item): ?>
                            <button type="button" class="lead-dot <?= $index === 0 ? 'active' : '' ?>" data-slide="<?= $index ?>" aria-label="Slide <?= $index + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Middle: 3 Trending News Items (Image Thumbnail + Title + Date List View) -->
                <div class="trending-list-box">
                    <?php foreach ($trendingNews as $trend): ?>
                        <a href="article.php?slug=<?= urlencode($trend['slug']) ?>" class="trending-list-item">
                            <div class="trend-thumb">
                                <img src="<?= sanitize($trend['image_url']) ?>" alt="<?= sanitize($trend['title']) ?>" loading="lazy">
                            </div>
                            <div class="trend-info">
                                <h3 class="trend-title"><?= sanitize($trend['title']) ?></h3>
                                <div class="trend-date">
                                    <span><?= date('M d, Y h:i a', strtotime($trend['created_at'])) ?> IST</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Right: 🔴 Live TV Broadcast Card + 🗳️ Opinion Poll Stack (Equal Height Layout) -->
                <div class="hero-right-col">
                    <!-- Live TV Card -->
                    <div class="hero-livetv-card" id="livetv-section">
                        <div class="livetv-header">
                            <div class="livetv-badge">
                                <span class="live-dot"></span> LIVE TV
                            </div>
                            <span class="livetv-channel-name"><i class="fas fa-satellite-dish"></i> न्यूज़ 24 हिमाचल HD</span>
                        </div>
                        
                        <div class="livetv-screen">
                            <iframe 
                                src="<?= sanitize(normalizeVideoEmbedUrl(getSetting($pdo, 'livetv_url', 'https://www.facebook.com/share/v/1MJyM4wWgR/'))) ?>" 
                                title="News 24 Himachal लाइव टीवी प्रसारण"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" 
                                allowfullscreen="true" 
                                loading="eager"
                                style="border:none;overflow:hidden;width:100%;height:100%;">
                            </iframe>
                            <div class="livetv-overlay-info">
                                <span class="livetv-tag"><i class="fas fa-circle live-pulse-dot"></i> लाइव प्रसारण</span>
                                <span class="livetv-viewers"><i class="fas fa-broadcast-tower"></i> 24x7 HD</span>
                            </div>
                        </div>

                        <div class="livetv-meta-wrap">
                            <h4 class="livetv-show-title">
                                <i class="fas fa-tv"></i> <?= sanitize(getSetting($pdo, 'site_name', 'News 24 Himachal')) ?>
                            </h4>
                            <p class="livetv-show-desc">शिमला, कांगड़ा, मंडी व कुल्लू से सीधा समाचार प्रसारण</p>
                        </div>
                    </div>

                    <!-- 🗳️ Opinion Poll Card (1 Question, 3 Options with %, Compact Fit) -->
                    <div class="hero-poll-card" id="heroPollCard">
                        <div class="poll-card-header">
                            <span class="poll-badge"><i class="fas fa-poll-h"></i> जनमत पोल</span>
                            <span class="poll-votes-count"><i class="fas fa-users"></i> <span id="pollTotalVotes"><?= number_format((int)getSetting($pdo, 'poll_total_votes', 2840)) ?></span> वोट</span>
                        </div>
                        <div class="poll-question">
                            <?= sanitize(getSetting($pdo, 'poll_question', 'क्या हिमाचल में विंटर टूरिज्म व स्नो-स्पोर्ट्स के लिए नई नीतियां बननी चाहिए?')) ?>
                        </div>
                        <div class="poll-options" id="pollOptionsList">
                            <button type="button" class="poll-opt-btn" data-opt="yes" data-percent="<?= (int)getSetting($pdo, 'poll_opt1_val', 74) ?>">
                                <span class="poll-opt-bg" style="width: <?= (int)getSetting($pdo, 'poll_opt1_val', 74) ?>%;"></span>
                                <span class="poll-opt-text"><?= sanitize(getSetting($pdo, 'poll_opt1', 'हाँ (Yes)')) ?></span>
                                <span class="poll-opt-percent"><?= (int)getSetting($pdo, 'poll_opt1_val', 74) ?>%</span>
                            </button>
                            <button type="button" class="poll-opt-btn" data-opt="no" data-percent="<?= (int)getSetting($pdo, 'poll_opt2_val', 18) ?>">
                                <span class="poll-opt-bg" style="width: <?= (int)getSetting($pdo, 'poll_opt2_val', 18) ?>%;"></span>
                                <span class="poll-opt-text"><?= sanitize(getSetting($pdo, 'poll_opt2', 'नहीं (No)')) ?></span>
                                <span class="poll-opt-percent"><?= (int)getSetting($pdo, 'poll_opt2_val', 18) ?>%</span>
                            </button>
                            <button type="button" class="poll-opt-btn" data-opt="cant_say" data-percent="<?= (int)getSetting($pdo, 'poll_opt3_val', 8) ?>">
                                <span class="poll-opt-bg" style="width: <?= (int)getSetting($pdo, 'poll_opt3_val', 8) ?>%;"></span>
                                <span class="poll-opt-text"><?= sanitize(getSetting($pdo, 'poll_opt3', 'कह नहीं सकते')) ?></span>
                                <span class="poll-opt-percent"><?= (int)getSetting($pdo, 'poll_opt3_val', 8) ?>%</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Main Content & Sidebar Layout -->
    <div class="main-layout">
        <div class="container content-grid">
            
            <!-- Left Primary Column (Category Blocks) -->
            <div class="main-content-column">

                <!-- Block 1: Himachal News (Districts: Shimla, Mandi, Kullu, Kangra) -->
                <section style="margin-bottom: 35px;">
                    <div class="section-header">
                        <h2 class="section-title section-title-with-logo">
                            <img src="assets/images/logo.png" alt="News 24 Himachal" class="section-header-logo">
                            <span>(जिलावार खबरें)</span>
                        </h2>
                        <a href="category.php?cat=himachal-news" class="view-all-link">
                            सभी जिले देखें <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- District Subcategory Filter Pills (All 12 Districts of Himachal Pradesh) -->
                    <div class="district-tabs">
                        <a href="category.php?cat=himachal-news&sub=shimla" class="district-tab">शिमला</a>
                        <a href="category.php?cat=himachal-news&sub=kangra" class="district-tab">कांगड़ा</a>
                        <a href="category.php?cat=himachal-news&sub=mandi" class="district-tab">मंडी</a>
                        <a href="category.php?cat=himachal-news&sub=kullu" class="district-tab">कुल्लू</a>
                        <a href="category.php?cat=himachal-news&sub=solan" class="district-tab">सोलन</a>
                        <a href="category.php?cat=himachal-news&sub=sirmaur" class="district-tab">सिरमौर</a>
                        <a href="category.php?cat=himachal-news&sub=hamirpur" class="district-tab">हमीरपुर</a>
                        <a href="category.php?cat=himachal-news&sub=una" class="district-tab">ऊना</a>
                        <a href="category.php?cat=himachal-news&sub=bilaspur" class="district-tab">बिलासपुर</a>
                        <a href="category.php?cat=himachal-news&sub=chamba" class="district-tab">चंबा</a>
                        <a href="category.php?cat=himachal-news&sub=kinnaur" class="district-tab">किन्नौर</a>
                        <a href="category.php?cat=himachal-news&sub=lahaul-spiti" class="district-tab">लाहौल-स्पीति</a>
                    </div>

                    <!-- 4 Major District News Blocks (Shimla, Mandi, Kullu, Kangra in 4-News List Format) -->
                    <div class="districts-quad-grid">
                        <?php 
                        $fourDistricts = [
                            ['slug' => 'shimla', 'name' => 'शिमला', 'news' => $shimlaNews, 'icon' => 'fa-mountain-city'],
                            ['slug' => 'mandi', 'name' => 'मंडी', 'news' => $mandiNews, 'icon' => 'fa-bridge'],
                            ['slug' => 'kullu', 'name' => 'कुल्लू', 'news' => $kulluNews, 'icon' => 'fa-campground'],
                            ['slug' => 'kangra', 'name' => 'कांगड़ा', 'news' => $kangraNews, 'icon' => 'fa-gopuram']
                        ];
                        foreach ($fourDistricts as $dist):
                        ?>
                        <div class="district-news-block">
                            <div class="district-block-header">
                                <span class="district-block-title">
                                    <i class="fas <?= $dist['icon'] ?>"></i> <?= $dist['name'] ?>
                                </span>
                                <a href="category.php?cat=himachal-news&sub=<?= $dist['slug'] ?>" class="district-block-more">
                                    और देखें &rarr;
                                </a>
                            </div>

                            <div class="district-list-box">
                                <?php foreach ($dist['news'] as $item): ?>
                                <a href="article.php?slug=<?= urlencode($item['slug']) ?>" class="trending-list-item">
                                    <div class="trend-thumb">
                                        <img src="<?= sanitize($item['image_url']) ?>" alt="<?= sanitize($item['title']) ?>" loading="lazy">
                                    </div>
                                    <div class="trend-info">
                                        <h3 class="trend-title"><?= sanitize($item['title']) ?></h3>
                                        <div class="trend-date">
                                            <span><i class="far fa-clock"></i> <?= timeAgoHindi($item['created_at']) ?></span>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Block 2: Himachal Darshan (Sacred Bhagwa & Spiritual Showcase) -->
                <section class="darshan-section">
                    <div class="darshan-header">
                        <div class="darshan-title-wrap">
                            <h2 class="darshan-title">
                                <i class="fas fa-om darshan-om-icon"></i> हिमाचल दर्शन 
                                <span class="darshan-subtitle">देवभूमि, संस्कृति एवं पर्यटन</span>
                            </h2>
                        </div>
                        <a href="category.php?cat=himachal-darshan" class="darshan-view-all-btn">
                            सभी शक्तिपीठ व स्थल <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="darshan-grid">
                        <?php foreach ($darshanNews as $darshan): ?>
                            <article class="darshan-card">
                                <div class="darshan-card-img">
                                    <span class="darshan-badge">
                                        <i class="fas fa-gopuram"></i> <?= sanitize($darshan['subcategory_name'] ?? 'देवभूमि दर्शन') ?>
                                    </span>
                                    <a href="article.php?slug=<?= urlencode($darshan['slug']) ?>">
                                        <img src="<?= sanitize($darshan['image_url']) ?>" alt="<?= sanitize($darshan['title']) ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="darshan-card-body">
                                    <h3 class="darshan-card-title">
                                        <a href="article.php?slug=<?= urlencode($darshan['slug']) ?>">
                                            <?= sanitize($darshan['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="darshan-card-meta">
                                        <span><i class="fas fa-calendar-alt"></i> <?= formatHindiDate($darshan['created_at']) ?></span>
                                        <span><i class="fas fa-eye"></i> <?= number_format($darshan['views']) ?> दर्शनार्थी</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Block 3: राजनीति (Politics - 3 Grid with Image) -->
                <section class="home-category-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-landmark"></i> राजनीति
                        </h2>
                        <a href="category.php?cat=rajniti" class="view-all-link">
                            और देखें <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="cat-grid-3">
                        <?php foreach ($rajnitiNews as $pol): ?>
                            <article class="cat-news-card">
                                <div class="cat-card-img">
                                    <a href="article.php?slug=<?= urlencode($pol['slug']) ?>" title="<?= sanitize($pol['title']) ?>">
                                        <img src="<?= sanitize($pol['image_url']) ?>" alt="<?= sanitize($pol['title']) ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="cat-card-body">
                                    <h3 class="cat-card-title">
                                        <a href="article.php?slug=<?= urlencode($pol['slug']) ?>">
                                            <?= sanitize($pol['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="cat-card-meta">
                                        <span><i class="far fa-clock"></i> <?= timeAgoHindi($pol['created_at']) ?></span>
                                        <span><i class="far fa-eye"></i> <?= number_format($pol['views']) ?> व्यूज</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Block 4: क्राइम (Crime - 3 Grid with Image & Badges) -->
                <section class="home-category-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-shield-halved"></i> क्राइम
                        </h2>
                        <a href="category.php?cat=crime" class="view-all-link">
                            और देखें <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="cat-grid-3">
                        <?php foreach ($crimeNews as $crime): ?>
                            <article class="cat-news-card">
                                <div class="cat-card-img">
                                    <span class="cat-card-badge" style="background: var(--primary-red); color: #fff;">
                                        <i class="fas fa-handcuffs"></i> क्राइम
                                    </span>
                                    <a href="article.php?slug=<?= urlencode($crime['slug']) ?>" title="<?= sanitize($crime['title']) ?>">
                                        <img src="<?= sanitize($crime['image_url']) ?>" alt="<?= sanitize($crime['title']) ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="cat-card-body">
                                    <h3 class="cat-card-title">
                                        <a href="article.php?slug=<?= urlencode($crime['slug']) ?>">
                                            <?= sanitize($crime['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="cat-card-meta">
                                        <span><i class="far fa-clock"></i> <?= timeAgoHindi($crime['created_at']) ?></span>
                                        <span><i class="far fa-eye"></i> <?= number_format($crime['views']) ?> व्यूज</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Block 5: खेल (Sports - 3 Grid with Image) -->
                <section class="home-category-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-trophy"></i> खेल
                        </h2>
                        <a href="category.php?cat=khel" class="view-all-link">
                            और देखें <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="cat-grid-3">
                        <?php foreach ($sportsNews as $sport): ?>
                            <article class="cat-news-card">
                                <div class="cat-card-img">
                                    <a href="article.php?slug=<?= urlencode($sport['slug']) ?>" title="<?= sanitize($sport['title']) ?>">
                                        <img src="<?= sanitize($sport['image_url']) ?>" alt="<?= sanitize($sport['title']) ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="cat-card-body">
                                    <h3 class="cat-card-title">
                                        <a href="article.php?slug=<?= urlencode($sport['slug']) ?>">
                                            <?= sanitize($sport['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="cat-card-meta">
                                        <span><i class="far fa-clock"></i> <?= timeAgoHindi($sport['created_at']) ?></span>
                                        <span><i class="far fa-eye"></i> <?= number_format($sport['views']) ?> व्यूज</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Block 6: मनोरंजन (Entertainment - 3 Grid with Image) -->
                <section class="home-category-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-film"></i> मनोरंजन
                        </h2>
                        <a href="category.php?cat=manoranjan" class="view-all-link">
                            और देखें <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="cat-grid-3">
                        <?php foreach ($entertainmentNews as $ent): ?>
                            <article class="cat-news-card">
                                <div class="cat-card-img">
                                    <?php if (!empty($ent['subcategory_name'])): ?>
                                        <span class="cat-card-badge">
                                            <i class="fas fa-clapperboard"></i> <?= sanitize($ent['subcategory_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <a href="article.php?slug=<?= urlencode($ent['slug']) ?>" title="<?= sanitize($ent['title']) ?>">
                                        <img src="<?= sanitize($ent['image_url']) ?>" alt="<?= sanitize($ent['title']) ?>" loading="lazy">
                                    </a>
                                </div>
                                <div class="cat-card-body">
                                    <h3 class="cat-card-title">
                                        <a href="article.php?slug=<?= urlencode($ent['slug']) ?>">
                                            <?= sanitize($ent['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="cat-card-meta">
                                        <span><i class="far fa-clock"></i> <?= timeAgoHindi($ent['created_at']) ?></span>
                                        <span><i class="far fa-eye"></i> <?= number_format($ent['views']) ?> व्यूज</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

            </div>

            <!-- Right Column (Sidebar Widgets) -->
            <div class="sidebar-column">
                <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
