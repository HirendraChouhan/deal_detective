<?php
include("../config.php");

session_destroy();
header("Location: login.php");
?>
<?php include("../components/footer.php"); ?>