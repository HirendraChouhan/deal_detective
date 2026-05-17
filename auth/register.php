<?php
include("../config.php");
include("../components/header.php");

if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name,email,password) VALUES ('$name','$email','$password')";
    
    if($conn->query($sql)){
        header("Location: login.php");
       
    } else {
        echo "Error: " . $conn->error;
    }
}
?><div class="min-h-screen flex overflow-hidden">

    <!-- LEFT PANEL -->

    <div class="hidden lg:flex lg:w-1/2 relative items-center justify-center overflow-hidden">

        <!-- BACKGROUND -->

        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 via-cyan-500/10 to-black"></div>

        <div class="absolute top-10 left-10 w-96 h-96 bg-cyan-500/20 rounded-full blur-[140px]"></div>

        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-purple-500/20 rounded-full blur-[160px]"></div>


        <!-- FLOATING CARDS -->

        <div class="absolute top-32 left-20 bg-white/5 border border-white/10 backdrop-blur-2xl px-6 py-5 rounded-3xl rotate-[-8deg] shadow-2xl">

            <p class="text-gray-400 text-sm mb-2">
                Best Deal Detected
            </p>

            <h3 class="text-3xl font-black text-green-400">
                ₹7,000 OFF
            </h3>

        </div>


        <div class="absolute bottom-24 right-20 bg-white/5 border border-white/10 backdrop-blur-2xl px-6 py-5 rounded-3xl rotate-[8deg] shadow-2xl">

            <p class="text-gray-400 text-sm mb-2">
                AI Confidence
            </p>

            <h3 class="text-3xl font-black text-cyan-400">
                92%
            </h3>

        </div>


        <!-- MAIN CONTENT -->

        <div class="relative z-10 max-w-xl px-12">

            <div class="inline-flex items-center gap-3 bg-white/5 border border-white/10 backdrop-blur-2xl px-5 py-3 rounded-full mb-10">

                <div class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></div>

                <span class="text-cyan-300 uppercase tracking-widest text-sm">

                    Next Generation Shopping

                </span>

            </div>

            <h1 class="text-6xl font-black leading-[0.95] mb-8">

                Shop with<br>
                intelligence.

            </h1>

            <p class="text-xl text-gray-400 leading-relaxed">

                Join thousands of users tracking prices,
                detecting trends, and saving money using AI-powered deal analysis.

            </p>

        </div>

    </div>


    <!-- RIGHT PANEL -->

    <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12 relative">

        <!-- MOBILE GLOW -->

        <div class="lg:hidden absolute top-20 left-10 w-72 h-72 bg-cyan-500/20 rounded-full blur-[120px]"></div>

        <div class="w-full max-w-md relative z-10">

            <!-- HEADER -->

            <div class="mb-12">

                <h2 class="text-5xl font-black mb-4">

                    Create account

                </h2>

                <p class="text-gray-400 text-lg">

                    Start tracking smarter deals today.

                </p>

            </div>


            <!-- FORM -->

            <form
                method="POST"
                class="space-y-6">

                <!-- NAME -->

                <div>

                    <label class="block text-sm text-gray-400 mb-3">

                        Full Name

                    </label>

                    <input
                        type="text"
                        name="name"
                        required
                        class="w-full bg-white/5 border border-white/10 focus:border-cyan-400 outline-none px-6 py-5 rounded-2xl backdrop-blur-xl transition">

                </div>


                <!-- EMAIL -->

                <div>

                    <label class="block text-sm text-gray-400 mb-3">

                        Email

                    </label>

                    <input
                        type="email"
                        name="email"
                        required
                        class="w-full bg-white/5 border border-white/10 focus:border-cyan-400 outline-none px-6 py-5 rounded-2xl backdrop-blur-xl transition">

                </div>


                <!-- PASSWORD -->

                <div>

                    <label class="block text-sm text-gray-400 mb-3">

                        Password

                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full bg-white/5 border border-white/10 focus:border-cyan-400 outline-none px-6 py-5 rounded-2xl backdrop-blur-xl transition">

                </div>


                <!-- BUTTON -->

                <button
                    name="register"
                    class="w-full bg-gradient-to-r from-cyan-400 to-blue-500 hover:scale-[1.02] transition duration-300 text-black font-black py-5 rounded-2xl text-lg shadow-2xl shadow-cyan-500/20">

                    Create Account

                </button>

            </form>


            <!-- FOOTER -->

            <div class="mt-10 text-center text-gray-500">

                Already have an account?

                <a
                    href="login.php"
                    class="text-cyan-400 hover:text-cyan-300">

                    Login

                </a>

            </div>

        </div>

    </div>

</div>
<?php include("../components/footer.php"); ?>