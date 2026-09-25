<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$flash = get_flash();
$pdo = db();

$products = $pdo->query('SELECT * FROM products ORDER BY created_at DESC')->fetchAll();
$contact = $pdo->query('SELECT * FROM contacts WHERE id = 1')->fetch() ?: [
    'phone' => '',
    'email' => '',
    'address' => '',
];
$sellerPhoneDigits = preg_replace('/\D+/', '', (string) $contact['phone']) ?: '';
$cartLines = get_cart_lines($pdo);
$cartTotal = cart_grand_total($cartLines);
$cartItemCount = cart_count();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chance Store Ltd</title>
    <link rel="icon" type="image/jpeg" href="logo.jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets.css">
</head>
<body>
<header>
    <div class="container header-row">
        <div class="brand-wrap">
            <img class="site-logo" src="logo.jpeg" alt="Chance Store Ltd Logo">
            <div class="brand">Chance Store Ltd</div>
        </div>
        <div class="header-nav-group">
            <nav class="site-nav" id="siteNav" aria-label="Main navigation">
                <a href="#products">Products</a>
                <a href="#cart" class="cart-nav-link cart-open-link">
                    My Order
                    <?php if ($cartItemCount > 0): ?>
                        <span class="cart-badge"><?= (int) $cartItemCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="#contact">Contact</a>
            </nav>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Toggle menu" aria-expanded="false">☰</button>
    </div>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <section class="hero">
        <div class="hero-inner">
            <span class="hero-badge">✦ Trusted Store in Rwanda</span>
            <h1>Welcome to Chance Store Ltd</h1>
            <p>Discover quality products, add multiple items to one order, and get direct support from our team. Simple, transparent, and reliable shopping.</p>
            <div class="hero-cta">
                <a class="btn btn-hero" href="#products">Browse Products</a>
                <a class="btn secondary btn-hero btn-hero-outline cart-open-link" href="#cart">View My Order</a>
            </div>
            <div class="stats">
                <div class="stat"><strong>24/7</strong><span>Order Collection</span></div>
                <div class="stat"><strong>Fast</strong><span>Customer Response</span></div>
                <div class="stat"><strong><?= count($products) ?></strong><span>Products Available</span></div>
            </div>
        </div>
    </section>

    <div class="panel" id="products">
        <div class="section-header">
            <div>
                <h2>Available Products</h2>
                <p class="section-subtitle">Add one or more products with quantities, then submit a single order.</p>
            </div>
            <span class="product-count"><?= count($products) ?> items</span>
        </div>
        <div class="search-wrap search-box">
            <span class="search-icon" aria-hidden="true">&#128269;</span>
            <input type="text" id="productSearch" placeholder="Search by name or description...">
        </div>
    </div>

    <?php if ($products): ?>
        <section class="grid" id="productGrid">
            <?php foreach ($products as $product): ?>
                <article class="card product-card">
                    <div class="product-img-wrap">
                        <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <span class="price-tag">FRW <?= number_format((float) $product['price'], 2) ?></span>
                    </div>
                    <div class="card-content">
                        <h3><?= e($product['name']) ?></h3>
                        <p><?= nl2br(e($product['description'])) ?></p>
                        <form class="add-to-cart-form" action="cart_actions.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                            <button type="button" class="btn add-to-cart-trigger">Add to Order</button>
                            <div class="add-to-cart-qty-wrap">
                                <label class="qty-label" for="qty-<?= (int) $product['id'] ?>">Quantity</label>
                                <div class="add-to-cart-row">
                                    <input id="qty-<?= (int) $product['id'] ?>" class="qty-input" name="quantity" type="number" min="1" value="1" required>
                                    <button type="submit" class="btn">Confirm Add</button>
                                </div>
                            </div>
                        </form>
                        <div class="actions" style="margin-top:10px">
                            <a class="btn secondary" href="tel:<?= e($sellerPhoneDigits) ?>">Call Seller</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <div class="panel empty-search" id="emptySearch">
            <div class="empty-search-icon" aria-hidden="true">&#128269;</div>
            <p><strong>No products found</strong></p>
            <p>Try a different search term or browse all products.</p>
        </div>
    <?php else: ?>
        <section class="panel">
            <h3>No Products Yet</h3>
            <p>Our catalog is being updated. Please check back shortly.</p>
        </section>
    <?php endif; ?>

    <section id="cart" class="panel order-panel cart-panel" hidden>
        <div class="section-header">
            <div>
                <h2>My Order</h2>
                <p class="section-subtitle">Review all products and quantities before submitting one combined order.</p>
            </div>
            <?php if ($cartLines !== []): ?>
                <span class="product-count"><?= (int) $cartItemCount ?> items</span>
            <?php endif; ?>
        </div>

        <?php if ($cartLines === []): ?>
            <div class="cart-empty">
                <div class="empty-search-icon" aria-hidden="true">&#128722;</div>
                <p><strong>Your order is empty</strong></p>
                <p>Add products above with the quantity you need for each item.</p>
                <a class="btn" href="#products">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Line Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartLines as $line): ?>
                            <tr>
                                <td>
                                    <div class="cart-product-cell">
                                        <img src="<?= e($line['image']) ?>" alt="">
                                        <span><?= e($line['name']) ?></span>
                                    </div>
                                </td>
                                <td>FRW <?= number_format((float) $line['unit_price'], 2) ?></td>
                                <td>
                                    <form class="cart-inline-form" action="cart_actions.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= (int) $line['product_id'] ?>">
                                        <input class="qty-input qty-input-sm" name="quantity" type="number" min="1" value="<?= (int) $line['quantity'] ?>" required>
                                        <button class="btn secondary btn-sm" type="submit">Update</button>
                                    </form>
                                </td>
                                <td><strong>FRW <?= number_format((float) $line['line_total'], 2) ?></strong></td>
                                <td>
                                    <form class="cart-inline-form" action="cart_actions.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= (int) $line['product_id'] ?>">
                                        <button class="btn danger btn-sm" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary">
                <div class="cart-grand-total">
                    <span>Grand Total</span>
                    <strong>FRW <?= number_format($cartTotal, 2) ?></strong>
                </div>
                <form action="cart_actions.php" method="post" class="cart-clear-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="clear">
                    <button class="btn secondary" type="submit">Clear Order</button>
                </form>
            </div>

            <div class="cart-checkout">
                <h3>Customer Details</h3>
                <form action="place_order.php" method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label for="customer_name">Your Name</label>
                    <input id="customer_name" name="customer_name" required maxlength="120" placeholder="Enter your full name">

                    <label for="customer_phone">Phone Number</label>
                    <input id="customer_phone" name="customer_phone" required maxlength="30" placeholder="e.g. 0781234567">

                    <label for="message">Optional Message</label>
                    <textarea id="message" name="message" rows="4" maxlength="1000" placeholder="Any special instructions for your order..."></textarea>

                    <div class="form-actions">
                        <button type="submit" class="btn">Submit Order (<?= count($cartLines) ?> product<?= count($cartLines) === 1 ? '' : 's' ?>)</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </section>

    <section class="two-cols">
        <div id="contact" class="panel contact-panel">
            <h3>Contact Seller</h3>
            <div class="contact-item">
                <span class="contact-item-icon" aria-hidden="true">&#9742;</span>
                <div>
                    <strong>Phone</strong>
                    <span><?= e($contact['phone']) ?></span>
                </div>
            </div>
            <div class="contact-item">
                <span class="contact-item-icon" aria-hidden="true">&#9993;</span>
                <div>
                    <strong>Email</strong>
                    <span><?= e($contact['email']) ?></span>
                </div>
            </div>
            <div class="contact-item">
                <span class="contact-item-icon" aria-hidden="true">&#128205;</span>
                <div>
                    <strong>Address</strong>
                    <span><?= e($contact['address']) ?></span>
                </div>
            </div>
            <a class="btn secondary whatsapp-btn" href="https://wa.me/<?= urlencode($sellerPhoneDigits) ?>" target="_blank" rel="noopener">
                <span aria-hidden="true">&#128172;</span> Chat on WhatsApp
            </a>
        </div>

        <div class="panel">
            <h3>Why Shop With Us?</h3>
            <div class="feature-grid">
                <div class="feature-card">
                    <span class="feature-icon" aria-hidden="true">&#128722;</span>
                    <div>
                        <h4>Multi-Product Orders</h4>
                        <p>Order several products in one request with different quantities.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <span class="feature-icon" aria-hidden="true">&#9889;</span>
                    <div>
                        <h4>Fast Response</h4>
                        <p>Quick communication and order confirmation every time.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <span class="feature-icon" aria-hidden="true">&#128172;</span>
                    <div>
                        <h4>Friendly Support</h4>
                        <p>Our team is ready to help with all your inquiries.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <span class="feature-icon" aria-hidden="true">&#128176;</span>
                    <div>
                        <h4>Clear Pricing</h4>
                        <p>Transparent prices and detailed product information.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="assets.js"></script>
</body>
</html>
