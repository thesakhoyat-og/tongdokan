<?php
// php logic start

session_start();
require_once 'db_connect.php';
if (!isset($_SESSION["customer_id"])) {
    header("Location: customer_login.php");
    exit;
}
if (empty($_SESSION["cart"])) {
    header("Location: index.php#shop");
    exit;
}

$customer_id = $_SESSION["customer_id"];
$error = "";
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
$cart_items = [];
$cart_total = 0;
$item_count = 0;
foreach ($_SESSION["cart"] as $pid => $qty) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    if ($item) {
        $item["qty"] = $qty;
        $item["subtotal"] = $item["price"] * $qty;
        $cart_total += $item["subtotal"];
        $item_count += $qty;
        $cart_items[] = $item;
    }
    $stmt->close();
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "place_order") {
    $payment_method = trim($_POST["payment_method"] ?? "");
    $shipping_address = trim($_POST["shipping_address"] ?? "");

    $valid_methods = ["PayPal", "Card", "Apple Pay"];

    if (empty($cart_items)) {
        $error = "Your cart is empty.";
    } elseif (!in_array($payment_method, $valid_methods)) {
        $error = "Please select a valid payment method.";
    } elseif ($shipping_address === "") {
        $error = "Please provide a shipping address.";
    } elseif (strlen($shipping_address) < 10) {
        $error = "Shipping address looks too short. Please provide full details.";
    } else {
        $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, item_count, total_amount, shipping_address, status, order_date) VALUES (?, ?, ?, ?, 'pending', CURDATE())");
        $order_stmt->bind_param("iids", $customer_id, $item_count, $cart_total, $shipping_address);
        $order_stmt->execute();
        $order_id = $order_stmt->insert_id;
        $order_stmt->close();
        $pay_stmt = $conn->prepare("INSERT INTO payments (order_id, amount, method, status, delivery_status, payment_date) VALUES (?, ?, ?, 'completed', 'Pending', CURDATE())");
        $pay_stmt->bind_param("ids", $order_id, $cart_total, $payment_method);
        $pay_stmt->execute();
        $pay_stmt->close();
        $upd = $conn->prepare("UPDATE customers SET total_orders = total_orders + 1, total_spent = total_spent + ? WHERE customer_id = ?");
        $upd->bind_param("di", $cart_total, $customer_id);
        $upd->execute();
        $upd->close();
        foreach ($cart_items as $item) {
            $stock = $conn->prepare("UPDATE products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE product_id = ?");
            $stock->bind_param("ii", $item["qty"], $item["product_id"]);
            $stock->execute();
            $stock->close();
        }
        $_SESSION["cart"] = [];
        header("Location: checkout.php?success=" . $order_id);
        exit;
    }
}
$success_order = null;
if (isset($_GET["success"])) {
    $sid = intval($_GET["success"]);
    $sstmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_id = ?");
    $sstmt->bind_param("ii", $sid, $customer_id);
    $sstmt->execute();
    $success_order = $sstmt->get_result()->fetch_assoc();
    $sstmt->close();
}
// php logic end
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Checkout — Tong Dokan</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Tiro+Bangla:ital@0;1&family=EB+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Bebas+Neue&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>" />
</head>
<body>

<!-- navbar start -->
<nav class="storefront-nav">
    <div class="nav-inner">
        <div class="nav-brand">
            <div class="logo-circle">
                <span class="logo-bangla">ট</span>
                <div class="logo-dash"></div>
            </div>
            <div>
                <div class="nav-title">TONG DOKAN</div>
                <div class="nav-sub">Checkout</div>
            </div>
        </div>
        <div class="nav-links">
            <a href="index.php#shop" class="nav-link">← Back to Shop</a>
            <a href="customer_logout.php" class="login-btn">Logout</a>
        </div>
    </div>
</nav>
<!-- navbar end -->

<!-- checkout container start -->
<div class="checkout-container">

<?php if ($success_order): ?>

    <!-- success page start -->
    <div class="checkout-success">
        <div class="success-emoji">🎉</div>
        <h1 class="success-title">Order Placed!</h1>
        <p class="success-bangla">তোমার অর্ডার পৌঁছে যাবে</p>
        <p class="success-text">Your order <strong>#TD-<?= str_pad($success_order["order_id"], 3, "0", STR_PAD_LEFT) ?></strong> has been received.</p>
        <p class="success-text">Total: <strong>$<?= number_format($success_order["total_amount"], 2) ?></strong></p>
        <p class="success-text muted">We'll send you an email when your parcel ships from Dhaka. 🛺</p>
        <div class="success-buttons">
            <a href="customer_dashboard.php" class="dash-btn">View My Orders</a>
            <a href="index.php#shop" class="dash-btn dash-btn-outline">Keep Shopping</a>
        </div>
    </div>

    <!-- success page end -->

<?php else: ?>

    <!-- checkout form start -->
    <div class="dash-header">
        <div class="dash-eyebrow">Checkout</div>
        <h1 class="dash-title">Review your <span class="accent-red">order</span></h1>
        <p class="dash-sub">One more step. Pick how you want to pay.</p>
    </div>

    <?php if ($error): ?>
        <div class="dash-message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="checkout-grid">

        <!-- left side: shipping + payment -->
        <div>
            <form method="POST" action="checkout.php" id="checkoutForm" novalidate>
                <input type="hidden" name="action" value="place_order" />

                <!-- shipping address start -->
                <div class="dash-section">
                    <h2 class="section-title">Shipping Address</h2>
                    <div class="form-group">
                        <label>Where should we send your parcel?</label>
                        <textarea name="shipping_address" rows="3" required placeholder="Full address with postcode, city, country"><?= htmlspecialchars($_POST["shipping_address"] ?? ($customer["location"] ?? "")) ?></textarea>
                    </div>
                </div>

                <!-- shipping address end -->

                <!-- payment method start -->
                <div class="dash-section">
                    <h2 class="section-title">Payment Method</h2>
                    <p class="checkout-help">Pick one. All payments are secure and encrypted.</p>

                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Card" required <?= ($_POST["payment_method"] ?? "Card") === "Card" ? "checked" : "" ?> />
                            <span class="payment-card">
                                <span class="payment-emoji">💳</span>
                                <span class="payment-name">Credit / Debit Card</span>
                                <span class="payment-desc">Visa, Mastercard, Amex</span>
                            </span>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="PayPal" <?= ($_POST["payment_method"] ?? "") === "PayPal" ? "checked" : "" ?> />
                            <span class="payment-card">
                                <span class="payment-emoji">🅿️</span>
                                <span class="payment-name">PayPal</span>
                                <span class="payment-desc">Pay with your PayPal account</span>
                            </span>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="Apple Pay" <?= ($_POST["payment_method"] ?? "") === "Apple Pay" ? "checked" : "" ?> />
                            <span class="payment-card">
                                <span class="payment-emoji">🍎</span>
                                <span class="payment-name">Apple Pay</span>
                                <span class="payment-desc">Touch ID / Face ID</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- payment method end -->

                <!-- place order button -->
                <button type="submit" class="checkout-place-btn">
                    Place Order · $<?= number_format($cart_total, 2) ?>
                </button>
            </form>
        </div>

        <!-- right side: order summary start -->
        <div>
            <div class="dash-section checkout-summary">
                <h2 class="section-title">Order Summary</h2>

                <?php foreach ($cart_items as $item): ?>
                    <div class="checkout-item">
                        <div class="checkout-item-emoji"><?= htmlspecialchars($item["emoji"]) ?></div>
                        <div class="checkout-item-info">
                            <div class="checkout-item-name"><?= htmlspecialchars($item["product_name"]) ?></div>
                            <div class="checkout-item-qty">× <?= $item["qty"] ?></div>
                        </div>
                        <div class="checkout-item-price">$<?= number_format($item["subtotal"], 2) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="checkout-totals">
                    <div class="checkout-total-row">
                        <span>Subtotal</span>
                        <span>$<?= number_format($cart_total, 2) ?></span>
                    </div>
                    <div class="checkout-total-row">
                        <span>Shipping</span>
                        <span class="accent-green"><?= $cart_total >= 50 ? "FREE" : "$8.00" ?></span>
                    </div>
                    <div class="checkout-total-row checkout-grand-total">
                        <span>Total</span>
                        <span>$<?= number_format($cart_total + ($cart_total >= 50 ? 0 : 8), 2) ?></span>
                    </div>
                </div>

                <p class="checkout-shipping-note">
                    🛺 Ships from Dhaka in 1-2 days. International delivery 5-9 days via DHL.
                </p>
            </div>
        </div>
        <!-- right side: order summary end -->
    </div>
    <!-- checkout form end -->

<?php endif; ?>

</div>
<!-- checkout container end -->

<!-- footer start -->
<footer class="site-footer">
    <div class="footer-bangla">আমাদের গল্প, তোমাদের ঘরে</div>
    <div class="footer-tag">© 2026 Tong Dokan · Made with chai in Dhaka</div>
</footer>
<!-- footer end -->

</body>
</html>
<?php $conn->close(); ?>
