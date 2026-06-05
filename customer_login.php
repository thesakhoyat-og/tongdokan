<?php
// php logic start

session_start();
require_once 'db_connect.php';

if (isset($_SESSION["customer_id"])) {
    header("Location: customer_dashboard.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Please fill in both fields.";
    } else {
        $stmt = $conn->prepare("SELECT customer_id, full_name, email, password, status FROM customers WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $customer = $result->fetch_assoc();
            if ($password === $customer["password"]) {
                $_SESSION["customer_id"]   = $customer["customer_id"];
                $_SESSION["customer_name"] = $customer["full_name"];
                $_SESSION["customer_email"]= $customer["email"];
                header("Location: customer_dashboard.php");
                exit;
            } else {
                $error = "Wrong password. Try again.";
            }
        } else {
            $error = "No account found with that email.";
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
<title>Customer Login - Tong Dokan</title>
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
            <div class="logo-sub">Customer login</div>
        </div>

        <form method="POST" action="customer_login.php" novalidate>
            <div class="form-group">
                <label>Email Address</label>
                <input class="login-input" type="email" name="email"
                       value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                       placeholder="your@email.com" autofocus />
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
            <div style="font-size:13px; color:#5A4A3A; margin-bottom:8px;">
                Don't have an account?
                <a href="customer_register.php" style="color:#B02F2F; font-weight:600;">Create one</a>
            </div>
            <div style="font-size:13px; color:#5A4A3A;">
                Are you staff?
                <a href="login.php" style="color:#B02F2F; font-weight:600;">Staff login →</a>
            </div>
        </div>
    </div>
</div>

<!-- customer login page end -->

</body>
</html>
<?php $conn->close(); ?>
