<?php
include("../config.php");


$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

$conn->query("DELETE FROM wishlist WHERE user_id='$user_id' AND product_id='$product_id'");

header("Location: wishlist.php");?>
<?php include("../components/footer.php"); ?>