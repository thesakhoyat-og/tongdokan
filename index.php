<?php
// php logic start

session_start();
require_once 'db_connect.php';
if (!isset($_SESSION["cart"])) $_SESSION["cart"] = [];
$category = $_GET["category"] ?? "All";
$search   = trim($_GET["search"] ?? "");

$where = ["is_available = 1"];
$params = [];
$types = "";

if ($category !== "All" && $category !== "") {
    $where[]  = "category = ?";
    $params[] = $category;
    $types   .= "s";
}
if ($search !== "") {
    $where[]  = "(product_name LIKE ? OR description LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
    $types   .= "ss";
}

$sql = "SELECT * FROM products";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY product_id ASC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cats_result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = ["All"];
while ($row = $cats_result->fetch_assoc()) {
    $categories[] = $row["category"];
}

$cart_count = 0;
foreach ($_SESSION["cart"] as $qty) $cart_count += $qty;

// php logic end
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Tong Dokan — From the Tong to Your Door</title>
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
                <div class="nav-sub">est. গ্রাম থেকে শহর</div>
            </div>
        </div>
        <div class="nav-links">
            <a href="#shop" class="nav-link">Shop</a>
            <a href="#stories" class="nav-link">Stories</a>
            <a href="#artisans" class="nav-link">Artisans</a>

            <button onclick="document.getElementById('cartDrawer').classList.add('open'); document.getElementById('cartOverlay').classList.add('show');" class="cart-nav-btn">
                🛍️ Cart
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </button>

            <?php if (isset($_SESSION["staff_id"])): ?>
                <?php
                $roleRoutes = [
                    "Product Manager"  => "dashboards/product_management.php",
                    "Order Manager"    => "dashboards/order_management.php",
                    "Customer Manager" => "dashboards/customer_management.php",
                    "Staff Manager"    => "dashboards/staff_management.php",
                    "Payment Manager"  => "dashboards/payment_delivery.php",
                ];
                $myDash = $roleRoutes[$_SESSION["role"]] ?? "index.php";
                ?>
                <a href="<?= $myDash ?>" class="login-btn">Dashboard</a>
            <?php elseif (isset($_SESSION["customer_id"])): ?>
                <a href="customer_dashboard.php" class="login-btn">My Account</a>
            <?php else: ?>
                <a href="customer_login.php" class="login-btn">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- navbar end -->

<!-- hero section start -->
<section class="hero">
    <div class="hero-bg-glow-1"></div>
    <div class="hero-bg-glow-2"></div>
    <div class="hero-grid">
        <div class="hero-text float-in">
            <div class="hero-eyebrow">
                <span>✦</span>
                <span>৬৪ জেলা · One Shop</span>
            </div>
            <h1 class="hero-title">
                FROM THE <span class="hero-italic">tong</span><br />
                TO YOUR <span class="hero-underlined">DOOR</span>
            </h1>
            <p class="hero-blurb">
                A corner shop. A whole country. Every saree carries a song, every spice remembers a kitchen.
                We ship the soul of Bangladesh — handwoven, handpicked, hand-delivered.
            </p>
            <div class="hero-buttons">
                <a href="#shop" class="btn-primary">Shop the Bazaar →</a>
                <a href="#artisans" class="btn-outline">Meet the Artisans</a>
            </div>
            <div class="hero-stats">
                <div><div class="stat-num">২৪০+</div><div class="stat-text">Artisans paid fairly</div></div>
                <div><div class="stat-num">৬৪</div><div class="stat-text">Districts sourced</div></div>
                <div><div class="stat-num">৪.৯★</div><div class="stat-text">From 1,200+ orders</div></div>
            </div>
        </div>
        <div class="hero-art float-in">
            <svg viewBox="0 0 300 400">
                <defs>
                    <pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <circle cx="10" cy="10" r="1" fill="#E8B43A" opacity="0.3"/>
                    </pattern>
                </defs>
                <rect width="300" height="400" fill="url(#dots)"/>
                <circle cx="150" cy="120" r="50" fill="#E8B43A" opacity="0.95"/>
                <g transform="translate(150, 280)">
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(0)"/>
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(45)"/>
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(90)"/>
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(135)"/>
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(180)"/>
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(225)"/>
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(270)"/>
                    <ellipse cx="0" cy="-25" rx="14" ry="35" fill="#FBF6EC" opacity="0.85" transform="rotate(315)"/>
                    <circle cx="0" cy="0" r="14" fill="#E8B43A"/>
                </g>
                <text x="150" y="370" text-anchor="middle" fill="#FBF6EC" font-size="28" font-family="'Tiro Bangla', serif" font-style="italic">টং দোকান</text>
            </svg>
            <div class="hero-tag-1">Free shipping $50+</div>
            <div class="hero-tag-2">"Like opening a parcel from home" — Rifa, Toronto</div>
        </div>
    </div>
</section>
<!-- hero section end -->

<!-- ticker start -->
<div class="ticker">
    <div class="ticker-track">
        <span>✦ Hand-stitched in Jessore</span>
        <span class="ticker-gold">✦ Brewed in Srimangal</span>
        <span>✦ Woven in Tangail</span>
        <span class="ticker-gold">✦ Hammered in Dhamrai</span>
        <span>✦ Shipped worldwide</span>
        <span class="ticker-gold">✦ ১০০% authentic</span>
        <span>✦ Hand-stitched in Jessore</span>
        <span class="ticker-gold">✦ Brewed in Srimangal</span>
        <span>✦ Woven in Tangail</span>
        <span class="ticker-gold">✦ Hammered in Dhamrai</span>
    </div>
</div>
<!-- ticker end -->

<!-- shop section start -->
<section id="shop" class="shop-section">
    <div class="shop-header">
        <div>
            <div class="shop-eyebrow">The Shelves</div>
            <h2 class="shop-title">Browse the <span class="hero-italic">bazaar</span></h2>
        </div>
        <form method="GET" action="index.php#shop" class="search-form">
            <?php if ($category !== "All"): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>" />
            <?php endif; ?>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search saree, pitha, honey..." />
            <button type="submit" class="search-btn">🔍</button>
        </form>
    </div>

    <div class="cat-pills">
        <?php foreach ($categories as $cat): ?>
            <?php
            $url = "?category=" . urlencode($cat);
            if ($search) $url .= "&search=" . urlencode($search);
            $url .= "#shop";
            ?>
            <a href="<?= $url ?>" class="cat-pill <?= $cat === $category ? "active" : "" ?>">
                <?= htmlspecialchars($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (isset($_GET["msg"])): ?>
        <div class="cart-message"><?= htmlspecialchars($_GET["msg"]) ?></div>
    <?php endif; ?>

    <div class="product-grid">
        <?php foreach ($products as $idx => $p): ?>
            <article class="product-card float-in" style="animation-delay: <?= $idx * 0.05 ?>s;">
                <div class="product-image">
                    <span class="emoji-art"><?= htmlspecialchars($p["emoji"] ?? "📦") ?></span>
                    <?php if ($p["tag"]): ?>
                        <div class="product-tag"><?= htmlspecialchars($p["tag"]) ?></div>
                    <?php endif; ?>
                    <div class="product-origin">📍 <?= htmlspecialchars($p["origin"]) ?></div>
                </div>
                <div class="product-info">
                    <div class="product-cat"><?= htmlspecialchars($p["category"]) ?></div>
                    <h3 class="product-name"><?= htmlspecialchars($p["product_name"]) ?></h3>
                    <p class="product-desc"><?= htmlspecialchars($p["description"]) ?></p>
                    <div class="product-foot">
                        <div class="product-price">$<?= number_format($p["price"], 0) ?></div>
                        <form method="POST" action="cart_action.php" style="margin:0;">
                            <input type="hidden" name="action" value="add" />
                            <input type="hidden" name="product_id" value="<?= $p["product_id"] ?>" />
                            <button type="submit" class="add-btn">+ Add</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <div class="empty-shop">
                <div style="font-size:48px;">🔍</div>
                <p>Nothing here yet. Try another shelf?</p>
            </div>
        <?php endif; ?>
    </div>
</section>
<!-- shop section end -->

<!-- trust section start -->
<section class="trust-section">
    <div class="trust-grid">
        <div>
            <div class="trust-icon">🚚</div>
            <h4>Worldwide Shipping</h4>
            <p>From Dhaka to your door — DHL, 5-9 days. Free on orders over $50.</p>
        </div>
        <div>
            <div class="trust-icon">🛡️</div>
            <h4>Authenticity Promise</h4>
            <p>Every item traceable to its maker. No middlemen, no markups.</p>
        </div>
        <div>
            <div class="trust-icon">❤️</div>
            <h4>Fair Trade, Always</h4>
            <p>70% of every dollar goes back to the artisan. We mean it.</p>
        </div>
    </div>
</section>
<!-- trust section end -->

<!-- footer start -->
<footer class="site-footer">
    <div class="footer-bangla">আমাদের গল্প, তোমাদের ঘরে</div>
    <p>Tong Dokan — a small shop with a big country behind it. Built by Khalid, Ovy, Siam, Shafin &amp; Mahraz.</p>
    <div class="footer-tag">© 2026 Tong Dokan · Made with chai in Dhaka</div>
    <div class="footer-staff-link">
        <?php if (isset($_SESSION["customer_id"])): ?>
            <a href="customer_logout.php">Sign out</a>
            <span class="footer-dot">·</span>
        <?php endif; ?>
        <?php if (isset($_SESSION["staff_id"])): ?>
            <a href="logout.php">Sign out of staff portal</a>
        <?php else: ?>
            <a href="login.php">Staff portal</a>
        <?php endif; ?>
    </div>
</footer>
<!-- footer end -->

<!-- cart drawer start -->
<!-- CART DRAWER (slides in from the right) -->
<div id="cartOverlay" class="cart-overlay" onclick="document.getElementById('cartDrawer').classList.remove('open'); this.classList.remove('show');"></div>

<aside id="cartDrawer" class="cart-drawer">
    <div class="cart-header">
        <div>
            <div class="cart-eyebrow">Your bazaar bag</div>
            <h3 class="cart-title"><?= $cart_count ?> item<?= $cart_count !== 1 ? "s" : "" ?></h3>
        </div>
        <button onclick="document.getElementById('cartDrawer').classList.remove('open'); document.getElementById('cartOverlay').classList.remove('show');" class="cart-close-btn">✕</button>
    </div>

    <div class="cart-body">
        <?php if (empty($_SESSION["cart"])): ?>
            <div class="cart-empty">
                <div style="font-size:48px; margin-bottom:16px;">🛍️</div>
                <p style="font-style:italic; color:#5A4A3A;">Your bag is feeling lonely. Send it some pitha.</p>
            </div>
        <?php else:
            $cart_total = 0;
            $cart_items = [];
            foreach ($_SESSION["cart"] as $pid => $qty) {
                $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
                $stmt->bind_param("i", $pid);
                $stmt->execute();
                $item = $stmt->get_result()->fetch_assoc();
                if ($item) {
                    $item["qty"] = $qty;
                    $item["subtotal"] = $item["price"] * $qty;
                    $cart_total += $item["subtotal"];
                    $cart_items[] = $item;
                }
                $stmt->close();
            }
        ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-img"><?= htmlspecialchars($item["emoji"]) ?></div>
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= htmlspecialchars($item["product_name"]) ?></div>
                        <div class="cart-item-price">$<?= number_format($item["subtotal"], 2) ?></div>
                        <div class="cart-item-qty">
                            <form method="POST" action="cart_action.php" style="margin:0;">
                                <input type="hidden" name="action" value="decrease" />
                                <input type="hidden" name="product_id" value="<?= $item["product_id"] ?>" />
                                <button type="submit" class="qty-btn">−</button>
                            </form>
                            <span class="qty-num"><?= $item["qty"] ?></span>
                            <form method="POST" action="cart_action.php" style="margin:0;">
                                <input type="hidden" name="action" value="increase" />
                                <input type="hidden" name="product_id" value="<?= $item["product_id"] ?>" />
                                <button type="submit" class="qty-btn">+</button>
                            </form>
                            <form method="POST" action="cart_action.php" style="margin:0; margin-left:8px;">
                                <input type="hidden" name="action" value="remove" />
                                <input type="hidden" name="product_id" value="<?= $item["product_id"] ?>" />
                                <button type="submit" class="qty-remove">✕</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION["cart"])): ?>
    <div class="cart-footer">
        <div class="cart-subtotal-row">
            <span class="cart-subtotal-label">Subtotal</span>
            <span class="cart-subtotal-amount">$<?= number_format($cart_total, 2) ?></span>
        </div>
        <?php if (isset($_SESSION["customer_id"])): ?>
            <a href="checkout.php" class="checkout-btn">Checkout →</a>
        <?php else: ?>
            <a href="customer_login.php" class="checkout-btn">Login to Checkout →</a>
        <?php endif; ?>
        <p class="cart-secure">Secure payment · PayPal, Apple Pay, Card</p>
    </div>
    <?php endif; ?>
</aside>
<!-- cart drawer end -->

<?php $conn->close(); ?>
</body>
</html>
