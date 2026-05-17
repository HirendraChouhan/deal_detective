<?php
include("../config.php");

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode([
        "status" => "error",
        "message" => "Login required"
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? 0;

// check duplicate
$check = $conn->query("
    SELECT * FROM wishlist
    WHERE user_id='$user_id'
    AND product_id='$product_id'
");

if($check->num_rows == 0){

    $conn->query("
        INSERT INTO wishlist(user_id, product_id)
        VALUES('$user_id','$product_id')
    ");

    echo json_encode([
        "status" => "success"
    ]);

} else {

    echo json_encode([
        "status" => "exists"
    ]);
}