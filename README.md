# Chance Store Ltd

A PHP and MySQL storefront with a product catalog, multi-item order basket, direct seller contact, and a role-based management dashboard.

## Local setup

1. Import `database.sql` into MySQL.
2. Set `DB_NAME=chance_store` for the supplied SQL setup, and configure the connection with `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables. The host, user, and password defaults are intended only for a local XAMPP installation.
3. Place the project inside your XAMPP `htdocs` directory.
4. Start Apache and MySQL from the XAMPP Control Panel.
5. Open `http://localhost/Chancestore/`.

For XAMPP, set the environment variables for Apache outside the project directory (for example, in the Apache configuration) and restart Apache. Use a dedicated MySQL account with only the permissions this application needs. Never commit production database or SMTP credentials. Change all seeded account passwords before deploying publicly.

## Features

- Responsive product catalog with live name and description search.
- Basket for combining products and quantities into one order.
- Customer order submission with seller follow-up through WhatsApp.
- Seller order dashboard and administrator dashboard for products, contacts, reports, and user management.
- Administrator reporting and PDF downloads.
- In-app seller order alerts, administrator notifications, audit history, and recent system-usage metrics.
- Optional notification email delivery through PHPMailer and authenticated SMTP.
- Role-checked actions, CSRF-protected forms, secure session cookies, password hashing, and prepared database statements.

## Notification email (optional)

In-app notifications work without email. To enable email, install PHPMailer with Composer from the project directory:

```powershell
composer require phpmailer/phpmailer
```

Set these variables in Apache's environment (not in a committed PHP file), then restart Apache:

- `SMTP_HOST`
- `SMTP_PORT` (defaults to `587` for TLS or `465` for SSL)
- `SMTP_USERNAME` and `SMTP_PASSWORD` (both optional if the SMTP server does not require authentication)
- `SMTP_ENCRYPTION` (`tls` or `ssl`; defaults to `tls`)
- `SMTP_FROM_EMAIL` (required when SMTP is enabled)
- `SMTP_FROM_NAME` (optional; defaults to `Chance Store`)

Email is sent to registered administrator accounts for system activity, and to sellers and administrators for order lifecycle events. Email delivery failures are written to the PHP error log; the in-app notification remains available.
