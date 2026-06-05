<?php
// php logic start

session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["staff_id"]) || $_SESSION["role"] !== "Customer Manager") {
    header("Location: ../login.php");
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "add") {
        $name     = trim($_POST["full_name"] ?? "");
        $email    = trim($_POST["email"] ?? "");
        $phone    = trim($_POST["phone"] ?? "");
        $location = trim($_POST["location"] ?? "");
        if ($name && $email) {
            $stmt = $conn->prepare("INSERT INTO customers (full_name, email, password, phone, location, status, joined_date) VALUES (?, ?, 'changeme123', ?, ?, 'new', CURDATE())");
            $stmt->bind_param("ssss", $name, $email, $phone, $location);
            if ($stmt->execute()) {
                $message = "New customer added. Default password is changeme123.";
            } else {
                $message = "Could not add customer. Email may already exist.";
            }
            $stmt->close();
        } else {
            $message = "Name and email are required.";
        }
    }
    if ($action === "update_status") {
        $id     = intval($_POST["customer_id"] ?? 0);
        $status = trim($_POST["status"] ?? "");
        $valid  = ["active", "vip", "new", "inactive"];
        if ($id > 0 && in_array($status, $valid)) {
            $stmt = $conn->prepare("UPDATE customers SET status = ? WHERE customer_id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();
            $message = "Customer status updated.";
        }
    }
    if ($action === "delete") {
        $id = intval($_POST["customer_id"] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $message = "Customer removed.";
        }
    }
}
$search        = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status_filter"] ?? "all");

$where  = [];
$params = [];
$types  = "";

if ($search !== "") {
    $where[]  = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $types   .= "ss";
}
if ($status_filter !== "all" && $status_filter !== "") {
    $where[]  = "status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}

$sql = "SELECT * FROM customers";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY total_spent DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats from all customers
$all_customers   = $conn->query("SELECT * FROM customers")->fetch_all(MYSQLI_ASSOC);
$total_customers = count($all_customers);
$vip_count       = 0;
$new_count       = 0;
$total_revenue   = 0;
foreach ($all_customers as $c) {
    if ($c["status"] === "vip") $vip_count++;
    if ($c["status"] === "new") $new_count++;
    $total_revenue += $c["total_spent"];
}

$page_title  = "Customer Management";
$active_page = "customer";
// php logic end
include '../includes/header.php';
?>

<!-- page header start -->
<div class="dash-header">
    <div class="dash-eyebrow">Customer Management</div>
    <h1 class="dash-title">Salam, <span class="accent-red">Khalid</span></h1>
    <p class="dash-sub">These are the people who love Bangladesh as much as we do.</p>
</div>
<!-- page header end -->

<?php if ($message): ?>
    <div class="dash-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- stat cards start -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= $total_customers ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">VIP Customers</div>
        <div class="stat-value accent-purple"><?= $vip_count ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">New This Month</div>
        <div class="stat-value accent-red"><?= $new_count ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value accent-green">$<?= number_format($total_revenue, 0) ?></div>
    </div>
</div>

<!-- add new customer section start -->
<section class="dash-section">
    <h2 class="section-title">Add New Customer</h2>
    <!-- add form start -->
<form method="POST" class="add-form">
        <input type="hidden" name="action" value="add" />
        <div class="form-row">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required />
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" />
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="City, Country" />
            </div>
        </div>
        <button type="submit" class="dash-btn">Add Customer</button>
    </form>
<!-- add form end -->
</section>
<!-- add new customer section end -->

<!-- all customers section start -->
<section class="dash-section">
    <div class="table-top-bar">
        <h2 class="section-title" style="margin:0;">All Customers</h2>
        <form method="GET" class="search-filter-bar">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email..." class="search-input" />
            <select name="status_filter" class="status-select">
                <option value="all"      <?= $status_filter === "all"      ? "selected" : "" ?>>All Status</option>
                <option value="vip"      <?= $status_filter === "vip"      ? "selected" : "" ?>>VIP</option>
                <option value="active"   <?= $status_filter === "active"   ? "selected" : "" ?>>Active</option>
                <option value="new"      <?= $status_filter === "new"      ? "selected" : "" ?>>New</option>
                <option value="inactive" <?= $status_filter === "inactive" ? "selected" : "" ?>>Inactive</option>
            </select>
            <button type="submit" class="dash-btn-small">Search</button>
            <?php if ($search || $status_filter !== "all"): ?>
                <a href="customer_management.php" class="link-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- data table start -->
<div class="table-wrap" style="margin-top:20px;">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Orders</th>
                    <th>Spent</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c):
                    $badgeMap = ["vip" => "badge-purple", "active" => "badge-green", "new" => "badge-blue", "inactive" => "badge-red"];
                    $badge = $badgeMap[$c["status"]] ?? "badge-blue";
                    $label = $c["status"] === "vip" ? "⭐ VIP" : $c["status"];
                ?>
                <tr>
                    <td>C<?= str_pad($c["customer_id"], 3, "0", STR_PAD_LEFT) ?></td>
                    <td class="bold"><?= htmlspecialchars($c["full_name"]) ?></td>
                    <td class="muted"><?= htmlspecialchars($c["email"]) ?></td>
                    <td class="muted"><?= htmlspecialchars($c["phone"]) ?></td>
                    <td class="muted"><?= htmlspecialchars($c["location"]) ?></td>
                    <td class="center"><?= $c["total_orders"] ?></td>
                    <td class="accent-red bold">$<?= number_format($c["total_spent"], 2) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                    <td>
                        <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
                            <form method="POST" style="display:flex; gap:6px; align-items:center;">
                                <input type="hidden" name="action" value="update_status" />
                                <input type="hidden" name="customer_id" value="<?= $c["customer_id"] ?>" />
                                <select name="status" class="status-select">
                                    <?php foreach (["active","vip","new","inactive"] as $opt): ?>
                                        <option value="<?= $opt ?>" <?= $opt === $c["status"] ? "selected" : "" ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="dash-btn-small">Save</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Remove this customer?');" style="display:inline">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="customer_id" value="<?= $c["customer_id"] ?>" />
                                <button type="submit" class="link-btn">Remove</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($customers)): ?>
                <tr><td colspan="9" class="empty-row">No customers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<!-- all customers section end -->

<?php
// dashboard end
include '../includes/footer.php';
$conn->close();
?>
