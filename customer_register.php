<?php
// php logic start

session_start();
require_once 'db_connect.php';

if (isset($_SESSION["customer_id"])) {
    header("Location: customer_dashboard.php");
    exit;
}

$error   = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $password  = trim($_POST["password"] ?? "");
    $confirm   = trim($_POST["confirm_password"] ?? "");
    $phone     = trim($_POST["phone"] ?? "");
    $location  = trim($_POST["location"] ?? "");

    if ($full_name === "" || $email === "" || $password === "" || $confirm === "") {
        $error = "Name, email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $check = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->get_result()->num_rows > 0 ? $exists = true : $exists = false;
        $check->close();

        if ($exists) {
            $error = "An account with that email already exists. Please log in.";
        } else {
            $stmt = $conn->prepare("INSERT INTO customers (full_name, email, password, phone, location, status, joined_date) VALUES (?, ?, ?, ?, ?, 'new', CURDATE())");
            $stmt->bind_param("sssss", $full_name, $email, $password, $phone, $location);
            if ($stmt->execute()) {
                $success = "Account created! You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }
}
// php logic end
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Create Account - Tong Dokan</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Tiro+Bangla:ital@0;1&family=EB+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Bebas+Neue&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>" />
</head>
<body>

<div class="login-page">
    <span class="petal" style="top:12%; left:10%; animation-delay:0s;">🪷</span>
    <span class="petal" style="top:70%; left:8%;  animation-delay:1.5s;">🌸</span>
    <span class="petal" style="top:20%; right:10%; animation-delay:0.8s;">✦</span>
    <span class="petal" style="top:75%; right:9%;  animation-delay:2.2s;">🪷</span>

    <a href="index.php" class="back-to-shop-btn">← Back to Shop</a>

    <div class="login-card" style="max-width: 500px;">
        <div class="login-logo">
            <div class="logo-circle">
                <span class="logo-bangla">ট</span>
                <div class="logo-dash"></div>
            </div>
            <div class="logo-title">TONG DOKAN</div>
            <div class="logo-sub">Create your account</div>
        </div>

        <?php if ($success): ?>
            <div class="dash-message"><?= htmlspecialchars($success) ?>
                <br /><a href="customer_login.php" style="color:#065F46; font-weight:600;">Go to Login →</a>
            </div>
        <?php else: ?>

        <?php if ($error): ?>
            <div class="login-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label>Full Name</label>
                <input class="login-input" type="text" name="full_name"
                       value="<?= htmlspecialchars($_POST["full_name"] ?? "") ?>" required />
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input class="login-input" type="email" name="email"
                       value="<?= htmlspecialchars($_POST["email"] ?? "") ?>" required />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input class="login-input" type="password" name="password" required minlength="6" placeholder="min 6 characters" />
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input class="login-input" type="password" name="confirm_password" required />
            </div>
            <div class="form-group">
                <label>Phone <span style="color:#5A4A3A; font-weight:400;">(optional)</span></label>
                <input class="login-input" type="text" name="phone"
                       value="<?= htmlspecialchars($_POST["phone"] ?? "") ?>" />
            </div>
            <div class="form-group">
                <label>Location <span style="color:#5A4A3A; font-weight:400;">(optional)</span></label>
                <input class="login-input" type="text" name="location"
                       value="<?= htmlspecialchars($_POST["location"] ?? "") ?>" placeholder="City, Country" />
            </div>
            <button type="submit" class="login-submit-btn">Create Account →</button>
        </form>

        <div class="login-footer">
            <div style="font-size:13px; color:#5A4A3A;">
                Already have an account?
                <a href="customer_login.php" style="color:#B02F2F; font-weight:600;">Log in</a>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<!-- register page end -->

</body>
</html>
<?php $conn->close(); ?>
