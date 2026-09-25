<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (is_admin_logged_in()) {
    header('Location: admin.php');
    exit;
}

$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chance Store Ltd</title>
    <link rel="icon" type="image/jpeg" href="logo.jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets.css">
</head>
<body class="login-page">
<div class="login-page-bg" aria-hidden="true"></div>

<header>
    <div class="container header-row">
        <div class="brand-wrap">
            <img class="site-logo" src="logo.jpeg" alt="Chance Store Ltd Logo">
            <div class="brand">Chance Store Ltd</div>
        </div>
        <div class="nav-actions">
            <a class="btn" href="index.php">Back to Website</a>
        </div>
    </div>
</header>

<main class="container login-main">
    <div class="login-shell">
        <section class="login-card-v2">
            <div class="login-card-accent" aria-hidden="true"></div>

            <div class="login-card-header">
                <div class="login-logo-wrap">
                    <img class="login-logo-circle" src="logo.jpeg" alt="Chance Store Ltd Logo">
                </div>
                <span class="login-secure-badge">&#128274; Secure Login</span>
                <h2>Welcome Back</h2>
                <p>Sign in as Admin or Seller to manage your workspace.</p>
            </div>

            <?php if ($flash): ?>
                <div class="login-alert login-alert-<?= e($flash['type']) ?>" role="alert" aria-live="polite">
                    <span class="login-alert-icon" aria-hidden="true">
                        <?= $flash['type'] === 'error' ? '&#9888;' : '&#10003;' ?>
                    </span>
                    <div>
                        <?php if ($flash['type'] === 'error'): ?>
                            <strong>Login Failed</strong>
                        <?php else: ?>
                            <strong>Success</strong>
                        <?php endif; ?>
                        <p><?= e($flash['message']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form class="login-form" action="login_process.php" method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="login-field">
                    <label for="email">Email Address</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true">&#9993;</span>
                        <input id="email" name="email" type="email" required maxlength="190" placeholder="you@example.com" autocomplete="email">
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <div class="login-input-wrap password-wrap">
                        <span class="login-input-icon" aria-hidden="true">&#128274;</span>
                        <input id="password" name="password" type="password" required minlength="6" placeholder="Enter your password" autocomplete="current-password">
                        <button class="password-toggle" type="button" id="togglePassword" aria-label="Show password" title="Show password">
                            <svg id="iconEyeOpen" aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                <path d="M12 5C6.5 5 2.05 8.42 1 12c1.05 3.58 5.5 7 11 7s9.95-3.42 11-7c-1.05-3.58-5.5-7-11-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"></path>
                            </svg>
                            <svg id="iconEyeClosed" class="icon-hidden" aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                <path d="M2.81 2.81 1.39 4.22l2.65 2.65C2.8 8.02 1.72 9.42 1 12c1.05 3.58 5.5 7 11 7 2.11 0 4.08-.5 5.8-1.35l2.98 2.98 1.41-1.41L2.81 2.81zM12 17c-2.76 0-5-2.24-5-5 0-.88.23-1.71.63-2.44l1.52 1.52A3 3 0 0 0 12 15c.69 0 1.32-.23 1.84-.61l1.54 1.54c-.98.68-2.16 1.07-3.38 1.07zm10-5c-.78-2.66-2.03-4.24-3.44-5.35l-1.45 1.45A9.35 9.35 0 0 1 20 12c-.83 2.83-4.48 5-8 5-.58 0-1.15-.06-1.7-.17l-1.73-1.73A4.95 4.95 0 0 1 7 12c0-.48.07-.95.2-1.39L4.9 8.3A10.9 10.9 0 0 0 2 12c1.05 3.58 5.5 7 11 7 2.09 0 4.03-.49 5.73-1.34l1.93 1.93 1.41-1.41-2.06-2.06A12.46 12.46 0 0 0 23 12h-1z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <button class="btn login-submit" type="submit">Sign In</button>
            </form>

            <p class="login-help-text">Need help? Visit the <a href="index.php#contact">contact page</a> or reach us on WhatsApp.</p>
        </section>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
<script src="assets.js?v=3"></script>
</body>
</html>
