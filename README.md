# Chance Store Ltd

A PHP and MySQL storefront with a product catalog, multi-item order basket, direct seller contact, and a role-based management dashboard.

## Local setup

1. Import `database.sql` into MySQL.
2. Configure the database connection in `config.php` for your local environment.
3. Place the project inside your XAMPP `htdocs` directory.
4. Start Apache and MySQL from the XAMPP Control Panel.
5. Open `http://localhost/Chancestore/`.

Change any seeded account passwords before deploying the site publicly. Do not commit production database credentials.

## Features

- Responsive product catalog with live name and description search.
- Basket for combining products and quantities into one order.
- Customer order submission with seller follow-up through WhatsApp.
- Seller and admin dashboard with product, order, and contact management.
- Admin reporting, PDF downloads, and user management.
- CSRF-protected forms, password hashing, and prepared database statements.
