<?php
/**
 * Daily Horoscope (दैनिक राशिफल) - All 12 Zodiac Signs
 * News 24 Himachal
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/rashifal-data.php';

$pdo = getDBConnection();

$todayDate = date('Y-m-d');
$hindiDays = ['रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'];
$dayOfWeek = $hindiDays[(int)date('w')];
$monthsHindi = [
    1 => 'जनवरी', 2 => 'फ़रवरी', 3 => 'मार्च', 4 => 'अप्रैल', 5 => 'मई', 6 => 'जून',
    7 => 'जुलाई', 8 => 'अगस्त', 9 => 'सितंबर', 10 => 'अक्टूबर', 11 => 'नवंबर', 12 => 'दिसंबर'
];
$formattedHindiDate = date('d') . ' ' . $monthsHindi[(int)date('n')] . ' ' . date('Y') . ', ' . $dayOfWeek;

$rashifalList = getDailyRashifalData($todayDate);

$pageTitle = 'दैनिक राशिफल (' . $formattedHindiDate . ') - 12 राशियों का आज का संपूर्ण भविष्यफल | News 24 Himachal';
$pageDescription = 'आज का दैनिक राशिफल (' . $formattedHindiDate . ') - मेष, वृषभ, मिथुन, कर्क, सिंह, कन्या, तुला, वृश्चिक, धनु, मकर, कुंभ, मीन राशियों का करियर, धन, प्रेम, स्वास्थ्य, शुभ रंग, अंक व अचूक उपाय।';

// Fetch any recent articles under Rashifal category for bottom section
$astrologyArticles = getNewsByCategorySlug($pdo, 'rashiphal', 4);

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Rich Modern Rashifal Aspect Card Styles */
.rashi-cards-container {
    display: flex;
    flex-direction: column;
    gap: 30px;
    margin-top: 20px;
}
.rashi-full-card {
    background: #FFFFFF;
    border-radius: 16px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    scroll-margin-top: 80px;
    transition: box-shadow 0.2s ease;
}
.rashi-full-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}
.rashi-card-header {
    background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
    color: #FFFFFF;
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    border-bottom: 3px solid var(--primary);
}
.rashi-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.rashi-symbol-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    font-weight: bold;
    box-shadow: 0 4px 10px rgba(227, 27, 35, 0.4);
}
.rashi-title-group h2 {
    font-size: 1.35rem;
    font-weight: 800;
    margin: 0;
    color: #FFFFFF;
}
.rashi-meta-info {
    font-size: 0.8rem;
    color: #CBD5E1;
    margin-top: 3px;
}
.rashi-share-btn {
    background: #25D366;
    color: #FFFFFF;
    border: none;
    border-radius: 20px;
    padding: 7px 16px;
    font-size: 0.82rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    cursor: pointer;
    transition: opacity 0.2s ease;
}
.rashi-share-btn:hover {
    opacity: 0.9;
    color: #FFFFFF;
}

/* Aspects Section */
.rashi-card-body {
    padding: 24px;
}
.rashi-overview-box {
    background: #FFF5F5;
    border-left: 4px solid var(--primary);
    padding: 14px 18px;
    border-radius: 0 8px 8px 0;
    margin-bottom: 22px;
    font-size: 1rem;
    line-height: 1.65;
    color: #1E293B;
}
.rashi-aspects-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
@media (max-width: 768px) {
    .rashi-aspects-grid {
        grid-template-columns: 1fr;
    }
}
.aspect-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 16px;
}
.aspect-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0F172A;
    margin-bottom: 8px;
}
.aspect-title-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.aspect-score-badge {
    font-size: 0.75rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 12px;
    background: rgba(22, 163, 74, 0.1);
    color: #16A34A;
}
.aspect-desc {
    font-size: 0.88rem;
    line-height: 1.55;
    color: #334155;
    margin-bottom: 10px;
}
.aspect-bar {
    height: 5px;
    background: #E2E8F0;
    border-radius: 3px;
    overflow: hidden;
}
.aspect-bar-fill {
    height: 100%;
    border-radius: 3px;
}

/* Lucky Highlights & Upay */
.rashi-bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 16px;
    margin-top: 10px;
}
@media (max-width: 768px) {
    .rashi-bottom-grid {
        grid-template-columns: 1fr;
    }
}
.lucky-pill-box {
    background: #F1F5F9;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
}
.lucky-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.86rem;
    border-bottom: 1px dashed #CBD5E1;
    padding-bottom: 6px;
}
.lucky-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.lucky-item strong {
    color: var(--primary);
}
.upay-box {
    background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
    border: 1.5px solid #FDE68A;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.upay-icon {
    width: 36px;
    height: 36px;
    background: #D97706;
    color: #FFFFFF;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.upay-text h4 {
    font-size: 0.92rem;
    font-weight: 800;
    color: #92400E;
    margin-bottom: 4px;
}
.upay-text p {
    font-size: 0.86rem;
    color: #78350F;
    line-height: 1.5;
    margin: 0;
}
</style>

<main>
    <div class="main-layout" style="padding-top: 15px; padding-bottom: 60px;">
        <div class="container" style="max-width: 1050px; margin: 0 auto;">
            <!-- Compact Space-Saving Category Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; border-bottom: 2px solid #E2E8F0; padding-bottom: 12px;">
                <div>
                    <div class="breadcrumbs" style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 4px;">
                        <a href="index.php" style="color: var(--text-muted); text-decoration: none;">होम</a>
                        <span class="separator" style="margin: 0 4px;">&rsaquo;</span>
                        <span style="color: var(--primary); font-weight: 700;">राशिफल</span>
                    </div>
                    <h1 style="font-size: 1.55rem; font-weight: 900; color: var(--text-heading); margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 4px; height: 22px; background: var(--primary); border-radius: 2px;"></span>
                        दैनिक राशिफल (Daily Horoscope)
                        <span style="font-size: 0.82rem; font-weight: normal; color: var(--text-muted); margin-left: 6px;">(<?= $formattedHindiDate ?>)</span>
                    </h1>
                </div>
            </div>

            <!-- Full Width Column: 12 Rashi Detail Cards -->
            <div style="width: 100%;">
                
                <div class="rashi-cards-container">
                    <?php foreach ($rashifalList as $r): ?>
                        <?php 
                        $whatsappShareText = urlencode("🔮 *आज का राशिफल (" . $r['name'] . " - " . $r['english'] . ")* - " . $formattedHindiDate . "\n\n" . $r['overview'] . "\n\n⭐ *शुभ रंग:* " . $r['lucky_color'] . " | *शुभ अंक:* " . $r['lucky_number'] . "\n🪐 *आज का उपाय:* " . $r['upay'] . "\n\nपूरी 12 राशियों का राशिफल देखें: " . (defined('APP_URL') ? APP_URL : 'https://news24hp.com') . "/rashifal.php#rashi-" . $r['id']);
                        ?>
                        <article class="rashi-full-card" id="rashi-<?= $r['id'] ?>">
                            
                            <!-- Header -->
                            <div class="rashi-card-header">
                                <div class="rashi-header-left">
                                    <div class="rashi-symbol-circle">
                                        <?= $r['symbol'] ?>
                                    </div>
                                    <div class="rashi-title-group">
                                        <h2><?= $r['name'] ?> राशिफल (<?= $r['english'] ?>)</h2>
                                        <div class="rashi-meta-info">
                                            <span><strong>तत्व:</strong> <?= $r['element'] ?></span> • 
                                            <span><strong>स्वामी ग्रह:</strong> <?= $r['ruler'] ?></span> • 
                                            <span><strong>नाम अक्षर:</strong> <?= $r['letters'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <a href="https://api.whatsapp.com/send?text=<?= $whatsappShareText ?>" target="_blank" class="rashi-share-btn">
                                    <i class="fab fa-whatsapp"></i> शेयर करें
                                </a>
                            </div>

                            <!-- Body -->
                            <div class="rashi-card-body">
                                
                                <!-- Overview -->
                                <div class="rashi-overview-box">
                                    <strong style="color: var(--primary); display: block; margin-bottom: 4px;">
                                        <i class="fas fa-sparkles"></i> आज का समग्र भविष्यफल:
                                    </strong>
                                    <?= $r['overview'] ?>
                                </div>

                                <!-- 4 Specific Aspects Grid -->
                                <div class="rashi-aspects-grid">
                                    
                                    <!-- 1. Career & Business -->
                                    <div class="aspect-card">
                                        <div class="aspect-title">
                                            <div class="aspect-title-left">
                                                <i class="fas fa-briefcase" style="color: #0284C7;"></i>
                                                <span>करियर एवं व्यवसाय</span>
                                            </div>
                                            <span class="aspect-score-badge" style="background: rgba(2, 132, 199, 0.1); color: #0284C7;">
                                                <?= $r['career_score'] ?>% अनुकूल
                                            </span>
                                        </div>
                                        <div class="aspect-desc"><?= $r['career'] ?></div>
                                        <div class="aspect-bar">
                                            <div class="aspect-bar-fill" style="width: <?= $r['career_score'] ?>%; background: #0284C7;"></div>
                                        </div>
                                    </div>

                                    <!-- 2. Finance & Wealth -->
                                    <div class="aspect-card">
                                        <div class="aspect-title">
                                            <div class="aspect-title-left">
                                                <i class="fas fa-sack-dollar" style="color: #16A34A;"></i>
                                                <span>आर्थिक स्थिति व धन</span>
                                            </div>
                                            <span class="aspect-score-badge" style="background: rgba(22, 163, 74, 0.1); color: #16A34A;">
                                                <?= $r['finance_score'] ?>% अनुकूल
                                            </span>
                                        </div>
                                        <div class="aspect-desc"><?= $r['finance'] ?></div>
                                        <div class="aspect-bar">
                                            <div class="aspect-bar-fill" style="width: <?= $r['finance_score'] ?>%; background: #16A34A;"></div>
                                        </div>
                                    </div>

                                    <!-- 3. Love & Family -->
                                    <div class="aspect-card">
                                        <div class="aspect-title">
                                            <div class="aspect-title-left">
                                                <i class="fas fa-heart" style="color: #E11D48;"></i>
                                                <span>प्रेम व दांपत्य जीवन</span>
                                            </div>
                                            <span class="aspect-score-badge" style="background: rgba(225, 29, 72, 0.1); color: #E11D48;">
                                                <?= $r['love_score'] ?>% अनुकूल
                                            </span>
                                        </div>
                                        <div class="aspect-desc"><?= $r['love'] ?></div>
                                        <div class="aspect-bar">
                                            <div class="aspect-bar-fill" style="width: <?= $r['love_score'] ?>%; background: #E11D48;"></div>
                                        </div>
                                    </div>

                                    <!-- 4. Health & Energy -->
                                    <div class="aspect-card">
                                        <div class="aspect-title">
                                            <div class="aspect-title-left">
                                                <i class="fas fa-heart-pulse" style="color: #D97706;"></i>
                                                <span>स्वास्थ्य एवं ऊर्जा</span>
                                            </div>
                                            <span class="aspect-score-badge" style="background: rgba(217, 119, 6, 0.1); color: #D97706;">
                                                <?= $r['health_score'] ?>% अनुकूल
                                            </span>
                                        </div>
                                        <div class="aspect-desc"><?= $r['health'] ?></div>
                                        <div class="aspect-bar">
                                            <div class="aspect-bar-fill" style="width: <?= $r['health_score'] ?>%; background: #D97706;"></div>
                                        </div>
                                    </div>

                                </div>

                                <!-- Lucky Elements & Astrological Remedy -->
                                <div class="rashi-bottom-grid">
                                    
                                    <div class="lucky-pill-box">
                                        <div class="lucky-item">
                                            <span><i class="fas fa-palette" style="color: var(--primary);"></i> शुभ रंग:</span>
                                            <strong><?= $r['lucky_color'] ?></strong>
                                        </div>
                                        <div class="lucky-item">
                                            <span><i class="fas fa-dice" style="color: #0284C7;"></i> शुभ अंक:</span>
                                            <strong><?= $r['lucky_number'] ?></strong>
                                        </div>
                                        <div class="lucky-item">
                                            <span><i class="fas fa-clock" style="color: #16A34A;"></i> शुभ समय:</span>
                                            <span style="font-size: 0.82rem; font-weight: 600; color: #334155;"><?= $r['lucky_time'] ?></span>
                                        </div>
                                    </div>

                                    <div class="upay-box">
                                        <div class="upay-icon">
                                            <i class="fas fa-om"></i>
                                        </div>
                                        <div class="upay-text">
                                            <h4>आज का अचूक उपाय (Astro Remedy)</h4>
                                            <p><?= $r['upay'] ?></p>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Related Astrology Articles -->
                <?php if (!empty($astrologyArticles)): ?>
                    <div style="margin-top: 45px;">
                        <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--text-heading); margin-bottom: 18px; border-left: 4px solid var(--primary); padding-left: 10px;">
                            धर्म, ज्योतिष एवं राशिफल समाचार (Astrology News)
                        </h3>
                        <div class="news-cards-grid">
                            <?php foreach ($astrologyArticles as $art): ?>
                                <article class="news-card">
                                    <div class="news-card-img">
                                        <img src="<?= sanitize($art['image_url']) ?>" alt="<?= sanitize($art['title']) ?>" loading="lazy">
                                    </div>
                                    <div class="news-card-body">
                                        <span class="category-tag"><?= sanitize($art['category_name']) ?></span>
                                        <h4 class="news-card-title" style="font-size: 1rem;">
                                            <a href="article.php?slug=<?= urlencode($art['slug']) ?>">
                                                <?= sanitize($art['title']) ?>
                                            </a>
                                        </h4>
                                        <div class="news-card-footer" style="margin-top: auto;">
                                            <span><i class="far fa-clock"></i> <?= timeAgoHindi($art['created_at']) ?></span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
