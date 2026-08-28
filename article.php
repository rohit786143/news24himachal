<?php
/**
 * Article Details Page (article.php)
 * News 24 Himachal
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$slug = trim((string)($_GET['slug'] ?? ($_GET['id'] ?? '')));

$article = getArticleBySlug($pdo, $slug);

// Handle 404 if article not found
if (!$article) {
    header("HTTP/1.0 404 Not Found");
    $pageTitle = 'समाचार नहीं मिला - News 24 Himachal';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding: 80px 20px; text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3.5rem; color: #E50914; margin-bottom: 20px;"></i>
            <h1 style="font-size: 2rem; margin-bottom: 12px;">क्षमा करें, यह समाचार उपलब्ध नहीं है।</h1>
            <p style="color: #666; margin-bottom: 25px;">हो सकता है कि यह लिंक टूट गया हो या खबर हटा दी गई हो।</p>
            <a href="index.php" class="btn-primary">होम पेज पर लौटें &rarr;</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Increment View Count
incrementArticleViews($pdo, $article['id']);

// Fetch Related Articles
$relatedArticles = getRelatedNews($pdo, $article['category_id'], $article['id'], 3);

$pageTitle = sanitize($article['title']) . ' - News 24 Himachal';
$pageDescription = sanitize($article['excerpt'] ?? mb_substr(strip_tags($article['content']), 0, 160) . '...');
$readingTime = estimateReadingTime($article['content']);

// Set Dynamic Open Graph (OG) Meta Variables for WhatsApp & Social Sharing
$ogImage = $article['image_url'] ?? '';
$ogType = 'article';

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$ogUrl = $currentUrl;
$encodedUrl = urlencode($currentUrl);
$encodedTitle = urlencode($article['title']);

require_once __DIR__ . '/includes/header.php';
?>

<main class="main-layout">
    <div class="container content-grid">
        
        <!-- Main Article Column -->
        <article class="main-content-column">
            
            <header class="article-header">
                <!-- Breadcrumbs -->
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <a href="index.php">होम</a>
                    <span class="separator"><i class="fas fa-chevron-right"></i></span>
                    <a href="category.php?cat=<?= urlencode($article['category_slug']) ?>">
                        <?= sanitize($article['category_name']) ?>
                    </a>
                    <?php if (!empty($article['subcategory_name'])): ?>
                        <span class="separator"><i class="fas fa-chevron-right"></i></span>
                        <a href="category.php?cat=<?= urlencode($article['category_slug']) ?>&sub=<?= urlencode($article['subcategory_slug']) ?>">
                            <?= sanitize($article['subcategory_name']) ?>
                        </a>
                    <?php endif; ?>
                </nav>

                <!-- Category Tag -->
                <span class="category-tag">
                    <?= sanitize($article['subcategory_name'] ?? $article['category_name']) ?>
                </span>

                <!-- Article Headline -->
                <h1 class="article-main-title"><?= sanitize($article['title']) ?></h1>

                <!-- Meta Information Bar -->
                <?php
                $authorId = (int)($article['author_id'] ?? ($article['editor_id'] ?? 1));
                $authorAvatar = !empty($article['editor_avatar']) ? $article['editor_avatar'] : getAdminAvatar($pdo);
                $authorName = !empty($article['editor_name']) ? $article['editor_name'] : ($article['author'] ?? 'Sudhir Dutt Sharma');
                $authorDesignation = !empty($article['editor_designation']) ? $article['editor_designation'] : 'Chief Editor • News 24 Himachal';
                ?>
                <div class="article-meta-bar">
                    <a href="author.php?id=<?= $authorId ?>" class="meta-author-link" style="text-decoration: none; color: inherit; display: inline-flex;" title="<?= sanitize($authorName) ?> की प्रोफाइल एवं खबरें देखें">
                        <div class="meta-author-box" style="cursor: pointer; transition: transform 0.2s ease;">
                            <div class="author-avatar" style="overflow: hidden; padding: 0; background: #E2E8F0; border: 2px solid var(--primary-red); width: 44px; height: 44px; border-radius: 50%; box-shadow: 0 2px 6px rgba(229,9,20,0.25);">
                                <img src="<?= sanitize($authorAvatar) ?>" alt="<?= sanitize($authorName) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div>
                                <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 5px;">
                                    <span><?= sanitize($authorName) ?></span>
                                    <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.65rem; color: var(--primary-red);"></i>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= sanitize($authorDesignation) ?></div>
                            </div>
                        </div>
                    </a>

                    <div class="meta-info-list">
                        <span><i class="far fa-calendar-alt"></i> <?= formatHindiDate($article['created_at']) ?></span>
                        <span><i class="far fa-clock"></i> <?= $readingTime ?> मिनट का समय</span>
                        <span><i class="far fa-eye"></i> <?= number_format($article['views'] + 1) ?> व्यूज</span>
                    </div>
                </div>
            </header>

            <!-- Social Share Bar (Top) -->
            <div class="social-share-box">
                <div class="share-label">
                    <i class="fas fa-share-alt"></i> इस खबर को शेयर करें:
                </div>
                <div class="share-buttons">
                    <a href="https://api.whatsapp.com/send?text=<?= $encodedTitle ?>%20-%20<?= $encodedUrl ?>" target="_blank" rel="noopener" class="share-btn whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encodedUrl ?>" target="_blank" rel="noopener" class="share-btn facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?= $encodedTitle ?>&url=<?= $encodedUrl ?>" target="_blank" rel="noopener" class="share-btn twitter">
                        <i class="fab fa-x-twitter"></i> X / Twitter
                    </a>
                    <a href="https://t.me/share/url?url=<?= $encodedUrl ?>&text=<?= $encodedTitle ?>" target="_blank" rel="noopener" class="share-btn telegram">
                        <i class="fab fa-telegram-plane"></i> Telegram
                    </a>
                    <button class="share-btn copy copy-share-btn">
                        <i class="fas fa-link"></i> लिंक कॉपी करें
                    </button>
                </div>
            <!-- 🎙️ Compact Text-to-Speech (खबर सुनें) Audio Bar -->
            <div class="news-tts-compact" id="newsTtsPlayer">
                <!-- Small Round Play Button -->
                <button type="button" class="tts-compact-play-btn" id="ttsPlayBtn" title="यह खबर सुनें">
                    <i class="fas fa-play" id="ttsPlayIcon"></i>
                </button>

                <!-- Audio Info & Progress -->
                <div class="tts-compact-info">
                    <div class="tts-compact-title-row">
                        <span class="tts-compact-label"><i class="fas fa-headphones" style="color: var(--primary-red); font-size: 0.78rem;"></i> यह खबर सुनें</span>
                        <span class="tts-compact-status" id="ttsStatusText">~<?= $readingTime ?> मिनट</span>
                        <!-- Mini Wave Equalizer -->
                        <div class="tts-mini-waves" id="ttsWaveEqualizer">
                            <span></span><span></span><span></span><span></span>
                        </div>
                    </div>
                    <!-- Slim Progress Track -->
                    <div class="tts-compact-progress-track">
                        <div class="tts-compact-progress-fill" id="ttsProgressFill" style="width: 0%;"></div>
                    </div>
                </div>

                <!-- Small Control Actions -->
                <div class="tts-compact-actions">
                    <!-- Stop Button -->
                    <button type="button" class="tts-compact-icon-btn" id="ttsStopBtn" title="ऑडियो बंद करें" disabled>
                        <i class="fas fa-stop"></i>
                    </button>
                    <!-- Speed Select -->
                    <select id="ttsRateSelect" class="tts-compact-speed-select" title="बोलने की गति (Playback Speed)">
                        <option value="0.85">0.8x</option>
                        <option value="1.0" selected>1.0x</option>
                        <option value="1.25">1.2x</option>
                        <option value="1.5">1.5x</option>
                    </select>
                </div>
            </div>

            <!-- Featured Image -->
            <div class="article-featured-img">
                <img src="<?= sanitize($article['image_url']) ?>" alt="<?= sanitize($article['title']) ?>">
            </div>

            <!-- Article Body Content -->
            <div class="article-body-content">
                <?php if (!empty($article['excerpt'])): ?>
                    <p style="font-size: 1.25rem; font-weight: 500; color: #1F2937; border-left: 4px solid var(--primary-red); padding-left: 16px; margin-bottom: 24px; line-height: 1.7;">
                        <?= sanitize($article['excerpt']) ?>
                    </p>
                <?php endif; ?>

                <?= $article['content'] ?>
            </div>

            <!-- Author Bio Card Box -->
            <div style="background: #F8FAFC; border: 1.5px solid var(--border-color); border-radius: 12px; padding: 18px 20px; margin: 30px 0 20px; display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                <a href="author.php?id=<?= $authorId ?>">
                    <img src="<?= sanitize($authorAvatar) ?>" alt="<?= sanitize($authorName) ?>" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-red); box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                </a>
                <div style="flex-grow: 1; min-width: 220px;">
                    <div style="font-size: 0.76rem; font-weight: 800; text-transform: uppercase; color: var(--primary-red); letter-spacing: 0.5px;">लेखक परिचय (About the Reporter)</div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-primary); margin: 2px 0 4px;">
                        <a href="author.php?id=<?= $authorId ?>" style="color: inherit; text-decoration: none;">
                            <?= sanitize($authorName) ?>
                        </a>
                    </h3>
                    <p style="font-size: 0.86rem; color: var(--text-muted); line-height: 1.45; margin-bottom: 8px;">
                        <?= sanitize($article['editor_bio'] ?: 'हिमाचल प्रदेश के प्रमुख मामलों और स्थानीय विकास पर निरंतर ग्राउंड रिपोर्टिंग।') ?>
                    </p>
                    <a href="author.php?id=<?= $authorId ?>" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: 700; color: var(--primary-red); text-decoration: none;">
                        <span>लेखक की सभी खबरें पढ़ें</span> <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Article Footer Tags & Bottom Share -->
            <div style="padding: 20px 0; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="font-weight: 700; font-size: 0.9rem;"><i class="fas fa-tags"></i> टैग्स:</span>
                    <a href="category.php?cat=<?= urlencode($article['category_slug']) ?>" class="district-tab">
                        #<?= sanitize($article['category_name']) ?>
                    </a>
                    <?php if (!empty($article['subcategory_name'])): ?>
                        <a href="category.php?cat=<?= urlencode($article['category_slug']) ?>&sub=<?= urlencode($article['subcategory_slug']) ?>" class="district-tab">
                            #<?= sanitize($article['subcategory_name']) ?>
                        </a>
                    <?php endif; ?>
                    <span class="district-tab">#हिमाचल_अपडेट</span>
                </div>
            </div>

            <!-- Related News Section -->
            <?php if (!empty($relatedArticles)): ?>
                <section class="related-news-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-layer-group"></i> संबंधित समाचार (Related News)
                        </h2>
                    </div>
                    <div class="news-cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                        <?php foreach ($relatedArticles as $rel): ?>
                            <div class="news-card">
                                <div class="news-card-img" style="height: 150px;">
                                    <img src="<?= sanitize($rel['image_url']) ?>" alt="<?= sanitize($rel['title']) ?>" loading="lazy">
                                </div>
                                <div class="news-card-body">
                                    <h3 class="news-card-title" style="font-size: 0.92rem;">
                                        <a href="article.php?slug=<?= urlencode($rel['slug']) ?>">
                                            <?= sanitize($rel['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="news-card-footer">
                                        <span><i class="far fa-clock"></i> <?= timeAgoHindi($rel['created_at']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </article>

        <!-- Sidebar Column -->
        <div class="sidebar-column">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

    </div>
</main>

<!-- Text-to-Speech (खबर सुनें) JavaScript Engine -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const playBtn = document.getElementById('ttsPlayBtn');
    const stopBtn = document.getElementById('ttsStopBtn');
    const playIcon = document.getElementById('ttsPlayIcon');
    const playLabel = document.getElementById('ttsPlayLabel');
    const statusText = document.getElementById('ttsStatusText');
    const progressFill = document.getElementById('ttsProgressFill');
    const playerContainer = document.getElementById('newsTtsPlayer');
    const rateSelect = document.getElementById('ttsRateSelect');

    if (!('speechSynthesis' in window)) {
        if (playerContainer) {
            statusText.textContent = "आपका ब्राउज़र स्पीच सपोर्ट नहीं करता।";
            if (playBtn) playBtn.disabled = true;
        }
        return;
    }

    const synth = window.speechSynthesis;
    let sentences = [];
    let currentSentenceIndex = 0;
    let isPlaying = false;
    let isPaused = false;
    let hindiVoice = null;

    function loadVoices() {
        const voices = synth.getVoices();
        hindiVoice = voices.find(v => v.lang.includes('hi') || v.lang.includes('HI')) 
                  || voices.find(v => v.lang.includes('IN') || v.name.toLowerCase().includes('hindi'))
                  || voices.find(v => v.lang.includes('en-IN'))
                  || voices[0];
    }

    loadVoices();
    if (speechSynthesis.onvoiceschanged !== undefined) {
        speechSynthesis.onvoiceschanged = loadVoices;
    }

    function extractArticleSentences() {
        const title = document.querySelector('.article-main-title') ? document.querySelector('.article-main-title').innerText : '';
        const bodyElem = document.querySelector('.article-body-content');
        let bodyText = '';
        if (bodyElem) {
            const clone = bodyElem.cloneNode(true);
            const scripts = clone.querySelectorAll('script, style, .social-share-box, button');
            scripts.forEach(s => s.remove());
            bodyText = clone.innerText;
        }

        const fullText = title + "। " + bodyText;
        return fullText
            .split(/(?<=[।!?\.\n])\s+/)
            .map(s => s.trim())
            .filter(s => s.length > 2);
    }

    function speakCurrentSentence() {
        if (!isPlaying || currentSentenceIndex >= sentences.length) {
            finishSpeech();
            return;
        }

        const currentText = sentences[currentSentenceIndex];
        const utterance = new SpeechSynthesisUtterance(currentText);

        if (hindiVoice) {
            utterance.voice = hindiVoice;
        }
        utterance.lang = 'hi-IN';
        utterance.rate = parseFloat(rateSelect.value) || 1.0;
        utterance.pitch = 1.0;

        utterance.onend = function() {
            if (isPlaying && !isPaused) {
                currentSentenceIndex++;
                updateProgress();
                speakCurrentSentence();
            }
        };

        utterance.onerror = function(e) {
            console.log('TTS Error:', e);
            if (isPlaying && !isPaused) {
                currentSentenceIndex++;
                updateProgress();
                speakCurrentSentence();
            }
        };

        synth.speak(utterance);
    }

    function updateProgress() {
        if (sentences.length === 0) return;
        const pct = Math.min(100, Math.round(((currentSentenceIndex + 1) / sentences.length) * 100));
        progressFill.style.width = pct + '%';
        statusText.textContent = `खबर पढ़ी जा रही है... (${pct}% पूर्ण)`;
    }

    function startSpeech() {
        if (sentences.length === 0) {
            sentences = extractArticleSentences();
        }

        if (sentences.length === 0) {
            statusText.textContent = "सामग्री नहीं मिली।";
            return;
        }

        isPlaying = true;
        isPaused = false;
        playerContainer.classList.add('playing');
        playIcon.className = 'fas fa-pause';
        playBtn.title = 'ऑडियो रोकें (Pause)';
        stopBtn.disabled = false;
        updateProgress();
        speakCurrentSentence();
    }

    function pauseSpeech() {
        if (synth.speaking && !synth.paused) {
            synth.pause();
            isPaused = true;
            playerContainer.classList.remove('playing');
            playIcon.className = 'fas fa-play';
            playBtn.title = 'जारी रखें (Resume)';
            statusText.textContent = 'ऑडियो रोका गया (Paused)';
        }
    }

    function resumeSpeech() {
        if (synth.paused) {
            synth.resume();
            isPaused = false;
            playerContainer.classList.add('playing');
            playIcon.className = 'fas fa-pause';
            playBtn.title = 'ऑडियो रोकें (Pause)';
            updateProgress();
        } else {
            speakCurrentSentence();
        }
    }

    function stopSpeech() {
        synth.cancel();
        isPlaying = false;
        isPaused = false;
        currentSentenceIndex = 0;
        playerContainer.classList.remove('playing');
        playIcon.className = 'fas fa-play';
        playBtn.title = 'यह खबर सुनें (Play)';
        stopBtn.disabled = true;
        progressFill.style.width = '0%';
        statusText.textContent = 'रीसेट • प्ले दबाएं';
    }

    function finishSpeech() {
        synth.cancel();
        isPlaying = false;
        isPaused = false;
        currentSentenceIndex = 0;
        playerContainer.classList.remove('playing');
        playIcon.className = 'fas fa-rotate-left';
        playBtn.title = 'पुनः सुनें (Replay)';
        stopBtn.disabled = true;
        progressFill.style.width = '100%';
        statusText.textContent = '✓ वाचन पूर्ण हुआ';
    }

    if (playBtn) {
        playBtn.addEventListener('click', function() {
            if (!isPlaying) {
                startSpeech();
            } else if (isPaused) {
                resumeSpeech();
            } else {
                pauseSpeech();
            }
        });
    }

    if (stopBtn) {
        stopBtn.addEventListener('click', function() {
            stopSpeech();
        });
    }

    if (rateSelect) {
        rateSelect.addEventListener('change', function() {
            if (isPlaying && !isPaused) {
                synth.cancel();
                speakCurrentSentence();
            }
        });
    }

    window.addEventListener('beforeunload', function() {
        synth.cancel();
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
