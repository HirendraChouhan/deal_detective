<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("config.php");

function cleanAmazonPrice($price)
{
    $price = html_entity_decode(strip_tags($price), ENT_QUOTES);
    $price = preg_replace('/[^\d.,]/', '', $price);

    if($price === ''){
        return 0;
    }

    if(strpos($price, '.') !== false){
        $price = str_replace(',', '', $price);

        return (int) round((float) $price);
    }

    $price = str_replace(',', '', $price);

    return (int) $price;
}

function isUsableAmazonPriceText($value)
{
    $value = strtolower(trim(html_entity_decode(strip_tags($value), ENT_QUOTES)));

    if($value === '' || cleanAmazonPrice($value) <= 0){
        return false;
    }

    $badFragments = ['%', 'emi', 'month', 'save', 'coupon', 'cashback', 'exchange'];

    foreach($badFragments as $fragment){
        if(strpos($value, $fragment) !== false){
            return false;
        }
    }

    return true;
}

function isAmazonBlockedHtml($html)
{
    return stripos($html, 'captcha') !== false ||
        stripos($html, 'robot check') !== false ||
        stripos($html, 'automated access') !== false ||
        stripos($html, 'validateCaptcha') !== false ||
        stripos($html, 'Enter the characters you see below') !== false;
}

function scraperUrlForAmazon($url)
{
    if(!defined('AMAZON_SCRAPER_URL_TEMPLATE') || AMAZON_SCRAPER_URL_TEMPLATE === ''){
        return '';
    }

    return str_replace(
        ['{url}', '{raw_url}'],
        [urlencode($url), $url],
        AMAZON_SCRAPER_URL_TEMPLATE
    );
}

function amazonHostFromUrl($url)
{
    $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

    if(strpos($host, 'amazon.') !== false){
        return $host;
    }

    return 'www.amazon.in';
}

function amazonAsinFromUrl($url)
{
    $patterns = [
        '/\/(?:dp|gp\/product|gp\/aw\/d)\/([A-Z0-9]{10})(?:[\/?]|$)/i',
        '/[?&](?:asin|ASIN)=([A-Z0-9]{10})(?:&|$)/',
    ];

    foreach($patterns as $pattern){
        if(preg_match($pattern, $url, $match)){
            return strtoupper($match[1]);
        }
    }

    return '';
}

function amazonCandidateUrls($url)
{
    $asin = amazonAsinFromUrl($url);

    if($asin === ''){
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

function extractAmazonPrice($html)
{
    $patterns = [
        '/"priceToPay"\s*:\s*\{(?:(?!\}\s*,\s*").)*?"displayString"\s*:\s*"([^"]+)"/is',
        '/"priceToPay"\s*:\s*\{(?:(?!\}\s*,\s*").)*?"amount"\s*:\s*([0-9.]+)/is',
        '/"currentPrice"\s*:\s*\{(?:(?!\}\s*,\s*").)*?"priceString"\s*:\s*"([^"]+)"/is',
    ];

    $sectionIds = [
        'corePriceDisplay_desktop_feature_div',
        'corePrice_feature_div',
        'apex_desktop',
        'tp_price_block_total_price_ww',
    ];

    foreach($sectionIds as $sectionId){
        if(preg_match('/<[^>]+id="' . preg_quote($sectionId, '/') . '"[^>]*>(.*?)<\/div>\s*<\/div>/is', $html, $sectionMatch)){
            preg_match_all('/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>(.*?)<\/span>/is', $sectionMatch[1], $matches);

            foreach($matches[1] as $match){
                $price = cleanAmazonPrice($match);

                if($price > 0 && isUsableAmazonPriceText($match)){
                    return $price;
                }
            }

            if(preg_match('/priceToPay.*?a-price-whole[^>]*>(.*?)</is', $sectionMatch[1], $wholeMatch)){
                return cleanAmazonPrice($wholeMatch[1]);
            }
        }
    }

    foreach($patterns as $pattern){
        if(preg_match($pattern, $html, $match)){
            $value = stripslashes($match[1]);
            $price = cleanAmazonPrice($value);

            if($price > 0 && isUsableAmazonPriceText($value)){
                return $price;
            }
        }
    }

    preg_match('/id="(?:newBuyBoxPrice|price_inside_buybox|priceblock_ourprice|priceblock_dealprice|priceblock_saleprice)"[^>]*>(.*?)</is', $html, $idMatch);

    if(!empty($idMatch[1])){
        return cleanAmazonPrice($idMatch[1]);
    }

    preg_match('/a-price-whole[^>]*>(.*?)</is', $html, $wholeMatch);

    return cleanAmazonPrice($wholeMatch[1] ?? 0);
}

$productsResult = $conn->query("
    SELECT *
    FROM products
");

$products = [];

while($row = $productsResult->fetch_assoc()){

    $products[] = $row;
}

foreach($products as $product){
    echo "<hr>";

    echo "PROCESSING: ";

    echo $product['title'];

    echo "<br>";

    echo "<h2>" . $product['title'] . "</h2>";
    // ==========================
// AMAZON UPDATE
// ==========================

if(!empty($product['link_amazon'])){
    echo "Checking Amazon...<br>";
    $url = $product['link_amazon'];

    $fetchAmazonHtml = function($fetchUrl) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $fetchUrl,
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
        $curlError = curl_error($ch);
        curl_close($ch);

        return [$html, $statusCode, $curlError, $effectiveUrl ?: $fetchUrl];
    };

    $candidateUrls = amazonCandidateUrls($url);
    $html = '';
    $statusCode = 0;
    $curlError = '';

    for($i = 0; $i < count($candidateUrls); $i++){
        [$html, $statusCode, $curlError, $effectiveUrl] = $fetchAmazonHtml($candidateUrls[$i]);

        $effectiveAsin = amazonAsinFromUrl($effectiveUrl);

        if($effectiveAsin !== ''){
            foreach(amazonCandidateUrls($effectiveUrl) as $extraUrl){
                if(!in_array($extraUrl, $candidateUrls, true)){
                    $candidateUrls[] = $extraUrl;
                }
            }
        }

        if($html !== false && $html !== '' && $statusCode < 400 && !isAmazonBlockedHtml($html)){
            break;
        }
    }

    echo "Amazon HTML fetched<br>";

    if($html === false || $html === '' || $statusCode >= 400 || isAmazonBlockedHtml($html)){
        $scraperUrl = scraperUrlForAmazon($url);

        if($scraperUrl === ''){
            echo "Amazon direct fetch failed or was blocked. Configure AMAZON_SCRAPER_URL_TEMPLATE in config.php.<br>";
            continue;
        }

        [$html, $statusCode, $curlError] = $fetchAmazonHtml($scraperUrl);
        echo "Amazon scraper fallback fetched<br>";

        if($html === false || $html === '' || $statusCode >= 400 || isAmazonBlockedHtml($html)){
            echo "Amazon scraper fallback failed: " . ($curlError ?: "HTTP $statusCode or captcha") . "<br>";
            continue;
        }
    }

    echo "Extracting Amazon price<br>";
    $price = extractAmazonPrice($html);

    if($price > 0){

        $conn->query("
            UPDATE products
            SET amazon_price = '$price'
            WHERE id = '{$product['id']}'
        ");

        echo "Amazon Updated: ₹$price <br>";

        // SAVE HISTORY

        $conn->query("
            INSERT INTO price_history
            (product_id, price)
            VALUES
            ('{$product['id']}', '$price')
        ");
    }
}
// ==========================
// FLIPKART UPDATE
// ==========================

if(!empty($product['link_flipkart'])){
    echo "Checking Flipkart...<br>";
    $url = $product['link_flipkart'];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($ch, CURLOPT_USERAGENT,
    "Mozilla/5.0");

    $html = curl_exec($ch);
    echo "Flipkart HTML fetched<br>";
    curl_close($ch);
    echo "Extracting Flipkart price<br>";
    preg_match(
        '/"price":"?([\d,]+)"?/',
        $html,
        $matches
    );

    $price = $matches[1] ?? 0;

    $price = str_replace(",", "", $price);

    if($price > 0){

        $conn->query("
            UPDATE products
            SET flipkart_price = '$price'
            WHERE id = '{$product['id']}'
        ");

        echo "Flipkart Updated: ₹$price <br>";

        // SAVE HISTORY

        $conn->query("
            INSERT INTO price_history
            (product_id, price)
            VALUES
            ('{$product['id']}', '$price')
        ");
    }
}
}
