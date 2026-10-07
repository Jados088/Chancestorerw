<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DB_HOST = 'localhost';
const DB_NAME = 'if0_41788004_chance_store';
const DB_USER = 'if0_41788004';
const DB_PASS = 'Jados2026';
const LEGACY_BAD_ADMIN_HASH = '$2y$10$tbJx8EnBa5jS4PPfQ0s4Iudph7YqqA9anQavPNo3x6xZC0x15jN7W';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Auto-fix old seeded admin hash so existing installs can log in.
    $fixStmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ? AND password = ?');
    $fixStmt->execute([
        password_hash('admin123', PASSWORD_DEFAULT),
        'admin@chancestore.com',
        LEGACY_BAD_ADMIN_HASH,
    ]);

    // Keep schema compatible with quantity feature for existing databases.
    $columnCheck = $pdo->query("SHOW COLUMNS FROM orders LIKE 'quantity'");
    if (!$columnCheck->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER customer_phone");
    }
    $statusCheck = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status'");
    if (!$statusCheck->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER quantity");
    }
    $pdo->exec("UPDATE orders SET status = 'pending' WHERE status IS NULL OR status = ''");

    $userRoleCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$userRoleCheck->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'seller' AFTER password");
        $pdo->exec("UPDATE users SET role = 'seller' WHERE role = '' OR role IS NULL");
    }
    $pdo->exec("ALTER TABLE users MODIFY role VARCHAR(30) NOT NULL DEFAULT 'seller'");
    $pdo->exec("UPDATE users SET role = 'seller' WHERE role NOT IN ('admin', 'seller') OR role IS NULL OR role = ''");
    $pdo->exec("UPDATE users SET role = 'admin' WHERE email = 'admin@chancestore.com'");

    ensure_multi_order_schema($pdo);

    return $pdo;
}

function ensure_multi_order_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS customer_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(120) NOT NULL,
            customer_phone VARCHAR(30) NOT NULL,
            message TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES customer_orders(id) ON DELETE CASCADE,
            CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )'
    );

    $customerOrderCount = (int) ($pdo->query('SELECT COUNT(*) FROM customer_orders')->fetchColumn() ?: 0);
    if ($customerOrderCount === 0) {
        $legacyOrders = $pdo->query(
            'SELECT o.*, p.price AS unit_price
             FROM orders o
             INNER JOIN products p ON p.id = o.product_id
             ORDER BY o.id ASC'
        )->fetchAll();

        foreach ($legacyOrders as $legacy) {
            $insertOrder = $pdo->prepare(
                'INSERT INTO customer_orders (id, customer_name, customer_phone, message, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insertOrder->execute([
                (int) $legacy['id'],
                (string) $legacy['customer_name'],
                (string) $legacy['customer_phone'],
                $legacy['message'] !== null && $legacy['message'] !== '' ? (string) $legacy['message'] : null,
                (string) ($legacy['status'] ?? 'pending'),
                (string) $legacy['created_at'],
            ]);

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price, created_at)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $insertItem->execute([
                (int) $legacy['id'],
                (int) $legacy['product_id'],
                (int) $legacy['quantity'],
                (float) $legacy['unit_price'],
                (string) $legacy['created_at'],
            ]);
        }

        if ($legacyOrders !== []) {
            $pdo->exec('ALTER TABLE customer_orders AUTO_INCREMENT = ' . ((int) max(array_column($legacyOrders, 'id')) + 1));
        }
    }
}

function get_cart(): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $cart = [];
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $id = (int) $productId;
        $qty = (int) $quantity;
        if ($id > 0 && $qty > 0) {
            $cart[$id] = $qty;
        }
    }

    $_SESSION['cart'] = $cart;

    return $cart;
}

function cart_count(): int
{
    return array_sum(get_cart());
}

function add_to_cart(int $productId, int $quantity): void
{
    if ($productId <= 0 || $quantity <= 0) {
        return;
    }

    $cart = get_cart();
    $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
    $_SESSION['cart'] = $cart;
}

function update_cart_item(int $productId, int $quantity): void
{
    $cart = get_cart();
    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }
    $_SESSION['cart'] = $cart;
}

function remove_from_cart(int $productId): void
{
    $cart = get_cart();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

function clear_cart(): void
{
    $_SESSION['cart'] = [];
}

function get_cart_lines(PDO $pdo): array
{
    $cart = get_cart();
    if ($cart === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll();

    $lines = [];
    foreach ($products as $product) {
        $productId = (int) $product['id'];
        $quantity = (int) ($cart[$productId] ?? 0);
        if ($quantity <= 0) {
            continue;
        }

        $unitPrice = (float) $product['price'];
        $lines[] = [
            'product_id' => $productId,
            'name' => (string) $product['name'],
            'image' => (string) $product['image'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice * $quantity,
        ];
    }

    return $lines;
}

function cart_grand_total(array $lines): float
{
    $total = 0.0;
    foreach ($lines as $line) {
        $total += (float) $line['line_total'];
    }

    return $total;
}

function fetch_customer_orders(PDO $pdo, int $limit, int $offset): array
{
    $stmt = $pdo->prepare(
        'SELECT co.id, co.customer_name, co.customer_phone, co.message, co.status, co.created_at,
                COALESCE(SUM(oi.quantity), 0) AS total_qty,
                COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS order_total,
                GROUP_CONCAT(CONCAT(p.name, \' (x\', oi.quantity, \')\') ORDER BY oi.id SEPARATOR \', \') AS products_summary
         FROM customer_orders co
         LEFT JOIN order_items oi ON oi.order_id = co.id
         LEFT JOIN products p ON p.id = oi.product_id
         GROUP BY co.id
         ORDER BY co.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function count_customer_orders(PDO $pdo): int
{
    return (int) ($pdo->query('SELECT COUNT(*) FROM customer_orders')->fetchColumn() ?: 0);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function current_user_role(): string
{
    return (string) ($_SESSION['admin_role'] ?? '');
}

function require_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (current_user_role() !== 'admin') {
        set_flash('error', 'You do not have permission to access that action.');
        header('Location: admin.php');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function get_site_contact(): array
{
    static $contact = null;

    if ($contact === null) {
        $contact = db()->query('SELECT * FROM contacts WHERE id = 1')->fetch() ?: [
            'phone' => '',
            'email' => '',
            'address' => '',
        ];
    }

    return $contact;
}

function phone_digits(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?: '';
}
