<?php
// php logic start

session_start();
require_once '../db_connect.php';
if (!isset($_SESSION["staff_id"]) || $_SESSION["role"] !== "Product Manager") {
    header("Location: ../login.php");
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // -----------------------------------------------
    // ADD NEW PRODUCT
    // Runs when the Add Product form is submitted
    // -----------------------------------------------
    if ($action === "add") {
        // Collect and clean all the form inputs
        $name     = trim($_POST["product_name"] ?? "");
        $desc     = trim($_POST["description"] ?? "");
        $price    = floatval($_POST["price"] ?? 0);
        $stock    = intval($_POST["stock_quantity"] ?? 0);
        $category = trim($_POST["category"] ?? "");
        $origin   = trim($_POST["origin"] ?? "");
        $emoji    = trim($_POST["emoji"] ?? "📦");
        $tag      = trim($_POST["tag"] ?? "New");

        // Only insert if we have at least a name and a valid price
        if ($name && $price > 0) {
            // Using prepared statement to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, stock_quantity, category, origin, emoji, tag) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdissss", $name, $desc, $price, $stock, $category, $origin, $emoji, $tag);
            $stmt->execute();
            $stmt->close();
            $message = "Product added successfully.";
        } else {
            $message = "Please provide name and price.";
        }
    }

    // -----------------------------------------------
    // UPDATE EXISTING PRODUCT
    // Runs when the inline edit form is saved
    // -----------------------------------------------
    if ($action === "update") {
        $id        = intval($_POST["product_id"] ?? 0);
        $name      = trim($_POST["product_name"] ?? "");
        $desc      = trim($_POST["description"] ?? "");
        $price     = floatval($_POST["price"] ?? 0);
        $stock     = intval($_POST["stock_quantity"] ?? 0);
        $category  = trim($_POST["category"] ?? "");
        $origin    = trim($_POST["origin"] ?? "");
        $tag       = trim($_POST["tag"] ?? "");
        // is_available controls whether the product shows on the storefront
        $available = intval($_POST["is_available"] ?? 1);

        if ($id > 0 && $name && $price > 0) {
            $stmt = $conn->prepare("UPDATE products SET product_name=?, description=?, price=?, stock_quantity=?, category=?, origin=?, tag=?, is_available=? WHERE product_id=?");
            $stmt->bind_param("ssdisssii", $name, $desc, $price, $stock, $category, $origin, $tag, $available, $id);
            $stmt->execute();
            $stmt->close();
            $message = "Product updated successfully.";
        }
    }

    // -----------------------------------------------
    // DELETE PRODUCT
    // Runs when the Remove button is clicked
    // -----------------------------------------------
    if ($action === "delete") {
        $id = intval($_POST["product_id"] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $message = "Product removed.";
        }
    }
}
$search = trim($_GET["search"] ?? "");
$filter = trim($_GET["filter"] ?? "all");

$where  = [];
$params = [];
$types  = "";
if ($search !== "") {
    $where[]  = "(product_name LIKE ? OR origin LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $types   .= "ss";
}
if ($filter === "active") {
    $where[] = "stock_quantity > 5";
} elseif ($filter === "low") {
    $where[] = "stock_quantity > 0 AND stock_quantity <= 5";
} elseif ($filter === "out") {
    $where[] = "stock_quantity = 0";
}
$sql = "SELECT * FROM products";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY product_id DESC";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$all_products   = $conn->query("SELECT * FROM products")->fetch_all(MYSQLI_ASSOC);
$total_products = count($all_products);
$active_count   = 0;
$low_stock      = 0;
$total_value    = 0;

foreach ($all_products as $p) {
    if ($p["stock_quantity"] > 5)                              $active_count++;
    if ($p["stock_quantity"] > 0 && $p["stock_quantity"] <= 5) $low_stock++;
    // Stock value = price x quantity for each product, added up
    $total_value += $p["price"] * $p["stock_quantity"];
}
$edit_id = intval($_GET["edit"] ?? 0);

$page_title = "Product Management";
$active_page = "product";
// php logic end
include '../includes/header.php';
?>

<!-- page header start -->
<div class="dash-header">
    <div class="dash-eyebrow">Product Management</div>
    <h1 class="dash-title">Hello, <span class="accent-red">Siam</span>!</h1>
    <p class="dash-sub">Welcome back. Manage the catalogue and keep the shelves alive.</p>
</div>
<!-- page header end -->

<?php if ($message): ?>
    <div class="dash-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- stat cards start -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Products</div>
        <div class="stat-value accent-red"><?= $total_products ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">In Stock</div>
        <div class="stat-value accent-green"><?= $active_count ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Low Stock</div>
        <div class="stat-value accent-yellow"><?= $low_stock ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Stock Value</div>
        <div class="stat-value">$<?= number_format($total_value, 0) ?></div>
    </div>
</div>

<!-- add new product section start -->
<section class="dash-section">
    <h2 class="section-title">Add New Product</h2>
    <!-- add form start -->
<form method="POST" class="add-form">
        <input type="hidden" name="action" value="add" />
        <div class="form-row">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_name" required />
            </div>
            <div class="form-group">
                <label>Price (USD)</label>
                <input type="number" name="price" step="0.01" min="0" required />
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" placeholder="e.g. Saree & Fabric" />
            </div>
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" />
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Origin (District)</label>
                <input type="text" name="origin" placeholder="e.g. Dhaka" />
            </div>
            <div class="form-group">
                <label>Tag</label>
                <input type="text" name="tag" placeholder="e.g. New, Heritage" />
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="2"></textarea>
        </div>
        <button type="submit" class="dash-btn">Add Product</button>
    </form>
<!-- add form end -->
</section>
<!-- add new product section end -->

<!-- all products section start -->
<section class="dash-section">
    <div class="table-top-bar">
        <h2 class="section-title" style="margin:0;">All Products</h2>

        <!-- Search and filter bar — submits as GET so filters stay in the URL -->
        <form method="GET" class="search-filter-bar">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or origin..." class="search-input" />
            <select name="filter" class="status-select">
                <option value="all"    <?= $filter === "all"    ? "selected" : "" ?>>All Status</option>
                <option value="active" <?= $filter === "active" ? "selected" : "" ?>>Active</option>
                <option value="low"    <?= $filter === "low"    ? "selected" : "" ?>>Low Stock</option>
                <option value="out"    <?= $filter === "out"    ? "selected" : "" ?>>Out of Stock</option>
            </select>
            <button type="submit" class="dash-btn-small">Search</button>
            <?php if ($search || $filter !== "all"): ?>
                <a href="product_management.php" class="link-btn">Clear</a>
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
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Origin</th>
                    <th>Status</th>
                    <th>Available</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p):
                    // Work out the stock badge colour based on quantity
                    $stock = (int)$p["stock_quantity"];
                    if ($stock === 0)    { $badge = "badge-red";    $status_text = "Out"; }
                    elseif ($stock <= 5) { $badge = "badge-yellow"; $status_text = "Low"; }
                    else                 { $badge = "badge-green";  $status_text = "Active"; }

                    // Check if this specific row is the one being edited
                    $is_editing = ($edit_id === (int)$p["product_id"]);
                ?>
                <tr>
                    <td>#<?= $p["product_id"] ?></td>
                    <td><span class="product-emoji"><?= htmlspecialchars($p["emoji"] ?? "📦") ?></span> <?= htmlspecialchars($p["product_name"]) ?></td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($p["category"]) ?></span></td>
                    <td class="accent-red bold">$<?= number_format($p["price"], 2) ?></td>
                    <td><?= $stock ?></td>
                    <td class="muted"><?= htmlspecialchars($p["origin"]) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $status_text ?></span></td>
                    <td>
                        <?php if ($p["is_available"]): ?>
                            <span class="badge badge-green">Visible</span>
                        <?php else: ?>
                            <span class="badge badge-red">Hidden</span>
                        <?php endif; ?>
                    </td>
                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
                        <!-- Edit button passes the product ID in the URL -->
                        <a href="product_management.php?edit=<?= $p["product_id"] ?><?= $search ? "&search=".urlencode($search) : "" ?><?= $filter !== "all" ? "&filter=".$filter : "" ?>" class="dash-btn-small">Edit</a>
                        <!-- Remove button asks for confirmation before deleting -->
                        <form method="POST" onsubmit="return confirm('Remove this product?');" style="display:inline">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="product_id" value="<?= $p["product_id"] ?>" />
                            <button type="submit" class="link-btn">Remove</button>
                        </form>
                    </td>
                </tr>

                <?php if ($is_editing): ?>
                <!-- INLINE EDIT FORM — only shows for the row being edited -->
                <tr class="edit-row">
                    <td colspan="9">
                        <div class="inline-edit-form">
                            <div class="inline-edit-title">Editing: <?= htmlspecialchars($p["product_name"]) ?></div>
                            <form method="POST" class="add-form">
                                <!-- Hidden fields tell PHP which action to run and which product to update -->
                                <input type="hidden" name="action" value="update" />
                                <input type="hidden" name="product_id" value="<?= $p["product_id"] ?>" />
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Product Name</label>
                                        <!-- Pre-filled with current values from the database -->
                                        <input type="text" name="product_name" value="<?= htmlspecialchars($p["product_name"]) ?>" required />
                                    </div>
                                    <div class="form-group">
                                        <label>Price (USD)</label>
                                        <input type="number" name="price" step="0.01" value="<?= $p["price"] ?>" required />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <input type="text" name="category" value="<?= htmlspecialchars($p["category"]) ?>" />
                                    </div>
                                    <div class="form-group">
                                        <label>Stock Quantity</label>
                                        <input type="number" name="stock_quantity" value="<?= $p["stock_quantity"] ?>" min="0" />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Origin (District)</label>
                                        <input type="text" name="origin" value="<?= htmlspecialchars($p["origin"]) ?>" />
                                    </div>
                                    <div class="form-group">
                                        <label>Tag</label>
                                        <input type="text" name="tag" value="<?= htmlspecialchars($p["tag"]) ?>" />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" rows="2"><?= htmlspecialchars($p["description"]) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <!-- Visibility toggle — hidden products do not appear on the storefront -->
                                        <label>Visibility on Shop</label>
                                        <select name="is_available">
                                            <option value="1" <?= $p["is_available"] ? "selected" : "" ?>>Visible (Active)</option>
                                            <option value="0" <?= !$p["is_available"] ? "selected" : "" ?>>Hidden</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display:flex; gap:12px; margin-top:8px;">
                                    <button type="submit" class="dash-btn">Save Changes</button>
                                    <!-- Cancel just goes back to the same page without the edit parameter -->
                                    <a href="product_management.php" class="dash-btn" style="background:#5A4A3A; text-decoration:none;">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>

                <?php endforeach; ?>

                <?php if (empty($products)): ?>
                <tr><td colspan="9" class="empty-row">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<!-- all products section end -->

<?php
// dashboard end
include '../includes/footer.php';
$conn->close();
?>
