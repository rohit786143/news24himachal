SET FOREIGN_KEY_CHECKS = 0;

-- =======================================================
-- Database Schema for News 24 Himachal
-- Character Set: utf8mb4 (Full Unicode & Devanagari Hindi support)
-- =======================================================

-- Drop existing tables
DROP TABLE IF EXISTS `notification_deliveries`;
DROP TABLE IF EXISTS `bulletin_updates`;
DROP TABLE IF EXISTS `manual_notifications`;
DROP TABLE IF EXISTS `live_bulletins`;
DROP TABLE IF EXISTS `subscribers`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

-- --------------------------------------------------------
-- Table 1: users (Admins & Editors - Parent Table)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    `designation` VARCHAR(150) DEFAULT 'संपादकीय प्रमुख',
    `location` VARCHAR(150) DEFAULT 'शिमला, हिमाचल प्रदेश',
    `avatar` VARCHAR(500) NULL,
    `bio` TEXT NULL,
    `social_twitter` VARCHAR(255) NULL,
    `social_facebook` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_username` (`username`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 2: settings (Key-Value System Settings)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` LONGTEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 3: categories (Supports Parent & Child Categories)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NULL DEFAULT NULL,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `display_order` INT DEFAULT 0,
    `is_nav` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 4: pages (Static CMS Pages)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `content` LONGTEXT NOT NULL,
    `meta_description` VARCHAR(300) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 5: contacts (Messages from contact form)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 6: subscribers (Device-Based 1-Click Subscribers)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NOT NULL UNIQUE,
    `device_type` VARCHAR(50) DEFAULT 'Desktop',
    `device_name` VARCHAR(150) NULL,
    `browser` VARCHAR(100) NULL,
    `os` VARCHAR(100) NULL,
    `ip_address` VARCHAR(50) NULL,
    `user_agent` TEXT NULL,
    `status` ENUM('active', 'inactive', 'unsubscribed') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sub_device` (`device_id`),
    INDEX `idx_sub_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 7: live_bulletins (Live Stream / YouTube Bulletin Embeds)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `live_bulletins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `video_url` VARCHAR(500) NOT NULL,
    `is_live` TINYINT(1) DEFAULT 0,
    `description` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 8: news (Articles - Child Table of Categories)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `subcategory_id` INT NULL DEFAULT NULL,
    `title` VARCHAR(300) NOT NULL,
    `slug` VARCHAR(300) NOT NULL UNIQUE,
    `excerpt` TEXT NULL,
    `content` LONGTEXT NOT NULL,
    `image_url` VARCHAR(600) NOT NULL,
    `author` VARCHAR(100) DEFAULT 'संपादकीय टीम (News 24 Himachal)',
    `views` INT DEFAULT 0,
    `is_breaking` TINYINT(1) DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_trending` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_news_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    INDEX `idx_news_breaking` (`is_breaking`),
    INDEX `idx_news_created` (`created_at`),
    INDEX `idx_news_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 9: manual_notifications (Admin-Dispatched Push Alerts)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `manual_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `news_id` INT NULL DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `image_url` VARCHAR(500) NULL,
    `badge_text` VARCHAR(50) DEFAULT 'ताज़ा खबर',
    `sent_by` VARCHAR(100) DEFAULT 'Admin',
    `recipient_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_manual_notif_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE SET NULL,
    INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 10: notification_deliveries (Tracking per device)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_deliveries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `notification_id` INT NOT NULL,
    `device_id` VARCHAR(100) NOT NULL,
    `delivered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `clicked_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uniq_dev_notif` (`notification_id`, `device_id`),
    INDEX `idx_deliv_dev` (`device_id`),
    CONSTRAINT `fk_deliv_notif` FOREIGN KEY (`notification_id`) REFERENCES `manual_notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table 11: bulletin_updates (Real-time Live Timeline Updates)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bulletin_updates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bulletin_id` INT NOT NULL,
    `timestamp_label` VARCHAR(50) NOT NULL,
    `headline` TEXT NOT NULL,
    `badge_type` VARCHAR(50) DEFAULT 'breaking',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (`bulletin_id`),
    CONSTRAINT `fk_bulletin_updates_bulletin` FOREIGN KEY (`bulletin_id`) REFERENCES `live_bulletins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- Seed Data: Categories & Subcategories (Explicit IDs & is_nav)
-- =======================================================

-- 1. ब्रेकिंग न्यूज़ (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (1, NULL, 'ब्रेकिंग न्यूज़', 'breaking-news', 1, 1);

-- 2. राजनीति (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (2, NULL, 'राजनीति', 'rajniti', 2, 1);

-- 3. हिमाचल दर्शन (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (3, NULL, 'हिमाचल दर्शन', 'himachal-darshan', 3, 1);
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES
(18, 3, 'पर्यटन', 'tourism', 1, 0),
(19, 3, 'कला एवं संस्कृति', 'art-culture', 2, 0),
(20, 3, 'मेले व उत्सव', 'fairs-festivals', 3, 0),
(21, 3, 'देव लोक', 'dev-lok', 4, 0),
(22, 3, 'हमारे देवालय', 'temples', 5, 0),
(23, 3, 'हमारे देवी-देवता', 'deities', 6, 0),
(24, 3, 'हमारी देव परम्पराएं', 'traditions', 7, 0);

-- 4. मनोरंजन (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (4, NULL, 'मनोरंजन', 'manoranjan', 4, 1);
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES
(26, 4, 'मनोरंजन समाचार', 'entertainment-news', 1, 0),
(27, 4, 'हमारे कलाकार', 'our-artists', 2, 0);

-- 5. खेल (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (5, NULL, 'खेल', 'khel', 5, 1);

-- 6. राशिफल (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (6, NULL, 'राशिफल', 'rashiphal', 6, 1);

-- 7. क्राइम (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (7, NULL, 'क्राइम', 'crime', 7, 1);

-- 8. देश (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (8, NULL, 'देश', 'desh', 8, 1);

-- 9. दुनिया (is_nav = 1)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (9, NULL, 'दुनिया', 'duniya', 9, 1);

-- 10. हिमाचल न्यूज़ (is_nav = 0)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES (10, NULL, 'हिमाचल न्यूज़', 'himachal-news', 10, 0);
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `display_order`, `is_nav`) VALUES
(11, 10, 'शिमला', 'shimla', 1, 0),
(12, 10, 'कांगड़ा', 'kangra', 2, 0),
(13, 10, 'मंडी', 'mandi', 3, 0),
(14, 10, 'हमीरपुर', 'hamirpur', 4, 0),
(15, 10, 'सोलन', 'solan', 5, 0),
(16, 10, 'सिरमौर', 'sirmaur', 6, 0),
(17, 10, 'चंबा', 'chamba', 7, 0),
(25, 10, 'कुल्लू', 'kullu', 8, 0),
(36, 10, 'बिलासपुर', 'bilaspur', 9, 0),
(37, 10, 'ऊना', 'una', 10, 0),
(38, 10, 'किन्नौर', 'kinnaur', 11, 0),
(39, 10, 'लाहौल-स्पीति', 'lahaul-spiti', 12, 0);

-- =======================================================
-- Seed Data: Static CMS Pages
-- =======================================================

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_description`) VALUES
('हमारे बारे में (About Us)', 'about', 
'<h2>निष्पक्ष, निर्भीक और सटीक पत्रकारिता</h2>
<p><strong>News 24 Himachal (न्यूज़ 24 हिमाचल)</strong> देवभूमि हिमाचल प्रदेश का अग्रणी और विश्वसनीय डिजिटल समाचार पोर्टल है। हमारा उद्देश्य हिमाचल के कोने-कोने—शिमला, कांगड़ा, मंडी, कुल्लू, चंबा, सिरमौर, सोलन, हमीरपुर से लेकर लाहौल-स्पीति और किन्नौर तक की हर महत्वपूर्ण खबर को सबसे पहले और प्रमाणिकता के साथ आप तक पहुंचाना है।</p>
<h3>हमारा विज़न</h3>
<p>हम केवल समाचार ही नहीं, बल्कि हिमाचल की समृद्ध संस्कृति, देव परंपराओं, पर्यटन स्थलों और हमारे लोक नायकों की वीर गाथाओं को विश्व पटल पर उजागर करने के लिए प्रतिबद्ध हैं।</p>
<h3>संपादकीय मूल्य</h3>
<ul>
  <li><strong>सत्यता और निष्पक्षता:</strong> किसी भी खबर को प्रसारित करने से पूर्व तथ्यों की गहन पड़ताल।</li>
  <li><strong>जनहित सर्वोपरि:</strong> जनता की समस्याओं को सरकार और प्रशासन तक मजबूती से पहुंचाना।</li>
  <li><strong>सांस्कृतिक संवर्धन:</strong> पहाड़ी भाषा, लोक कला और धरोहर का संरक्षण।</li>
</ul>',
'News 24 Himachal - हिमाचल प्रदेश का सबसे तेज़ और विश्वसनीय हिंदी न्यूज़ पोर्टल।'),

('अस्वीकरण (Disclaimer)', 'disclaimer',
'<h2>कानूनी अस्वीकरण (Legal Disclaimer)</h2>
<p>इस न्यूज़ पोर्टल (News 24 Himachal) पर प्रकाशित सभी समाचार, लेख, विचार और विश्लेषण सूचनात्मक एवं जनहित के उद्देश्यों से प्रकाशित किए जाते हैं।</p>
<h3>सटीकता और संपादन</h3>
<p>यद्यपि हमारी संपादकीय टीम हर खबर की प्रमाणिकता सुनिश्चित करने का हरसंभव प्रयास करती है, फिर भी किसी अनजाने त्रुटि या टाइपोग्राफिकल भूल के लिए पोर्टल उत्तरदायी नहीं होगा। पाठकों से अनुरोध है कि महत्वपूर्ण निर्णयों से पूर्व संबंधित सरकारी विभाग अथवा आधिकारिक विज्ञप्ति से पुष्टि अवश्य करें।</p>
<h3>कॉपीराइट और बौद्धिक संपदा</h3>
<p>पोर्टल पर मौजूद मूल सामग्री, लोगो और डिज़ाइन News 24 Himachal की बौद्धिक संपदा हैं। बिना लिखित अनुमति के व्यावसायिक उपयोग प्रतिबंधित है।</p>',
'News 24 Himachal portal disclaimer and legal editorial terms.'),

('गोपनीयता नीति (Privacy Policy)', 'privacy-policy',
'<h2>गोपनीयता नीति (Privacy Policy)</h2>
<p>News 24 Himachal पर हम अपने पाठकों की डिजिटल निजता का पूरा सम्मान करते हैं। यह नीति स्पष्ट करती है कि जब आप हमारी वेबसाइट का उपयोग करते हैं तो आपकी जानकारी किस प्रकार सुरक्षित रखी जाती है।</p>
<h3>एकत्र की जाने वाली जानकारी</h3>
<p>हम केवल न्यूज़लेटर सब्सक्रिप्शन और संपर्क फॉर्म के माध्यम से नाम एवं ईमेल जैसी आवश्यक जानकारी पाठक की सहमति से प्राप्त करते हैं। हम आपकी निजी जानकारी किसी तीसरे पक्ष के साथ साझा या विक्रय नहीं करते हैं।</p>
<h3>कुकीज़ नीति</h3>
<p>उपयोगकर्ता अनुभव को बेहतर बनाने और वेबसाइट के सुचारू संचालन के लिए मानक ब्राउज़र कुकीज़ का उपयोग किया जा सकता है।</p>',
'News 24 Himachal privacy policy regarding user information and cookie usage.'),

('नियम एवं शर्तें (Terms & Conditions)', 'terms',
'<h2>नियम एवं शर्तें (Terms of Service)</h2>
<p>हमारी वेबसाइट का उपयोग करने पर आप निम्नलिखित शर्तों से बंधे होने की सहमति देते हैं:</p>
<ol>
  <li>वेबसाइट की सामग्री का उपयोग केवल व्यक्तिगत और गैर-व्यावसायिक अध्ययन हेतु किया जा सकता है।</li>
  <li>कमेंट या संवाद अनुभाग में किसी भी प्रकार की अभद्र, गैर-कानूनी या भ्रामक टिप्पणी करना प्रतिबंधित है।</li>
  <li>पोर्टल प्रबंधन को किसी भी समय सामग्री या नियमों को अद्यतन करने का पूर्ण अधिकार सुरक्षित है।</li>
</ol>',
'News 24 Himachal Terms and conditions of service.');
-- =======================================================

-- =======================================================
-- News articles are seeded from seeds.php
-- =======================================================
