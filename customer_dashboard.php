<?php
// php logic start

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["customer_id"])) {
    header("Location: customer_login.php");
    exit;
}

$customer_id = $_SESSION["customer_id"];
$success = "";
$error   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "change_password") {
        $current  = trim($_POST["current_password"] ?? "");
        $new_pass = trim($_POST["new_password"] ?? "");
        $confirm  = trim($_POST["confirm_password"] ?? "");
        if ($current === "" || $new_pass === "" || $confirm === "") {
            $error = "All fields are required.";
        } elseif ($new_pass !== $confirm) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_pass) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM customers WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($current !== $row["password"]) {
                $error = "Current password is incorrect.";
            } else {
                $upd = $conn->prepare("UPDATE customers SET password = ? WHERE customer_id = ?");
                $upd->bind_param("si", $new_pass, $customer_id);
                $upd->execute();
                $upd->close();
                $success = "Password updated successfully.";
            }
        }
    }
    if ($action === "cancel_order") {
        $order_id = intval($_POST["order_id"] ?? 0);

        if ($order_id > 0) {
            $check = $conn->prepare("SELECT status, total_amount FROM orders WHERE order_id = ? AND customer_id = ?");
            $check->bind_param("ii", $order_id, $customer_id);
            $check->execute();
            $order = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$order) {
                $error = "Order not found.";
            } elseif (in_array($order["status"], ["delivered", "cancelled", "shipped"])) {
                $error = "This order can no longer be cancelled.";
            } else {
                $upd = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
                $upd->bind_param("i", $order_id);
                $upd->execute();
                $upd->close();
                $pay = $conn->prepare("UPDATE payments SET status = 'refunded', delivery_status = 'Cancelled' WHERE order_id = ?");
                $pay->bind_param("i", $order_id);
                $pay->execute();
                $pay->close();
                $refund = $conn->prepare("UPDATE customers SET total_spent = GREATEST(0, total_spent - ?) WHERE customer_id = ?");
                $refund->bind_param("di", $order["total_amount"], $customer_id);
                $refund->execute();
                $refund->close();

                $success = "Order cancelled. Refund processed.";
            }
        }
    }
}
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
$orders_query = $conn->prepare("
    SELECT o.order_id, o.item_count, o.total_amount, o.shipping_address, o.status, o.order_date,
           p.method AS payment_method, p.delivery_status
    FROM orders o
    LEFT JOIN payments p ON o.order_id = p.order_id
    WHERE o.customer_id = ?
    ORDER BY o.order_date DESC, o.order_id DESC
");
$orders_query->bind_param("i", $customer_id);
$orders_query->execute();
$orders = $orders_query->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_query->close();
// php logic end
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>My Account — Tong Dokan</title>
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
                <div class="nav-sub">My Account</div>
            </div>
        </div>
        <div class="nav-links">
            <a href="index.php#shop" class="nav-link">Back to Shop</a>
            <a href="customer_logout.php" class="login-btn">Logout</a>
        </div>
    </div>
</nav>
<!-- navbar end -->

<!-- dashboard content start -->
<div style="max-width:1200px; margin:0 auto; padding:48px 5%;">

    <div class="dash-header">
        <div class="dash-eyebrow">My Account</div>
        <h1 class="dash-title">Welcome, <span class="accent-red"><?= htmlspecialchars($customer["full_name"]) ?></span> 👋</h1>
        <p class="dash-sub">Here you can see your orders and manage your account details.</p>
    </div>

    <?php if ($success): ?>
        <div class="dash-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="dash-message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- stat cards start -->
    <div class="stat-grid" style="margin-bottom:32px;">
        <div class="stat-card">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value accent-red"><?= count($orders) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Spent</div>
            <div class="stat-value">$<?= number_format($customer["total_spent"], 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Account Status</div>
            <div class="stat-value" style="font-size:24px; text-transform:capitalize;"><?= htmlspecialchars($customer["status"]) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Member Since</div>
            <div class="stat-value" style="font-size:22px;"><?= htmlspecialchars($customer["joined_date"]) ?></div>
        </div>
    </div>

    <!-- stat cards end -->

    <!-- profile + orders section start -->
    <div class="profile-grid">

        <div>
            <!-- my orders table start -->
            <div class="dash-section" style="margin-bottom:24px;">
                <h2 class="section-title">My Orders</h2>
                <?php if (empty($orders)): ?>
                    <p class="muted" style="text-align:center; padding:24px;">You have no orders yet. <a href="index.php#shop" style="color:#B02F2F;">Go shopping →</a></p>
                <?php else: ?>
                <div class="table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Order Status</th>
                                <th>Delivery</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o):
                                $statusBadge = [
                                    "pending"    => "badge-purple",
                                    "processing" => "badge-blue",
                                    "shipped"    => "badge-yellow",
                                    "delivered"  => "badge-green",
                                    "cancelled"  => "badge-red",
                                ];
                                $delBadge = [
                                    "Delivered"  => "badge-green",
                                    "In Transit" => "badge-blue",
                                    "Shipped"    => "badge-yellow",
                                    "Pending"    => "badge-purple",
                                    "Processing" => "badge-blue",
                                    "Cancelled"  => "badge-red",
                                ];
                                $sb = $statusBadge[$o["status"]] ?? "badge-blue";
                                $db = $delBadge[$o["delivery_status"]] ?? "badge-blue";
                                // Only pending/processing orders can be cancelled
                                $can_cancel = in_array($o["status"], ["pending", "processing"]);
                            ?>
                            <tr>
                                <td class="accent-red bold">#TD-<?= str_pad($o["order_id"], 3, "0", STR_PAD_LEFT) ?></td>
                                <td class="center"><?= $o["item_count"] ?></td>
                                <td class="bold">$<?= number_format($o["total_amount"], 2) ?></td>
                                <td><span class="badge badge-purple"><?= htmlspecialchars($o["payment_method"] ?? "—") ?></span></td>
                                <td><span class="badge <?= $sb ?>"><?= $o["status"] ?></span></td>
                                <td><span class="badge <?= $db ?>"><?= htmlspecialchars($o["delivery_status"] ?? "—") ?></span></td>
                                <td class="muted"><?= htmlspecialchars($o["order_date"]) ?></td>
                                <td>
                                    <?php if ($can_cancel): ?>
                                        <form method="POST" onsubmit="return confirm('Cancel this order? This action cannot be undone.');" style="margin:0;">
                                            <input type="hidden" name="action" value="cancel_order" />
                                            <input type="hidden" name="order_id" value="<?= $o["order_id"] ?>" />
                                            <button type="submit" class="link-btn">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <!-- my details section start -->
            <div class="dash-section" style="margin-bottom:24px;">
                <h2 class="section-title">My Details</h2>
                <div class="profile-info-row">
                    <span class="profile-label">Name</span>
                    <span class="profile-value bold"><?= htmlspecialchars($customer["full_name"]) ?></span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-label">Email</span>
                    <span class="profile-value"><?= htmlspecialchars($customer["email"]) ?></span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-label">Phone</span>
                    <span class="profile-value muted"><?= htmlspecialchars($customer["phone"] ?: "—") ?></span>
                </div>
                <div class="profile-info-row">
                    <span class="profile-label">Location</span>
                    <span class="profile-value muted"><?= htmlspecialchars($customer["location"] ?: "—") ?></span>
                </div>
            </div>

            <!-- my details section end -->

            <!-- change password form start -->
            <div class="dash-section">
                <h2 class="section-title">Change Password</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password" />
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required placeholder="current password" />
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="6" placeholder="min 6 characters" />
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required placeholder="type again" />
                    </div>
                    <button type="submit" class="dash-btn">Update Password</button>
                </form>
            </div>
        </div>

            <!-- change password form end -->
        </div>

    </div>
    <!-- profile + orders section end -->
</div>
<!-- dashboard content end -->

<!-- footer start -->
<footer class="site-footer">
    <div class="footer-bangla">আমাদের গল্প, তোমাদের ঘরে</div>
    <div class="footer-tag">© 2026 Tong Dokan · Made with chai in Dhaka</div>
</footer>
<!-- footer end -->

</body>
</html>
<?php $conn->close(); ?>
