<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

try {
    $pdo = db();
    $orders = $pdo->query(
        'SELECT co.id, co.customer_name, co.customer_phone, co.message, co.status, co.created_at,
                p.name AS product_name, oi.quantity, oi.unit_price
         FROM customer_orders co
         INNER JOIN order_items oi ON oi.order_id = co.id
         INNER JOIN products p ON p.id = oi.product_id
         ORDER BY co.created_at DESC, oi.id ASC'
    )->fetchAll();

    $summary = $pdo->query(
        "SELECT
            COUNT(*) AS total_orders,
            COALESCE(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0) AS done_orders,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders
         FROM customer_orders"
    )->fetch() ?: ['total_orders' => 0, 'done_orders' => 0, 'pending_orders' => 0];

    $summary['total_items'] = (int) ($pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM order_items')->fetchColumn() ?: 0);

    $revenue = $pdo->query(
        'SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS estimated_revenue FROM order_items oi'
    )->fetch() ?: ['estimated_revenue' => 0];
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Report generation failed: ' . $e->getMessage();
    exit;
}

while (ob_get_level() > 0) {
    if (!ob_end_clean()) {
        break;
    }
}

$filename = 'chance-store-report-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'wb');
if ($out === false) {
    http_response_code(500);
    echo 'Unable to open output stream.';
    exit;
}

fputcsv($out, ['Chance Store Ltd Orders Report']);
fputcsv($out, ['Generated At', date('Y-m-d H:i:s')]);
fputcsv($out, []);
fputcsv($out, ['Summary']);
fputcsv($out, ['Total Orders', (string) (int) $summary['total_orders']]);
fputcsv($out, ['Pending Orders', (string) (int) $summary['pending_orders']]);
fputcsv($out, ['Done Orders', (string) (int) $summary['done_orders']]);
fputcsv($out, ['Total Items', (string) (int) $summary['total_items']]);
fputcsv($out, ['Estimated Revenue (FRW)', number_format((float) $revenue['estimated_revenue'], 2, '.', '')]);
fputcsv($out, []);
fputcsv($out, ['No.', 'Order #', 'Product', 'Customer', 'Phone', 'Qty', 'Total (FRW)', 'Status', 'Date', 'Message']);

foreach ($orders as $index => $order) {
    fputcsv($out, [
        (string) ((int) $index + 1),
        (string) (int) $order['id'],
        (string) $order['product_name'],
        (string) $order['customer_name'],
        (string) $order['customer_phone'],
        (string) (int) $order['quantity'],
        number_format((float) $order['unit_price'] * (int) $order['quantity'], 2, '.', ''),
        (string) $order['status'],
        (string) $order['created_at'],
        (string) $order['message'],
    ]);
}

fclose($out);
exit;
