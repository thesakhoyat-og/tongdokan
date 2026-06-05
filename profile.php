<?php
// php logic start

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION["staff_id"])) {
    header("Location: login.php");
    exit;
}

$staff_id = $_SESSION["staff_id"];
$success  = "";
$error    = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "change_username") {
        $new_username     = trim($_POST["new_username"] ?? "");
        $password_confirm = trim($_POST["password_confirm"] ?? "");

        if ($new_username === "" || $password_confirm === "") {
            $error = "All fields are required.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM staff WHERE staff_id = ?");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($password_confirm !== $row["password"]) {
                $error = "Current password is incorrect.";
            } else {
                $check = $conn->prepare("SELECT staff_id FROM staff WHERE username = ? AND staff_id != ?");
                $check->bind_param("si", $new_username, $staff_id);
                $check->execute();
                $taken = $check->get_result()->num_rows > 0;
                $check->close();

                if ($taken) {
                    $error = "That username is already taken.";
                } else {
                    $upd = $conn->prepare("UPDATE staff SET username = ? WHERE staff_id = ?");
                    $upd->bind_param("si", $new_username, $staff_id);
                    $upd->execute();
                    $upd->close();
                    $_SESSION["username"] = $new_username;
                    $success = "Username updated.";
                }
            }
        }
    }

    if ($action === "change_password") {
        $current_password = trim($_POST["current_password"] ?? "");
        $new_password     = trim($_POST["new_password"] ?? "");
        $confirm_password = trim($_POST["confirm_password"] ?? "");

        if ($current_password === "" || $new_password === "" || $confirm_password === "") {
            $error = "All fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM staff WHERE staff_id = ?");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($current_password !== $row["password"]) {
                $error = "Current password is incorrect.";
            } else {
                $upd = $conn->prepare("UPDATE staff SET password = ? WHERE staff_id = ?");
                $upd->bind_param("si", $new_password, $staff_id);
                $upd->execute();
                $upd->close();
                $success = "Password updated.";
            }
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();

$page_title  = "My Profile";
$active_page = "profile";
include 'includes/header.php';
// php logic end
?>

<!-- page header start -->
<div class="dash-header">
    <div class="dash-eyebrow">Account Settings</div>
    <h1 class="dash-title">My <span class="accent-red">Profile</span></h1>
    <p class="dash-sub">Change your username or password here. Changes save to the database instantly.</p>
</div>
<!-- page header end -->

<?php if ($success): ?>
    <div class="dash-message"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="dash-message-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- profile grid start -->
<div class="profile-grid">

    <!-- current info card start -->
    <div class="dash-section">
        <h2 class="section-title">Current Info</h2>

        <div class="profile-info-row">
            <span class="profile-label">FULL NAME</span>
            <span class="profile-value bold"><?= htmlspecialchars($me["full_name"]) ?></span>
        </div>
        <div class="profile-info-row">
            <span class="profile-label">USERNAME</span>
            <span class="profile-value accent-red bold"><?= htmlspecialchars($me["username"]) ?></span>
        </div>
        <div class="profile-info-row">
            <span class="profile-label">ROLE</span>
            <span class="profile-value"><?= htmlspecialchars($me["role"]) ?></span>
        </div>
        <div class="profile-info-row">
            <span class="profile-label">DEPARTMENT</span>
            <span class="profile-value muted"><?= htmlspecialchars($me["department"]) ?></span>
        </div>
        <div class="profile-info-row">
            <span class="profile-label">STATUS</span>
            <span class="badge badge-green"><?= htmlspecialchars($me["status"]) ?></span>
        </div>
        <div class="profile-info-row">
            <span class="profile-label">JOINED</span>
            <span class="profile-value muted"><?= htmlspecialchars($me["joined_date"]) ?></span>
        </div>
    </div>
    <!-- current info card end -->

    <div>
        <!-- change username form start -->
        <div class="dash-section" style="margin-bottom: 24px;">
            <h2 class="section-title">Change Username</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_username" />
                <div class="form-group">
                    <label>NEW USERNAME</label>
                    <input type="text" name="new_username" required minlength="3" value="<?= htmlspecialchars($me["username"]) ?>" />
                </div>
                <div class="form-group">
                    <label>CONFIRM WITH CURRENT PASSWORD</label>
                    <input type="password" name="password_confirm" required placeholder="current password" />
                </div>
                <button type="submit" class="dash-btn">UPDATE USERNAME</button>
            </form>
        </div>
        <!-- change username form end -->

        <!-- change password form start -->
        <div class="dash-section">
            <h2 class="section-title">Change Password</h2>
            <form method="POST">
                <input type="hidden" name="action" value="change_password" />
                <div class="form-group">
                    <label>CURRENT PASSWORD</label>
                    <input type="password" name="current_password" required placeholder="your current password" />
                </div>
                <div class="form-group">
                    <label>NEW PASSWORD</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="min 6 characters" />
                </div>
                <div class="form-group">
                    <label>CONFIRM NEW PASSWORD</label>
                    <input type="password" name="confirm_password" required placeholder="type again" />
                </div>
                <button type="submit" class="dash-btn">UPDATE PASSWORD</button>
            </form>
        </div>
        <!-- change password form end -->
    </div>

</div>
<!-- profile grid end -->

<?php
include 'includes/footer.php';
$conn->close();
?>
