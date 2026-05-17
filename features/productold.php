<?php
include("../config.php");
include("../components/header.php");

$id = $_GET['id'] ?? 0;

// fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

if(!$product){
    echo "Product not found";
    exit();
}
//fetch price history
$history = $conn->query("
    SELECT * FROM price_history 
    WHERE product_id = $id 
    ORDER BY recorded_at ASC
");

$dates = [];
$prices = [];

while($row = $history->fetch_assoc()){
    $dates[] = $row['recorded_at'];
    $prices[] = $row['price'];
}
// check for price drop
$latest = $conn->query("
    SELECT price FROM price_history 
    WHERE product_id = $id 
    ORDER BY recorded_at DESC 
    LIMIT 1
")->fetch_assoc();

$previous = $conn->query("
    SELECT price FROM price_history 
    WHERE product_id = $id 
    ORDER BY recorded_at DESC 
    LIMIT 1 OFFSET 1
")->fetch_assoc();

$priceDropped = false;

if($previous && $latest['price'] < $previous['price']){
    $priceDropped = true;
}

?>
<?php

$trendQuery = $conn->query("
    SELECT price
    FROM price_history
    WHERE product_id = $id
    ORDER BY recorded_at DESC
    LIMIT 5
");

$trendPrices = [];

while($row = $trendQuery->fetch_assoc()){
    $trendPrices[] = $row['price'];
}

$prediction = "⚖️ Stable Pricing";
$predictionColor = "text-yellow-300";

if(count($trendPrices) >= 2){

    $latest = $trendPrices[0];

    $oldest = end($trendPrices);

    if($latest < $oldest){

        $prediction =
        "📉 Price likely to drop further";

        $predictionColor =
        "text-cyan-400";

    } elseif($latest > $oldest){

        $prediction =
        "🔥 Prices are rising — buy soon";

        $predictionColor =
        "text-red-400";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $product['title'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100">

<div class="max-w-5xl mx-auto p-10 bg-white rounded-xl shadow">

    <div class="grid md:grid-cols-2 gap-10">
        
        <!-- IMAGE -->
        <img src="<?= $product['image'] ?>" class="rounded-xl">


        <!-- DETAILS -->
        <div>
            <h1 class="text-3xl font-bold mb-4"><?= $product['title'] ?></h1>

            <p class="mb-4 text-lg">
                Amazon: <span class="font-semibold text-green-600">₹<?= $product['amazon_price'] ?></span><br>
                Flipkart: <span class="font-semibold text-blue-600">₹<?= $product['flipkart_price'] ?></span>
            </p>
                <?php if($priceDropped){ ?>
                <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                🔥 Price Dropped! Now ₹<?= $latest ?>
                </div>
                <div class="mt-6 glass rounded-2xl p-4">

                    <p class="text-sm text-gray-400 mb-2">
                        AI Deal Prediction
                    </p>

                    <h3 class="text-xl font-bold <?= $predictionColor ?>">

                        <?= $prediction ?>

                    </h3>

                </div>
                <?php } ?>
            
          <div class="grid md:grid-cols-2 gap-6 mt-10">

            <!-- AMAZON -->

            <div class="theme-card rounded-3xl p-6">

                <p class="text-gray-400 mb-2">
                    Amazon
                </p>

                <h2 class="text-4xl font-bold">

                    ₹<?= $product['amazon_price'] ?>

                </h2>

                <a
                    href="<?= $product['link_amazon'] ?>"
                    target="_blank"
                    class="mt-4 inline-block bg-yellow-500 hover:scale-105 transition px-6 py-3 rounded-2xl">

                    Visit Amazon

                </a>

            </div>

            <!-- FLIPKART -->

            <div class="theme-card rounded-3xl p-6 border border-cyan-400">

                <p class="text-cyan-400 mb-2 font-bold">
                    🏆 BEST DEAL
                </p>

                <p class="text-gray-400 mb-2">
                    Flipkart
                </p>

                <h2 class="text-4xl font-bold">

                    ₹<?= $product['flipkart_price'] ?>

                </h2>

                <p class="text-green-400 mt-2">

                    Save ₹<?= $product['amazon_price'] - $product['flipkart_price'] ?>

                </p>

                <a
                    href="<?= $product['link_flipkart'] ?>"
                    target="_blank"
                    class="mt-4 inline-block bg-cyan-500 hover:scale-105 transition px-6 py-3 rounded-2xl">

                    Visit Flipkart

                </a>

            </div>

        </div>

            <!-- WISHLIST -->
            <form method="POST" action="add_to_wishlist.php" class="mt-4">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button class="bg-red-500 text-white px-4 py-2 rounded-lg">
                    ❤️ Add to Wishlist
                </button>
            </form>

        </div>
    </div>
            <div class="mt-10">
            <h2 class="text-xl font-bold mb-4">Price History 📉</h2>

            <canvas id="priceChart"></canvas>
        </div>

        <script>
        const ctx = document.getElementById('priceChart');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [{
                    label: 'Price Trend',
                    data: <?= json_encode($prices) ?>,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true
            }
        });
        </script>

</div>



</body>
</html>
<?php include("../components/footer.php"); ?>