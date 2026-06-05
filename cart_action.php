<?php
// cart action handler start

session_start();

if (!isset($_SESSION["cart"])) $_SESSION["cart"] = [];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$action     = $_POST["action"] ?? "";
$product_id = intval($_POST["product_id"] ?? 0);

if ($product_id <= 0) {
    header("Location: index.php#shop");
    exit;
}
if ($action === "add") {
    if (isset($_SESSION["cart"][$product_id])) {
        $_SESSION["cart"][$product_id]++;
    } else {
        $_SESSION["cart"][$product_id] = 1;
    }
    $msg = "Added to your bag.";
}
elseif ($action === "increase") {
    if (isset($_SESSION["cart"][$product_id])) {
        $_SESSION["cart"][$product_id]++;
    }
    $msg = "";
}
elseif ($action === "decrease") {
    if (isset($_SESSION["cart"][$product_id])) {
        $_SESSION["cart"][$product_id]--;
        if ($_SESSION["cart"][$product_id] <= 0) {
            unset($_SESSION["cart"][$product_id]);
        }
    }
    $msg = "";
}
elseif ($action === "remove") {
    unset($_SESSION["cart"][$product_id]);
    $msg = "Removed from your bag.";
}
$redirect = "index.php#shop";
if (!empty($msg)) $redirect .= "?msg=" . urlencode($msg);
header("Location: " . $redirect);
exit;

// cart action handler end
?>
