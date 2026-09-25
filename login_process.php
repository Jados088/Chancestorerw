<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('error', 'Invalid request.');
    header('Location: login.php');
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    set_flash('error', 'Provide valid login details.');
    header('Location: login.php');
    exit;
}

$stmt = db()->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (
    !$user
    || !password_verify($password, $user['password'])
    || !in_array((string) ($user['role'] ?? ''), ['admin', 'seller'], true)
) {
    set_flash('error', 'Incorrect email or password. Please check your credentials and try again.');
    header('Location: login.php');
    exit;
}

$_SESSION['admin_id'] = (int) $user['id'];
$_SESSION['admin_name'] = $user['name'];
$_SESSION['admin_role'] = $user['role'];
session_regenerate_id(true);

set_flash('success', 'Welcome back, ' . $user['name'] . '.');
header('Location: admin.php');
exit;
