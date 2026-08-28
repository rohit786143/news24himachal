<?php
/**
 * Database & Application Configuration
 * News 24 Himachal
 */

if (!defined('APP_NAME')) {
    define('APP_NAME', 'News 24 Himachal');
}
define('APP_TAGLINE', 'हिमाचल प्रदेश का नंबर 1 हिंदी न्यूज़ पोर्टल');
define('APP_EMAIL', 'editor@news24himachal.com');
define('APP_PHONE', '+91 177 265 8900');
define('APP_ADDRESS', 'प्रेस एवेन्यू, माल रोड, शिमला, हिमाचल प्रदेश - 171001');
// Multi-Environment Detection (Local XAMPP vs Hostinger Live)
$isLocalEnv = (
    (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false))
    || (php_sapi_name() === 'cli' && empty($_SERVER['HTTP_HOST']))
);

if ($isLocalEnv) {
    define('APP_URL', 'http://localhost:8000');
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'news_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('APP_URL', 'https://news24hp.com');
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'u238667987_news24hp');
    define('DB_USER', 'u238667987_news24hp');
    define('DB_PASS', 'Rohit@40014');
}
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Check if database doesn't exist, redirect to installer if accessed via browser
            if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                if (php_sapi_name() !== 'cli' && basename($_SERVER['PHP_SELF']) !== 'install.php') {
                    header("Location: install.php");
                    exit;
                }
            }
            
            // Detailed error fallback
            die("<div style='font-family:sans-serif;max-width:600px;margin:50px auto;padding:24px;border-left:5px solid #E50914;background:#fff5f5;box-shadow:0 4px 12px rgba(0,0,0,0.08);border-radius:4px;'>
                <h2 style='color:#E50914;margin-top:0;'>डेटाबेस कनेक्शन त्रुटि (Database Connection Error)</h2>
                <p>डेटाबेस से कनेक्ट करने में असमर्थ। कृपया सुनिश्चित करें कि MySQL सेवा चालू है और डेटाबेस सेटअप पूरा हो चुका है।</p>
                <p><strong>Error Details:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <a href='install.php' style='display:inline-block;background:#E50914;color:#fff;padding:10px 18px;text-decoration:none;font-weight:bold;border-radius:4px;margin-top:10px;'>1-Click Database Setup Run करें &rarr;</a>
            </div>");
        }
    }

    return $pdo;
}