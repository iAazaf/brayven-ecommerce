-- =====================================================================
-- BRAYVEN — Define Your Own Standard.
-- Full schema + seed data for XAMPP / MariaDB 10.4+ (also MySQL 8+).
--
-- Import: phpMyAdmin > Import, or
--         mysql -u root brayven < database.sql
--
-- NOTE: All products are demo/sample content, not real inventory.
--       The only seeded account is a demo CUSTOMER (no admin is seeded).
--       Create the first admin with setup-admin.php (see README.md).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS brayven CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE brayven;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items, orders, wishlist_items, wishlists, product_images, products, categories, users, newsletter_subscribers, contact_messages;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Users (customers + admins share one table; role decides access)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  phone         VARCHAR(30)  NULL,
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  status        ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(80)  NOT NULL,
  slug        VARCHAR(90)  NOT NULL,
  description VARCHAR(255) NULL,
  image       VARCHAR(190) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Products
-- Sizes & colors are stored as comma-separated lists; stock is per product.
-- ---------------------------------------------------------------------
CREATE TABLE products (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  name        VARCHAR(140) NOT NULL,
  slug        VARCHAR(160) NOT NULL,
  sku         VARCHAR(40)  NOT NULL,
  description TEXT         NOT NULL,
  price       DECIMAL(10,2) NOT NULL,
  sale_price  DECIMAL(10,2) NULL,
  image       VARCHAR(190) NULL,
  sizes       VARCHAR(120) NOT NULL DEFAULT '',
  colors      VARCHAR(190) NOT NULL DEFAULT '',
  stock       INT NOT NULL DEFAULT 0,
  department  ENUM('men','women','unisex') NOT NULL DEFAULT 'unisex',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_new      TINYINT(1) NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_category (category_id),
  KEY idx_products_active (is_active),
  KEY idx_products_flags (is_featured, is_new),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Product gallery images
-- ---------------------------------------------------------------------
CREATE TABLE product_images (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  image      VARCHAR(190) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_product_images_product (product_id),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Wishlists
-- ---------------------------------------------------------------------
CREATE TABLE wishlists (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wishlists_user (user_id),
  CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlist_items (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  wishlist_id INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wishlist_item (wishlist_id, product_id),
  KEY idx_wishlist_items_product (product_id),
  CONSTRAINT fk_wishlist_items_wishlist FOREIGN KEY (wishlist_id) REFERENCES wishlists (id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_items_product  FOREIGN KEY (product_id)  REFERENCES products (id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Orders (customer_id NULL = guest checkout)
-- Product name/sku/price are snapshotted onto order_items so history
-- stays correct even if a product is later removed.
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_number   VARCHAR(24) NOT NULL,
  user_id        INT UNSIGNED NULL,
  customer_name  VARCHAR(100) NOT NULL,
  email          VARCHAR(190) NOT NULL,
  phone          VARCHAR(30)  NOT NULL,
  address        VARCHAR(255) NOT NULL,
  city           VARCHAR(100) NOT NULL,
  postal_code    VARCHAR(20)  NOT NULL,
  notes          VARCHAR(500) NULL,
  subtotal       DECIMAL(10,2) NOT NULL,
  shipping_fee   DECIMAL(10,2) NOT NULL,
  total          DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cod') NOT NULL DEFAULT 'cod',
  payment_status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  status         ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_number (order_number),
  KEY idx_orders_user (user_id),
  KEY idx_orders_status (status),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,
  product_name VARCHAR(140) NOT NULL,
  sku          VARCHAR(40)  NOT NULL,
  size         VARCHAR(20)  NOT NULL,
  color        VARCHAR(40)  NOT NULL,
  unit_price   DECIMAL(10,2) NOT NULL,
  quantity     INT NOT NULL,
  line_total   DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_product (product_id),
  CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id)   REFERENCES orders (id)   ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Marketing / support
-- ---------------------------------------------------------------------
CREATE TABLE newsletter_subscribers (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email      VARCHAR(190) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_newsletter_email (email)
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  subject    VARCHAR(150) NOT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA
-- =====================================================================

INSERT INTO categories (id, name, slug, description, sort_order) VALUES
(1, 'T-Shirts',          't-shirts',          'Everyday jersey, cut with intention.',      1),
(2, 'Shirts',            'shirts',            'Oxford, linen and overshirt silhouettes.',  2),
(3, 'Hoodies',           'hoodies',           'Heavyweight fleece, structured hoods.',     3),
(4, 'Sweatshirts',       'sweatshirts',       'Considered layers in loopback cotton.',     4),
(5, 'Trousers',          'trousers',          'Tailored and relaxed, ankle to waist.',     5),
(6, 'Jeans',             'jeans',             'Selvedge and comfort denim, honest washes.',6),
(7, 'Jackets',           'jackets',           'Outer layers built to outlast seasons.',    7),
(8, 'Women''s Essentials','womens-essentials', 'Refined foundations for daily rotation.',   8),
(9, 'Accessories',       'accessories',       'Leather goods and finishing details.',      9);

INSERT INTO users (name, email, password_hash, phone, role, status) VALUES
('Demo Customer', 'demo@brayven.test', '$2y$10$1FtsCpfQrV4E13aLEu1lueUFXNeG5rHaP30I6FSJ8wWhI.4yMEbmC', '+1 555 012 3456', 'customer', 'active');
-- Demo customer password: Brayven@123  (sample data only — delete before real use)

-- ----------------------------------------------------------------------
-- Products (34 items — sample content, not real inventory)
-- ----------------------------------------------------------------------
INSERT INTO products (category_id, name, slug, sku, description, price, sale_price, image, sizes, colors, stock, department, is_featured, is_new, is_active) VALUES
(1, 'Essential Crew Tee', 'essential-crew-tee', 'BRV-TEE-001',
 'A cornerstone crew neckline in mid-weight combed cotton with a clean shoulder line and a softly structured collar that holds its shape wash after wash.',
 29.00, NULL, 'essential-crew-tee-1.svg', 'S,M,L,XL', 'Sand, Charcoal, Ivory', 48, 'unisex', 1, 1, 1),
(1, 'Heavyweight Oversized Tee', 'heavyweight-oversized-tee', 'BRV-TEE-002',
 'Cut from dense 240gsm jersey with a boxy, dropped shoulder. The heavyweight knit drapes rather than clings — an easy layer that stands on its own.',
 35.00, NULL, 'heavyweight-oversized-tee-1.svg', 'S,M,L,XL', 'Charcoal, Ivory, Navy', 36, 'unisex', 0, 1, 1),
(1, 'Pima Cotton Pocket Tee', 'pima-cotton-pocket-tee', 'BRV-TEE-003',
 'Long-staple Pima cotton gives this pocket tee its signature softness and subtle lustre. Finished with a patch chest pocket and ribbed neckline.',
 49.00, 39.00, 'pima-cotton-pocket-tee-1.svg', 'S,M,L,XL', 'White, Stone, Navy', 30, 'unisex', 1, 0, 1),
(1, 'Slub Jersey Longline Tee', 'slub-jersey-longline-tee', 'BRV-TEE-004',
 'Textured slub yarns give this longline tee a lived-in surface, with a gently curved hem and relaxed body through the torso.',
 32.00, NULL, 'slub-jersey-longline-tee-1.svg', 'S,M,L,XL', 'Stone, Charcoal', 0, 'unisex', 0, 0, 1),
(2, 'Oxford Button-Down Shirt', 'oxford-button-down-shirt', 'BRV-SHT-001',
 'A proper oxford in wash-woven cotton with a soft button-down collar, single chest pocket and a regular fit that layers easily under knitwear.',
 69.00, NULL, 'oxford-button-down-shirt-1.svg', 'S,M,L,XL', 'Ivory, Navy, Stone', 26, 'men', 1, 0, 1),
(2, 'Linen Camp Collar Shirt', 'linen-camp-collar-shirt', 'BRV-SHT-002',
 'Woven from European flax with an open camp collar and relaxed body. Breathable, honest and unforced — summer tailoring without the stiffness.',
 79.00, NULL, 'linen-camp-collar-shirt-1.svg', 'S,M,L,XL', 'Sand, Ivory, Olive', 22, 'men', 0, 1, 1),
(2, 'Flannel Overshirt', 'flannel-overshirt', 'BRV-SHT-003',
 'A brushed cotton flannel overshirt cut roomy enough to wear over a tee. Dual chest pockets and a straight hem make it a shirt-jacket hybrid.',
 89.00, 65.00, 'flannel-overshirt-1.svg', 'M,L,XL', 'Rust, Charcoal', 18, 'men', 0, 0, 1),
(2, 'Poplin Stretch Shirt', 'poplin-stretch-shirt', 'BRV-SHT-004',
 'Crisp cotton poplin with a whisper of elastane for movement. A semi-cutaway collar and clean front placket keep it boardroom sharp, weekend easy.',
 75.00, NULL, 'poplin-stretch-shirt-1.svg', 'S,M,L,XL', 'Navy, White', 28, 'men', 0, 0, 1),
(3, 'Classic Pullover Hoodie', 'classic-pullover-hoodie', 'BRV-HOD-001',
 'Our benchmark hoodie: 400gsm loopback fleece, double-layer hood, ribbed cuffs and a kangaroo pocket. Garment-dyed for a soft, matte finish.',
 85.00, NULL, 'classic-pullover-hoodie-1.svg', 'S,M,L,XL', 'Charcoal, Sand, Navy', 40, 'unisex', 1, 1, 1),
(3, 'Heavyweight Zip Hoodie', 'heavyweight-zip-hoodie', 'BRV-HOD-002',
 'A full-zip built on the same 400gsm fleece as our pullover, with YKK hardware, split kangaroo pockets and a straight, no-fuss silhouette.',
 95.00, NULL, 'heavyweight-zip-hoodie-1.svg', 'S,M,L,XL', 'Black, Charcoal', 24, 'unisex', 0, 0, 1),
(3, 'Cropped Boxy Hoodie', 'cropped-boxy-hoodie', 'BRV-HOD-003',
 'A shorter, boxier take on the classic hoodie with raw-edge cuffs and a wide ribbed hem. Sits at the high hip and layers over longer tops.',
 79.00, NULL, 'cropped-boxy-hoodie-1.svg', 'XS,S,M,L', 'Stone, Charcoal, Sand', 20, 'women', 0, 1, 1),
(4, 'Crewneck Sweatshirt', 'crewneck-sweatshirt', 'BRV-SWT-001',
 'The quiet workhorse: mid-weight loopback cotton, set-in sleeves and a collar that stays flat. No logos, no noise — just the fit.',
 75.00, NULL, 'crewneck-sweatshirt-1.svg', 'S,M,L,XL', 'Sand, Charcoal, Olive', 34, 'unisex', 1, 0, 1),
(4, 'Fleece-Lined Sweatshirt', 'fleece-lined-sweatshirt', 'BRV-SWT-002',
 'Brushed inside for warmth without bulk. A slightly extended body and ribbed side panels keep the silhouette clean when layered.',
 85.00, NULL, 'fleece-lined-sweatshirt-1.svg', 'M,L,XL', 'Olive, Charcoal', 16, 'unisex', 0, 0, 1),
(4, 'Cropped Crew Sweatshirt', 'cropped-crew-sweatshirt', 'BRV-SWT-003',
 'A boxy cropped crew in heavyweight fleece with dropped shoulders and a wide neckline. Pairs naturally with high-rise denim.',
 69.00, NULL, 'cropped-crew-sweatshirt-1.svg', 'XS,S,M,L', 'Ivory, Stone', 22, 'women', 0, 1, 1),
(5, 'Tailored Wool Trousers', 'tailored-wool-trousers', 'BRV-TRT-001',
 'A flat-front trouser in a wool-blend suiting cloth with a gentle taper and 4cm turn-up option. Pressed crease, half-lined to the knee.',
 119.00, NULL, 'tailored-wool-trousers-1.svg', '30,32,34,36', 'Charcoal, Navy', 18, 'men', 1, 0, 1),
(5, 'Pleated Linen Trousers', 'pleated-linen-trousers', 'BRV-TRT-002',
 'Single-pleat linen trousers with a relaxed seat and straight leg. They crease — that is the point. An unhurried warm-weather staple.',
 99.00, NULL, 'pleated-linen-trousers-1.svg', '30,32,34,36', 'Sand, Ivory', 20, 'men', 0, 1, 1),
(5, 'Drawstring Cotton Trousers', 'drawstring-cotton-trousers', 'BRV-TRT-003',
 'Heavy cotton twill with a clean-front finish and a covered drawstring waist. The rare lounge trouser you can wear out of the house.',
 79.00, NULL, 'drawstring-cotton-trousers-1.svg', 'S,M,L,XL', 'Stone, Charcoal', 25, 'unisex', 0, 0, 1),
(5, 'Wide-Leg Cropped Trousers', 'wide-leg-cropped-trousers', 'BRV-TRT-004',
 'A high-rise, wide-leg trouser cropped at the ankle in fluid tencel twill. Deep side pockets, hidden hook closure, no distractions.',
 89.00, NULL, 'wide-leg-cropped-trousers-1.svg', 'XS,S,M,L', 'Black, Stone', 19, 'women', 0, 1, 1),
(6, 'Straight-Leg Selvedge Jeans', 'straight-leg-selvedge-jeans', 'BRV-JNS-001',
 'Japanese-milled selvedge denim in a rigid 13oz weave. Straight from hip to hem, with a mid rise that settles in and fades with you.',
 129.00, NULL, 'straight-leg-selvedge-jeans-1.svg', '30,32,34,36', 'Indigo', 22, 'men', 1, 0, 1),
(6, 'Relaxed Tapered Jeans', 'relaxed-tapered-jeans', 'BRV-JNS-002',
 'Room through the thigh, tapered below the knee, in a comfort-stretch denim with a clean stone wash. Weekend volume, tailored restraint.',
 109.00, NULL, 'relaxed-tapered-jeans-1.svg', '30,32,34,36', 'Stone Blue, Indigo', 26, 'men', 0, 0, 1),
(6, 'High-Rise Mom Jeans', 'high-rise-mom-jeans', 'BRV-JNS-003',
 'A high waist, a relaxed leg and a gently tapered ankle — the archetype, redrawn in rigid cotton denim with a soft light-blue wash.',
 99.00, NULL, 'high-rise-mom-jeans-1.svg', 'XS,S,M,L', 'Light Blue', 21, 'women', 0, 1, 1),
(7, 'Minimal Bomber Jacket', 'minimal-bomber-jacket', 'BRV-JKT-001',
 'A stripped-back bomber in matte twill: ribbed collar, cuffs and hem, welt pockets and a two-way zip. Nothing embroidered, nothing extra.',
 149.00, NULL, 'minimal-bomber-jacket-1.svg', 'S,M,L,XL', 'Black, Olive', 14, 'unisex', 1, 0, 1),
(7, 'Field Jacket', 'field-jacket', 'BRV-JKT-002',
 'Four pockets, storm flap, cotton-nylon ripstop with a light wax finish. Built on workwear logic, finished with city restraint.',
 179.00, NULL, 'field-jacket-1.svg', 'M,L,XL', 'Olive, Charcoal', 12, 'men', 0, 0, 1),
(7, 'Quilted Liner Jacket', 'quilted-liner-jacket', 'BRV-JKT-003',
 'A diamond-quilted liner in recycled nylon with a matte shell and slim profile. Wear it alone, or under a shell when the temperature drops.',
 139.00, 109.00, 'quilted-liner-jacket-1.svg', 'S,M,L,XL', 'Sand, Black', 15, 'unisex', 0, 0, 1),
(7, 'Wool Overcoat', 'wool-overcoat', 'BRV-JKT-004',
 'A single-breasted overcoat in an Italian wool-cashmere blend, cut long and clean with a concealed placket and welt pockets. Quiet authority.',
 249.00, NULL, 'wool-overcoat-1.svg', 'M,L,XL', 'Charcoal, Navy', 8, 'men', 1, 1, 1),
(8, 'Ribbed Knit Top', 'ribbed-knit-top', 'BRV-WMN-001',
 'A second-skin rib knit in a merino blend with a high neck and long sleeves. The layering piece the rest of the wardrobe agrees on.',
 55.00, NULL, 'ribbed-knit-top-1.svg', 'XS,S,M,L', 'Ivory, Black, Sand', 28, 'women', 0, 1, 1),
(8, 'Midi Slip Dress', 'midi-slip-dress', 'BRV-WMN-002',
 'A bias-cut slip in washed cupro that moves like liquid and skims rather than clings. Adjustable straps, midi length, quiet finish.',
 119.00, NULL, 'midi-slip-dress-1.svg', 'XS,S,M,L', 'Sand, Black', 16, 'women', 1, 1, 1),
(8, 'Boxy Linen Blazer', 'boxy-linen-blazer', 'BRV-WMN-003',
 'An unstructured single-button blazer in washed linen with soft shoulders and patch pockets. Tailoring with the volume turned up.',
 139.00, NULL, 'boxy-linen-blazer-1.svg', 'XS,S,M,L', 'Stone, Sand', 13, 'women', 0, 1, 1),
(8, 'High-Neck Ribbed Bodysuit', 'high-neck-ribbed-bodysuit', 'BRV-WMN-004',
 'A fitted ribbed bodysuit with a high neck and snap base. Stays tucked, stays smooth, disappears under everything and works alone.',
 49.00, NULL, 'high-neck-ribbed-bodysuit-1.svg', 'XS,S,M,L', 'Black, Ivory', 30, 'women', 0, 0, 1),
(9, 'Leather Card Holder', 'leather-card-holder', 'BRV-ACC-001',
 'Full-grain vegetable-tanned leather, edge-painted by hand. Four card slots and a centre pocket in a package thinner than a wallet has any right to be.',
 45.00, NULL, 'leather-card-holder-1.svg', 'One Size', 'Tan, Black', 40, 'unisex', 0, 0, 1),
(9, 'Canvas Weekender Bag', 'canvas-weekender-bag', 'BRV-ACC-002',
 'Heavy cotton canvas with bridle leather trim, a two-way zip and a detachable shoulder strap. Forty-eight hours of packing, handled.',
 129.00, NULL, 'canvas-weekender-bag-1.svg', 'One Size', 'Olive, Charcoal', 10, 'unisex', 1, 0, 1),
(9, 'Ribbed Cotton Beanie', 'ribbed-cotton-beanie', 'BRV-ACC-003',
 'A fine-rib beanie in merino-blend cotton with a double-layer brim. Close-fitting, itch-free and deliberately unbranded.',
 29.00, NULL, 'ribbed-cotton-beanie-1.svg', 'One Size', 'Charcoal, Sand, Navy', 45, 'unisex', 0, 0, 1),
(9, 'Silk-Blend Scarf', 'silk-blend-scarf', 'BRV-ACC-004',
 'A brushed silk-blend scarf with hand-rolled edges and a tonal herringbone weave. Warmth without weight, finish without flash.',
 59.00, NULL, 'silk-blend-scarf-1.svg', 'One Size', 'Rust, Stone', 0, 'unisex', 0, 1, 1),
(9, 'Leather Belt', 'leather-belt', 'BRV-ACC-005',
 'A 3cm belt in vegetable-tanned leather with a matte gunmetal buckle. Cut long, then to size — it will outlast the trousers it holds.',
 55.00, NULL, 'leather-belt-1.svg', 'S,M,L', 'Black, Tan', 35, 'unisex', 0, 0, 1);

-- ----------------------------------------------------------------------
-- Gallery images: every product gets a main shot; featured ones get a detail shot.
-- ----------------------------------------------------------------------
INSERT INTO product_images (product_id, image, sort_order)
SELECT p.id, CONCAT(p.slug, '-1.svg'), 0 FROM products p WHERE p.image IS NOT NULL;

INSERT INTO product_images (product_id, image, sort_order)
SELECT p.id, CONCAT(p.slug, '-2.svg'), 1 FROM products p WHERE p.is_featured = 1;

-- ----------------------------------------------------------------------
-- Sample content rows
-- ----------------------------------------------------------------------
INSERT INTO newsletter_subscribers (email) VALUES
('early.access@brayven.test');

INSERT INTO contact_messages (name, email, subject, message) VALUES
('Jordan M.', 'jordan.m@brayven.test', 'Sizing question', 'Hi — between sizes on the selvedge jeans. Should I size down?');
