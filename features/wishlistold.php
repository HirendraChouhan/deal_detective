<?php
include("../config.php");
include("../components/header.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$result = $conn->query("
    SELECT products.* FROM wishlist
    JOIN products ON wishlist.product_id = products.id
    WHERE wishlist.user_id = '$user_id'
");
?>

<h1>Your Wishlist ❤️</h1>

<?php while($row = $result->fetch_assoc()){ ?>

<div style="border:1px solid #ccc; padding:10px; margin:10px;">
    <img src="<?= $row['image'] ?>" width="100">
    <h3><?= $row['title'] ?></h3>

    <p>
        Amazon: ₹<?= $row['amazon_price'] ?><br>
        Flipkart: ₹<?= $row['flipkart_price'] ?>
    </p>

    <form method="POST" action="remove_wishlist.php">
        <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
        <button>❌ Remove</button>
    </form>
</div>

<?php } ?>