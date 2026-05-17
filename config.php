<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "deal_detective");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: configure a scraping provider for Amazon pages that return captcha.
// Example format: https://your-scraper-service.example/?url={url}
// Use {url} for urlencode($url), or {raw_url} if your provider expects the raw URL.
if (!defined('AMAZON_SCRAPER_URL_TEMPLATE')) {
    define('AMAZON_SCRAPER_URL_TEMPLATE', '');
}
?>
