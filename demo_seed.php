<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 *  DEMO SEEDER  —  Clears ALL data and populates the database with demo content
 *
 *  Access:  /demo_seed.php?token=DEMO_RESET_2024
 *
 *  ⚠  WARNING: Running this script PERMANENTLY DELETES all existing data.
 *              Delete this file from production servers after use.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Security token guard ─────────────────────────────────────────────────────
// Token is read from the DEMO_SEED_TOKEN environment variable first.
// Falls back to the constant below only when the env var is not set.
// On production: set the env var and then delete this file after use.
define('DEMO_TOKEN', getenv('DEMO_SEED_TOKEN') ?: 'DEMO_RESET_2024');

if (!isset($_GET['token']) || !hash_equals(DEMO_TOKEN, $_GET['token'])) {
    http_response_code(403);
    die('403 Forbidden – a valid token query parameter is required.');
}

// ── Boot ─────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('Could not connect to the database. Check config/database.php.');
}

// Output helpers
$log = [];
function ok(string $msg): void  { global $log; $log[] = ['ok',  $msg]; }
function err(string $msg): void { global $log; $log[] = ['err', $msg]; }
function step(string $msg): void{ global $log; $log[] = ['step',$msg]; }

// ── Helper: execute SQL, log result; $fatal=true halts on error ──────────────
function run(PDO $db, string $sql, bool $fatal = false): void {
    try {
        $db->exec($sql);
        ok(substr(trim($sql), 0, 80) . '…');
    } catch (PDOException $e) {
        $msg = $e->getMessage() . ' — SQL: ' . substr($sql, 0, 80);
        err($msg);
        if ($fatal) {
            // Re-enable FK checks before bailing out
            try { $db->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $ignored) {}
            die('Fatal seeder error: ' . htmlspecialchars($msg));
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
//  1. DROP  ─  wipe everything
// ─────────────────────────────────────────────────────────────────────────────
step('Dropping existing tables…');
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    foreach ([
        'order_items','orders','purchase_items','purchases',
        'product_images','products','categories','suppliers',
        'payment_methods','delivery_methods','coupons','promotions',
        'banners','page_sections','contact_messages','expenses',
        'settings','users',
    ] as $table) {
        run($db, "DROP TABLE IF EXISTS `{$table}`");
    }
} finally {
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
}

// ─────────────────────────────────────────────────────────────────────────────
//  2. CREATE TABLES
// ─────────────────────────────────────────────────────────────────────────────
step('Creating tables…');

run($db, "CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','customer','sales','account') DEFAULT 'customer',
    is_verified TINYINT(1) DEFAULT 0,
    otp_code VARCHAR(10) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    default_discount DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    type ENUM('physical','digital') DEFAULT 'physical',
    category_id INT DEFAULT NULL,
    price DECIMAL(12,2) NOT NULL,
    stock INT DEFAULT 0,
    barcode VARCHAR(100) DEFAULT NULL,
    warranty_period VARCHAR(50) DEFAULT NULL,
    colors TEXT DEFAULT NULL,
    download_link TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE delivery_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type VARCHAR(20) DEFAULT 'checkout',
    account_number VARCHAR(100) DEFAULT NULL,
    account_name VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(150) DEFAULT NULL,
    customer_phone VARCHAR(30) DEFAULT NULL,
    customer_address TEXT DEFAULT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    delivery_method_id INT DEFAULT NULL,
    payment_method_id INT DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_receipt VARCHAR(255) DEFAULT NULL,
    delivery_proof VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    cancellation_reason TEXT DEFAULT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    address TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT DEFAULT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('pending','received','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    quantity INT DEFAULT 1,
    cost DECIMAL(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('standard','tiered') DEFAULT 'standard',
    discount_type ENUM('fixed','percentage') DEFAULT 'percentage',
    value DECIMAL(10,2) DEFAULT 0.00,
    min_spend DECIMAL(12,2) DEFAULT 0.00,
    tier_data JSON DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(50) DEFAULT NULL,
    value DECIMAL(10,2) DEFAULT 0.00,
    requirement DECIMAL(12,2) DEFAULT 0.00,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    applicable_products JSON DEFAULT NULL,
    applicable_categories JSON DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(150) DEFAULT 'My Store',
    phone VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    free_shipping_min DECIMAL(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE page_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(50) NOT NULL,
    section_key VARCHAR(100) NOT NULL,
    title VARCHAR(255) DEFAULT '',
    content TEXT DEFAULT '',
    is_visible TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    settings TEXT DEFAULT NULL,
    UNIQUE KEY uq_page_section (page, section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

run($db, "CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    date DATE DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", true);

// ─────────────────────────────────────────────────────────────────────────────
//  3. SETTINGS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding settings…');
$db->exec("INSERT INTO settings (site_name, phone, address, free_shipping_min)
    VALUES ('Phlox Store', '+1 800 123 4567', '123 Commerce Street, New York, NY 10001', 50000)");
ok('Settings inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  4. USERS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding users…');
$users = [
    ['admin',    'admin@phlox.store',    password_hash('admin123',   PASSWORD_DEFAULT), 'admin',    1, '+1 800 000 0001', 'Admin HQ, New York'],
    ['john_doe', 'john@example.com',     password_hash('demo1234',   PASSWORD_DEFAULT), 'customer', 1, '+1 555 111 2222', '45 Elm St, Chicago'],
    ['jane_lee', 'jane@example.com',     password_hash('demo1234',   PASSWORD_DEFAULT), 'customer', 1, '+1 555 333 4444', '88 Oak Ave, Los Angeles'],
    ['sales_rep','sales@phlox.store',    password_hash('sales123',   PASSWORD_DEFAULT), 'sales',    1, '+1 555 555 6666', 'Sales Dept, New York'],
];
$uStmt = $db->prepare("INSERT INTO users (username,email,password,role,is_verified,phone,address) VALUES (?,?,?,?,?,?,?)");
foreach ($users as $u) { $uStmt->execute($u); }
ok(count($users) . ' users inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  5. CATEGORIES
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding categories…');
$categories = ['Earphones','Wearables','Laptops','Gaming','VR & AR','Smart Speakers','Digital Products'];
$catStmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
foreach ($categories as $c) { $catStmt->execute([$c]); }
// Build category name→id map
$catMap = [];
foreach ($db->query("SELECT id, name FROM categories")->fetchAll() as $row) {
    $catMap[$row['name']] = (int)$row['id'];
}
ok(count($categories) . ' categories inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  6. PRODUCTS  (16 demo products matching the design image)
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding products…');

// [name, description, type, category, price, stock, warranty, colors, download_link, image]
$products = [
    // Earphones
    ['Beats Solo Wireless Headphone',
     'Premium wireless headphone with deep bass and up to 40-hour battery life. Foldable design with soft ear cushions.',
     'physical', 'Earphones', 45000, 28, '12 Months', 'Black,Red,White', null, null],
    ['Sony WH-1000XM5 ANC',
     'Industry-leading noise cancellation with Dual Noise Sensor technology. Crystal clear hands-free calling.',
     'physical', 'Earphones', 89000, 15, '12 Months', 'Black,Silver', null, null],
    ['Apple AirPods Pro (2nd Gen)',
     'Active Noise Cancellation, Adaptive Transparency, and Personalized Spatial Audio with dynamic head tracking.',
     'physical', 'Earphones', 75000, 20, '12 Months', 'White', null, null],
    ['JBL Tune 510BT',
     'Wireless on-ear headphones with 40-hour battery, foldable design, and JBL Pure Bass Sound.',
     'physical', 'Earphones', 28000, 40, '6 Months', 'Black,Blue,White,Pink', null, null],

    // Wearables
    ['Smart Watch Pro X3',
     'Fitness tracker with heart rate monitor, GPS, 7-day battery, and 100+ sport modes. IP68 waterproof.',
     'physical', 'Wearables', 35000, 22, '12 Months', 'Yellow,Black,Silver', null, null],
    ['Amazfit GTR 4',
     'Premium smartwatch with Alexa built-in, dual-band GPS, and 150+ sport modes. 14-day battery life.',
     'physical', 'Wearables', 52000, 18, '12 Months', 'Black,Brown,Gold', null, null],

    // Laptops
    ['Dell XPS 15 Laptop',
     '15.6" OLED display, Intel Core i7-13700H, 32GB RAM, 1TB SSD. The ultimate creator\'s machine.',
     'physical', 'Laptops', 1850000, 8, '24 Months', 'Silver,Black', null, null],
    ['MacBook Air M2',
     'Apple M2 chip, 13.6" Liquid Retina display, 18-hour battery, and 1080p FaceTime HD camera.',
     'physical', 'Laptops', 1650000, 10, '12 Months', 'Space Gray,Silver,Starlight,Midnight', null, null],
    ['ASUS VivoBook 15',
     '15.6" FHD display, AMD Ryzen 5, 8GB RAM, 512GB SSD. Thin, light and powerful everyday laptop.',
     'physical', 'Laptops', 680000, 14, '12 Months', 'Transparent Silver,Indie Black', null, null],

    // Gaming
    ['PlayStation 5 Console',
     'Experience lightning-fast loading, deeper immersion with haptic feedback and 4K gaming at 120fps.',
     'physical', 'Gaming', 950000, 5, '12 Months', 'White,Black', null, null],
    ['Xbox Series X',
     'True 4K gaming at 60fps, up to 120fps, 1TB custom NVMe SSD and ray-tracing support.',
     'physical', 'Gaming', 880000, 7, '12 Months', 'Black', null, null],

    // VR & AR
    ['Meta Quest 3',
     'Mixed reality headset with high-res colour passthrough, powerful Snapdragon XR2 Gen 2 processor.',
     'physical', 'VR & AR', 750000, 9, '12 Months', 'White', null, null],

    // Smart Speakers
    ['Amazon Echo (4th Gen)',
     'Premium sound with Dolby, built-in Alexa, smart home hub. Spherical design that complements any room.',
     'physical', 'Smart Speakers', 55000, 30, '12 Months', 'Charcoal,Glacier White,Twilight Blue', null, null],
    ['Sonos One SL',
     'Powerful stereo sound, multi-room music, and works with Apple AirPlay 2, Spotify Connect and more.',
     'physical', 'Smart Speakers', 125000, 16, '12 Months', 'Black,White', null, null],

    // Digital Products
    ['Adobe Creative Cloud 1-Year License',
     'Full access to 20+ creative desktop and mobile apps including Photoshop, Illustrator, and Premiere Pro.',
     'digital', 'Digital Products', 480000, 999, null, null, 'https://adobe.com/activate', null],
    ['Tech Productivity Bundle (eBook)',
     'A curated collection of 5 premium eBooks covering productivity, UI/UX design, and web development.',
     'digital', 'Digital Products', 25000, 999, null, null, 'https://example.com/ebook-bundle', null],
];

$pStmt = $db->prepare("INSERT INTO products
    (name, description, type, category_id, price, stock, warranty_period, colors, download_link, image, is_active)
    VALUES (?,?,?,?,?,?,?,?,?,?,1)");

foreach ($products as $p) {
    $catId = $catMap[$p[3]] ?? null;
    $pStmt->execute([
        $p[0], // name
        $p[1], // description
        $p[2], // type
        $catId, // category_id
        $p[4], // price
        $p[5], // stock
        $p[6], // warranty_period
        $p[7], // colors
        $p[8], // download_link
        $p[9], // image
    ]);
}
ok(count($products) . ' products inserted');

// Add has_discount / original_price / discount_percent virtual display via promotion seeding
// (The app reads promotions to show discounts on the front-end)

// ─────────────────────────────────────────────────────────────────────────────
//  7. PROMOTIONS (sale badges on products)
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding promotions…');
// Get product IDs for Beats & PS5 to give them a sale badge
$beatId  = $db->query("SELECT id FROM products WHERE name LIKE 'Beats%' LIMIT 1")->fetchColumn();
$ps5Id   = $db->query("SELECT id FROM products WHERE name LIKE 'PlayStation%' LIMIT 1")->fetchColumn();
$echoId  = $db->query("SELECT id FROM products WHERE name LIKE 'Amazon Echo%' LIMIT 1")->fetchColumn();

$promoStmt = $db->prepare("INSERT INTO promotions
    (name, type, value, requirement, start_date, end_date, is_active, applicable_products, applicable_categories)
    VALUES (?,?,?,?,?,?,?,?,?)");

$promoStmt->execute(['Summer Sale 20%', 'percentage_discount', 20, 0,
    date('Y-m-d'), date('Y-m-d', strtotime('+60 days')), 1,
    json_encode([$beatId, $ps5Id, $echoId]), null]);

$promoStmt->execute(['Earphone Category 10% Off', 'percentage_discount', 10, 0,
    date('Y-m-d'), date('Y-m-d', strtotime('+30 days')), 1,
    null, json_encode([$catMap['Earphones']])]);
ok('2 promotions inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  8. DELIVERY METHODS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding delivery methods…');
$db->exec("INSERT INTO delivery_methods (name, cost, is_active) VALUES
    ('Standard Delivery', 3500, 1),
    ('Express Delivery',  7500, 1),
    ('Same-Day Delivery', 12000, 1),
    ('Pickup In-Store',   0,     1)");
ok('4 delivery methods inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  9. PAYMENT METHODS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding payment methods…');
$db->exec("INSERT INTO payment_methods (name, type, account_number, account_name, is_active) VALUES
    ('KBZ Pay',   'checkout', '09250000001', 'Phlox Store Ltd',   1),
    ('Wave Money', 'checkout', '09778000002', 'Phlox Store Ltd',   1),
    ('AYA Pay',   'checkout', '09510000003', 'Phlox Store Ltd',   1),
    ('Cash',      'pos',       NULL,          NULL,                1)");
ok('4 payment methods inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  10. COUPONS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding coupons…');
$db->exec("INSERT INTO coupons (code, type, discount_type, value, min_spend, usage_limit, start_date, end_date) VALUES
    ('DEMO10',   'standard', 'percentage', 10,  0,      100, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY)),
    ('SUMMER20', 'standard', 'percentage', 20,  50000,  50,  CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
    ('SAVE5000', 'standard', 'fixed',      5000,100000, 30,  CURDATE(), DATE_ADD(CURDATE(), INTERVAL 45 DAY)),
    ('WELCOME',  'standard', 'percentage', 15,  0,      200, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY))");
ok('4 coupons inserted (DEMO10 / SUMMER20 / SAVE5000 / WELCOME)');

// ─────────────────────────────────────────────────────────────────────────────
//  11. PAGE SECTIONS  (home page builder)
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding page sections…');
$sections = [
    // Home
    ['home','hero',
     'Beats Solo Wireless',
     'There are many variations passages of Lorem Ipsum available, but the majority have suffered alteration.',
     1, 10, json_encode(['cta_text'=>'Shop By Category','cta_link'=>'/shop'])],

    ['home','promo_strip','Why Shop With Us','',1,20,null],

    ['home','featured_products','Best Seller Products',
     'There are many variations passages',
     1, 30, json_encode(['limit'=>8])],

    ['home','categories','Shop by Category','Find exactly what you\'re looking for.',1,40,null],

    // Shop
    ['shop','page_header','Our Products','Browse our full range of products.',1,10,null],

    // Contact
    ['contact','hero','Contact Us',"We'd love to hear from you. Reach out anytime.",1,10,null],
    ['contact','contact_info','Get In Touch','',1,20,null],
    ['contact','contact_form','Send Us a Message','',1,40,null],
];

$sStmt = $db->prepare("INSERT IGNORE INTO page_sections
    (page, section_key, title, content, is_visible, sort_order, settings)
    VALUES (?,?,?,?,?,?,?)");
foreach ($sections as $s) { $sStmt->execute($s); }
ok(count($sections) . ' page sections inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  12. SUPPLIERS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding suppliers…');
$db->exec("INSERT INTO suppliers (name, phone, email, address) VALUES
    ('TechWholesale Inc.',  '+1 312 000 1111', 'orders@techwholesale.com', '500 Tech Blvd, Chicago, IL'),
    ('GlobalGadgets Ltd.',  '+1 415 000 2222', 'supply@globalgadgets.com', '200 Market St, San Francisco, CA'),
    ('Digital Assets Hub',  '+1 212 000 3333', 'info@digitalhub.com',     '10 Wall St, New York, NY')");
ok('3 suppliers inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  13. SAMPLE ORDERS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding sample orders…');

// Fetch IDs
$johnId    = $db->query("SELECT id FROM users WHERE username='john_doe' LIMIT 1")->fetchColumn();
$janeId    = $db->query("SELECT id FROM users WHERE username='jane_lee'  LIMIT 1")->fetchColumn();
$delivId   = $db->query("SELECT id FROM delivery_methods WHERE name='Standard Delivery' LIMIT 1")->fetchColumn();
$exprId    = $db->query("SELECT id FROM delivery_methods WHERE name='Express Delivery'  LIMIT 1")->fetchColumn();
$kbzId     = $db->query("SELECT id FROM payment_methods  WHERE name='KBZ Pay' LIMIT 1")->fetchColumn();
$cashId    = $db->query("SELECT id FROM payment_methods  WHERE name='Cash'    LIMIT 1")->fetchColumn();
$beatsRow  = $db->query("SELECT id, price FROM products WHERE name LIKE 'Beats%' LIMIT 1")->fetch();
$sonyRow   = $db->query("SELECT id, price FROM products WHERE name LIKE 'Sony%'  LIMIT 1")->fetch();
$watchRow  = $db->query("SELECT id, price FROM products WHERE name LIKE 'Smart Watch%' LIMIT 1")->fetch();
$adobeRow  = $db->query("SELECT id, price FROM products WHERE name LIKE 'Adobe%' LIMIT 1")->fetch();

// Order 1 – delivered
$db->exec("INSERT INTO orders
    (user_id, customer_name, customer_phone, customer_address, total_amount, shipping_cost, discount_amount,
     delivery_method_id, payment_method_id, payment_method, status, created_at)
    VALUES ({$johnId},'John Doe','+1 555 111 2222','45 Elm St, Chicago',
    " . ($beatsRow['price']*2 + $sonyRow['price'] + 3500) . ", 3500, 0,
    {$delivId}, {$kbzId}, 'KBZ Pay', 'delivered', DATE_SUB(NOW(), INTERVAL 10 DAY))");
$o1 = $db->lastInsertId();
$db->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)")
   ->execute([$o1, $beatsRow['id'], 2, $beatsRow['price']]);
$db->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)")
   ->execute([$o1, $sonyRow['id'], 1, $sonyRow['price']]);

// Order 2 – processing
$db->exec("INSERT INTO orders
    (user_id, customer_name, customer_phone, customer_address, total_amount, shipping_cost, discount_amount,
     delivery_method_id, payment_method_id, payment_method, status, created_at)
    VALUES ({$janeId},'Jane Lee','+1 555 333 4444','88 Oak Ave, Los Angeles',
    " . ($watchRow['price'] + 7500) . ", 7500, 0,
    {$exprId}, {$kbzId}, 'KBZ Pay', 'processing', DATE_SUB(NOW(), INTERVAL 2 DAY))");
$o2 = $db->lastInsertId();
$db->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)")
   ->execute([$o2, $watchRow['id'], 1, $watchRow['price']]);

// Order 3 – digital / pending
$db->exec("INSERT INTO orders
    (user_id, customer_name, customer_phone, customer_address, total_amount, shipping_cost, discount_amount,
     delivery_method_id, payment_method_id, payment_method, status, created_at)
    VALUES ({$johnId},'John Doe','+1 555 111 2222','45 Elm St, Chicago',
    " . $adobeRow['price'] . ", 0, 0,
    {$delivId}, {$cashId}, 'Cash', 'pending', NOW())");
$o3 = $db->lastInsertId();
$db->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)")
   ->execute([$o3, $adobeRow['id'], 1, $adobeRow['price']]);

ok('3 sample orders + 4 order items inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  14. SAMPLE EXPENSES
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding expenses…');
$db->exec("INSERT INTO expenses (title, amount, category, date, description) VALUES
    ('Office Rent – March',  350000, 'Rent',      CURDATE(), 'Monthly office space rental'),
    ('Electricity Bill',      45000, 'Utilities',  DATE_SUB(CURDATE(),INTERVAL 5 DAY), 'Monthly electricity'),
    ('Packaging Materials',   28000, 'Operations', DATE_SUB(CURDATE(),INTERVAL 8 DAY), 'Boxes, tape, bubble wrap'),
    ('Facebook Ads – Q1',    120000, 'Marketing',  DATE_SUB(CURDATE(),INTERVAL 15 DAY),'Q1 social media campaign')");
ok('4 expenses inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  15. SAMPLE CONTACT MESSAGES
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding contact messages…');
$db->exec("INSERT INTO contact_messages (name, email, phone, message) VALUES
    ('Alice Wong',  'alice@example.com', '+1 555 999 0001', 'Hi! I have a question about the headphone warranty. Can I extend it?'),
    ('Bob Smith',   'bob@example.com',   '+1 555 999 0002', 'Do you ship internationally? I would like to order the PS5.'),
    ('Carol Davis', 'carol@example.com', '+1 555 999 0003', 'Love the summer sale! When does it end?')");
ok('3 contact messages inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  Done!
// ─────────────────────────────────────────────────────────────────────────────
$totalOk  = count(array_filter($log, fn($l) => $l[0] === 'ok'));
$totalErr = count(array_filter($log, fn($l) => $l[0] === 'err'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demo Seeder — Phlox Store</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-10 px-4 font-sans">
<div class="max-w-3xl mx-auto">

  <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

    <!-- Header -->
    <div class="bg-gradient-to-r from-rose-500 to-rose-600 px-8 py-6">
      <h1 class="text-2xl font-black text-white">🌱 Demo Seeder Complete</h1>
      <p class="text-rose-100 text-sm mt-1">Database cleared and populated with fresh demo data.</p>
    </div>

    <!-- Credentials card -->
    <div class="px-8 py-6 border-b border-gray-100 bg-amber-50">
      <h2 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-widest">🔑 Demo Login Credentials</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ([
          ['Admin',      'admin@phlox.store',  'admin123',  'admin',    'All access'],
          ['Customer 1', 'john@example.com',   'demo1234',  'customer', 'Shopping + orders'],
          ['Customer 2', 'jane@example.com',   'demo1234',  'customer', 'Shopping + orders'],
          ['Sales Rep',  'sales@phlox.store',  'sales123',  'sales',    'POS terminal'],
        ] as [$role, $email, $pass, $tag, $note]): ?>
        <div class="bg-white rounded-xl border border-amber-200 p-4">
          <p class="font-bold text-gray-800 text-sm"><?= $role ?> <span class="ml-1 inline-block text-[10px] bg-rose-100 text-rose-600 font-bold px-1.5 py-0.5 rounded-full uppercase"><?= $tag ?></span></p>
          <p class="text-xs text-gray-500 mt-1">📧 <?= $email ?></p>
          <p class="text-xs text-gray-500">🔑 <?= $pass ?></p>
          <p class="text-xs text-gray-400 mt-1 italic"><?= $note ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Coupon codes -->
    <div class="px-8 py-5 border-b border-gray-100 bg-blue-50">
      <h2 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-widest">🏷 Demo Coupon Codes</h2>
      <div class="flex flex-wrap gap-2">
        <?php foreach ([
          ['DEMO10',   '10% off any order'],
          ['SUMMER20', '20% off orders over 50,000 Ks'],
          ['SAVE5000', '5,000 Ks off orders over 100,000 Ks'],
          ['WELCOME',  '15% off – new customer'],
        ] as [$code, $desc]): ?>
        <div class="bg-white border border-blue-200 rounded-lg px-3 py-2">
          <span class="font-mono font-bold text-blue-700 text-sm"><?= $code ?></span>
          <span class="text-gray-400 text-xs ml-2"><?= $desc ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- What was seeded -->
    <div class="px-8 py-5 border-b border-gray-100">
      <h2 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-widest">📦 What Was Seeded</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs text-gray-600">
        <?php foreach ([
          '7 categories','16 products (14 physical + 2 digital)','4 users',
          '4 delivery methods','4 payment methods','4 coupon codes',
          '2 promotions','3 sample orders','3 suppliers',
          '4 expenses','3 contact messages','8 page sections',
        ] as $item): ?>
        <div class="flex items-center gap-1.5 bg-gray-50 rounded-lg px-3 py-2">
          <span class="text-green-500">✓</span> <?= $item ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="px-8 py-6 flex flex-wrap gap-3">
      <a href="/" class="inline-flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-colors">
        🏠 View Homepage
      </a>
      <a href="/shop" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-colors">
        🛒 View Shop
      </a>
      <a href="/admin" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-colors">
        ⚙️ Admin Panel
      </a>
      <a href="/login" class="inline-flex items-center gap-2 border border-gray-300 hover:border-gray-400 text-gray-700 font-bold px-6 py-2.5 rounded-xl text-sm transition-colors">
        🔐 Login
      </a>
    </div>

    <!-- Log output -->
    <details class="border-t border-gray-100">
      <summary class="px-8 py-4 cursor-pointer text-sm font-semibold text-gray-500 hover:text-gray-700 select-none">
        📋 Show execution log (<?= $totalOk ?> ok, <?= $totalErr ?> errors)
      </summary>
      <div class="px-8 pb-6">
        <div class="bg-gray-900 rounded-xl p-4 text-xs font-mono max-h-96 overflow-y-auto">
          <?php foreach ($log as [$type, $msg]): ?>
          <div class="<?= $type==='step' ? 'text-yellow-300 font-bold mt-2' : ($type==='err' ? 'text-red-400' : 'text-green-400') ?>">
            <?= $type==='step' ? '▶ ' : ($type==='err' ? '✗ ' : '✓ ') ?><?= htmlspecialchars($msg) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </details>

    <!-- Warning -->
    <div class="mx-8 mb-6 bg-red-50 border border-red-200 rounded-xl p-4 text-xs text-red-700">
      <strong>⚠ Security Note:</strong> Delete or password-protect <code>demo_seed.php</code> on production servers.
      Anyone who knows the URL and token can wipe your database.
    </div>

  </div>
</div>
</body>
</html>
