<?php
/**
 * Reset All News Views to Zero (0)
 * News 24 Himachal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Check Admin Access if accessed via web browser
if (php_sapi_name() !== 'cli') {
    if (empty($_SESSION['admin_user'])) {
        header("Location: login.php");
        exit;
    }
}

try {
    $pdo->exec("UPDATE `news` SET `views` = 0");
    $totalReset = $pdo->query("SELECT COUNT(*) FROM `news`")->fetchColumn();
    $msg = "सफलता: सभी {$totalReset} खबरों के पाठक व्यूज 0 पर रीसेट कर दिए गए हैं। अब सिर्फ नए लाइव व्यूज काउंट होंगे!";

    if (php_sapi_name() === 'cli') {
        echo "$msg\n";
        exit;
    }

    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_type'] = "success";
    header("Location: index.php");
    exit;
} catch (PDOException $e) {
    $err = "त्रुटि: " . $e->getMessage();
    if (php_sapi_name() === 'cli') {
        echo "$err\n";
        exit;
    }
    $_SESSION['flash_message'] = $err;
    $_SESSION['flash_type'] = "danger";
    header("Location: index.php");
    exit;
}
