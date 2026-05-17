<?php
include("../config.php");
include("../components/header.php");

$id = $_GET['id'] ?? 0;

// Fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "Product not found";
    exit();
}

// Fetch price history
$history = $conn->prepare("SELECT * FROM price_history WHERE product_id = ? ORDER BY recorded_at ASC");
$history->bind_param("i", $id);
$history->execute();
$historyResult = $history->get_result();

$dates  = [];
$prices = [];
while ($row = $historyResult->fetch_assoc()) {
    $dates[]  = $row['recorded_at'];
    $prices[] = $row['price'];
}

// Check for price drop
$latestStmt = $conn->prepare("SELECT price FROM price_history WHERE product_id = ? ORDER BY recorded_at DESC LIMIT 1");
$latestStmt->bind_param("i", $id);
$latestStmt->execute();
$latest = $latestStmt->get_result()->fetch_assoc();

$previousStmt = $conn->prepare("SELECT price FROM price_history WHERE product_id = ? ORDER BY recorded_at DESC LIMIT 1 OFFSET 1");
$previousStmt->bind_param("i", $id);
$previousStmt->execute();
$previous = $previousStmt->get_result()->fetch_assoc();

$priceDropped = $previous && $latest['price'] < $previous['price'];

// AI trend prediction (last 5 prices)
$trendStmt = $conn->prepare("SELECT price FROM price_history WHERE product_id = ? ORDER BY recorded_at DESC LIMIT 5");
$trendStmt->bind_param("i", $id);
$trendStmt->execute();
$trendResult = $trendStmt->get_result();

$trendPrices = [];
while ($row = $trendResult->fetch_assoc()) {
    $trendPrices[] = $row['price'];
}

$prediction      = "⚖️ Stable pricing";
$predictionClass = "neutral";

if (count($trendPrices) >= 2) {
    $newestPrice = $trendPrices[0];
    $oldestPrice = end($trendPrices);

    if ($newestPrice < $oldestPrice) {
        $prediction      = "📉 Price likely to drop further";
        $predictionClass = "drop";
    } elseif ($newestPrice > $oldestPrice) {
        $prediction      = "🔥 Prices are rising — buy soon";
        $predictionClass = "rise";
    }
}

$amazonPrice = (int) $product['amazon_price'];
$flipkartPrice = (int) $product['flipkart_price'];

if ($amazonPrice > 0 && $flipkartPrice > 0) {
    $bestDeal = ($amazonPrice <= $flipkartPrice) ? 'amazon' : 'flipkart';
    $savings = abs($amazonPrice - $flipkartPrice);
} elseif ($amazonPrice > 0) {
    $bestDeal = 'amazon';
    $savings = 0;
} elseif ($flipkartPrice > 0) {
    $bestDeal = 'flipkart';
    $savings = 0;
} else {
    $bestDeal = '';
    $savings = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .pred-drop  { color: #0F6E56; }
        .pred-rise  { color: #A32D2D; }
        .pred-neutral { color: #BA7517; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="max-w-5xl mx-auto p-6 md:p-10 relative">
    <div class="rounded-2xl shadow p-6 md:p-10" relative>

        <div class="grid md:grid-cols-2 gap-10">

            <!-- Image -->
            <img
                src="<?= htmlspecialchars($product['image']) ?>"
                alt="<?= htmlspecialchars($product['title']) ?>"
                class="rounded-xl w-full object-cover aspect-square border border-gray-100">

            <!-- Details -->
            <div>

                <?php if ($priceDropped): ?>
                <div class="inline-flex items-center gap-1 text-sm bg-green-100 text-green-800 px-3 py-1 rounded-full mb-3">
                    ↓ Price dropped
                </div>
                <?php endif; ?>

                <h1 class="text-2xl font-semibold mb-1">
                    <?= htmlspecialchars($product['title']) ?>
                </h1>

                <p class="text-sm text-gray-500 mb-4">
                    Amazon: <span class="font-medium text-white">₹<?= number_format($amazonPrice) ?></span>
                    &nbsp;·&nbsp;
                    Flipkart: <span class="font-medium text-white">₹<?= number_format($flipkartPrice) ?></span>
                </p>

                <?php if ($priceDropped): ?>
                <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-xl mb-4">
                    ↘ Price dropped! Now ₹<?= number_format($latest['price']) ?>
                </div>
                <?php endif; ?>

                <!-- AI Prediction -->
                <div class="bg- border border-gray-200 rounded-xl px-4 py-3 mb-5">
                    <p class="text-xs text-gray-400 mb-1">AI deal prediction</p>
                    <p class="text-base font-medium pred-<?= $predictionClass ?>">
                        <?= $prediction ?>
                    </p>
                </div>

                <!-- Deal Cards -->
                <div class="grid grid-cols-2 gap-3 mb-5">
                  <!-- Amazon -->
                    <div class="<?= $bestDeal === 'amazon' ? 'border-2 border-blue-400' : 'border border-gray-200' ?> rounded-2xl p-4">
                        <?php if ($bestDeal === 'amazon'): ?>
                        <p class="text-xs font-medium text-blue-500 mb-1">🏆 Best deal</p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mb-1">Amazon</p>
                        <p class="text-2xl font-semibold mb-1">₹<?= number_format($amazonPrice) ?></p>
                        <?php if ($bestDeal === 'amazon' && $savings > 0): ?>
                        <p class="text-xs text-green-600 mb-3">Save ₹<?= number_format($savings) ?></p>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($product['link_amazon']) ?>" target="_blank" rel="noopener noreferrer"
                        class="inline-block text-sm border border-yellow-500 text-yellow-700 px-4 py-2 rounded-xl hover:bg-yellow-50 transition">
                            Visit Amazon ↗
                        </a>
                    </div>

                    <!-- Flipkart -->
                    <div class="<?= $bestDeal === 'flipkart' ? 'border-2 border-blue-400' : 'border border-gray-200' ?> rounded-2xl p-4">
                        <?php if ($bestDeal === 'flipkart'): ?>
                        <p class="text-xs font-medium text-blue-500 mb-1">🏆 Best deal</p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mb-1">Flipkart</p>
                        <p class="text-2xl font-semibold mb-1">₹<?= number_format($flipkartPrice) ?></p>
                        <?php if ($bestDeal === 'flipkart' && $savings > 0): ?>
                        <p class="text-xs text-green-600 mb-3">Save ₹<?= number_format($savings) ?></p>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($product['link_flipkart']) ?>" target="_blank" rel="noopener noreferrer"
                        class="inline-block text-sm border border-blue-500 text-blue-700 px-4 py-2 rounded-xl hover:bg-blue-50 transition">
                            Visit Flipkart ↗
                        </a>
                    </div>

                </div>

                <!-- Wishlist -->
                <form method="POST" action="add_to_wishlist.php">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <button type="submit"
                            class="flex items-center gap-2 text-sm border border-gray-200 px-4 py-2 rounded-xl hover:bg-gray-50 transition">
                        ♥ Add to wishlist
                    </button>
                </form>

            </div>
        </div>

        <!-- Price History Chart -->
        <div class="mt-10 bg-white backdrop-blur-xl border border-gray-200 rounded-2xl p-6">
            <h2 class="text-base font-medium mb-4">Price history</h2>
            <div class="relative w-full" style="height: 260px;">
                <canvas id="priceChart"
                        role="img"
                        aria-label="Line chart showing price history for this product over time">
                </canvas>
            </div>
        </div>

    </div>
</div>

<script>
new Chart(document.getElementById('priceChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'Price (₹)',
            data: <?= json_encode($prices) ?>,
            borderColor: '#0F6E56',
            backgroundColor: 'rgba(15,110,86,0.08)',
            borderWidth: 1.5,
            pointRadius: 3,
            fill: true,
            tension: 0.35
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => '₹' + ctx.parsed.y.toLocaleString('en-IN')
                }
            }
        },
        scales: {
            x: { ticks: { autoSkip: false, maxRotation: 45 } },
            y: {
                ticks: {
                    callback: v => '₹' + (v / 1000).toFixed(0) + 'k'
                }
            }
        }
    }
});
</script>

<?php include("../components/footer.php"); ?>
</body>
</html>
