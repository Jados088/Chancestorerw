# Chance Store Ltd Marketing Web App

Single-seller marketing and order management system built with PHP, HTML, CSS, JavaScript, and MySQL.

## Setup

1. Create/import database using `database.sql`.
2. Confirm DB credentials in `config.php`:
   - host: `127.0.0.1`
   - db: `chance_store`
   - user: `root`
   - pass: ``
3. Place project inside your XAMPP htdocs folder.
4. Start Apache and MySQL in XAMPP.
5. Open `http://localhost/Chance/`.

## Admin Login

- Email: `admin@chancestore.com`
- Password: `admin123`

Change this default password immediately after first login by updating the value in the database with a new `password_hash`.

## Features Included

- Public product listing with search
- Order form per product (saved to DB)
- Contact Seller section with WhatsApp button
- Admin secure login (`password_hash` / `password_verify`, PHP sessions)
- Admin product CRUD with image upload handling
- Admin order history view
- Admin contact details management
- CSRF token validation and SQL injection prevention using prepared statements
