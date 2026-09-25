<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_login();

function pdf_escape(string $text): string
{
    $normalized = str_replace(["\r", "\n"], ' ', $text);
    $asciiSafe = preg_replace('/[^\x20-\x7E]/', '?', $normalized);
    if (!is_string($asciiSafe)) {
        $asciiSafe = $normalized;
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $asciiSafe);
}

function money(float $value): string
{
    return number_format($value, 2, '.', ',');
}

function shorten(string $text, int $max): string
{
    if (strlen($text) <= $max) {
        return $text;
    }
    return substr($text, 0, max(0, $max - 3)) . '...';
}

function parse_report_datetime(string $value, bool $isEnd): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $formats = ['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            if ($format === 'Y-m-d') {
                $dt->setTime($isEnd ? 23 : 0, $isEnd ? 59 : 0, $isEnd ? 59 : 0);
            }
            return $dt->format('Y-m-d H:i:s');
        }
    }
    return null;
}

function report_period_label(?string $startSql, ?string $endSql): string
{
    if ($startSql === null && $endSql === null) {
        return 'Period: All time';
    }
    if ($startSql !== null && $endSql !== null) {
        return 'Period: ' . date('Y-m-d H:i', strtotime($startSql)) . ' to ' . date('Y-m-d H:i', strtotime($endSql));
    }
    if ($startSql !== null) {
        return 'Period: From ' . date('Y-m-d H:i', strtotime($startSql));
    }
    return 'Period: Up to ' . date('Y-m-d H:i', strtotime((string) $endSql));
}

function load_logo_for_pdf(string $logoPath): ?array
{
    if (!is_file($logoPath)) {
        return null;
    }

    $rawData = @file_get_contents($logoPath);
    if (!is_string($rawData) || $rawData === '') {
        return null;
    }

    // Try GD conversion first so PNG/WEBP/JPEG all work as PDF-embedded JPEG stream.
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $img = @imagecreatefromstring($rawData);
        if ($img !== false) {
            $width = imagesx($img);
            $height = imagesy($img);
            ob_start();
            imagejpeg($img, null, 88);
            $jpegData = (string) ob_get_clean();
            imagedestroy($img);
            if ($jpegData !== '') {
                return [
                    'width' => $width,
                    'height' => $height,
                    'data' => $jpegData,
                ];
            }
        }
    }

    // Fallback for genuine JPEG files when GD is unavailable.
    $imageInfo = @getimagesize($logoPath);
    if ($imageInfo !== false && isset($imageInfo[2]) && $imageInfo[2] === IMAGETYPE_JPEG) {
        return [
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
            'data' => $rawData,
        ];
    }

    return null;
}

function build_page_content(
    array $rows,
    int $pageNo,
    int $totalPages,
    array $summary,
    float $revenue,
    string $periodLabel,
    bool $showSummary,
    bool $hasLogo
): string {
    $content = '';
    // Header banner
    $content .= "0.10 0.24 0.55 rg\n0 790 595 52 re f\n";

    if ($hasLogo) {
        // Draw logo in header (first page only by caller)
        $content .= "q 42 0 0 42 36 796 cm /Im1 Do Q\n";
    }

    $content .= "BT\n/F2 16 Tf\n1 1 1 rg\n1 0 0 1 88 818 Tm (Chance store rw - Orders Report) Tj\n";
    $content .= "/F1 9 Tf\n1 0 0 1 88 803 Tm (Generated: " . pdf_escape(date('Y-m-d H:i:s')) . ") Tj\nET\n";
    $content .= "0.94 0.96 1 rg\n0 770 595 18 re f\n";
    $content .= "BT\n/F2 10 Tf\n0.10 0.24 0.55 rg\n1 0 0 1 36 775 Tm (Company: Chance store rw    |    " . pdf_escape($periodLabel) . ") Tj\nET\n";

    $y = 662;
    if ($showSummary) {
        $content .= "0.96 0.97 0.99 rg\n36 {$y} 523 88 re f\n";
        $content .= "0.82 0.86 0.93 RG\n0.5 w\n36 {$y} 523 88 re S\n";
        $content .= "BT\n/F2 11 Tf\n0.12 0.18 0.30 rg\n1 0 0 1 44 " . ($y + 67) . " Tm (Summary) Tj\n";
        $content .= "/F1 10 Tf\n";
        $content .= "1 0 0 1 44 " . ($y + 49) . " Tm (Total Orders: " . (int) $summary['total_orders'] . ") Tj\n";
        $content .= "1 0 0 1 220 " . ($y + 49) . " Tm (Pending: " . (int) $summary['pending_orders'] . ") Tj\n";
        $content .= "1 0 0 1 360 " . ($y + 49) . " Tm (Done: " . (int) $summary['done_orders'] . ") Tj\n";
        $content .= "1 0 0 1 44 " . ($y + 30) . " Tm (Total Items: " . (int) $summary['total_items'] . ") Tj\n";
        $content .= "1 0 0 1 220 " . ($y + 30) . " Tm (Cancelled: " . (int) $summary['cancel_orders'] . ") Tj\n";
        $content .= "1 0 0 1 360 " . ($y + 30) . " Tm (Revenue: FRW " . pdf_escape(money($revenue)) . ") Tj\nET\n";
        $y -= 106;
    } else {
        $y -= 20;
    }

    // Table header
    $content .= "0.16 0.37 0.68 rg\n36 {$y} 523 20 re f\n";
    $content .= "BT\n/F1 8 Tf\n1 1 1 rg\n";
    $content .= "1 0 0 1 42 " . ($y + 6) . " Tm (No) Tj\n";
    $content .= "1 0 0 1 66 " . ($y + 6) . " Tm (Product) Tj\n";
    $content .= "1 0 0 1 188 " . ($y + 6) . " Tm (Customer) Tj\n";
    $content .= "1 0 0 1 306 " . ($y + 6) . " Tm (Qty) Tj\n";
    $content .= "1 0 0 1 338 " . ($y + 6) . " Tm (Total FRW) Tj\n";
    $content .= "1 0 0 1 415 " . ($y + 6) . " Tm (Status) Tj\n";
    $content .= "1 0 0 1 470 " . ($y + 6) . " Tm (Date) Tj\nET\n";
    $y -= 20;

    $lineHeight = 15;
    foreach ($rows as $row) {
        $content .= "0.90 0.92 0.96 RG\n0.5 w\n36 {$y} 523 {$lineHeight} re S\n";
        $content .= "BT\n/F1 8 Tf\n0.08 0.08 0.08 rg\n";
        $content .= "1 0 0 1 42 " . ($y + 4) . " Tm (" . pdf_escape((string) $row['no']) . ") Tj\n";
        $content .= "1 0 0 1 66 " . ($y + 4) . " Tm (" . pdf_escape($row['product']) . ") Tj\n";
        $content .= "1 0 0 1 188 " . ($y + 4) . " Tm (" . pdf_escape($row['customer']) . ") Tj\n";
        $content .= "1 0 0 1 306 " . ($y + 4) . " Tm (" . pdf_escape((string) $row['qty']) . ") Tj\n";
        $content .= "1 0 0 1 338 " . ($y + 4) . " Tm (" . pdf_escape($row['total']) . ") Tj\n";
        $content .= "1 0 0 1 415 " . ($y + 4) . " Tm (" . pdf_escape($row['status']) . ") Tj\n";
        $content .= "1 0 0 1 470 " . ($y + 4) . " Tm (" . pdf_escape($row['date']) . ") Tj\n";
        $content .= "ET\n";
        $y -= $lineHeight;
    }

    $content .= "BT\n/F1 8 Tf\n0.35 0.35 0.35 rg\n1 0 0 1 36 20 Tm (Page {$pageNo} of {$totalPages}) Tj\nET\n";
    return $content;
}

function build_pdf(array $summary, float $revenue, array $orders, ?array $logo, string $periodLabel): string
{
    $tableRows = [];
    foreach ($orders as $index => $order) {
        $tableRows[] = [
            'no' => (int) $index + 1,
            'product' => shorten((string) $order['product_name'], 23),
            'customer' => shorten((string) $order['customer_name'], 23),
            'qty' => (int) $order['quantity'],
            'total' => money((float) $order['unit_price'] * (int) $order['quantity']),
            'status' => (string) $order['status'],
            'date' => date('Y-m-d', strtotime((string) $order['created_at'])),
        ];
    }

    $firstPageRows = 28;
    $nextPageRows = 40;
    $chunks = [];
    if (count($tableRows) <= $firstPageRows) {
        $chunks[] = $tableRows;
    } else {
        $chunks[] = array_slice($tableRows, 0, $firstPageRows);
        $remaining = array_slice($tableRows, $firstPageRows);
        while ($remaining !== []) {
            $chunks[] = array_slice($remaining, 0, $nextPageRows);
            $remaining = array_slice($remaining, $nextPageRows);
        }
    }
    if ($chunks === []) {
        $chunks[] = [];
    }

    $pageContents = [];
    $totalPages = count($chunks);
    foreach ($chunks as $i => $chunk) {
        $pageContents[] = build_page_content(
            $chunk,
            $i + 1,
            $totalPages,
            $summary,
            $revenue,
            $periodLabel,
            $i === 0,
            $logo !== null
        );
    }

    $objects = [];
    // 1 Catalog
    $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

    $fontRegularNum = 3;
    $fontBoldNum = 4;
    $objects[$fontRegularNum] = "{$fontRegularNum} 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    $objects[$fontBoldNum] = "{$fontBoldNum} 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";

    $imageNum = null;
    $nextNum = 5;
    if ($logo !== null) {
        $imageNum = $nextNum++;
        $imageStream = $logo['data'];
        $objects[$imageNum] = "{$imageNum} 0 obj\n<< /Type /XObject /Subtype /Image /Width {$logo['width']} /Height {$logo['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($imageStream) . " >>\nstream\n{$imageStream}\nendstream\nendobj\n";
    }

    $contentNums = [];
    foreach ($pageContents as $content) {
        $num = $nextNum++;
        $contentNums[] = $num;
        $objects[$num] = "{$num} 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream\nendobj\n";
    }

    $pageNums = [];
    foreach ($contentNums as $contentNum) {
        $num = $nextNum++;
        $pageNums[] = $num;
        $resources = "/Font << /F1 {$fontRegularNum} 0 R /F2 {$fontBoldNum} 0 R >>";
        if ($imageNum !== null) {
            $resources .= " /XObject << /Im1 {$imageNum} 0 R >>";
        }
        $objects[$num] = "{$num} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << {$resources} >> /Contents {$contentNum} 0 R >>\nendobj\n";
    }

    // 2 Pages (after we know kids)
    $kids = '';
    foreach ($pageNums as $pageNum) {
        $kids .= "{$pageNum} 0 R ";
    }
    $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [{$kids}] /Count " . count($pageNums) . " >>\nendobj\n";

    ksort($objects);
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    $maxObject = max(array_keys($objects));
    for ($i = 1; $i <= $maxObject; $i++) {
        if (!isset($objects[$i])) {
            continue;
        }
        $offsets[$i] = strlen($pdf);
        $pdf .= $objects[$i];
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . ($maxObject + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $maxObject; $i++) {
        if (!isset($offsets[$i])) {
            $pdf .= "0000000000 00000 f \n";
            continue;
        }
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . ($maxObject + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

    return $pdf;
}

$startRaw = (string) ($_GET['start_date'] ?? ($_GET['report_start'] ?? ''));
$endRaw = (string) ($_GET['end_date'] ?? ($_GET['report_end'] ?? ''));
$startSql = parse_report_datetime($startRaw, false);
$endSql = parse_report_datetime($endRaw, true);
if ($startSql !== null && $endSql !== null && strtotime($startSql) > strtotime($endSql)) {
    $tmp = $startSql;
    $startSql = $endSql;
    $endSql = $tmp;
}

$filterSql = '';
$filterParams = [];
if ($startSql !== null) {
    $filterSql .= ' AND co.created_at >= ?';
    $filterParams[] = $startSql;
}
if ($endSql !== null) {
    $filterSql .= ' AND co.created_at <= ?';
    $filterParams[] = $endSql;
}
$periodLabel = report_period_label($startSql, $endSql);

try {
    $pdo = db();
    $ordersStmt = $pdo->prepare(
        'SELECT co.id, co.customer_name, co.customer_phone, co.message, co.status, co.created_at,
                p.name AS product_name, oi.quantity, oi.unit_price
         FROM customer_orders co
         INNER JOIN order_items oi ON oi.order_id = co.id
         INNER JOIN products p ON p.id = oi.product_id
         WHERE 1=1' . $filterSql . '
         ORDER BY co.created_at DESC, oi.id ASC'
    );
    $ordersStmt->execute($filterParams);
    $orders = $ordersStmt->fetchAll();

    $summaryStmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total_orders,
            COALESCE(SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END), 0) AS done_orders,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders,
            COALESCE(SUM(CASE WHEN status = 'cancel' THEN 1 ELSE 0 END), 0) AS cancel_orders
         FROM customer_orders co
         WHERE 1=1" . $filterSql
    );
    $summaryStmt->execute($filterParams);
    $summary = $summaryStmt->fetch() ?: [
        'total_orders' => 0,
        'done_orders' => 0,
        'pending_orders' => 0,
        'cancel_orders' => 0,
    ];
    $itemsStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(oi.quantity), 0) AS total_items
         FROM customer_orders co
         INNER JOIN order_items oi ON oi.order_id = co.id
         WHERE 1=1' . $filterSql
    );
    $itemsStmt->execute($filterParams);
    $summary['total_items'] = (int) ($itemsStmt->fetchColumn() ?: 0);

    $revenueStmt = $pdo->prepare(
        'SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS estimated_revenue
         FROM customer_orders co
         INNER JOIN order_items oi ON oi.order_id = co.id
         WHERE 1=1' . $filterSql
    );
    $revenueStmt->execute($filterParams);
    $revenue = $revenueStmt->fetch() ?: ['estimated_revenue' => 0];
} catch (Throwable $e) {
    $orders = [];
    $summary = ['total_orders' => 0, 'total_items' => 0, 'done_orders' => 0, 'pending_orders' => 0, 'cancel_orders' => 0];
    $revenue = ['estimated_revenue' => 0];
    $periodLabel = 'Period: All time';
}

$logo = load_logo_for_pdf(__DIR__ . '/logo.jpeg');

$pdf = build_pdf($summary, (float) $revenue['estimated_revenue'], $orders, $logo, $periodLabel);

$reportDir = __DIR__ . '/reports';
if (!is_dir($reportDir) && !mkdir($reportDir, 0755, true) && !is_dir($reportDir)) {
    http_response_code(500);
    echo 'Could not create reports directory.';
    exit;
}

$serverFileName = 'chance-store-report-latest.pdf';
$serverFilePath = $reportDir . '/' . $serverFileName;
if (file_put_contents($serverFilePath, $pdf, LOCK_EX) === false) {
    http_response_code(500);
    echo 'Failed to save report file.';
    exit;
}

clearstatcache(true, $serverFilePath);

if (!isset($_GET['stream']) || $_GET['stream'] !== '1') {
    header('Location: reports/' . rawurlencode($serverFileName) . '?v=' . time());
    exit;
}

while (ob_get_level() > 0) {
    if (!ob_end_clean()) {
        break;
    }
}

if (headers_sent()) {
    echo 'Download failed: headers already sent.';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="chance-store-report.pdf"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . (string) filesize($serverFilePath));
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Download-Options: noopen');
header('X-Content-Type-Options: nosniff');
readfile($serverFilePath);
exit;
