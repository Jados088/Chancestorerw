<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_login();

$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request.');
    header('Location: admin.php');
    exit;
}

if ($action !== 'download_report' && !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    header('Location: admin.php');
    exit;
}

if ($action === 'download_report') {
    require __DIR__ . '/report_pdf.php';
    exit;
}

function upload_product_image(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tmp = (string) $file['tmp_name'];
    $mime = mime_content_type($tmp) ?: '';
    if (!isset($allowedMime[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Image size must be less than 2MB.');
    }

    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Failed to create upload directory.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
    $fullPath = $dir . '/' . $filename;

    if (!move_uploaded_file($tmp, $fullPath)) {
        throw new RuntimeException('Could not save uploaded image.');
    }

    return 'uploads/' . $filename;
}

$pdo = db();

try {
    if ($action === 'add_product') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $image = upload_product_image($_FILES['image'] ?? []);

        if ($name === '' || $description === '' || $price < 0) {
            throw new RuntimeException('Provide valid product details.');
        }

        $stmt = $pdo->prepare('INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $price, $description, $image]);
        set_flash('success', 'Product added.');
        header('Location: admin.php');
        exit;
    }

    if ($action === 'update_product') {
        $id = (int) ($_POST['product_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $price = (float) ($_POST['price'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        if ($id <= 0 || $name === '' || $description === '' || $price < 0) {
            throw new RuntimeException('Provide valid product details.');
        }

        $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) {
            throw new RuntimeException('Product not found.');
        }

        $imagePath = $existing['image'];
        if (!empty($_FILES['image']['name'])) {
            $imagePath = upload_product_image($_FILES['image']);
        }

        $update = $pdo->prepare('UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?');
        $update->execute([$name, $price, $description, $imagePath, $id]);
        set_flash('success', 'Product updated.');
        header('Location: admin.php');
        exit;
    }

    if ($action === 'delete_product') {
        $id = (int) ($_POST['product_id'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('Invalid product ID.');
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('success', 'Product deleted.');
        header('Location: admin.php');
        exit;
    }

    if ($action === 'update_contact') {
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));

        if ($phone === '' || $email === '' || $address === '') {
            throw new RuntimeException('All contact fields are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid contact email.');
        }

        $stmt = $pdo->prepare('UPDATE contacts SET phone = ?, email = ?, address = ? WHERE id = 1');
        $stmt->execute([$phone, $email, $address]);
        set_flash('success', 'Contact details updated.');
        header('Location: admin.php');
        exit;
    }

    if ($action === 'update_order_status') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'pending'));
        if ($orderId <= 0 || !in_array($status, ['pending', 'done', 'cancel'], true)) {
            throw new RuntimeException('Invalid order status update.');
        }

        $stmt = $pdo->prepare('UPDATE customer_orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $orderId]);
        set_flash('success', 'Order status updated.');
        header('Location: admin.php?view=orders');
        exit;
    }

    if ($action === 'update_my_profile') {
        $userId = (int) ($_SESSION['admin_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telephone = trim((string) ($_POST['telephone'] ?? ''));
        if ($userId <= 0 || $name === '' || $telephone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Provide valid profile details.');
        }

        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $emailCheck->execute([$email, $userId]);
        if ($emailCheck->fetch()) {
            throw new RuntimeException('Another user already has this email.');
        }

        $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, telephone = ? WHERE id = ?');
        $update->execute([$name, $email, $telephone, $userId]);

        $_SESSION['admin_name'] = $name;
        set_flash('success', 'Profile updated successfully.');
        header('Location: admin.php?view=profile');
        exit;
    }

    if ($action === 'change_my_password') {
        $userId = (int) ($_SESSION['admin_id'] ?? 0);
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmNewPassword = (string) ($_POST['confirm_new_password'] ?? '');

        if ($userId <= 0 || strlen($currentPassword) < 6 || strlen($newPassword) < 6) {
            throw new RuntimeException('Provide valid password details.');
        }
        if ($newPassword !== $confirmNewPassword) {
            throw new RuntimeException('New password confirmation does not match.');
        }
        if ($currentPassword === $newPassword) {
            throw new RuntimeException('New password must be different from current password.');
        }

        $userStmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        if (!$user) {
            throw new RuntimeException('User account not found.');
        }
        if (!password_verify($currentPassword, (string) $user['password'])) {
            throw new RuntimeException('Current password is incorrect.');
        }

        $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

        set_flash('success', 'Password changed successfully.');
        header('Location: admin.php?view=profile');
        exit;
    }

    if ($action === 'create_user') {
        if (current_user_role() !== 'admin') {
            throw new RuntimeException('Only admin can create users.');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telephone = trim((string) ($_POST['telephone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = trim((string) ($_POST['role'] ?? 'seller'));

        if (
            $name === ''
            || $telephone === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 6
            || !in_array($role, ['admin', 'seller'], true)
        ) {
            throw new RuntimeException('Provide valid user details.');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Email is already used by another user.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO users (name, email, password, role, telephone) VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $telephone]);
        set_flash('success', ucfirst($role) . ' user created successfully.');
        header('Location: admin.php?view=users');
        exit;
    }

    if ($action === 'reset_user_password') {
        if (current_user_role() !== 'admin') {
            throw new RuntimeException('Only admin can reset user passwords.');
        }
        $userId = (int) ($_POST['user_id'] ?? 0);
        $newPassword = (string) ($_POST['new_password'] ?? '');
        if ($userId <= 0 || strlen($newPassword) < 6) {
            throw new RuntimeException('Invalid user or password.');
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            throw new RuntimeException('User not found.');
        }

        $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        set_flash('success', 'Password reset completed.');
        header('Location: admin.php?view=users');
        exit;
    }

    if ($action === 'update_user') {
        if (current_user_role() !== 'admin') {
            throw new RuntimeException('Only admin can edit users.');
        }
        $userId = (int) ($_POST['user_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telephone = trim((string) ($_POST['telephone'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'seller'));

        if (
            $userId <= 0
            || $name === ''
            || $telephone === ''
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($role, ['admin', 'seller'], true)
        ) {
            throw new RuntimeException('Provide valid user details.');
        }

        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $emailCheck->execute([$email, $userId]);
        if ($emailCheck->fetch()) {
            throw new RuntimeException('Another user already has this email.');
        }

        $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, telephone = ?, role = ? WHERE id = ?');
        $update->execute([$name, $email, $telephone, $role, $userId]);
        set_flash('success', 'User updated successfully.');
        header('Location: admin.php?view=users');
        exit;
    }

    if ($action === 'delete_user') {
        if (current_user_role() !== 'admin') {
            throw new RuntimeException('Only admin can remove users.');
        }
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Invalid user ID.');
        }
        if ($userId === (int) ($_SESSION['admin_id'] ?? 0)) {
            throw new RuntimeException('You cannot remove your own logged-in account.');
        }

        $delete = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $delete->execute([$userId]);
        set_flash('success', 'User removed successfully.');
        header('Location: admin.php?view=users');
        exit;
    }
    throw new RuntimeException('Unknown action.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    header('Location: admin.php');
    exit;
}