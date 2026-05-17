<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("config.php");

function cleanAmazonPrice($price)
{
    return preg_replace('/[^\d]/', '', $price);
}

function extractAmazonPrice($html)
{
    $sectionIds = [
        'corePriceDisplay_desktop_feature_div',
        'corePrice_feature_div',
        'apex_desktop',
    ];

    foreach($sectionIds as $sectionId){
        if(preg_match('/<[^>]+id="' . preg_quote($sectionId, '/') . '"[^>]*>(.*?)<\/div>\s*<\/div>/is', $html, $sectionMatch)){
            preg_match_all('/<span[^>]*class="[^"]*a-offscreen[^"]*"[^>]*>(.*?)<\/span>/is', $sectionMatch[1], $matches);

            foreach($matches[1] as $match){
                $price = cleanAmazonPrice($match);

                if($price > 0){
                    return $price;
                }
            }

            if(preg_match('/a-price-whole[^>]*>(.*?)</is', $sectionMatch[1], $wholeMatch)){
                return cleanAmazonPrice($wholeMatch[1]);
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
    $curlError = curl_error($ch);
    echo "Amazon HTML fetched<br>";
    curl_close($ch);

    if($html === false || $html === '' || $statusCode >= 400){
        echo "Amazon fetch failed: " . ($curlError ?: "HTTP $statusCode") . "<br>";
        continue;
    }

    if(
        stripos($html, 'captcha') !== false ||
        stripos($html, 'robot check') !== false ||
        stripos($html, 'automated access') !== false
    ){
        echo "Amazon blocked this request with a bot/captcha page.<br>";
        continue;
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
