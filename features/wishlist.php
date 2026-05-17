<?php
include("../config.php");
include("../components/header.php");

$user_id = $_SESSION['user_id'];

$result = $conn->query("
    SELECT products.* FROM wishlist
    JOIN products ON wishlist.product_id = products.id
    WHERE wishlist.user_id = '$user_id'
");
?>

<div class="px-6 md:px-16 py-12">

    <h1 class="text-4xl font-bold mb-10">
        ❤️ Your Wishlist
    </h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

    <?php while($row = $result->fetch_assoc()){ ?>

        <div class="glass rounded-3xl overflow-hidden shadow-xl">

            <img src="<?= $row['image'] ?>"
                class="w-full h-64 object-cover">

            <div class="p-6">

                <h2 class="text-2xl font-semibold">
                    <?= $row['title'] ?>
                </h2>

                <div class="mt-4 text-gray-300">
                    <p>Amazon: ₹<?= $row['amazon_price'] ?></p>
                    <p>Flipkart: ₹<?= $row['flipkart_price'] ?></p>
                </div>

                <div class="flex gap-3 mt-6">

                    <a href="product.php?id=<?= $row['id'] ?>"
                        class="flex-1 bg-cyan-500 hover:bg-cyan-400 text-center py-3 rounded-xl transition">

                        View
                    </a>

                    <form method="POST" action="remove_wishlist.php">

                        <input type="hidden"
                            name="product_id"
                            value="<?= $row['id'] ?>">

                        <button class="bg-red-500 hover:bg-red-400 px-4 py-3 rounded-xl transition">
                            ❌
                        </button>

                    </form>

                </div>

            </div>

        </div>

    <?php } ?>

    </div>

</div>

<?php include("../components/footer.php"); ?>