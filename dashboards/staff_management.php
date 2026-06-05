<?php
// php logic start

session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["staff_id"]) || $_SESSION["role"] !== "Staff Manager") {
    header("Location: ../login.php");
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    if ($action === "add_staff") {
        $username   = trim($_POST["username"] ?? "");
        $password   = trim($_POST["password"] ?? "");
        $full_name  = trim($_POST["full_name"] ?? "");
        $role       = trim($_POST["role"] ?? "");
        $department = trim($_POST["department"] ?? "");
        $shift      = trim($_POST["shift"] ?? "Morning");
        if ($username && $password && $full_name && $role) {
            // Check for duplicate username
            $check = $conn->prepare("SELECT staff_id FROM staff WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $message = "Username already taken. Choose another.";
            } else {
                $stmt = $conn->prepare("INSERT INTO staff (username, password, full_name, role, department, shift, status, joined_date) VALUES (?, ?, ?, ?, ?, ?, 'active', CURDATE())");
                $stmt->bind_param("ssssss", $username, $password, $full_name, $role, $department, $shift);
                if ($stmt->execute()) {
                    $message = "New staff member added successfully.";
                } else {
                    $message = "Could not add staff member.";
                }
                $stmt->close();
            }
        } else {
            $message = "Username, password, full name and role are required.";
        }
    }
    if ($action === "delete_staff") {
        $id = intval($_POST["staff_id"] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $message = "Staff member removed.";
        }
    }
    if ($action === "update_status") {
        $id     = intval($_POST["staff_id"] ?? 0);
        $status = trim($_POST["status"] ?? "");
        $valid  = ["active", "inactive", "on_leave"];
        if ($id > 0 && in_array($status, $valid)) {
            $stmt = $conn->prepare("UPDATE staff SET status = ? WHERE staff_id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();
            $message = "Staff status updated.";
        }
    }
}

$staff = $conn->query("SELECT * FROM staff ORDER BY staff_id ASC")->fetch_all(MYSQLI_ASSOC);
$search = trim($_GET["search"] ?? "");
$filter = trim($_GET["filter"] ?? "all");

$where  = [];
$params = [];
$types  = "";

if ($search !== "") {
    $where[]  = "(full_name LIKE ? OR username LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $types   .= "ss";
}
if ($filter !== "all" && in_array($filter, ["active","inactive","on_leave"])) {
    $where[]  = "status = ?";
    $params[] = $filter;
    $types   .= "s";
}

$sql = "SELECT * FROM staff";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY staff_id ASC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$filtered_staff = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_staff  = count($staff);
$active_count = 0;
$departments  = [];
foreach ($staff as $s) {
    if ($s["status"] === "active") $active_count++;
    if (!in_array($s["department"], $departments)) $departments[] = $s["department"];
}
$dept_count = count($departments);

$page_title  = "Staff Management";
$active_page = "staff";
// php logic end
include '../includes/header.php';
?>

<!-- page header start -->
<div class="dash-header">
    <div class="dash-eyebrow">Staff Management</div>
    <h1 class="dash-title">What's up, <span class="accent-red">Shafin</span>!</h1>
    <p class="dash-sub">The team that makes the dokan run. You keep them together.</p>
</div>
<!-- page header end -->

<?php if ($message): ?>
    <div class="dash-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- stat cards start -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Staff</div>
        <div class="stat-value"><?= $total_staff ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active Today</div>
        <div class="stat-value accent-green"><?= $active_count ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Departments</div>
        <div class="stat-value accent-red"><?= $dept_count ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Inactive</div>
        <div class="stat-value accent-yellow"><?= $total_staff - $active_count ?></div>
    </div>
</div>

<!-- add new staff member section start -->
<section class="dash-section">
    <h2 class="section-title">Add New Staff Member</h2>
    <!-- add form start -->
<form method="POST" class="add-form">
        <input type="hidden" name="action" value="add_staff" />
        <div class="form-row">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. John Smith" />
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="e.g. johnsmith" />
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Password</label>
                <input type="text" name="password" required placeholder="set a password" />
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="Product Manager">Product Manager</option>
                    <option value="Order Manager">Order Manager</option>
                    <option value="Customer Manager">Customer Manager</option>
                    <option value="Staff Manager">Staff Manager</option>
                    <option value="Payment Manager">Payment Manager</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" placeholder="e.g. Operations" />
            </div>
            <div class="form-group">
                <label>Shift</label>
                <select name="shift">
                    <option value="Morning">Morning</option>
                    <option value="Evening">Evening</option>
                    <option value="Night">Night</option>
                </select>
            </div>
        </div>
        <button type="submit" class="dash-btn">Add Staff Member</button>
    </form>
<!-- add form end -->
</section>
<!-- add new staff member section end -->

<!-- department overview section start -->
<section class="dash-section">
    <h2 class="section-title">Department Overview</h2>
    <div class="dept-grid">
        <?php foreach ($staff as $s):
            $emojiMap = ["Catalogue" => "🪡", "Operations" => "📦", "Support" => "💬", "HR" => "👨‍💼", "Finance" => "💳"];
            $emoji = $emojiMap[$s["department"]] ?? "👤";
        ?>
        <div class="dept-card">
            <div class="dept-emoji"><?= $emoji ?></div>
            <div>
                <div class="dept-name"><?= htmlspecialchars($s["department"]) ?></div>
                <div class="bold"><?= htmlspecialchars($s["full_name"]) ?></div>
                <div class="muted small"><?= htmlspecialchars($s["role"]) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<!-- department overview section end -->

<!-- all staff members section start -->
<section class="dash-section">
    <div class="table-top-bar">
        <h2 class="section-title" style="margin:0;">All Staff Members</h2>

        <form method="GET" class="search-filter-bar">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or username..." class="search-input" />
            <select name="filter" class="status-select">
                <option value="all"      <?= $filter === "all"      ? "selected" : "" ?>>All Status</option>
                <option value="active"   <?= $filter === "active"   ? "selected" : "" ?>>Active</option>
                <option value="inactive" <?= $filter === "inactive" ? "selected" : "" ?>>Inactive</option>
                <option value="on_leave" <?= $filter === "on_leave" ? "selected" : "" ?>>On Leave</option>
            </select>
            <button type="submit" class="dash-btn-small">Search</button>
            <?php if ($search || $filter !== "all"): ?>
                <a href="staff_management.php" class="link-btn">Clear</a>
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
                    <th>Username</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Shift</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Update Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filtered_staff as $s):
                    $badgeMap = ["active" => "badge-green", "inactive" => "badge-red", "on_leave" => "badge-yellow"];
                    $badge = $badgeMap[$s["status"]] ?? "badge-blue";
                ?>
                <tr>
                    <td>S<?= str_pad($s["staff_id"], 3, "0", STR_PAD_LEFT) ?></td>
                    <td class="bold"><?= htmlspecialchars($s["full_name"]) ?></td>
                    <td class="muted"><?= htmlspecialchars($s["username"]) ?></td>
                    <td><?= htmlspecialchars($s["role"]) ?></td>
                    <td><span class="badge badge-blue"><?= htmlspecialchars($s["department"]) ?></span></td>
                    <td class="muted"><?= htmlspecialchars($s["shift"]) ?></td>
                    <td class="muted"><?= htmlspecialchars($s["joined_date"]) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($s["status"]) ?></span></td>
                    <td>
                        <form method="POST" style="display:flex; gap:6px;">
                            <input type="hidden" name="action" value="update_status" />
                            <input type="hidden" name="staff_id" value="<?= $s["staff_id"] ?>" />
                            <select name="status" class="status-select">
                                <?php foreach (["active","inactive","on_leave"] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $opt === $s["status"] ? "selected" : "" ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="dash-btn-small">Save</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Remove this staff member?');" style="display:inline">
                            <input type="hidden" name="action" value="delete_staff" />
                            <input type="hidden" name="staff_id" value="<?= $s["staff_id"] ?>" />
                            <button type="submit" class="link-btn">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<!-- all staff members section end -->

<?php
// dashboard end
include '../includes/footer.php';
$conn->close();
?>
