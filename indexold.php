<?php include("components/navbar.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deal Detective</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<!-- HERO -->
<div class="flex flex-col items-center justify-center h-[60vh] text-center">
    <h1 class="text-4xl font-bold mb-4">Find the Best Deals 🔥</h1>
    
    <form method="GET" action="features/search.php" class="w-1/2">
        <input type="text" name="query"
            placeholder="Search products across Amazon, Flipkart..."
            class="w-full p-4 rounded-xl shadow focus:outline-none">
    </form>
</div>

<!-- PRODUCT GRID -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-10">

<?php
// dummy data for now
$products = [
    ["id"=>1,"title"=>"ipineapple 14","price1"=>70000,"price2"=>68000,"img"=>"https://via.placeholder.com/150"],
    ["id"=>2,"title"=>"Samsung S23","price1"=>65000,"price2"=>64000,"img"=>"https://via.placeholder.com/150"],
];
$result = $conn->query("SELECT * FROM products");

while($p = $result->fetch_assoc()){
?>
    <div class="bg-white p-4 rounded-xl shadow">
        <img src="<?= $p['image'] ?>">

        <h2><?= $p['title'] ?></h2>

        <p>
            Amazon: ₹<?= $p['amazon_price'] ?><br>
            Flipkart: ₹<?= $p['flipkart_price'] ?>
        </p>
            <a href="features/product.php?id=<?= $p['id'] ?>" class="text-blue-500">
                View Details
            </a>

        <form method="POST" action="features/add_to_wishlist.php">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <button>❤️ Add</button>
        </form>
    </div>
<?php } ?>
<!-- // foreach($products as $p){
// ?>
//     <div class="bg-white p-4 rounded-xl shadow hover:shadow-lg transition">
//         <img src="<?= $p['img'] ?>" class="w-full rounded-lg">

//         <h2 class="text-lg font-semibold mt-2"><?= $p['title'] ?></h2>

//         <p class="text-sm text-gray-600">
//             Amazon: ₹<?= $p['price1'] ?> <br>
//             Flipkart: ₹<?= $p['price2'] ?>
//         </p>
//         <form method="POST" action="features/add_to_wishlist.php">
//             <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
//             <button class="mt-3 bg-red-500 text-white px-4 py-2 rounded-lg">
//                 ❤️ Add to Wishlist
//             </button>
//         </form>
//     </div>
//  <?php  ?> -->

</div>

</body>
</html>-->