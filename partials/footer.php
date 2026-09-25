<?php
declare(strict_types=1);

$contact = get_site_contact();
$waPhone = phone_digits((string) $contact['phone']);
?>
<footer class="site-footer">
    <div class="container site-footer-grid">
        <div class="site-footer-brand">
            <h3 class="site-footer-title">
                <span class="site-footer-title-main">CHANCE</span>
                <span class="site-footer-title-accent">STORE LTD</span>
            </h3>
            <p class="site-footer-desc">Rwanda's trusted destination for quality products. We guarantee authenticity, fair prices, and fast customer support across the country.</p>
            <div class="site-footer-socials">
                <a class="footer-social-circle" href="https://www.instagram.com/chance_store_rw/" target="_blank" rel="noopener" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5a3.95 3.95 0 0 0 3.95 3.95h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5Zm8.95 1.45a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4Z"/></svg>
                </a>
                <a class="footer-social-circle" href="https://www.facebook.com/profile.php?id=61578514747233" target="_blank" rel="noopener" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="M13.3 22v-8.15h2.75l.42-3.2H13.3V8.6c0-.93.26-1.55 1.6-1.55h1.7V4.2a22.6 22.6 0 0 0-2.47-.12c-2.45 0-4.13 1.5-4.13 4.24v2.34H7.2v3.2H10V22h3.3Z"/></svg>
                </a>
                <a class="footer-social-circle" href="https://wa.me/<?= urlencode($waPhone) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
                    <svg viewBox="0 0 24 24" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
            </div>
        </div>

        <div class="site-footer-col">
            <h4 class="site-footer-col-title">Shop</h4>
            <ul class="site-footer-links">
                <li><a href="index.php#products">All Products</a></li>
                <li><a href="index.php#cart" class="cart-open-link">My Order</a></li>
                <li><a href="index.php#contact">Contact Us</a></li>
                <?php if (is_admin_logged_in()): ?>
                    <li><a href="admin.php">Dashboard</a></li>
                    <li><a class="footer-auth-link footer-auth-logout" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a class="footer-auth-link footer-auth-login" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="site-footer-col">
            <h4 class="site-footer-col-title">Contact</h4>
            <ul class="site-footer-contact">
                <li>
                    <span class="site-footer-contact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
                    </span>
                    <span><?= e((string) $contact['address']) ?></span>
                </li>
                <li>
                    <span class="site-footer-contact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.07 21 3 13.93 3 5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.46.57 3.58a1 1 0 0 1-.25 1.01l-2.2 2.2z"/></svg>
                    </span>
                    <a href="tel:<?= e($waPhone) ?>"><?= e((string) $contact['phone']) ?></a>
                </li>
                <li>
                    <span class="site-footer-contact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5L4 8V6l8 5 8-5v2z"/></svg>
                    </span>
                    <a href="mailto:<?= e((string) $contact['email']) ?>"><?= e((string) $contact['email']) ?></a>
                </li>
            </ul>
        </div>

        <div class="site-footer-col">
            <h4 class="site-footer-col-title">Payment Method</h4>
            <div class="payment-card">
                <div class="payment-card-top">
                    <span class="payment-badge">MOMO PAY</span>
                    <span class="payment-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M17 1H7a2 2 0 0 0-2 2v18a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm-5 20a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5-4H7V4h10v13z"/></svg>
                    </span>
                </div>
                <div class="payment-number">1604382</div>
                <div class="payment-merchant">Merchant: <strong>Chance Store Ltd</strong></div>
            </div>
            <p class="payment-secure">
                <span aria-hidden="true">&#128274;</span> Secure Payment on Delivery
            </p>
        </div>
    </div>

    <div class="site-footer-bottom">
        <div class="container site-footer-bottom-inner">
            <p>&copy; <?= date('Y') ?> CHANCE STORE LTD. All Rights Reserved.</p>
            <p>Developed by <strong>Chance Store Team</strong></p>
        </div>
    </div>
</footer>

<a class="whatsapp-fab" href="https://wa.me/<?= urlencode($waPhone) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 24 24" focusable="false"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
</a>
