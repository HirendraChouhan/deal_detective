<?php include("config.php"); ?>

<nav class="bg-white shadow p-4 flex justify-between">
    <h1 class="font-bold text-xl">Deal Detective 🕵️</h1>

    <div class="space-x-4">
        <a href="index.php">Home</a>
        <a href="features/wishlist.php">Wishlist ❤️</a>
        <a href="features/history.php">History 📜</a>

        <?php if(isset($_SESSION['user_id'])){ ?>
            <a href="auth/logout.php">Logout</a>
        <?php } else { ?>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php">Register</a>
        <?php } ?>
    </div>
</nav>