<?php
// php logic start

session_start();
require_once 'db_connect.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        $error = "Please fill in both fields.";
    } else {
        $stmt = $conn->prepare("SELECT staff_id, username, password, full_name, role FROM staff WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $staff = $result->fetch_assoc();
            if ($password === $staff["password"]) {
                $_SESSION["staff_id"]   = $staff["staff_id"];
                $_SESSION["username"]   = $staff["username"];
                $_SESSION["full_name"]  = $staff["full_name"];
                $_SESSION["role"]       = $staff["role"];

                $roleRoutes = [
                    "Product Manager"  => "dashboards/product_management.php",
                    "Order Manager"    => "dashboards/order_management.php",
                    "Customer Manager" => "dashboards/customer_management.php",
                    "Staff Manager"    => "dashboards/staff_management.php",
                    "Payment Manager"  => "dashboards/payment_delivery.php",
                ];
                $redirect = $roleRoutes[$staff["role"]] ?? "index.php";
                header("Location: " . $redirect);
                exit;
            } else {
                $error = "Wrong password. Try again.";
            }
        } else {
            $error = "Username not found.";
        }
        $stmt->close();
    }
}
// php logic end
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Login - Tong Dokan</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Tiro+Bangla:ital@0;1&family=EB+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Bebas+Neue&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="styles.css?v=<?= filemtime(__DIR__ . '/styles.css') ?>" />
</head>
<body>

<div class="login-page">
    <span class="petal" style="top:12%; left:10%; animation-delay:0s;">🪷</span>
    <span class="petal" style="top:72%; left:8%;  animation-delay:1.5s;">🌸</span>
    <span class="petal" style="top:18%; right:12%; animation-delay:0.8s;">✦</span>
    <span class="petal" style="top:78%; right:10%; animation-delay:2.2s;">🪷</span>

    <a href="index.php" class="back-to-shop-btn">← Back to Shop</a>

    <div class="login-card <?= $error ? 'shake' : '' ?>">
        <div class="login-logo">
            <div class="logo-circle">
                <span class="logo-bangla">ট</span>
                <div class="logo-dash"></div>
            </div>
            <div class="logo-title">TONG DOKAN</div>
            <div class="logo-sub">স্বাগতম · welcome back</div>
        </div>

        <form method="POST" action="login.php" novalidate>
            <div class="form-group">
                <label>Username</label>
                <input class="login-input" type="text" name="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    placeholder="your username..." autofocus />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input class="login-input" type="password" name="password" placeholder="••••••••" />
            </div>

            <?php if ($error): ?>
                <div class="login-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <button type="submit" class="login-submit-btn">Log In →</button>
        </form>

        <div class="login-footer">
            <div class="login-footer-bangla">আমাদের গল্প, তোমাদের ঘরে</div>
            <div class="login-footer-tag">Made with chai in Dhaka</div>
        </div>
    </div>
</div>

<!-- login page end -->

</body>
</html>
