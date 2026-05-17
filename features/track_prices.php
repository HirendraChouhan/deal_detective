<?php
include("../config.php");
include("../components/header.php");

// fetch all products
$result = $conn->query("SELECT * FROM products");
if(!$result){
    die("Query failed: " . $conn->error);
}

while($product = $result->fetch_assoc()){

    $product_id = $product['id'];

    // simulate new price (random change)
    $new_price = $product['amazon_price'] - rand(100, 1000);

    if($new_price < 1000){
        $new_price = $product['amazon_price']; // prevent unrealistic drop
    }

    // insert into price history
    $conn->query("
        INSERT INTO price_history (product_id, price, source)
        VALUES ('$product_id', '$new_price', 'Amazon')
    ");

    // update current price
    $conn->query("
        UPDATE products 
        SET amazon_price = '$new_price'
        WHERE id = '$product_id'
    ");
    require_once("send_alerts.php");

// check if price dropped
$latest = $new_price;
$old = $product['amazon_price'];

if($latest < $old){

    // find users who have this in wishlist
    $users = $conn->query("
        SELECT users.email, products.title 
        FROM wishlist
        JOIN users ON wishlist.user_id = users.id
        JOIN products ON wishlist.product_id = products.id
        WHERE wishlist.product_id = '$product_id'
    ");

    while($u = $users->fetch_assoc()){
        sendPriceAlert($u['email'], $u['title'], $latest);
    }
}
}
?>
<?php

?>

echo "Prices Updated!";
<?php include("../components/footer.php"); ?>