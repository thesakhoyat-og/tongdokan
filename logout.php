<?php
// staff logout start

session_start();
session_destroy();
header("Location: login.php");
exit;

// staff logout end
?>
