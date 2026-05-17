<?php
include("../config.php");
include("../components/header.php");

$query = $_GET['query'] ?? '';

$stmt = $conn->prepare("SELECT * FROM products WHERE title LIKE ?");
$searchTerm = "%" . $query . "%";

$stmt->bind_param("s", $searchTerm);
$stmt->execute();
if(empty($query)){
    echo "Please enter something to search.";
    exit();
}
$result = $stmt->get_result();
?>

<h1>Search Results for "<?= $query ?>"</h1>

<div style="display:flex; flex-wrap:wrap;">

<?php while($p = $result->fetch_assoc()){ ?>

<div style="border:1px solid #ccc; padding:10px; margin:10px; width:200px;">
    <img src="<?= $p['image'] ?>" width="100%">

    <h3><?= $p['title'] ?></h3>

    <p>
        Amazon: ₹<?= $p['amazon_price'] ?><br>
        Flipkart: ₹<?= $p['flipkart_price'] ?>
    </p>

    <form method="POST" action="add_to_wishlist.php">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <button>❤️ Add</button>
    </form>

    <a href="product.php?id=<?= $p['id'] ?>"  class="text-blue-500">View Details</a>
</div>

<?php } ?>

</div>
<?php include("../components/footer.php"); ?>