<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    header('Location: index.php');
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$productId = (int) ($_POST['product_id'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);
$redirect = 'index.php#cart';

if ($action === 'add') {
    if ($productId <= 0 || $quantity <= 0) {
        set_flash('error', 'Select a valid product and quantity.');
        header('Location: index.php#products');
        exit;
    }

    $stmt = db()->prepare('SELECT id, name FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        set_flash('error', 'Product not found.');
        header('Location: index.php#products');
        exit;
    }

    add_to_cart($productId, $quantity);
    set_flash('success', (string) $product['name'] . ' added to your order (' . $quantity . ').');
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'update') {
    if ($productId <= 0) {
        set_flash('error', 'Invalid product.');
        header('Location: ' . $redirect);
        exit;
    }

    update_cart_item($productId, $quantity);
    set_flash('success', 'Order updated.');
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'remove') {
    if ($productId <= 0) {
        set_flash('error', 'Invalid product.');
        header('Location: ' . $redirect);
        exit;
    }

    remove_from_cart($productId);
    set_flash('success', 'Product removed from your order.');
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'clear') {
    clear_cart();
    set_flash('success', 'Your order was cleared.');
    header('Location: index.php#products');
    exit;
}

set_flash('error', 'Unknown cart action.');
header('Location: index.php');
exit;
