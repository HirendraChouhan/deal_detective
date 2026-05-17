<script>

document.addEventListener("DOMContentLoaded", () => {

    const savedTheme =
    localStorage.getItem("theme");

    if(savedTheme === "light"){

        document.body.classList.add("light-mode");
    }
});

</script>
</body>
</html>