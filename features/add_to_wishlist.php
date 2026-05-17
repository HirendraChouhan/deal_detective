<?php
include("../config.php");


if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

// prevent duplicates
$check = $conn->query("SELECT * FROM wishlist WHERE user_id='$user_id' AND product_id='$product_id'");

if($check->num_rows == 0){
    $conn->query("INSERT INTO wishlist (user_id, product_id) VALUES ('$user_id','$product_id')");
}

header("Location: ../index.php");?>
<?php include("../components/footer.php"); ?>