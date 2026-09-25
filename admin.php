<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_login();

$isAdmin = current_user_role() === 'admin';
$roleLabel = ucfirst(current_user_role() !== '' ? current_user_role() : 'seller');
$flash = get_flash();
$pdo = db();

$view = (string) ($_GET['view'] ?? 'orders');
$allowedViews = ['orders', 'products', 'contacts', 'reports', 'profile'];
if ($isAdmin) {
    $allowedViews[] = 'users';
}
if (!in_array($view, $allowedViews, true)) {
    $view = 'orders';
}
$reportStartInput = trim((string) ($_GET['report_start'] ?? ''));
$reportEndInput = trim((string) ($_GET['report_end'] ?? ''));

$perPage = 15;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = 0;

$products = [];
$productsTotal = 0;
$orders = [];
$ordersTotal = 0;
$users = [];
$usersTotal = 0;
$totalPages = 1;

if ($view === 'products') {
    $productsTotal = (int) ($pdo->query('SELECT COUNT(*) AS c FROM products')->fetch()['c'] ?? 0);
    $totalPages = max(1, (int) ceil($productsTotal / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare('SELECT * FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
}

if ($view === 'orders' || $view === 'reports') {
    $ordersTotal = count_customer_orders($pdo);
    $totalPages = max(1, (int) ceil($ordersTotal / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $orders = fetch_customer_orders($pdo, $perPage, $offset);
}

if ($isAdmin && $view === 'users') {
    $usersTotal = (int) ($pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'] ?? 0);
    $totalPages = max(1, (int) ceil($usersTotal / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare('SELECT id, name, email, role, telephone, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
}

function pagination_href(string $view, int $page): string
{
    return 'admin.php?view=' . urlencode($view) . '&page=' . $page;
}

function report_pdf_href(string $startDate, string $endDate): string
{
    $params = ['stream' => '1'];
    if ($startDate !== '') {
        $params['start_date'] = $startDate;
    }
    if ($endDate !== '') {
        $params['end_date'] = $endDate;
    }
    return 'report_pdf.php?' . http_build_query($params);
}

function order_status_badge_class(string $status): string
{
    $value = strtolower(trim($status));
    if ($value === 'pending') {
        return 'status-badge status-pending';
    }
    if ($value === 'done' || $value === 'completed') {
        return 'status-badge status-done';
    }
    if ($value === 'cancel' || $value === 'canceled' || $value === 'cancelled') {
        return 'status-badge status-cancel';
    }
    return 'status-badge';
}

function render_pagination(string $view, int $page, int $totalPages): void
{
    if ($totalPages <= 1) {
        return;
    }
    $prev = max(1, $page - 1);
    $next = min($totalPages, $page + 1);
    ?>
    <div class="pagination">
        <a class="btn secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(pagination_href($view, $prev)) ?>">Prev</a>
        <div class="pagination-meta">Page <strong><?= (int) $page ?></strong> of <strong><?= (int) $totalPages ?></strong></div>
        <a class="btn secondary <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= e(pagination_href($view, $next)) ?>">Next</a>
    </div>
    <?php
}

$contact = $pdo->query('SELECT * FROM contacts WHERE id = 1')->fetch() ?: ['phone' => '', 'email' => '', 'address' => ''];
$users = $isAdmin ? $users : [];

$report = ['total_orders' => 0, 'total_items' => 0, 'done_orders' => 0, 'pending_orders' => 0, 'cancel_orders' => 0];
$reportProducts = ['total_products' => 0];
$reportUsers = ['total_users' => 0];
$reportRevenue = ['estimated_revenue' => 0];
if ($isAdmin) {
    $report = $pdo->query(
        "SELECT
            COUNT(*) AS total_orders,
            COALESCE(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0) AS done_orders,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders,
            COALESCE(SUM(CASE WHEN status = 'cancel' THEN 1 ELSE 0 END), 0) AS cancel_orders
         FROM customer_orders"
    )->fetch() ?: $report;
    $report['total_items'] = (int) ($pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM order_items')->fetchColumn() ?: 0);
    $reportProducts = $pdo->query('SELECT COUNT(*) AS total_products FROM products')->fetch() ?: $reportProducts;
    $reportUsers = $pdo->query('SELECT COUNT(*) AS total_users FROM users')->fetch() ?: $reportUsers;
    $reportRevenue = $pdo->query(
        'SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS estimated_revenue FROM order_items oi'
    )->fetch() ?: $reportRevenue;
}

$editProduct = null;
if (!empty($_GET['edit'])) {
    $productId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $editProduct = $stmt->fetch();
    $view = 'products';
}
$createProductOpen = isset($_GET['create_product']) && $_GET['create_product'] === '1';
$editContactOpen = isset($_GET['edit_contact']) && $_GET['edit_contact'] === '1';

$createUserOpen = isset($_GET['create_user']) && $_GET['create_user'] === '1';
$editUser = null;
if ($isAdmin && $view === 'users' && !empty($_GET['edit_user'])) {
    $editUserId = (int) $_GET['edit_user'];
    if ($editUserId > 0) {
        $editUserStmt = $pdo->prepare('SELECT id, name, email, role, telephone FROM users WHERE id = ?');
        $editUserStmt->execute([$editUserId]);
        $editUser = $editUserStmt->fetch();
    }
}
$resetUser = null;
if ($isAdmin && $view === 'users' && !empty($_GET['reset_user'])) {
    $resetUserId = (int) $_GET['reset_user'];
    if ($resetUserId > 0) {
        $resetUserStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $resetUserStmt->execute([$resetUserId]);
        $resetUser = $resetUserStmt->fetch();
    }
}

$currentUser = null;
$currentUserId = (int) ($_SESSION['admin_id'] ?? 0);
if ($currentUserId > 0) {
    $currentUserStmt = $pdo->prepare('SELECT id, name, email, telephone, role, created_at FROM users WHERE id = ? LIMIT 1');
    $currentUserStmt->execute([$currentUserId]);
    $currentUser = $currentUserStmt->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chance Store Ltd</title>
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
            <div class="brand">Dashboard</div>
        </div>
        <div class="nav-actions">
            <span class="admin-greeting">Hi, <?= e((string) ($_SESSION['admin_name'] ?? 'User')) ?> (<?= e($roleLabel) ?>)</span>
            <a class="btn" href="index.php">View Site</a>
            <a class="btn danger" href="logout.php">Logout</a>
        </div>
    </div>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <nav class="admin-nav panel" aria-label="Dashboard menu">
                <div class="admin-nav-header">
                    <div class="admin-nav-brand">
                        <img class="admin-nav-logo" src="logo.jpeg" alt="">
                        <div>
                            <strong>Dashboard</strong>
                            <span>Chance Store Ltd</span>
                        </div>
                    </div>
                    <button id="menuToggle" class="admin-nav-toggle" type="button" aria-label="Collapse menu" title="Collapse menu">☰</button>
                </div>

                <div class="admin-nav-section">
                    <span class="admin-nav-label">Main Menu</span>
                    <div class="task-menu-list">
                        <a class="task-link <?= $view === 'orders' ? 'active' : '' ?>" href="admin.php?view=orders" title="Orders">
                            <span class="task-link-icon" aria-hidden="true">&#128203;</span>
                            <span class="task-link-text">Orders</span>
                        </a>
                        <a class="task-link <?= $view === 'products' ? 'active' : '' ?>" href="admin.php?view=products" title="Products">
                            <span class="task-link-icon" aria-hidden="true">&#128230;</span>
                            <span class="task-link-text">Products</span>
                        </a>
                        <a class="task-link <?= $view === 'contacts' ? 'active' : '' ?>" href="admin.php?view=contacts" title="Contacts">
                            <span class="task-link-icon" aria-hidden="true">&#128222;</span>
                            <span class="task-link-text">Contacts</span>
                        </a>
                        <a class="task-link <?= $view === 'profile' ? 'active' : '' ?>" href="admin.php?view=profile" title="Profile">
                            <span class="task-link-icon" aria-hidden="true">&#128100;</span>
                            <span class="task-link-text">Profile</span>
                        </a>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
                    <div class="admin-nav-section">
                        <span class="admin-nav-label">Administration</span>
                        <div class="task-menu-list">
                            <a class="task-link <?= $view === 'reports' ? 'active' : '' ?>" href="admin.php?view=reports" title="Reports">
                                <span class="task-link-icon" aria-hidden="true">&#128202;</span>
                                <span class="task-link-text">Reports</span>
                            </a>
                            <a class="task-link <?= $view === 'users' ? 'active' : '' ?>" href="admin.php?view=users" title="Users">
                                <span class="task-link-icon" aria-hidden="true">&#128101;</span>
                                <span class="task-link-text">Users</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>
        </aside>

        <div class="dashboard-content">

    <?php if ($view === 'products'): ?>
        <section class="panel">
            <h2>Product Actions</h2>
            <div class="nav-actions">
                <a class="btn" href="admin.php?view=products&create_product=1">Add New Product</a>
                <?php if ($createProductOpen || $editProduct): ?>
                    <a class="btn secondary" href="admin.php?view=products">Close Form</a>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($createProductOpen || $editProduct): ?>
            <section class="panel">
                <h2><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h2>
                <form action="admin_actions.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $editProduct ? 'update_product' : 'add_product' ?>">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="product_id" value="<?= (int) $editProduct['id'] ?>">
                    <?php endif; ?>
                    <label for="name">Name</label>
                    <input id="name" name="name" value="<?= e((string) ($editProduct['name'] ?? '')) ?>" required maxlength="200">
                    <label for="price">Price (FRW)</label>
                    <input id="price" name="price" type="number" step="0.01" min="0" value="<?= e((string) ($editProduct['price'] ?? '')) ?>" required>
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?= e((string) ($editProduct['description'] ?? '')) ?></textarea>
                    <label for="image">Product Image <?= $editProduct ? '(optional if unchanged)' : '' ?></label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                    <?php if ($editProduct): ?><p>Current Image: <code><?= e($editProduct['image']) ?></code></p><?php endif; ?>
                    <div style="margin-top:12px">
                        <button class="btn" type="submit"><?= $editProduct ? 'Update Product' : 'Add Product' ?></button>
                        <a class="btn secondary" href="admin.php?view=products">Cancel</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="panel">
            <h2>Products</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th>No.</th><th>Name</th><th>Price</th><th>Image</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($products as $i => $product): ?>
                    <tr>
                        <td><?= (int) ($offset + (int) $i + 1) ?></td>
                        <td><?= e($product['name']) ?></td>
                        <td>FRW <?= number_format((float) $product['price'], 2) ?></td>
                        <td><code><?= e($product['image']) ?></code></td>
                        <td>
                            <a class="btn" href="admin.php?view=products&edit=<?= (int) $product['id'] ?>">Edit</a>
                            <form action="admin_actions.php" method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <button class="btn danger" onclick="return confirm('Delete this product?')" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <?php render_pagination('products', $page, $totalPages); ?>
        </section>
    <?php endif; ?>

    <?php if ($view === 'orders'): ?>
        <section class="panel">
            <h2>Order Management</h2>
            <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>No.</th><th>Order #</th><th>Products</th><th>Customer</th><th>Phone</th>
                    <th>Items</th><th>Total (FRW)</th><th>Status</th><th>Message</th><th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $i => $order): ?>
                    <tr>
                        <td><?= (int) ($offset + (int) $i + 1) ?></td>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td class="order-products-cell"><?= e((string) ($order['products_summary'] ?? '')) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e($order['customer_phone']) ?></td>
                        <td><?= (int) $order['total_qty'] ?></td>
                        <td><?= number_format((float) $order['order_total'], 2) ?></td>
                        <td>
                            <form class="order-status-form" action="admin_actions.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <select class="order-status-select status-<?= e((string) $order['status']) ?>" name="status" aria-label="Order status for <?= e($order['customer_name']) ?>">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="done" <?= $order['status'] === 'done' ? 'selected' : '' ?>>Done</option>
                                    <option value="cancel" <?= $order['status'] === 'cancel' ? 'selected' : '' ?>>Cancel</option>
                                </select>
                            </form>
                        </td>
                        <td><?= e((string) $order['message']) ?></td>
                        <td><?= e($order['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <?php render_pagination('orders', $page, $totalPages); ?>
            <div class="download-row">
                <button class="btn report-filter-toggle" type="button" data-target="orders-report-filter">Download Orders PDF</button>
            </div>
            <div id="orders-report-filter" class="report-filter-panel is-hidden">
                <form method="get" action="report_pdf.php" class="panel" style="margin:12px 0 0;">
                    <input type="hidden" name="stream" value="1">
                    <label for="orders_report_start">Report Start Time</label>
                    <input id="orders_report_start" type="datetime-local" name="start_date" value="<?= e($reportStartInput) ?>">
                    <label for="orders_report_end">Report End Time</label>
                    <input id="orders_report_end" type="datetime-local" name="end_date" value="<?= e($reportEndInput) ?>">
                    <div style="margin-top:10px;">
                        <button class="btn" type="submit">Download Now</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($view === 'contacts'): ?>
        <section class="panel">
            <h2>Contact Management</h2>
            <p><strong>Phone:</strong> <?= e((string) $contact['phone']) ?></p>
            <p><strong>Email:</strong> <?= e((string) $contact['email']) ?></p>
            <p><strong>Address:</strong> <?= e((string) $contact['address']) ?></p>
            <div class="nav-actions">
                <a class="btn secondary" href="admin.php?view=contacts&edit_contact=1">Update Contact</a>
                <?php if ($editContactOpen): ?>
                    <a class="btn" href="admin.php?view=contacts">Close Form</a>
                <?php endif; ?>
            </div>
        </section>
        <?php if ($editContactOpen): ?>
            <section class="panel">
                <h2>Update Contact Information</h2>
                <form action="admin_actions.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_contact">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" required maxlength="30" value="<?= e((string) $contact['phone']) ?>">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required maxlength="190" value="<?= e((string) $contact['email']) ?>">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" required rows="3"><?= e((string) $contact['address']) ?></textarea>
                    <div style="margin-top:12px">
                        <button class="btn secondary" type="submit">Update Contact Info</button>
                        <a class="btn" href="admin.php?view=contacts">Cancel</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isAdmin && $view === 'reports'): ?>
        <section class="panel">
            <h2>Reports Overview</h2>
            <div class="admin-stat-grid">
                <div class="admin-stat-card"><strong><?= (int) $report['total_orders'] ?></strong><span>Total Orders</span></div>
                <div class="admin-stat-card"><strong><?= (int) $report['pending_orders'] ?></strong><span>Pending Orders</span></div>
                <div class="admin-stat-card"><strong><?= (int) $report['done_orders'] ?></strong><span>Done Orders</span></div>
                <div class="admin-stat-card"><strong><?= (int) $report['cancel_orders'] ?></strong><span>Cancelled Orders</span></div>
                <div class="admin-stat-card"><strong><?= (int) $report['total_items'] ?></strong><span>Total Items</span></div>
                <div class="admin-stat-card"><strong><?= (int) $reportProducts['total_products'] ?></strong><span>Total Products</span></div>
                <div class="admin-stat-card"><strong><?= (int) $reportUsers['total_users'] ?></strong><span>Total Users</span></div>
                <div class="admin-stat-card"><strong>FRW <?= number_format((float) $reportRevenue['estimated_revenue'], 2) ?></strong><span>Estimated Revenue</span></div>
            </div>
        </section>
        <section class="panel">
            <h2>Detailed Order Report (All Orders)</h2>
            <div class="table-wrap">
            <table>
                <thead><tr><th>No.</th><th>Order #</th><th>Products</th><th>Customer</th><th>Items</th><th>Total (FRW)</th><th>Status</th><th>Date</th><th>Message</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $i => $order): ?>
                    <tr>
                        <td><?= (int) ($offset + (int) $i + 1) ?></td>
                        <td>#<?= (int) $order['id'] ?></td>
                        <td class="order-products-cell"><?= e((string) ($order['products_summary'] ?? '')) ?></td>
                        <td><?= e($order['customer_name']) ?> (<?= e($order['customer_phone']) ?>)</td>
                        <td><?= (int) $order['total_qty'] ?></td>
                        <td><?= number_format((float) $order['order_total'], 2) ?></td>
                        <td><span class="<?= e(order_status_badge_class((string) $order['status'])) ?>"><?= e((string) $order['status']) ?></span></td>
                        <td><?= e($order['created_at']) ?></td>
                        <td><?= e((string) $order['message']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <?php render_pagination('reports', $page, $totalPages); ?>
            <div class="download-row">
                <button class="btn report-filter-toggle" type="button" data-target="full-report-filter">Download Designed PDF</button>
            </div>
            <div id="full-report-filter" class="report-filter-panel is-hidden">
                <form method="get" action="report_pdf.php" class="panel" style="margin:12px 0 0;">
                    <input type="hidden" name="stream" value="1">
                    <label for="reports_report_start">Report Start Time</label>
                    <input id="reports_report_start" type="datetime-local" name="start_date" value="<?= e($reportStartInput) ?>">
                    <label for="reports_report_end">Report End Time</label>
                    <input id="reports_report_end" type="datetime-local" name="end_date" value="<?= e($reportEndInput) ?>">
                    <div style="margin-top:10px;">
                        <button class="btn" type="submit">Download Now</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($view === 'profile' && $currentUser): ?>
        <section class="panel">
            <h2>My Profile</h2>
            <p><strong>Role:</strong> <?= e((string) $currentUser['role']) ?></p>
            <p><strong>Joined:</strong> <?= e((string) $currentUser['created_at']) ?></p>
            <div class="profile-actions">
                <button class="btn secondary profile-toggle-btn" type="button" data-target="profile-edit-panel">Edit Profile</button>
                <button class="btn profile-toggle-btn" type="button" data-target="profile-password-panel">Change Password</button>
            </div>
            <div id="profile-edit-panel" class="profile-panel is-hidden">
                <form action="admin_actions.php" method="post" style="margin-top:12px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="update_my_profile">
                    <label for="profile_name">Name</label>
                    <input id="profile_name" name="name" required maxlength="120" placeholder="Enter your full name" value="<?= e((string) $currentUser['name']) ?>">
                    <label for="profile_email">Email</label>
                    <input id="profile_email" name="email" type="email" required maxlength="190" placeholder="Enter your email address" value="<?= e((string) $currentUser['email']) ?>">
                    <label for="profile_phone">Telephone</label>
                    <input id="profile_phone" name="telephone" required maxlength="30" placeholder="Enter your phone number" value="<?= e((string) $currentUser['telephone']) ?>">
                    <div style="margin-top:12px;">
                        <button class="btn secondary" type="submit">Save Profile</button>
                    </div>
                </form>
            </div>
            <div id="profile-password-panel" class="profile-panel is-hidden">
                <form action="admin_actions.php" method="post" style="margin-top:12px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="change_my_password">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" name="current_password" type="password" minlength="6" maxlength="64" placeholder="Enter current password" required>
                    <label for="new_profile_password">New Password</label>
                    <input id="new_profile_password" name="new_password" type="password" minlength="6" maxlength="64" placeholder="Enter new password" required>
                    <label for="confirm_new_profile_password">Confirm New Password</label>
                    <input id="confirm_new_profile_password" name="confirm_new_password" type="password" minlength="6" maxlength="64" placeholder="Re-enter new password" required>
                    <div style="margin-top:12px;">
                        <button class="btn" type="submit">Save New Password</button>
                    </div>
                </form>
            </div>
        </section>
        <section class="panel">
            <h2>Profile Security Tips</h2>
            <p>Use a strong password and keep your contact details updated for better account recovery and order notifications.</p>
        </section>
        <section class="panel">
            <h2>Account Snapshot</h2>
            <p><strong>Name:</strong> <?= e((string) $currentUser['name']) ?></p>
            <p><strong>Email:</strong> <?= e((string) $currentUser['email']) ?></p>
            <p><strong>Telephone:</strong> <?= e((string) $currentUser['telephone']) ?></p>
        </section>
    <?php endif; ?>

    <?php if ($isAdmin && $view === 'users'): ?>
        <section class="panel">
            <h2>User Actions</h2>
            <div class="nav-actions">
                <a class="btn" href="admin.php?view=users&create_user=1">Create User</a>
                <?php if ($createUserOpen): ?>
                    <a class="btn secondary" href="admin.php?view=users">Close Create Form</a>
                <?php endif; ?>
            </div>
            <?php if ($createUserOpen): ?>
                <form action="admin_actions.php" method="post" style="margin-top:14px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="create_user">
                    <label for="user_name">Name</label>
                    <input id="user_name" name="name" required maxlength="120">
                    <label for="user_email">Email</label>
                    <input id="user_email" name="email" type="email" required maxlength="190">
                    <label for="user_phone">Telephone</label>
                    <input id="user_phone" name="telephone" required maxlength="30">
                    <label for="user_role">Role</label>
                    <select id="user_role" name="role" required>
                        <option value="seller">Seller</option>
                        <option value="admin">Admin</option>
                    </select>
                    <label for="user_password">Password</label>
                    <input id="user_password" name="password" type="password" required minlength="6" maxlength="64">
                    <div style="margin-top:12px"><button class="btn" type="submit">Create User</button></div>
                </form>
            <?php endif; ?>
        </section>

        <?php if ($editUser): ?>
        <section class="panel">
            <h2>Edit User</h2>
            <form action="admin_actions.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" value="<?= (int) $editUser['id'] ?>">
                <label for="edit_name">Name</label>
                <input id="edit_name" name="name" value="<?= e((string) $editUser['name']) ?>" required maxlength="120">
                <label for="edit_email">Email</label>
                <input id="edit_email" name="email" type="email" value="<?= e((string) $editUser['email']) ?>" required maxlength="190">
                <label for="edit_phone">Telephone</label>
                <input id="edit_phone" name="telephone" value="<?= e((string) $editUser['telephone']) ?>" required maxlength="30">
                <label for="edit_role">Role</label>
                <select id="edit_role" name="role" required>
                    <option value="seller" <?= $editUser['role'] === 'seller' ? 'selected' : '' ?>>Seller</option>
                    <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <div style="margin-top:12px">
                    <button class="btn secondary" type="submit">Save User Changes</button>
                    <a class="btn" href="admin.php?view=users">Cancel Edit</a>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <?php if ($resetUser): ?>
        <section class="panel">
            <h2>Reset User Password</h2>
            <p>Reset password for: <strong><?= e((string) $resetUser['name']) ?></strong> (<?= e((string) $resetUser['email']) ?>)</p>
            <form action="admin_actions.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="reset_user_password">
                <input type="hidden" name="user_id" value="<?= (int) $resetUser['id'] ?>">
                <label for="new_password">New Password</label>
                <input id="new_password" name="new_password" type="password" minlength="6" maxlength="64" required>
                <div style="margin-top:12px">
                    <button class="btn secondary" type="submit">Reset Password</button>
                    <a class="btn" href="admin.php?view=users">Cancel</a>
                </div>
            </form>
        </section>
        <?php endif; ?>
        <section class="panel">
            <h2>User Management</h2>
            <table>
                <thead><tr><th>No.</th><th>Name</th><th>Email</th><th>Role</th><th>Telephone</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $i => $user): ?>
                    <tr>
                        <td><?= (int) ($offset + (int) $i + 1) ?></td>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['role']) ?></td>
                        <td><?= e($user['telephone']) ?></td>
                        <td><?= e($user['created_at']) ?></td>
                        <td>
                            <a class="btn" href="admin.php?view=users&edit_user=<?= (int) $user['id'] ?>">Edit</a>
                            <a class="btn secondary" href="admin.php?view=users&reset_user=<?= (int) $user['id'] ?>">Reset Password</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php render_pagination('users', $page, $totalPages); ?>
        </section>
    <?php endif; ?>
</div>
</div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
<script src="assets.js"></script>
</body>
</html>
