<?php
// php logic start

session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["staff_id"]) || $_SESSION["role"] !== "Payment Manager") {
    header("Location: ../login.php");
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // Update payment status (pending, completed, refunded)
    if ($action === "update_payment_status") {
        $payment_id = intval($_POST["payment_id"] ?? 0);
        $status = trim($_POST["status"] ?? "");
        $valid_statuses = ["pending", "completed", "refunded"];
        if ($payment_id > 0 && in_array($status, $valid_statuses)) {
            $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE payment_id = ?");
            $stmt->bind_param("si", $status, $payment_id);
            $stmt->execute();
            $stmt->close();
            $message = "Payment status updated.";
        } else {
            $message = "Invalid status value.";
        }
    }

    // Update delivery status
    if ($action === "update_delivery_status") {
        $payment_id = intval($_POST["payment_id"] ?? 0);
        $delivery_status = trim($_POST["delivery_status"] ?? "");
        $valid_delivery = ["Pending", "Processing", "Shipped", "In Transit", "Delivered", "Cancelled"];

        if ($payment_id > 0 && in_array($delivery_status, $valid_delivery)) {
            $stmt = $conn->prepare("UPDATE payments SET delivery_status = ? WHERE payment_id = ?");
            $stmt->bind_param("si", $delivery_status, $payment_id);
            $stmt->execute();
            $stmt->close();
            $message = "Delivery status updated.";
        }
    }

    // Remove a transaction (CRUD - Delete)
    if ($action === "delete_payment") {
        $payment_id = intval($_POST["payment_id"] ?? 0);
        if ($payment_id > 0) {
            $stmt = $conn->prepare("DELETE FROM payments WHERE payment_id = ?");
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $stmt->close();
            $message = "Transaction removed.";
        }
    }
}
$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status_filter"] ?? "all");

$where = [];
$params = [];
$types = "";
if ($search !== "") {
    $where[]  = "(c.full_name LIKE ? OR c.email LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $types   .= "ss";
}
if ($status_filter === "completed") {
    $where[] = "p.status = 'completed'";
} elseif ($status_filter === "pending") {
    $where[] = "p.status = 'pending'";
} elseif ($status_filter === "refunded") {
    $where[] = "p.status = 'refunded'";
}

$sql = "SELECT p.*, o.shipping_address, o.status AS order_status, c.full_name, c.email
        FROM payments p
        LEFT JOIN orders o ON p.order_id = o.order_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY p.payment_id DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$all_payments = $conn->query("SELECT * FROM payments")->fetch_all(MYSQLI_ASSOC);
$total_revenue = 0;
$completed_count = 0;
$pending_count = 0;
$refunded_count = 0;
$cancelled_count = 0;
foreach ($all_payments as $p) {
    if ($p["status"] === "completed") {
        $completed_count++;
        $total_revenue += $p["amount"];
    }
    if ($p["status"] === "pending")  $pending_count++;
    if ($p["status"] === "refunded") $refunded_count++;
    if ($p["delivery_status"] === "Cancelled") $cancelled_count++;
}
$methods = ["PayPal", "Card", "Apple Pay"];
$method_data = [];
foreach ($methods as $m) {
    $count = 0; $total = 0;
    foreach ($all_payments as $p) {
        if ($p["method"] === $m && $p["status"] === "completed") {
            $count++;
            $total += $p["amount"];
        }
    }
    $method_data[] = ["method" => $m, "count" => $count, "total" => $total];
}

$page_title = "Payment & Delivery";
$active_page = "payment";
// php logic end
include '../includes/header.php';
?>

<!-- page header start -->
<div class="dash-header">
    <div class="dash-eyebrow">Payment & Delivery</div>
    <h1 class="dash-title">Hello, <span class="accent-red">Mahraz</span>!</h1>
    <p class="dash-sub">Money in, parcels out. The heartbeat of the bazaar.</p>
</div>
<!-- page header end -->

<?php if ($message): ?>
    <div class="dash-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- stat cards start -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value accent-green">$<?= number_format($total_revenue, 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value accent-green"><?= $completed_count ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value accent-yellow"><?= $pending_count ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Refunded / Cancelled</div>
        <div class="stat-value accent-red"><?= $refunded_count + $cancelled_count ?></div>
    </div>
</div>

<!-- payment method breakdown section start -->
<section class="dash-section">
    <h2 class="section-title">Payment Method Breakdown</h2>
    <div class="method-breakdown">
        <?php foreach ($method_data as $i => $m):
            $emoji = ["PayPal" => "🅿️", "Card" => "💳", "Apple Pay" => "🍎"][$m["method"]];
            $pct = $total_revenue > 0 ? ($m["total"] / $total_revenue * 100) : 0;
            $bar_colors = ["#2A5FBF", "#B02F2F", "#2A1810"];
        ?>
        <div class="method-row">
            <div class="method-header">
                <span class="method-name"><?= $emoji ?> <?= htmlspecialchars($m["method"]) ?></span>
                <span class="method-total accent-red">$<?= number_format($m["total"], 0) ?></span>
            </div>
            <div class="method-bar">
                <div class="method-bar-fill" style="width: <?= $pct ?>%; background: <?= $bar_colors[$i] ?>;"></div>
            </div>
            <div class="method-count muted small"><?= $m["count"] ?> transactions</div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<!-- payment method breakdown section end -->

<!-- all transactions section start -->
<section class="dash-section">
    <div class="table-top-bar">
        <h2 class="section-title" style="margin:0;">All Transactions</h2>

        <!-- search bar -->
        <form method="GET" class="search-filter-bar">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by customer name or email..." class="search-input" />
            <select name="status_filter" class="status-select">
                <option value="all"       <?= $status_filter === "all"       ? "selected" : "" ?>>All Status</option>
                <option value="completed" <?= $status_filter === "completed" ? "selected" : "" ?>>Completed</option>
                <option value="pending"   <?= $status_filter === "pending"   ? "selected" : "" ?>>Pending</option>
                <option value="refunded"  <?= $status_filter === "refunded"  ? "selected" : "" ?>>Refunded</option>
            </select>
            <button type="submit" class="dash-btn-small">Search</button>
            <?php if ($search || $status_filter !== "all"): ?>
                <a href="payment_delivery.php" class="link-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- data table start -->
<div class="table-wrap" style="margin-top:20px;">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Customer</th>
                    <th>Order</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Payment Status</th>
                    <th>Delivery Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p):
                    $payBadge = [
                        "completed" => "badge-green",
                        "pending"   => "badge-yellow",
                        "refunded"  => "badge-red",
                    ];
                    $delBadge = [
                        "Delivered"  => "badge-green",
                        "In Transit" => "badge-blue",
                        "Shipped"    => "badge-yellow",
                        "Pending"    => "badge-purple",
                        "Processing" => "badge-blue",
                        "Cancelled"  => "badge-red",
                    ];
                    $pb = $payBadge[$p["status"]] ?? "badge-blue";
                    $db = $delBadge[$p["delivery_status"]] ?? "badge-blue";
                ?>
                <tr>
                    <td class="accent-red bold">PAY-<?= str_pad($p["payment_id"], 3, "0", STR_PAD_LEFT) ?></td>
                    <td class="bold"><?= htmlspecialchars($p["full_name"] ?? "—") ?>
                        <div class="muted small"><?= htmlspecialchars($p["email"] ?? "") ?></div>
                    </td>
                    <td class="muted">#TD-<?= str_pad($p["order_id"], 3, "0", STR_PAD_LEFT) ?></td>
                    <td class="bold">$<?= number_format($p["amount"], 2) ?></td>
                    <td><span class="badge badge-purple"><?= htmlspecialchars($p["method"]) ?></span></td>
                    <td class="muted small"><?= htmlspecialchars($p["payment_date"]) ?></td>

                    <td>
                        <!-- payment status dropdown -->
                        <form method="POST" style="display:flex; gap:4px; align-items:center;">
                            <input type="hidden" name="action" value="update_payment_status" />
                            <input type="hidden" name="payment_id" value="<?= $p["payment_id"] ?>" />
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <?php foreach (["completed", "pending", "refunded"] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $opt === $p["status"] ? "selected" : "" ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>

                    <td>
                        <!-- delivery status dropdown -->
                        <form method="POST" style="display:flex; gap:4px; align-items:center;">
                            <input type="hidden" name="action" value="update_delivery_status" />
                            <input type="hidden" name="payment_id" value="<?= $p["payment_id"] ?>" />
                            <select name="delivery_status" class="status-select" onchange="this.form.submit()">
                                <?php foreach (["Pending", "Processing", "Shipped", "In Transit", "Delivered", "Cancelled"] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $opt === $p["delivery_status"] ? "selected" : "" ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>

                    <td>
                        <!-- remove button -->
                        <form method="POST" onsubmit="return confirm('Remove this transaction permanently?');" style="margin:0;">
                            <input type="hidden" name="action" value="delete_payment" />
                            <input type="hidden" name="payment_id" value="<?= $p["payment_id"] ?>" />
                            <button type="submit" class="link-btn">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($payments)): ?>
                <tr><td colspan="9" class="empty-row">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<!-- all transactions section end -->

<?php
// dashboard end
include '../includes/footer.php';
$conn->close();
?>
