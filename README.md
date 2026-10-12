# BRAYVEN — Premium Fashion E-Commerce

**“Define Your Own Standard.”**

A complete, functional full-stack fashion e-commerce application built with **PHP 8+, MySQL/MariaDB, PDO, Bootstrap 5.3.3, vanilla JavaScript and jQuery** — designed to run on a standard XAMPP stack. No frameworks, no build step.

> All catalogue content is **sample data** for demonstration — not real inventory or pricing.

---

## Features

| Area | What works |
|---|---|
| Storefront | Campaign homepage, shop, category pages, search, product detail with gallery, related products |
| Catalogue | 34 seeded products, 9 categories, size/colour variants, sale pricing, featured & new-arrival flags |
| Filtering | Category, department, price range, size, colour, sort (newest / price ↑ / price ↓), pagination, clear-filters — all server-side |
| Cart | Session cart, add/update/remove, server-recomputed prices, stock clamping, free-shipping threshold |
| Checkout | Validated COD checkout, **database transaction** with row locks, stock decrement, duplicate-submission guard, PRG flow, guest or member checkout |
| Accounts | Register / login / logout with `password_hash`, session regeneration, profile editing (incl. password change), order history |
| Wishlist | DB-backed per user, toggle from cards or product pages, merge-safe |
| Admin | Dashboard stats, product CRUD with image upload, order management with status/payment updates, category CRUD, customer enable/disable — all role-gated server-side |
| Security | PDO prepared statements everywhere, `htmlspecialchars` output escaping, CSRF tokens on every state-changing form, secure session cookies, role checks, validated uploads (MIME + size + random names), no credential leaks in error output |

---

## 1. Prerequisites

- **XAMPP** (Apache + MySQL/MariaDB) — tested with PHP 8.2 and MariaDB 10.4
- A modern browser
- Internet access the first time you load a page, for CDN assets
  (Bootstrap 5.3.3, Font Awesome 6.5, Google Fonts, jQuery). Without internet the layout
  still functions but loads with fallback fonts and unstyled controls.

## 2. Install (XAMPP)

1. Copy (or clone) this project folder into your web root:
   ```
   C:\xampp\htdocs\brayven
   ```
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Import the database — either:
   - **phpMyAdmin**: open http://localhost/phpmyadmin → *Import* → choose `database.sql` → Go, or
   - **Command line**:
     ```
     C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\brayven\database.sql
     ```
   The script creates the `brayven` database, all tables, and 34 sample products.
4. Open the site: **http://localhost/brayven/** (or `http://localhost/` if the project folder *is* your web root).

## 3. Database Configuration

Credentials live in one file: **`config/database.php`**

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'brayven');
define('DB_USER', 'root');
define('DB_PASS', '');   // XAMPP default is empty
```

Change these to match your MySQL setup. Store settings (shipping fee, free-shipping
threshold, page size) are constants in the same file.

## 4. Create the First Admin (no hardcoded passwords)

There is **no admin account in the seed data** and no default password anywhere.

1. While on `localhost`, visit: **http://localhost/brayven/setup-admin.php**
2. Choose a name, email and a strong password (10+ characters, letters + numbers).
3. Submit — the account is created, you are signed in, and the page **locks itself permanently**
   (it refuses to run once any admin exists, and only ever runs from localhost).
4. **Delete `setup-admin.php`** after setup — recommended housekeeping.

Admin panel: **http://localhost/brayven/admin/**

### Demo customer (seeded for testing only)

| | |
|---|---|
| Email | `demo@brayven.test` |
| Password | `Brayven@123` |

This is sample content — delete the row from `users` before any real use.

## 5. Suggested Test Walkthrough

1. **Browse** — homepage → Shop Collection → open a product → pick size + colour.
2. **Filter** — in Shop: set price range 40–100, tick size `M`, sort by price, then Clear all filters.
3. **Search** — search `linen`, then an empty query for the search landing state.
4. **Cart** — add a product twice with different sizes, update quantity, remove a line, try adding more than the available stock (stock is clamped server-side).
5. **Checkout** — fill the form (try an invalid email/phone first), place the order, note the `BRV-` order number, verify the receipt and that the bag is now empty.
6. **Orders as guest** — the receipt is viewable once from the session; visiting it later as another user is denied.
7. **Register** a new account → edit profile → change password → sign out → sign back in.
8. **Wishlist** — heart a product while signed out (redirects to login), sign in, heart again, view wishlist.
9. **Admin** — sign in at `/setup-admin.php`-created account → dashboard stats → add a product with an image upload → edit it → hide it → check it disappeared from the storefront → orders page → update a status → categories page → customers page.
10. **Security spot-checks** — try `product.php?slug=does-not-exist` (404 page), view a customer's order URL as an admin (receipt page is customer-scoped), submit the login form without a CSRF token (419).

## 6. Project Structure

```
├── admin/                  # Admin console (separate layout, role-gated)
│   ├── index.php           # Dashboard
│   ├── products.php        # List / hide / delete products
│   ├── product-edit.php    # Add/edit product + image upload
│   ├── orders.php          # Order list, filters, inline status updates
│   ├── order-view.php      # Order detail
│   ├── categories.php      # Category CRUD
│   ├── customers.php       # Customer list + enable/disable
│   └── includes/           # Admin header/footer
├── assets/
│   ├── css/style.css       # Storefront design system
│   ├── css/admin.css       # Admin design system
│   ├── js/main.js          # Progressive-enhancement behaviour
│   └── images/             # Favicon + seeded SVG product/editorial artwork
├── config/database.php     # PDO connection + credentials + store settings
├── includes/
│   ├── init.php            # Session, helpers, auth, cart, wishlist, uploads
│   ├── header.php          # Storefront header
│   ├── footer.php          # Storefront footer
│   ├── product-card.php    # Reusable card partial
│   └── product-listing.php # Filter/sort/pagination engine
├── uploads/products/       # Admin image uploads (validated)
├── tools/generate-images.php  # Regenerates the seeded SVG artwork
├── index.php shop.php category.php product.php search.php
├── cart.php checkout.php order-success.php
├── login.php register.php logout.php profile.php orders.php wishlist.php
├── about.php contact.php faq.php privacy.php terms.php
├── setup-admin.php         # One-time first-admin creation
├── database.sql            # Full schema + seed data
└── README.md
```

## 7. Design Notes

- Palette: `#F7F6F2` background · `#171717` ink · `#77736E` muted · `#B7A58A` accent · `#E8E5DF` borders
- Typography: **Marcellus** (display serif) + **Inter** (body), via Google Fonts
- Product artwork is generated SVG (see `tools/generate-images.php`) so the demo catalogue looks coherent without stock photography. Admin uploads of real JPG/PNG/WebP photos drop straight into the same layout.
- Mobile-first: offcanvas navigation, collapsible filters, responsive grids from 320px up.

## 8. Honest Limitations

- **Payments**: Cash on Delivery only. No card processing is simulated — there is no gateway.
- **Email**: order confirmations are simulated (no SMTP configured); hooks exist where mail would be sent.
- **CDNs**: Bootstrap/Font Awesome/Google Fonts/jQuery load from CDNs; on a fully offline machine, download them into `assets/` and update the tags in `includes/header.php` / `includes/footer.php`.
- **Images in the seed**: generated SVG illustrations, not photography.
