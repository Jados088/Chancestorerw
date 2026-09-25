<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    header('Location: index.php');
    exit;
}

$name = trim((string) ($_POST['customer_name'] ?? ''));
$phone = trim((string) ($_POST['customer_phone'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$cartLines = get_cart_lines(db());

if ($cartLines === []) {
    set_flash('error', 'Your order is empty. Add products first.');
    header('Location: index.php#products');
    exit;
}

if ($name === '' || $phone === '') {
    set_flash('error', 'Please fill all required fields.');
    header('Location: index.php#cart');
    exit;
}

if (strlen($name) > 120 || strlen($phone) > 30 || strlen($message) > 1000) {
    set_flash('error', 'One or more fields exceed allowed length.');
    header('Location: index.php#cart');
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $insertOrder = $pdo->prepare(
        'INSERT INTO customer_orders (customer_name, customer_phone, message, status)
         VALUES (?, ?, ?, ?)'
    );
    $insertOrder->execute([
        $name,
        $phone,
        $message !== '' ? $message : null,
        'pending',
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $insertItem = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, quantity, unit_price)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($cartLines as $line) {
        $insertItem->execute([
            $orderId,
            (int) $line['product_id'],
            (int) $line['quantity'],
            (float) $line['unit_price'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Could not save your order. Please try again.');
    header('Location: index.php#cart');
    exit;
}

clear_cart();

$sellerStmt = $pdo->prepare("SELECT telephone FROM users WHERE role = 'seller' ORDER BY id ASC LIMIT 1");
$sellerStmt->execute();
$seller = $sellerStmt->fetch();
$sellerPhone = preg_replace('/\D+/', '', (string) ($seller['telephone'] ?? ''));
if ($sellerPhone === '') {
    $contact = get_site_contact();
    $sellerPhone = phone_digits((string) $contact['phone']);
}

$grandTotal = cart_grand_total($cartLines);
$itemLines = [];
foreach ($cartLines as $line) {
    $itemLines[] = '- ' . $line['name']
        . ' | Qty: ' . (int) $line['quantity']
        . ' | Unit: FRW ' . number_format((float) $line['unit_price'], 2)
        . ' | Line Total: FRW ' . number_format((float) $line['line_total'], 2);
}

$summary = "Dear Seller,\n\n"
    . "A new customer order has been received from the Chance Store Ltd website.\n\n"
    . "Order #" . $orderId . "\n"
    . "Products:\n"
    . implode("\n", $itemLines) . "\n\n"
    . "Grand Total: FRW " . number_format($grandTotal, 2) . "\n"
    . "Status: pending\n\n"
    . "Customer Details:\n"
    . "- Name: " . $name . "\n"
    . "- Phone: " . $phone . "\n"
    . "- Message: " . ($message !== '' ? $message : 'No additional message') . "\n\n"
    . "Please follow up with the customer as soon as possible.\n"
    . "Thank you.";

set_flash('success', 'Order #' . $orderId . ' saved with ' . count($cartLines) . ' product(s).');
if ($sellerPhone !== '') {
    header('Location: https://wa.me/' . urlencode($sellerPhone) . '?text=' . urlencode($summary));
    exit;
}

header('Location: index.php');
exit;
