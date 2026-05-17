<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include("../config.php");

$url = trim($_POST['url'] ?? '');

if ($url === '') {
    die("No URL provided");
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    die("Invalid URL provided");
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

function fetchProductHtml(string $url): string
{
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-IN,en;q=0.9',
            'Cache-Control: no-cache',
        ],
    ]);

    $html = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($html === false || $html === '') {
        die("Could not fetch product page. cURL error: " . ($error ?: "empty response"));
    }

    if ($statusCode >= 400) {
        die("Could not fetch product page. HTTP status: $statusCode");
    }

    return $html;
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
    $price = preg_replace('/[^\d]/', '', $price);

    return (int) $price;
}

function amazonPriceFromXPath(DOMXPath $xpath): string
{
    $queries = [
        "//*[@id='corePriceDisplay_desktop_feature_div']//*[contains(@class,'a-price') and not(ancestor::*[contains(@class,'basisPrice')])]//*[contains(@class,'a-offscreen')]",
        "//*[@id='corePrice_feature_div']//*[contains(@class,'a-price') and not(ancestor::*[contains(@class,'basisPrice')])]//*[contains(@class,'a-offscreen')]",
        "//*[@id='apex_desktop']//*[contains(@class,'a-price') and not(ancestor::*[contains(@class,'basisPrice')])]//*[contains(@class,'a-offscreen')]",
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

            if (cleanPrice($value) > 0) {
                return $value;
            }
        }
    }

    $whole = textFromXPath($xpath, [
        "//*[@id='corePriceDisplay_desktop_feature_div']//*[contains(@class,'a-price-whole')]",
        "//*[@id='corePrice_feature_div']//*[contains(@class,'a-price-whole')]",
        "//*[@id='apex_desktop']//*[contains(@class,'a-price-whole')]",
        "//*[contains(@class,'a-price-whole')]",
    ]);

    return $whole;
}

function extractAmazonProduct(string $html): array
{
    if (
        stripos($html, 'captcha') !== false ||
        stripos($html, 'robot check') !== false ||
        stripos($html, 'automated access') !== false
    ) {
        die("Amazon blocked the scraper with a bot/captcha page. Use Amazon's Product Advertising API or a scraping service/proxy for reliable Amazon data.");
    }

    $xpath = domXPathFromHtml($html);

    $title = textFromXPath($xpath, [
        "//*[@id='productTitle']",
        "//span[contains(@class,'product-title-word-break')]",
        "//meta[@property='og:title']/@content",
    ]);

    $priceText = amazonPriceFromXPath($xpath);

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

$html = fetchProductHtml($url);
$isAmazon = stripos($url, "amazon") !== false;
$isFlipkart = stripos($url, "flipkart") !== false;

if ($isAmazon) {
    $productData = extractAmazonProduct($html);
} elseif ($isFlipkart) {
    $productData = extractFlipkartProduct($html);
} else {
    die("Only Amazon and Flipkart URLs are supported");
}

$title = $productData['title'];
$price = $productData['price'];
$image = $productData['image'];

if ($title === '' || $title === 'Unknown Product') {
    die("Could not extract the product title from this page.");
}

if ($price <= 0) {
    die("Could not extract a valid product price from this page.");
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
