<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include("../config.php");

$url = trim($_POST['url'] ?? '');

function redirectWithImportError(string $message, string $url = ''): void
{
    $_SESSION['import_error'] = $message;
    $_SESSION['import_url'] = $url;

    header("Location: ../index.php#import-product");
    exit();
}

if ($url === '') {
    redirectWithImportError("Please paste a product URL.");
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    redirectWithImportError("Please paste a valid product URL.", $url);
}

$removeWords = [
    'buy',
    'online',
    'at',
    'best',
    'price',
    'in',
    'india',
    'with',
    'for',
    'gb',
    'ram',
    'storage',
    'color',
    'cm',
    'inch',
    'inches',
    'smartphone',
    'mobile',
    'phone',
    'android',
    '5g',
    '4g',
    'wifi',
    'bluetooth',
    'black',
    'blue',
    'green',
    'red',
    'white'
];

function normalizeProductTitle(string $title, array $removeWords): string
{
    $title = strtolower($title);
    $title = preg_replace('/\(.+\)/', '', $title);
    $title = preg_replace('/[^a-z0-9 ]/', '', $title);
    $title = str_replace($removeWords, '', $title);
    $title = preg_replace('/\s+/', ' ', $title);

    return trim($title);
}

function isAmazonUrl(string $url): bool
{
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

    return strpos($host, 'amazon.') !== false || $host === 'amzn.in' || str_ends_with($host, '.amzn.in');
}

function isFlipkartUrl(string $url): bool
{
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

    return strpos($host, 'flipkart.') !== false;
}

function amazonHostFromUrl(string $url): string
{
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

    if (strpos($host, 'amazon.') !== false) {
        return $host;
    }

    return 'www.amazon.in';
}

function amazonAsinFromUrl(string $url): string
{
    $patterns = [
        '/\/(?:dp|gp\/product|gp\/aw\/d)\/([A-Z0-9]{10})(?:[\/?]|$)/i',
        '/[?&](?:asin|ASIN)=([A-Z0-9]{10})(?:&|$)/',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $match)) {
            return strtoupper($match[1]);
        }
    }

    return '';
}

function amazonCandidateUrls(string $url): array
{
    $asin = amazonAsinFromUrl($url);

    if ($asin === '') {
        return [$url];
    }

    $host = amazonHostFromUrl($url);

    return array_values(array_unique([
        $url,
        "https://$host/dp/$asin?th=1&psc=1",
        "https://$host/gp/aw/d/$asin?th=1&psc=1",
        "https://$host/dp/$asin?language=en_IN",
    ]));
}

function isAmazonBlockedHtml(string $html): bool
{
    return stripos($html, 'captcha') !== false ||
        stripos($html, 'robot check') !== false ||
        stripos($html, 'automated access') !== false ||
        stripos($html, 'validateCaptcha') !== false ||
        stripos($html, 'Enter the characters you see below') !== false;
}

function scraperUrlForAmazon(string $url): string
{
    if (!defined('AMAZON_SCRAPER_URL_TEMPLATE') || AMAZON_SCRAPER_URL_TEMPLATE === '') {
        return '';
    }

    return str_replace(
        ['{url}', '{raw_url}'],
        [urlencode($url), $url],
        AMAZON_SCRAPER_URL_TEMPLATE
    );
}

function curlFetchPage(string $url): array
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-IN,en;q=0.9',
            'Cache-Control: no-cache',
            'DNT: 1',
            'Upgrade-Insecure-Requests: 1',
            'Cookie: i18n-prefs=INR; lc-acbin=en_IN; ubid-acbin=257-0000000-0000000',
        ],
    ]);

    $html = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'html' => is_string($html) ? $html : '',
        'effective_url' => $effectiveUrl ?: $url,
        'source' => 'direct',
        'status_code' => $statusCode,
        'error' => $error,
        'ok' => $html !== false && $html !== '' && $statusCode < 400,
    ];
}

function fetchProductPage(string $url): array
{
    $candidateUrls = isAmazonUrl($url) ? amazonCandidateUrls($url) : [$url];
    $lastPage = null;

    for ($i = 0; $i < count($candidateUrls); $i++) {
        $candidateUrl = $candidateUrls[$i];
        $page = curlFetchPage($candidateUrl);
        $lastPage = $page;

        if (isAmazonUrl($page['effective_url'])) {
            foreach (amazonCandidateUrls($page['effective_url']) as $extraUrl) {
                if (!in_array($extraUrl, $candidateUrls, true)) {
                    $candidateUrls[] = $extraUrl;
                }
            }
        }

        if (!$page['ok']) {
            continue;
        }

        if (isAmazonUrl($candidateUrl) || isAmazonUrl($page['effective_url'])) {
            if (isAmazonBlockedHtml($page['html'])) {
                continue;
            }
        }

        return $page;
    }

    $page = $lastPage ?? curlFetchPage($url);

    if (!isAmazonUrl($url) && !isAmazonUrl($page['effective_url'])) {
        if (!$page['ok']) {
            redirectWithImportError(
                "Could not fetch the product page. " . ($page['error'] ?: "HTTP {$page['status_code']}"),
                $url
            );
        }

        return $page;
    }

    if ($page['ok'] && !isAmazonBlockedHtml($page['html'])) {
        return $page;
    }

    $scraperUrl = scraperUrlForAmazon($url);

    if ($scraperUrl === '') {
        redirectWithImportError(
            "Amazon blocked this request with a captcha page. Add AMAZON_SCRAPER_URL_TEMPLATE in config.php to retry Amazon through your scraping provider.",
            $url
        );
    }

    $scraperPage = curlFetchPage($scraperUrl);

    if (!$scraperPage['ok']) {
        redirectWithImportError(
            "Amazon scraper fallback failed. " . ($scraperPage['error'] ?: "HTTP {$scraperPage['status_code']}"),
            $url
        );
    }

    if (isAmazonBlockedHtml($scraperPage['html'])) {
        redirectWithImportError("Amazon still returned a captcha page through the configured scraper provider.", $url);
    }

    $scraperPage['effective_url'] = $page['effective_url'];
    $scraperPage['source'] = 'scraper';

    return $scraperPage;
}

function domXPathFromHtml(string $html): DOMXPath
{
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($html);

    return new DOMXPath($dom);
}

function textFromXPath(DOMXPath $xpath, array $queries): string
{
    foreach ($queries as $query) {
        $node = $xpath->query($query)->item(0);

        if ($node) {
            $value = trim($node->nodeValue);

            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}

function attributeFromXPath(DOMXPath $xpath, array $queries, string $attribute): string
{
    foreach ($queries as $query) {
        $node = $xpath->query($query)->item(0);

        if ($node instanceof DOMElement) {
            $value = trim($node->getAttribute($attribute));

            if ($value !== '') {
                return $value;
            }
        }
    }

    return '';
}

function cleanPrice(string $price): int
{
    $price = html_entity_decode(strip_tags($price), ENT_QUOTES);
    $price = preg_replace('/[^\d.,]/', '', $price);

    if ($price === '') {
        return 0;
    }

    if (strpos($price, '.') !== false) {
        $price = str_replace(',', '', $price);

        return (int) round((float) $price);
    }

    $price = str_replace(',', '', $price);

    return (int) $price;
}

function isUsableAmazonPriceText(string $value): bool
{
    $value = strtolower(trim(html_entity_decode(strip_tags($value), ENT_QUOTES)));

    if ($value === '' || cleanPrice($value) <= 0) {
        return false;
    }

    $badFragments = ['%', 'emi', 'month', 'save', 'coupon', 'cashback', 'exchange'];

    foreach ($badFragments as $fragment) {
        if (strpos($value, $fragment) !== false) {
            return false;
        }
    }

    return true;
}

function amazonPriceFromScripts(string $html): string
{
    $patterns = [
        '/"priceToPay"\s*:\s*\{(?:(?!\}\s*,\s*").)*?"displayString"\s*:\s*"([^"]+)"/is',
        '/"priceToPay"\s*:\s*\{(?:(?!\}\s*,\s*").)*?"amount"\s*:\s*([0-9.]+)/is',
        '/"currentPrice"\s*:\s*\{(?:(?!\}\s*,\s*").)*?"priceString"\s*:\s*"([^"]+)"/is',
        '/"value"\s*:\s*\{[^{}]*"amount"\s*:\s*([0-9.]+)[^{}]*"currencyCode"\s*:\s*"INR"/is',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $match)) {
            $value = html_entity_decode(stripslashes($match[1]), ENT_QUOTES);

            if (isUsableAmazonPriceText($value)) {
                return $value;
            }
        }
    }

    return '';
}

function amazonPriceFromXPath(DOMXPath $xpath): string
{
    $queries = [
        "//*[@id='corePriceDisplay_desktop_feature_div']//*[@id='priceToPay']//*[contains(@class,'a-offscreen')]",
        "//*[@id='corePriceDisplay_desktop_feature_div']//*[contains(@class,'priceToPay')]//*[contains(@class,'a-offscreen')]",
        "//*[@id='corePrice_feature_div']//*[@id='priceToPay']//*[contains(@class,'a-offscreen')]",
        "//*[@id='corePrice_feature_div']//*[contains(@class,'priceToPay')]//*[contains(@class,'a-offscreen')]",
        "//*[@id='apex_desktop']//*[@id='priceToPay']//*[contains(@class,'a-offscreen')]",
        "//*[@id='tp_price_block_total_price_ww']//*[@id='priceToPay']//*[contains(@class,'a-offscreen')]",
        "//*[@id='corePriceDisplay_desktop_feature_div']//*[contains(@class,'a-price') and @data-a-color='price']//*[contains(@class,'a-offscreen')]",
        "//*[@id='corePrice_feature_div']//*[contains(@class,'a-price') and @data-a-color='price']//*[contains(@class,'a-offscreen')]",
        "//*[@id='apex_desktop']//*[contains(@class,'a-price') and @data-a-color='price']//*[contains(@class,'a-offscreen')]",
        "//*[@id='priceToPay']//*[contains(@class,'a-offscreen')]",
        "//*[@id='newBuyBoxPrice']",
        "//*[@id='price_inside_buybox']",
        "//*[@id='priceblock_ourprice']",
        "//*[@id='priceblock_dealprice']",
        "//*[@id='priceblock_saleprice']",
    ];

    foreach ($queries as $query) {
        $nodes = $xpath->query($query);

        foreach ($nodes as $node) {
            $value = trim($node->nodeValue);

            if (isUsableAmazonPriceText($value)) {
                return $value;
            }
        }
    }

    $whole = textFromXPath($xpath, [
        "//*[@id='corePriceDisplay_desktop_feature_div']//*[@id='priceToPay']//*[contains(@class,'a-price-whole')]",
        "//*[@id='corePriceDisplay_desktop_feature_div']//*[contains(@class,'a-price-whole')]",
        "//*[@id='corePrice_feature_div']//*[contains(@class,'a-price-whole')]",
        "//*[@id='apex_desktop']//*[contains(@class,'a-price-whole')]",
    ]);

    return $whole;
}

function extractAmazonProduct(string $html): array
{
    if (isAmazonBlockedHtml($html)) {
        redirectWithImportError(
            "Amazon blocked this request with a captcha page. Add AMAZON_SCRAPER_URL_TEMPLATE in config.php to retry through your scraping provider.",
            $GLOBALS['url'] ?? ''
        );
    }

    $xpath = domXPathFromHtml($html);

    $title = textFromXPath($xpath, [
        "//*[@id='productTitle']",
        "//span[contains(@class,'product-title-word-break')]",
        "//meta[@property='og:title']/@content",
    ]);

    $priceText = amazonPriceFromXPath($xpath);

    if (cleanPrice($priceText) <= 0) {
        $priceText = amazonPriceFromScripts($html);
    }

    $image = attributeFromXPath($xpath, [
        "//*[@id='landingImage']",
        "//img[@data-old-hires]",
        "//meta[@property='og:image']",
    ], 'src');

    if ($image === '') {
        $image = attributeFromXPath($xpath, ["//meta[@property='og:image']"], 'content');
    }

    return [
        'title' => $title,
        'price' => cleanPrice($priceText),
        'image' => $image,
    ];
}

function extractFlipkartProduct(string $html): array
{
    preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
    $title = trim(explode("|", html_entity_decode($titleMatch[1] ?? '', ENT_QUOTES))[0]);

    preg_match('/"price":"?([\d,]+)"?/', $html, $priceMatch);
    $price = cleanPrice($priceMatch[1] ?? '0');

    preg_match('/https:\/\/rukminim[^\"]+\.(jpg|jpeg|png)/', $html, $imageMatch);

    return [
        'title' => $title,
        'price' => $price,
        'image' => $imageMatch[0] ?? '',
    ];
}

$page = fetchProductPage($url);
$html = $page['html'];
$effectiveUrl = $page['effective_url'];
$isAmazon = isAmazonUrl($url) || isAmazonUrl($effectiveUrl);
$isFlipkart = isFlipkartUrl($url) || isFlipkartUrl($effectiveUrl);

if ($isAmazon) {
    $productData = extractAmazonProduct($html);
} elseif ($isFlipkart) {
    $productData = extractFlipkartProduct($html);
} else {
    redirectWithImportError("Only Amazon and Flipkart URLs are supported.", $url);
}

$title = $productData['title'];
$price = $productData['price'];
$image = $productData['image'];

if ($title === '' || $title === 'Unknown Product') {
    redirectWithImportError("Could not extract the product title from this page.", $url);
}

if ($price <= 0) {
    redirectWithImportError("Could not extract a valid product price from this page.", $url);
}

$normalizedTitle = normalizeProductTitle($title, $removeWords);

$existing = $conn->query("SELECT * FROM products");
$matchedProduct = null;

while ($row = $existing->fetch_assoc()) {
    $dbTitle = normalizeProductTitle($row['title'], $removeWords);

    similar_text($normalizedTitle, $dbTitle, $percent);

    if ($percent > 40) {
        $matchedProduct = $row;
        break;
    }
}

if ($matchedProduct) {
    $productId = (int) $matchedProduct['id'];

    if ($isAmazon) {
        $stmt = $conn->prepare("
            UPDATE products
            SET amazon_price = ?, link_amazon = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $price, $url, $productId);
        $stmt->execute();
    } elseif ($isFlipkart) {
        $stmt = $conn->prepare("
            UPDATE products
            SET flipkart_price = ?, link_flipkart = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $price, $url, $productId);
        $stmt->execute();
    }
} else {
    $amazonPrice = $isAmazon ? $price : 0;
    $flipkartPrice = $isFlipkart ? $price : 0;
    $amazonUrl = $isAmazon ? $url : '';
    $flipkartUrl = $isFlipkart ? $url : '';

    $stmt = $conn->prepare("
        INSERT INTO products
        (
            title,
            image,
            amazon_price,
            flipkart_price,
            link_amazon,
            link_flipkart
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssiiss",
        $title,
        $image,
        $amazonPrice,
        $flipkartPrice,
        $amazonUrl,
        $flipkartUrl
    );
    $stmt->execute();
}

header("Location: ../index.php");
exit();
