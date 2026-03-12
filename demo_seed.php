<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 *  DEMO SEEDER  —  Clears ALL data and populates the database with demo content
 *                  including GD-generated product photos and hero banners.
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

// Upload directory – same as AdminController::uploadFile uses
define('UPLOADS_DIR', __DIR__ . '/uploads/');
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

// TTF font path (DejaVu is standard on Debian/Ubuntu servers; Lato as fallback)
$ttfCandidates = [
    '/usr/share/fonts/truetype/lato/Lato-Bold.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/usr/share/fonts/truetype/ubuntu/Ubuntu-B.ttf',
];
define('DEMO_FONT', (function() use ($ttfCandidates) {
    foreach ($ttfCandidates as $f) { if (file_exists($f)) return $f; }
    return '';
})());

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
            try { $db->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $ignored) {}
            die('Fatal seeder error: ' . htmlspecialchars($msg));
        }
    }
}

// ── Image helpers ─────────────────────────────────────────────────────────────

/**
 * Draw centred, auto-wrapped text on a GD image using imagettftext when a
 * TTF font is available; falls back to imagestring (built-in bitmap font).
 *
 * @param resource $im     GD image resource
 * @param int      $cx     Centre-X of the text block
 * @param int      $cy     Centre-Y of the text block
 * @param int      $color  GD colour
 * @param string   $text   Text to render
 * @param int      $ptSize TTF point size (ignored for bitmap fallback)
 * @param int      $maxW   Max pixel width before wrapping
 */
function gdText($im, int $cx, int $cy, int $color, string $text,
                int $ptSize = 20, int $maxW = 500): void
{
    $font = DEMO_FONT;
    if ($font && function_exists('imagettftext')) {
        // Wrap text so each line fits inside $maxW
        $words  = explode(' ', $text);
        $lines  = [];
        $cur    = '';
        foreach ($words as $w) {
            $test = $cur === '' ? $w : $cur . ' ' . $w;
            $bb   = imagettfbbox($ptSize, 0, $font, $test);
            if (($bb[2] - $bb[0]) > $maxW && $cur !== '') {
                $lines[] = $cur;
                $cur     = $w;
            } else {
                $cur = $test;
            }
        }
        if ($cur !== '') $lines[] = $cur;

        // Measure line height
        $bb      = imagettfbbox($ptSize, 0, $font, 'Ag');
        $lineH   = abs($bb[7] - $bb[1]) + (int)($ptSize * 0.3);
        $totalH  = count($lines) * $lineH;
        $startY  = $cy - (int)($totalH / 2) + $lineH;

        foreach ($lines as $i => $ln) {
            $bb  = imagettfbbox($ptSize, 0, $font, $ln);
            $lW  = abs($bb[2] - $bb[0]);
            $x   = $cx - (int)($lW / 2);
            $y   = $startY + $i * $lineH;
            imagettftext($im, $ptSize, 0, $x, $y, $color, $font, $ln);
        }
    } else {
        // Bitmap font fallback (GD font 5)
        $f  = 5;
        $fW = imagefontwidth($f);
        $fH = imagefontheight($f);
        // Wrap at ~maxW / fontWidth chars
        $maxChars = (int)($maxW / $fW);
        $words    = explode(' ', $text);
        $lines    = [];
        $cur      = '';
        foreach ($words as $w) {
            $test = $cur === '' ? $w : $cur . ' ' . $w;
            if (strlen($test) > $maxChars && $cur !== '') {
                $lines[] = $cur;
                $cur     = $w;
            } else {
                $cur = $test;
            }
        }
        if ($cur !== '') $lines[] = $cur;

        $totalH = count($lines) * ($fH + 4);
        $startY = $cy - (int)($totalH / 2);
        foreach ($lines as $i => $ln) {
            $lW = strlen($ln) * $fW;
            $x  = $cx - (int)($lW / 2);
            $y  = $startY + $i * ($fH + 4);
            imagestring($im, $f, $x, $y, $ln, $color);
        }
    }
}

/**
 * Generate a 600×600 product-placeholder WebP (or PNG) via GD.
 *
 * @param string $name    Product display name
 * @param array  $bgTop   RGB of gradient top
 * @param array  $bgBot   RGB of gradient bottom
 * @param array  $accent  RGB of accent colour (strip, ring, dot)
 * @param string $icon    One of: earphone|watch|laptop|gamepad|vr|speaker|ebook
 * @return string|null    Relative path like 'uploads/demo_product_xxxx.webp'
 */
function makeProductImage(string $name, array $bgTop, array $bgBot,
                          array $accent, string $icon = 'dot'): ?string
{
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) return null;

    $w = 600; $h = 600;
    $im = imagecreatetruecolor($w, $h);

    // ── Vertical gradient background ─────────────────────────────────────────
    for ($y = 0; $y < $h; $y++) {
        $t = $y / ($h - 1);
        $c = imagecolorallocate($im,
            max(0, min(255, (int)($bgTop[0] + ($bgBot[0] - $bgTop[0]) * $t))),
            max(0, min(255, (int)($bgTop[1] + ($bgBot[1] - $bgTop[1]) * $t))),
            max(0, min(255, (int)($bgTop[2] + ($bgBot[2] - $bgTop[2]) * $t)))
        );
        imageline($im, 0, $y, $w - 1, $y, $c);
    }

    // ── Accent header strip ──────────────────────────────────────────────────
    $accentC = imagecolorallocate($im, $accent[0], $accent[1], $accent[2]);
    imagefilledrectangle($im, 0, 0, $w, 44, $accentC);
    $white   = imagecolorallocate($im, 255, 255, 255);
    $black   = imagecolorallocate($im, 0,   0,   0);
    $darkGray= imagecolorallocate($im, 40,  40,  40);
    // Small label in strip
    $label = strtoupper(strlen($name) > 26 ? substr($name, 0, 26) . '..' : $name);
    gdText($im, $w / 2, 22, $white, $label, 13, $w - 20);

    // ── Central icon area ────────────────────────────────────────────────────
    $cx = $w / 2; $cy = 300;

    // Background circle (card)
    $cardC = imagecolorallocate($im,
        min(255, $bgTop[0] + 35), min(255, $bgTop[1] + 35), min(255, $bgTop[2] + 35));
    imagefilledellipse($im, $cx, $cy, 360, 360, $cardC);

    // Accent ring
    imagesetthickness($im, 10);
    $ringC = imagecolorallocate($im, $accent[0], $accent[1], $accent[2]);
    imagearc($im, $cx, $cy, 330, 330, 0, 360, $ringC);
    imagesetthickness($im, 1);

    // ── Category-specific icon ───────────────────────────────────────────────
    switch ($icon) {
        case 'earphone':
            // Two ear cups + headband arc
            imagefilledellipse($im, $cx - 90, $cy, 90, 90, $accentC);
            imagefilledellipse($im, $cx + 90, $cy, 90, 90, $accentC);
            imagesetthickness($im, 12);
            imagearc($im, $cx, $cy - 40, 220, 160, 190, 350, $white);
            imagesetthickness($im, 1);
            imagefilledellipse($im, $cx - 90, $cy, 50, 50, $white);
            imagefilledellipse($im, $cx + 90, $cy, 50, 50, $white);
            imagefilledellipse($im, $cx - 90, $cy, 22, 22, $accentC);
            imagefilledellipse($im, $cx + 90, $cy, 22, 22, $accentC);
            break;
        case 'watch':
            // Watch body + strap
            imagefilledrectangle($im, $cx - 55, $cy + 100, $cx + 55, $cy + 150, $white);
            imagefilledrectangle($im, $cx - 55, $cy - 150, $cx + 55, $cy - 100, $white);
            imagefilledrectangle($im, $cx - 70, $cy - 100, $cx + 70, $cy + 100, $accentC);
            imagefilledrectangle($im, $cx - 58, $cy - 88, $cx + 58, $cy + 88, $white);
            // Clock face dot
            imagefilledellipse($im, $cx, $cy, 20, 20, $accentC);
            // Clock hands
            imageline($im, $cx, $cy, $cx, $cy - 50, $accentC);
            imageline($im, $cx, $cy, $cx + 35, $cy, $accentC);
            break;
        case 'laptop':
            // Screen
            imagefilledrectangle($im, $cx - 130, $cy - 90, $cx + 130, $cy + 20, $accentC);
            imagefilledrectangle($im, $cx - 118, $cy - 78, $cx + 118, $cy + 10, $white);
            // Keyboard base
            imagefilledrectangle($im, $cx - 150, $cy + 20, $cx + 150, $cy + 50, $accentC);
            // Keyboard keys (small squares)
            for ($ki = 0; $ki < 8; $ki++) {
                $kx = ($cx - 100) + $ki * 28;
                imagefilledrectangle($im, $kx, $cy + 28, $kx + 20, $cy + 44, $white);
            }
            break;
        case 'gamepad':
            // Body
            imagefilledellipse($im, $cx, $cy, 220, 160, $accentC);
            // D-pad
            imagefilledrectangle($im, $cx - 70, $cy - 20, $cx - 20, $cy + 20, $white);
            imagefilledrectangle($im, $cx - 55, $cy - 40, $cx - 35, $cy + 40, $white);
            // ABXY buttons
            imagefilledellipse($im, $cx + 70,  $cy - 20, 24, 24, $white);
            imagefilledellipse($im, $cx + 90,  $cy,      24, 24, $white);
            imagefilledellipse($im, $cx + 70,  $cy + 20, 24, 24, $white);
            imagefilledellipse($im, $cx + 50,  $cy,      24, 24, $white);
            // Grips
            imagefilledellipse($im, $cx - 80, $cy + 60, 80, 80, $accentC);
            imagefilledellipse($im, $cx + 80, $cy + 60, 80, 80, $accentC);
            break;
        case 'vr':
            // Headset body
            imagefilledellipse($im, $cx, $cy, 260, 140, $accentC);
            imagefilledrectangle($im, $cx - 130, $cy - 70, $cx + 130, $cy + 70, $accentC);
            // Lenses
            imagefilledellipse($im, $cx - 55, $cy, 90, 90, $white);
            imagefilledellipse($im, $cx + 55, $cy, 90, 90, $white);
            imagefilledellipse($im, $cx - 55, $cy, 60, 60, $cardC);
            imagefilledellipse($im, $cx + 55, $cy, 60, 60, $cardC);
            // Strap
            imagesetthickness($im, 8);
            imageline($im, $cx - 130, $cy, $cx - 200, $cy - 30, $white);
            imageline($im, $cx + 130, $cy, $cx + 200, $cy - 30, $white);
            imagesetthickness($im, 1);
            break;
        case 'speaker':
            // Speaker cylinder
            imagefilledellipse($im, $cx, $cy - 20, 180, 200, $accentC);
            imagefilledellipse($im, $cx, $cy - 20, 120, 140, $cardC);
            // Rings
            imagesetthickness($im, 5);
            imagearc($im, $cx, $cy - 20, 160, 180, 0, 360, $white);
            imagearc($im, $cx, $cy - 20, 90, 100, 0, 360, $white);
            imagesetthickness($im, 1);
            // Base
            imagefilledrectangle($im, $cx - 50, $cy + 80, $cx + 50, $cy + 95, $accentC);
            break;
        case 'ebook':
            // Book pages
            imagefilledrectangle($im, $cx - 70, $cy - 100, $cx + 80, $cy + 100, $accentC);
            imagefilledrectangle($im, $cx - 80, $cy - 90,  $cx + 70, $cy + 90,  $white);
            // Lines of text
            for ($li = 0; $li < 7; $li++) {
                $lw = ($li % 3 === 2) ? 80 : 120;
                imagefilledrectangle($im,
                    $cx - 50, $cy - 60 + $li * 22,
                    $cx - 50 + $lw, $cy - 50 + $li * 22, $cardC);
            }
            // Ribbon bookmark
            imagefilledrectangle($im, $cx + 40, $cy - 90, $cx + 56, $cy - 40, $accentC);
            break;
        default:
            // Generic dot
            imagefilledellipse($im, $cx, $cy, 140, 140, $white);
            imagefilledellipse($im, $cx, $cy, 70,  70,  $accentC);
    }

    // ── Product name at bottom ───────────────────────────────────────────────
    // Semi-dark overlay band at bottom
    $band = imagecolorallocate($im,
        max(0, $bgBot[0] - 20), max(0, $bgBot[1] - 20), max(0, $bgBot[2] - 20));
    imagefilledrectangle($im, 0, $h - 110, $w, $h, $band);
    gdText($im, $cx, $h - 60, $white, $name, 22, $w - 40);

    // ── Save ─────────────────────────────────────────────────────────────────
    $fname  = 'demo_product_' . substr(md5($name), 0, 12) . '.webp';
    $saved  = imagewebp($im, UPLOADS_DIR . $fname, 85);
    if (!$saved) {
        $fname = str_replace('.webp', '.png', $fname);
        imagepng($im, UPLOADS_DIR . $fname, 6);
    }
    imagedestroy($im);
    return 'uploads/' . $fname;
}

/**
 * Generate a 1400×450 banner WebP via GD.
 *
 * @param string $headline Large promo text
 * @param string $sub      Smaller sub-text
 * @param string $cta      Call-to-action button label
 * @param array  $left     RGB gradient left
 * @param array  $right    RGB gradient right
 * @param string $fname    Output filename (without path)
 * @return string|null     Relative path like 'uploads/demo_banner_n.webp'
 */
function makeBannerImage(string $headline, string $sub, string $cta,
                         array $left, array $right, string $fname): ?string
{
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) return null;

    $w = 1400; $h = 450;
    $im = imagecreatetruecolor($w, $h);

    // ── Horizontal gradient ──────────────────────────────────────────────────
    for ($x = 0; $x < $w; $x++) {
        $t = $x / ($w - 1);
        $c = imagecolorallocate($im,
            max(0, min(255, (int)($left[0] + ($right[0] - $left[0]) * $t))),
            max(0, min(255, (int)($left[1] + ($right[1] - $left[1]) * $t))),
            max(0, min(255, (int)($left[2] + ($right[2] - $left[2]) * $t)))
        );
        imageline($im, $x, 0, $x, $h - 1, $c);
    }

    // ── Decorative circles (right side) ─────────────────────────────────────
    $d1 = imagecolorallocate($im,
        min(255, $right[0] + 30), min(255, $right[1] + 30), min(255, $right[2] + 30));
    $d2 = imagecolorallocate($im,
        min(255, $right[0] + 60), min(255, $right[1] + 60), min(255, $right[2] + 60));
    imagefilledellipse($im, $w - 200, $h / 2, 500, 500, $d1);
    imagefilledellipse($im, $w - 80,  $h / 2, 280, 280, $d2);
    imagefilledellipse($im, $w - 350, $h - 60, 220, 220, $d1);

    // ── Text ─────────────────────────────────────────────────────────────────
    $white  = imagecolorallocate($im, 255, 255, 255);
    $yellow = imagecolorallocate($im, 255, 220, 50);
    // Headline (centred at y=160; 2-line wrap ends ≈ y=240)
    gdText($im, 350, 160, $white, $headline, 52, 680);
    // Accent separator line — drawn BELOW the headline, ABOVE the sub-text
    imagefilledrectangle($im, 80, 250, 340, 255, $yellow);
    // Sub-text
    gdText($im, 350, 300, $yellow, $sub, 26, 620);
    // CTA button outline
    imagefilledrectangle($im, 80, 360, 340, 420, $white);
    $btnC = imagecolorallocate($im, $left[0], $left[1], $left[2]);
    gdText($im, 210, 390, $btnC, $cta, 18, 250);

    // ── Save ─────────────────────────────────────────────────────────────────
    $saved = imagewebp($im, UPLOADS_DIR . $fname, 85);
    if (!$saved) {
        $fname = str_replace('.webp', '.png', $fname);
        imagepng($im, UPLOADS_DIR . $fname, 6);
    }
    imagedestroy($im);
    return 'uploads/' . $fname;
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
    usage_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
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
    VALUES ('Areative Shop', '+1 800 123 4567', '123 Commerce Street, New York, NY 10001', 50000)");
ok('Settings inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  4. USERS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding users…');
$users = [
    ['admin',    'admin@phlox.store',  password_hash('admin123', PASSWORD_DEFAULT), 'admin',    1, '+1 800 000 0001', 'Admin HQ, New York'],
    ['john_doe', 'john@example.com',   password_hash('demo1234', PASSWORD_DEFAULT), 'customer', 1, '+1 555 111 2222', '45 Elm St, Chicago'],
    ['jane_lee', 'jane@example.com',   password_hash('demo1234', PASSWORD_DEFAULT), 'customer', 1, '+1 555 333 4444', '88 Oak Ave, Los Angeles'],
    ['sales_rep','sales@phlox.store',  password_hash('sales123', PASSWORD_DEFAULT), 'sales',    1, '+1 555 555 6666', 'Sales Dept, New York'],
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
$catMap = [];
foreach ($db->query("SELECT id, name FROM categories")->fetchAll() as $row) {
    $catMap[$row['name']] = (int)$row['id'];
}
ok(count($categories) . ' categories inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  6. PRODUCT IMAGES  (real photos via Unsplash CDN — no download needed)
// ─────────────────────────────────────────────────────────────────────────────
step('Assigning real product images from Unsplash CDN…');

// Curated Unsplash photo URLs — 600×600 crop, auto-format WebP, quality 80
// These are stable public CDN URLs that never expire and serve optimised images.
$productImages = [
    // Earphones
    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&h=600&fit=crop&auto=format&q=80', // Beats headphones
    'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=600&h=600&fit=crop&auto=format&q=80', // Sony over-ear ANC
    'https://images.unsplash.com/photo-1603351154351-5e2d0600bb77?w=600&h=600&fit=crop&auto=format&q=80', // AirPods Pro
    'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=600&h=600&fit=crop&auto=format&q=80', // JBL colourful headphones
    // Wearables
    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&h=600&fit=crop&auto=format&q=80', // Premium watch
    'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&h=600&fit=crop&auto=format&q=80', // Amazfit-style smartwatch
    // Laptops
    'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=600&h=600&fit=crop&auto=format&q=80', // Dell XPS open laptop
    'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&h=600&fit=crop&auto=format&q=80', // MacBook silver
    'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=600&h=600&fit=crop&auto=format&q=80', // ASUS VivoBook on desk
    // Gaming
    'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=600&h=600&fit=crop&auto=format&q=80', // PS5 DualSense controller
    'https://images.unsplash.com/photo-1621259182978-fbf93132d53d?w=600&h=600&fit=crop&auto=format&q=80', // Xbox Series controller
    // VR & AR
    'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?w=600&h=600&fit=crop&auto=format&q=80', // VR headset
    // Smart Speakers
    'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=600&h=600&fit=crop&auto=format&q=80', // Amazon Echo smart speaker
    'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=600&h=600&fit=crop&auto=format&q=80', // Sonos One speaker
    // Digital Products
    'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=600&h=600&fit=crop&auto=format&q=80', // Creative/design tools
    'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&h=600&fit=crop&auto=format&q=80', // eBook / books
];
ok(count($productImages) . ' Unsplash product image URLs assigned');

// ─────────────────────────────────────────────────────────────────────────────
//  7. PRODUCTS  (16 demo products)
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding products…');

// [name, description, type, category, price, stock, warranty, colors, download_link]
$products = [
    // Earphones
    ['Beats Solo Wireless Headphone',
     'Premium wireless headphone with deep bass and up to 40-hour battery life. Foldable design with soft ear cushions.',
     'physical', 'Earphones', 45000, 28, '12 Months', 'Black,Red,White', null],
    ['Sony WH-1000XM5 ANC',
     'Industry-leading noise cancellation with Dual Noise Sensor technology. Crystal clear hands-free calling.',
     'physical', 'Earphones', 89000, 15, '12 Months', 'Black,Silver', null],
    ['Apple AirPods Pro (2nd Gen)',
     'Active Noise Cancellation, Adaptive Transparency, and Personalized Spatial Audio with dynamic head tracking.',
     'physical', 'Earphones', 75000, 20, '12 Months', 'White', null],
    ['JBL Tune 510BT',
     'Wireless on-ear headphones with 40-hour battery, foldable design, and JBL Pure Bass Sound.',
     'physical', 'Earphones', 28000, 40, '6 Months', 'Black,Blue,White,Pink', null],
    // Wearables
    ['Smart Watch Pro X3',
     'Fitness tracker with heart rate monitor, GPS, 7-day battery, and 100+ sport modes. IP68 waterproof.',
     'physical', 'Wearables', 35000, 22, '12 Months', 'Yellow,Black,Silver', null],
    ['Amazfit GTR 4',
     'Premium smartwatch with Alexa built-in, dual-band GPS, and 150+ sport modes. 14-day battery life.',
     'physical', 'Wearables', 52000, 18, '12 Months', 'Black,Brown,Gold', null],
    // Laptops
    ['Dell XPS 15 Laptop',
     '15.6" OLED display, Intel Core i7-13700H, 32GB RAM, 1TB SSD. The ultimate creator\'s machine.',
     'physical', 'Laptops', 1850000, 8, '24 Months', 'Silver,Black', null],
    ['MacBook Air M2',
     'Apple M2 chip, 13.6" Liquid Retina display, 18-hour battery, and 1080p FaceTime HD camera.',
     'physical', 'Laptops', 1650000, 10, '12 Months', 'Space Gray,Silver,Starlight,Midnight', null],
    ['ASUS VivoBook 15',
     '15.6" FHD display, AMD Ryzen 5, 8GB RAM, 512GB SSD. Thin, light and powerful everyday laptop.',
     'physical', 'Laptops', 680000, 14, '12 Months', 'Transparent Silver,Indie Black', null],
    // Gaming
    ['PlayStation 5 Console',
     'Experience lightning-fast loading, deeper immersion with haptic feedback and 4K gaming at 120fps.',
     'physical', 'Gaming', 950000, 5, '12 Months', 'White,Black', null],
    ['Xbox Series X',
     'True 4K gaming at 60fps, up to 120fps, 1TB custom NVMe SSD and ray-tracing support.',
     'physical', 'Gaming', 880000, 7, '12 Months', 'Black', null],
    // VR & AR
    ['Meta Quest 3',
     'Mixed reality headset with high-res colour passthrough, powerful Snapdragon XR2 Gen 2 processor.',
     'physical', 'VR & AR', 750000, 9, '12 Months', 'White', null],
    // Smart Speakers
    ['Amazon Echo (4th Gen)',
     'Premium sound with Dolby, built-in Alexa, smart home hub. Spherical design that complements any room.',
     'physical', 'Smart Speakers', 55000, 30, '12 Months', 'Charcoal,Glacier White,Twilight Blue', null],
    ['Sonos One SL',
     'Powerful stereo sound, multi-room music, and works with Apple AirPlay 2, Spotify Connect and more.',
     'physical', 'Smart Speakers', 125000, 16, '12 Months', 'Black,White', null],
    // Digital Products
    ['Adobe Creative Cloud 1-Year License',
     'Full access to 20+ creative desktop and mobile apps including Photoshop, Illustrator, and Premiere Pro.',
     'digital', 'Digital Products', 480000, 999, null, null, 'https://adobe.com/activate'],
    ['Tech Productivity Bundle eBook',
     'A curated collection of 5 premium eBooks covering productivity, UI/UX design, and web development.',
     'digital', 'Digital Products', 25000, 999, null, null, 'https://example.com/ebook-bundle'],
];

$pStmt = $db->prepare("INSERT INTO products
    (name, description, type, category_id, price, stock, warranty_period, colors, download_link, image, is_active)
    VALUES (?,?,?,?,?,?,?,?,?,?,1)");

foreach ($products as $idx => $p) {
    $catId = $catMap[$p[3]] ?? null;
    $pStmt->execute([
        $p[0],                          // name
        $p[1],                          // description
        $p[2],                          // type
        $catId,                         // category_id
        $p[4],                          // price
        $p[5],                          // stock
        $p[6],                          // warranty_period
        $p[7],                          // colors
        $p[8],                          // download_link
        $productImages[$idx] ?? null,   // generated image path
    ]);
}
ok(count($products) . ' products inserted with images');

// ─────────────────────────────────────────────────────────────────────────────
//  8. PROMOTIONS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding promotions…');
$beatId = $db->query("SELECT id FROM products WHERE name LIKE 'Beats%' LIMIT 1")->fetchColumn();
$ps5Id  = $db->query("SELECT id FROM products WHERE name LIKE 'PlayStation%' LIMIT 1")->fetchColumn();
$echoId = $db->query("SELECT id FROM products WHERE name LIKE 'Amazon Echo%' LIMIT 1")->fetchColumn();

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
//  9. DELIVERY METHODS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding delivery methods…');
$db->exec("INSERT INTO delivery_methods (name, cost, is_active) VALUES
    ('Standard Delivery', 3500,  1),
    ('Express Delivery',  7500,  1),
    ('Same-Day Delivery', 12000, 1),
    ('Pickup In-Store',   0,     1)");
ok('4 delivery methods inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  10. PAYMENT METHODS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding payment methods…');
$db->exec("INSERT INTO payment_methods (name, type, account_number, account_name, is_active) VALUES
    ('KBZ Pay',    'checkout', '09250000001', 'Areative Shop Ltd', 1),
    ('Wave Money', 'checkout', '09778000002', 'Areative Shop Ltd', 1),
    ('AYA Pay',    'checkout', '09510000003', 'Areative Shop Ltd', 1),
    ('Cash',       'pos',       NULL,          NULL,              1)");
ok('4 payment methods inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  11. COUPONS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding coupons…');
$db->exec("INSERT INTO coupons (code, type, discount_type, value, min_spend, usage_limit, start_date, end_date) VALUES
    ('DEMO10',   'standard', 'percentage', 10,   0,       100, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY)),
    ('SUMMER20', 'standard', 'percentage', 20,   50000,   50,  CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
    ('SAVE5000', 'standard', 'fixed',      5000, 100000,  30,  CURDATE(), DATE_ADD(CURDATE(), INTERVAL 45 DAY)),
    ('WELCOME',  'standard', 'percentage', 15,   0,       200, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY))");
ok('4 coupons inserted (DEMO10 / SUMMER20 / SAVE5000 / WELCOME)');

// ─────────────────────────────────────────────────────────────────────────────
//  12. PAGE SECTIONS  (page builder)
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding page sections…');
$sections = [
    ['home', 'hero',
     'Beats Solo Wireless',
     'Discover premium audio, gaming, and tech products at the best prices.',
     1, 10, json_encode(['cta_text' => 'Shop By Category', 'cta_link' => '/shop'])],
    ['home', 'promo_strip',    'Why Shop With Us', '',  1, 20, null],
    ['home', 'featured_products', 'Best Seller Products', 'Top picks our customers love.',
     1, 30, json_encode(['limit' => 8])],
    ['home', 'categories',    'Shop by Category', 'Find exactly what you\'re looking for.', 1, 40, null],
    ['shop', 'page_header',   'Our Products',     'Browse our full range of products.',     1, 10, null],
    ['contact', 'hero',        'Contact Us',       "We'd love to hear from you. Reach out anytime.", 1, 10, null],
    ['contact', 'contact_info','Get In Touch',     '',                                              1, 20, null],
    ['contact', 'contact_form','Send Us a Message','',                                              1, 40, null],
];
$sStmt = $db->prepare("INSERT IGNORE INTO page_sections
    (page, section_key, title, content, is_visible, sort_order, settings)
    VALUES (?,?,?,?,?,?,?)");
foreach ($sections as $s) { $sStmt->execute($s); }
ok(count($sections) . ' page sections inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  13. SUPPLIERS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding suppliers…');
$db->exec("INSERT INTO suppliers (name, phone, email, address) VALUES
    ('TechWholesale Inc.',  '+1 312 000 1111', 'orders@techwholesale.com', '500 Tech Blvd, Chicago, IL'),
    ('GlobalGadgets Ltd.',  '+1 415 000 2222', 'supply@globalgadgets.com', '200 Market St, San Francisco, CA'),
    ('Digital Assets Hub',  '+1 212 000 3333', 'info@digitalhub.com',     '10 Wall St, New York, NY')");
ok('3 suppliers inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  14. BANNERS  (real hero photos via Unsplash CDN)
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding banner images from Unsplash CDN…');

// Remove stale GD-generated demo banners from older runs
$staleBanners = glob(UPLOADS_DIR . 'demo_banner_*.{webp,png}', GLOB_BRACE) ?: [];
foreach ($staleBanners as $sf) { @unlink($sf); }

// Curated wide hero photos — 1400×500, auto-format WebP, quality 85
$bannerSpecs = [
    [
        'image_path' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1400&h=500&fit=crop&auto=format&q=85',
        'link_url'   => '/shop',
    ],
    [
        'image_path' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=1400&h=500&fit=crop&auto=format&q=85',
        'link_url'   => '/shop',
    ],
    [
        'image_path' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1400&h=500&fit=crop&auto=format&q=85',
        'link_url'   => '/shop',
    ],
];
$banStmt = $db->prepare("INSERT INTO banners (image_path, link_url) VALUES (?, ?)");
foreach ($bannerSpecs as $b) {
    $banStmt->execute([$b['image_path'], $b['link_url']]);
    ok('Banner inserted: ' . $b['image_path']);
}

// ─────────────────────────────────────────────────────────────────────────────
//  15. SAMPLE ORDERS
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding sample orders…');

$johnId   = $db->query("SELECT id FROM users WHERE username='john_doe' LIMIT 1")->fetchColumn();
$janeId   = $db->query("SELECT id FROM users WHERE username='jane_lee'  LIMIT 1")->fetchColumn();
$delivId  = $db->query("SELECT id FROM delivery_methods WHERE name='Standard Delivery' LIMIT 1")->fetchColumn();
$exprId   = $db->query("SELECT id FROM delivery_methods WHERE name='Express Delivery'  LIMIT 1")->fetchColumn();
$kbzId    = $db->query("SELECT id FROM payment_methods  WHERE name='KBZ Pay' LIMIT 1")->fetchColumn();
$cashId   = $db->query("SELECT id FROM payment_methods  WHERE name='Cash'    LIMIT 1")->fetchColumn();
$beatsRow = $db->query("SELECT id, price FROM products WHERE name LIKE 'Beats%' LIMIT 1")->fetch();
$sonyRow  = $db->query("SELECT id, price FROM products WHERE name LIKE 'Sony%'  LIMIT 1")->fetch();
$watchRow = $db->query("SELECT id, price FROM products WHERE name LIKE 'Smart Watch%' LIMIT 1")->fetch();
$adobeRow = $db->query("SELECT id, price FROM products WHERE name LIKE 'Adobe%' LIMIT 1")->fetch();

// Order 1 – delivered
$db->exec("INSERT INTO orders
    (user_id,customer_name,customer_phone,customer_address,total_amount,shipping_cost,
     discount_amount,delivery_method_id,payment_method_id,payment_method,status,created_at)
    VALUES ({$johnId},'John Doe','+1 555 111 2222','45 Elm St, Chicago',
    " . ($beatsRow['price'] * 2 + $sonyRow['price'] + 3500) . ",3500,0,
    {$delivId},{$kbzId},'KBZ Pay','delivered',DATE_SUB(NOW(),INTERVAL 10 DAY))");
$o1 = $db->lastInsertId();
$oi = $db->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)");
$oi->execute([$o1, $beatsRow['id'], 2, $beatsRow['price']]);
$oi->execute([$o1, $sonyRow['id'],  1, $sonyRow['price']]);

// Order 2 – processing
$db->exec("INSERT INTO orders
    (user_id,customer_name,customer_phone,customer_address,total_amount,shipping_cost,
     discount_amount,delivery_method_id,payment_method_id,payment_method,status,created_at)
    VALUES ({$janeId},'Jane Lee','+1 555 333 4444','88 Oak Ave, Los Angeles',
    " . ($watchRow['price'] + 7500) . ",7500,0,
    {$exprId},{$kbzId},'KBZ Pay','processing',DATE_SUB(NOW(),INTERVAL 2 DAY))");
$o2 = $db->lastInsertId();
$oi->execute([$o2, $watchRow['id'], 1, $watchRow['price']]);

// Order 3 – digital / pending
$db->exec("INSERT INTO orders
    (user_id,customer_name,customer_phone,customer_address,total_amount,shipping_cost,
     discount_amount,delivery_method_id,payment_method_id,payment_method,status,created_at)
    VALUES ({$johnId},'John Doe','+1 555 111 2222','45 Elm St, Chicago',
    " . $adobeRow['price'] . ",0,0,
    {$delivId},{$cashId},'Cash','pending',NOW())");
$o3 = $db->lastInsertId();
$oi->execute([$o3, $adobeRow['id'], 1, $adobeRow['price']]);

ok('3 sample orders + 4 order items inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  16. SAMPLE EXPENSES
// ─────────────────────────────────────────────────────────────────────────────
step('Seeding expenses…');
$db->exec("INSERT INTO expenses (title, amount, category, date, description) VALUES
    ('Office Rent – March',  350000, 'Rent',      CURDATE(), 'Monthly office space rental'),
    ('Electricity Bill',      45000, 'Utilities',  DATE_SUB(CURDATE(),INTERVAL 5 DAY),  'Monthly electricity'),
    ('Packaging Materials',   28000, 'Operations', DATE_SUB(CURDATE(),INTERVAL 8 DAY),  'Boxes, tape, bubble wrap'),
    ('Facebook Ads – Q1',    120000, 'Marketing',  DATE_SUB(CURDATE(),INTERVAL 15 DAY), 'Q1 social media campaign')");
ok('4 expenses inserted');

// ─────────────────────────────────────────────────────────────────────────────
//  17. SAMPLE CONTACT MESSAGES
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
$genImages = count($productImages);

// Reusable HTML img src helper for the success page (same logic as view helpers)
function seederImgSrc(string $path): string {
    if (empty($path)) return '';
    return (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
        ? htmlspecialchars($path, ENT_QUOTES)
        : '/' . htmlspecialchars($path, ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Demo Seeder — Areative Shop</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, -apple-system, sans-serif; background: #f1f5f9; min-height: 100vh; padding: 2.5rem 1rem; color: #1e293b; }
.card { max-width: 860px; margin: 0 auto; background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.07); }
.header { background: linear-gradient(135deg, #e11d48 0%, #9333ea 100%); padding: 2rem 2.5rem; color: #fff; }
.header h1 { font-size: 1.75rem; font-weight: 900; }
.header p  { font-size: .9rem; opacity: .85; margin-top: .25rem; }
.section   { padding: 1.5rem 2.5rem; border-bottom: 1px solid #f1f5f9; }
.section h2{ font-size: .7rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #64748b; margin-bottom: 1rem; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem; }
@media(max-width:600px){ .grid2,.grid3{ grid-template-columns:1fr; } }
.cred { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 1rem; }
.cred strong { font-size: .9rem; }
.cred .badge { display:inline-block; font-size:.6rem; font-weight:700; padding:.1rem .4rem; border-radius:99px; background:#fee2e2; color:#b91c1c; margin-left:.4rem; text-transform:uppercase; }
.cred p { font-size: .78rem; color: #64748b; margin-top: .3rem; }
.chip { display:inline-flex; align-items:center; gap:.4rem; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:.35rem .7rem; font-size:.78rem; }
.chip code { font-family:monospace; font-weight:700; color:#1d4ed8; }
.check-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; }
@media(max-width:600px){ .check-grid{ grid-template-columns:1fr 1fr; } }
.check { display:flex; align-items:center; gap:.5rem; background:#f8fafc; border-radius:8px; padding:.5rem .75rem; font-size:.78rem; }
.check .ok { color: #22c55e; font-size: .9rem; }
.btns { display:flex; flex-wrap:wrap; gap:.6rem; padding: 1.5rem 2.5rem; }
.btn { display:inline-flex; align-items:center; gap:.4rem; font-weight:700; font-size:.82rem; padding:.6rem 1.4rem; border-radius:12px; text-decoration:none; border:none; cursor:pointer; }
.btn-red   { background:#e11d48; color:#fff; }
.btn-dark  { background:#1e293b; color:#fff; }
.btn-blue  { background:#2563eb; color:#fff; }
.btn-ghost { background:#fff; color:#374151; border:1px solid #d1d5db; }
details { border-top: 1px solid #f1f5f9; }
summary { padding: 1rem 2.5rem; font-size:.82rem; font-weight:600; color:#64748b; cursor:pointer; user-select:none; }
summary:hover { color: #1e293b; }
.log { background:#0f172a; border-radius:12px; padding:1rem; font-size:.7rem; font-family:monospace; max-height:20rem; overflow-y:auto; margin:0 2.5rem 1.5rem; }
.log .s { color:#fbbf24; font-weight:700; margin-top:.5rem; display:block; }
.log .o { color:#4ade80; display:block; }
.log .e { color:#f87171; display:block; }
.warn { margin: 0 2.5rem 1.5rem; background:#fff1f2; border:1px solid #fecdd3; border-radius:12px; padding:1rem; font-size:.78rem; color:#be123c; }
.thumb-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.6rem; margin-top:.75rem; }
@media(max-width:600px){ .thumb-grid{ grid-template-columns:repeat(2,1fr); } }
.thumb { border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; aspect-ratio:1; }
.thumb img { width:100%; height:100%; object-fit:cover; display:block; }
.banner-grid { display:grid; grid-template-columns:1fr; gap:.6rem; margin-top:.75rem; }
.banner img  { width:100%; border-radius:8px; border:1px solid #e2e8f0; display:block; }
</style>
</head>
<body>
<div class="card">

  <!-- Header -->
  <div class="header">
    <h1>🌱 Demo Seeder Complete</h1>
    <p>Database cleared and populated with fresh demo data, product images & banners.</p>
  </div>

  <!-- Credentials -->
  <div class="section" style="background:#fffbeb">
    <h2>🔑 Demo Login Credentials</h2>
    <div class="grid2">
      <?php foreach ([
        ['Admin',      'admin@phlox.store', 'admin123', 'admin',    'Full admin access'],
        ['Customer 1', 'john@example.com',  'demo1234', 'customer', 'Shopping + orders'],
        ['Customer 2', 'jane@example.com',  'demo1234', 'customer', 'Shopping + orders'],
        ['Sales Rep',  'sales@phlox.store', 'sales123', 'sales',    'POS terminal'],
      ] as [$role, $email, $pass, $tag, $note]): ?>
      <div class="cred">
        <strong><?= $role ?> <span class="badge"><?= $tag ?></span></strong>
        <p>📧 <?= $email ?></p>
        <p>🔑 <?= $pass ?></p>
        <p style="font-style:italic;margin-top:.2rem;color:#92400e"><?= $note ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Coupons -->
  <div class="section" style="background:#eff6ff">
    <h2>🏷 Demo Coupon Codes</h2>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem">
      <?php foreach ([
        ['DEMO10',   '10% off any order'],
        ['SUMMER20', '20% off orders over 50,000 Ks'],
        ['SAVE5000', '5,000 Ks off orders over 100,000 Ks'],
        ['WELCOME',  '15% off — new customer'],
      ] as [$code, $desc]): ?>
      <div class="chip"><code><?= $code ?></code><span style="color:#64748b"><?= $desc ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- What was seeded -->
  <div class="section">
    <h2>📦 What Was Seeded</h2>
    <div class="check-grid">
      <?php foreach ([
        '7 categories', '16 products (14 physical + 2 digital)',
        $genImages . ' product photos (Unsplash)', '3 hero banners (Unsplash)',
        '4 users', '4 delivery methods',
        '4 payment methods', '4 coupon codes',
        '2 promotions', '3 sample orders',
        '3 suppliers', '4 expenses',
        '3 contact messages', '8 page sections',
      ] as $item): ?>
      <div class="check"><span class="ok">✓</span><?= $item ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Product image thumbnails -->
  <?php if (!empty($productImages)): ?>
  <div class="section">
    <h2>🖼 Real Product Photos (Unsplash)</h2>
    <div class="thumb-grid">
      <?php
      $productNames = array_column($products, 0);
      foreach ($productImages as $i => $tp): ?>
      <div class="thumb">
        <img src="<?= seederImgSrc($tp) ?>"
             alt="<?= htmlspecialchars($productNames[$i] ?? 'Product') ?>"
             title="<?= htmlspecialchars($productNames[$i] ?? 'Product') ?>"
             loading="lazy">
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Banner image previews -->
  <?php
  $bannerPaths = $db->query("SELECT image_path FROM banners ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
  if ($bannerPaths): ?>
  <div class="section">
    <h2>🎨 Real Hero Banners (Unsplash)</h2>
    <div class="banner-grid">
      <?php foreach ($bannerPaths as $bp): ?>
      <div><img src="<?= seederImgSrc($bp) ?>" alt="Banner" loading="lazy"></div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Action buttons -->
  <div class="btns">
    <a href="/"      class="btn btn-red">🏠 View Homepage</a>
    <a href="/shop"  class="btn btn-dark">🛒 View Shop</a>
    <a href="/admin" class="btn btn-blue">⚙️ Admin Panel</a>
    <a href="/login" class="btn btn-ghost">🔐 Login</a>
  </div>

  <!-- Execution log -->
  <details>
    <summary>📋 Show execution log (<?= $totalOk ?> ok, <?= $totalErr ?> errors)</summary>
    <div class="log">
      <?php foreach ($log as [$type, $msg]): ?>
      <span class="<?= $type === 'step' ? 's' : ($type === 'err' ? 'e' : 'o') ?>">
        <?= $type === 'step' ? '▶ ' : ($type === 'err' ? '✗ ' : '✓ ') ?><?= htmlspecialchars($msg) ?>
      </span>
      <?php endforeach; ?>
    </div>
  </details>

  <!-- Security warning -->
  <div class="warn">
    <strong>⚠ Security Note:</strong> Delete or password-protect <code>demo_seed.php</code>
    on production servers. Anyone who knows the URL and token can wipe your entire database.
  </div>

</div>
</body>
</html>
