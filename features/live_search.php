<?php
include("../config.php");

$query = $_GET['query'] ?? '';

if(empty($query)){
    exit();
}

$stmt = $conn->prepare("
    SELECT * FROM products
    WHERE title LIKE ?
    LIMIT 5
");

$search = "%" . $query . "%";

$stmt->bind_param("s", $search);

$stmt->execute();

$result = $stmt->get_result();

while($p = $result->fetch_assoc()){
?>

<a href="features/product.php?id=<?= $p['id'] ?>"
class="block px-4 py-3 hover:bg-white/10 transition border-b border-white/5">

    <div class="flex items-center gap-4">

    <img src="<?= $p['image'] ?>"
        class="w-12 h-12 object-cover rounded-lg">

    <div>
        <p class="font-semibold">
            <?= $p['title'] ?>
        </p>

        <p class="text-sm text-gray-400">
            ₹<?= min($p['amazon_price'], $p['flipkart_price']) ?>
        </p>
    </div>

    </div>

</a>

<?php } ?>