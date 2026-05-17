

<nav class="flex justify-between items-center px-8 py-6 bg-white/5 backdrop-blur-xl border-b border-white/10 sticky top-0 z-50">

    <h1 class="text-2xl font-bold text-cyan-400">
        Deal Detective 🕵️
    </h1>

    <div class="flex items-center gap-6 text-sm">
      <button
            onclick="
            document.body.classList.toggle('light-mode');

            if(document.body.classList.contains('light-mode')){
            localStorage.setItem('theme','light');
            }else{
            localStorage.setItem('theme','dark');
            }
            "
            class="glass px-4 py-2 rounded-xl hover:scale-105 transition">

            🌙

            </button>

        <a href="/deal_detective_v3/index.php" class="hover:text-cyan-400 transition hover:scale-105 transition duration-300">
            Home
        </a>

        <a href="/deal_detective_v3/features/wishlist.php" class="hover:text-cyan-400 transition hover:scale-105 transition duration-300">
            Wishlist
        </a>

        <?php if(isset($_SESSION['user_id'])){ ?>

            <a href="/deal_detective_v3/auth/logout.php"
                class="bg-red-500 hover:bg-red-400 px-4 py-2 rounded-xl transition hover:scale-105 transition duration-300">
                Logout
            </a>

        <?php } else { ?>

            <a href="/deal_detective_v3/auth/login.php"
                class="hover:text-cyan-400 transition hover:scale-105 transition duration-300">
                Login
            </a>

        <?php } ?>
    </div>

</nav>