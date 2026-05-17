<?php
include("config.php");
include("components/header.php");

$sort = $_GET['sort'] ?? '';

$sql = "SELECT * FROM products";

if($sort == "low"){
    $sql .= " ORDER BY LEAST(amazon_price, flipkart_price) ASC";
}

if($sort == "high"){
    $sql .= " ORDER BY GREATEST(amazon_price, flipkart_price) DESC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deal Detective</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="dark min-h-screen">
<div id="toast-container"
    class="fixed top-6 right-6 z-50 space-y-4">
</div>
<div class="fixed inset-0 -z-10 overflow-hidden">

    <div class="absolute w-96 h-96 bg-cyan-500/20 blur-3xl rounded-full top-10 left-10"></div>

    <div class="absolute w-96 h-96 bg-blue-500/20 blur-3xl rounded-full bottom-10 right-10"></div>

</div>

<!-- HERO -->
<section class="flex flex-col items-center justify-center text-center py-24 px-6">

    <h1 class="text-5xl md:text-7xl font-bold leading-tight max-w-4xl">
        Track Prices.  
        <span class="gradient-text">Save Money.</span>
    </h1>

    <p class="text-gray-400 mt-6 text-lg max-w-2xl">
        Compare Amazon & Flipkart prices, track drops,
        and never overpay again.
    </p>

    <!-- SEARCH -->
    <form method="GET"
    action="features/search.php"
    class="mt-10 w-full max-w-2xl relative">

        <!-- SEARCH BAR -->
        <div class="flex bg-white/10 backdrop-blur-lg border border-white/10 rounded-2xl overflow-hidden shadow-2xl float">

            <input
                id="searchInput"
                type="text"
                name="query"
                placeholder="Search products..."
                autocomplete="off"
                class="flex-1 bg-transparent px-6 py-4 outline-none text-inherit placeholder-gray-400">

            <button
                class="bg-cyan-500 hover:bg-cyan-400 transition px-8 font-semibold">

                Search

            </button>

        </div>

        <!-- LIVE SUGGESTIONS -->
        <div id="suggestions"
            class="hidden absolute left-0 top-full mt-3 w-full glass rounded-2xl overflow-hidden shadow-2xl z-[999]">
        </div>

    </form>
</section>
<!-- IMPORT PRODUCT -->

<div class="glass rounded-3xl p-6 mb-10">

    <h2 class="text-2xl font-bold mb-4">
        Import Product URL
    </h2>

    <form method="POST"
        action="features/import_product.php"
        class="flex flex-col md:flex-row gap-4">

        <input
            type="text"
            name="url"
            placeholder="Paste Amazon or Flipkart product URL..."
            class="flex-1 bg-white/10 px-6 py-4 rounded-2xl outline-none">

        <button
            class="bg-cyan-500 hover:bg-cyan-400 px-8 py-4 rounded-2xl">

            Import Product

        </button>

    </form>

</div>

<!-- TRENDING -->
<section class="px-6 md:px-16 pb-16">

    <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold">🔥 Trending Deals</h2>
    </div>

    <form method="GET" class="mb-10 flex gap-4">

    <select name="sort"
        class="bg-white/10 border border-white/10 rounded-xl px-4 py-3 text-inherit hover:scale-105 transition duration-300">

        <option value="">Sort By</option>
        <option value="low">Lowest Price</option>
        <option value="high">Highest Price</option>
    </select>

    <button class="bg-cyan-500 px-6 rounded-xl hover:scale-105 transition duration-300">
        Apply
    </button>

    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

    <?php while($p = $result->fetch_assoc()){

        $amazon = $p['amazon_price'];
        $flipkart = $p['flipkart_price'];
        
        $current = min($amazon, $flipkart);

        $historyQuery = $conn->query("
            SELECT AVG(price) as avg_price,
                MIN(price) as min_price
            FROM price_history
            WHERE product_id = {$p['id']}
        ");

        $stats = $historyQuery->fetch_assoc();

        $avgPrice = $stats['avg_price'] ?? $current;
        $lowestPrice = $stats['min_price'] ?? $current;

        $dealScore = "⚠️ Overpriced";
        $dealColor = "text-red-400";

        if($current <= $lowestPrice){
            $dealScore = "🔥 Excellent Deal";
            $dealColor = "text-green-400";
        }
        elseif($current < $avgPrice){
            $dealScore = "✅ Fair Price";
            $dealColor = "text-yellow-300";
        }

        $cheaper = $amazon < $flipkart
            ? "Amazon cheaper by ₹" . ($flipkart - $amazon)
            : "Flipkart cheaper by ₹" . ($amazon - $flipkart);
    ?>

        <!-- CARD -->
        
       <div class="theme-card border border-white/10 backdrop-blur-xl rounded-3xl overflow-hidden shadow-xl card-hover glow  card-enter">
            <img src="<?= $p['image'] ?>"
                class="w-full h-64 object-cover">

            <div class="p-6">

                <h3 class="text-2xl font-semibold">
                    <?= $p['title'] ?>
                </h3>

                <div class="mt-4 text-gray-300 space-y-1">
                    <p>Amazon: ₹<?= $amazon ?></p>
                    <p>Flipkart: ₹<?= $flipkart ?></p>
                </div>

                <!-- CHEAPER -->
                <div class="mt-4 inline-block bg-cyan-500/20 text-cyan-300 px-4 py-2 rounded-full text-sm">
                    <?= $cheaper ?>
                </div>

                <div class="mt-3 font-semibold <?= $dealColor ?>">
                    <?= $dealScore ?>
                </div>

                <!-- BUTTONS -->
                <div class="flex gap-3 mt-6">

                    <a href="features/product.php?id=<?= $p['id'] ?>"
                        class="flex-1 bg-cyan-500 hover:bg-cyan-400 text-center py-3 rounded-xl font-semibold transition">

                        View
                    </a>

                   <button
                        onclick="addToWishlist(<?= $p['id'] ?>, this)"
                        class="bg-white/10 hover:bg-white/20 px-4 rounded-xl  transition duration-300">
                        ❤️
                    </button>

                </div>

            </div>
        </div>

    <?php } ?>

    </div>
</section>
<script>

const searchInput =
document.getElementById("searchInput");

const suggestions =
document.getElementById("suggestions");

searchInput.addEventListener("input", async () => {

    const query = searchInput.value;

    console.log(query);

    if(query.length < 1){

        suggestions.classList.add("hidden");

        return;
    }

    const response = await fetch(
        `/deal_detective_v3/features/live_search.php?query=${query}`
    );

    const data = await response.text();

    suggestions.innerHTML = data;

    suggestions.classList.remove("hidden");
});


 function showToast(message, type="success"){

    const toast = document.createElement("div");

    let color = "bg-green-500";

    if(type === "error"){
        color = "bg-red-500";
    }

    toast.className =
        `${color} text-inherit px-6 py-4 rounded-2xl shadow-2xl
        animate-toast font-semibold`;

    toast.innerText = message;

    document
        .getElementById("toast-container")
        .appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}
suggestions.innerHTML = data;

if(data.trim() === ""){
    suggestions.classList.add("hidden");
} else {
    suggestions.classList.remove("hidden");
}

async function addToWishlist(productId, button){

    const formData = new FormData();
    formData.append("product_id", productId);

    const response = await fetch("features/ajax_wishlist.php", {
        method: "POST",
        body: formData
    });

    const data = await response.json();

    if(data.status === "success"){
        showToast("Added to Wishlist ✅");
        button.innerHTML = "✅";
        button.classList.add(
            "bg-green-500",
            "scale-110"
        );

    } else if(data.status === "exists"){
        showToast("Already in Wishlist ❤️", "error");
        button.innerHTML = "❤️ Added";

    } else {

        showToast("Please login first", "error");
        button.innerHTML = "Login First";
    }


}

</script>
</body>
</html>