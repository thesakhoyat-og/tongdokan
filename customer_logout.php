<?php
// customer logout start

session_start();
session_destroy();
header("Location: index.php");
exit;

// customer logout end
?>
