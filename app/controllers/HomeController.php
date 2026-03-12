<?php
if (!class_exists('HomeController')) {
    class HomeController {
        private $db;
        private $emailService;

        public function __construct() {
            if (!class_exists('Database')) require_once dirname(__DIR__) . '/../config/database.php';
            $this->db = (new Database())->getConnection();

            // --- LOAD EMAIL SERVICE ---
            $emailServicePath = dirname(__DIR__) . '/helpers/EmailService.php';
            if (file_exists($emailServicePath)) {
                require_once $emailServicePath;
            }

            // --- LOAD TELEGRAM SERVICE ---
            $telegramPath = dirname(__DIR__) . '/helpers/TelegramService.php';
            if (file_exists($telegramPath)) {
                require_once $telegramPath;
            }

            // Initialize Email Service
            if (class_exists('EmailService')) {
                $this->emailService = new EmailService();
            }
        }

        // --- HELPER: ATTACH DISCOUNTS (STRICT CHECK) ---
        private function attachDiscounts($products) {
            if (empty($products)) return [];

            $today = date('Y-m-d');
            
            // Fetch Active Promotions
            $promos = $this->db->query("SELECT * FROM promotions WHERE is_active = 1 AND '$today' BETWEEN start_date AND end_date")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as &$p) {
                $p['original_price'] = $p['price'];
                $p['has_discount'] = false;
                $p['discount_percent'] = 0;

                foreach ($promos as $promo) {
                    $applies = false;

                    // CHECK 1: Specific Product
                    if (!empty($promo['applicable_products'])) {
                        $ids = explode(',', $promo['applicable_products']);
                        if (in_array($p['id'], $ids)) $applies = true;
                    } 
                    // CHECK 2: Specific Category
                    elseif (!empty($promo['applicable_categories'])) {
                        $catIds = explode(',', $promo['applicable_categories']);
                        if (!empty($p['category_id']) && in_array($p['category_id'], $catIds)) $applies = true;
                    } 
                    // CHECK 3: Site-wide
                    else {
                        $applies = true;
                    }

                    if ($applies) {
                        if ($promo['type'] === 'percentage') {
                            $discountVal = ($p['price'] * $promo['value']) / 100;
                            $p['discount_percent'] = $promo['value'];
                        } else {
                            $discountVal = $promo['value'];
                            if ($p['price'] > 0) $p['discount_percent'] = round(($discountVal / $p['price']) * 100);
                        }

                        $p['price'] = $p['price'] - $discountVal;
                        if ($p['price'] < 0) $p['price'] = 0;
                        $p['has_discount'] = true;
                        break; 
                    }
                }
            }
            return $products;
        }

        public function index() {
            try { $banners = $this->db->query("SELECT * FROM banners ORDER BY id DESC")->fetchAll(); } catch (Exception $e) { $banners = []; }
            $physical = $this->db->query("SELECT * FROM products WHERE type = 'physical' AND (is_active = 1 OR is_active IS NULL) ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            $digital = $this->db->query("SELECT * FROM products WHERE type = 'digital' AND (is_active = 1 OR is_active IS NULL) ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            $products = array_merge($physical, $digital);
            $products = $this->attachDiscounts($products); 
            $this->renderView('home', ['products' => $products, 'banners' => $banners]);
        }

        public function shop() {
            $category_id = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? null;
            $sql = "SELECT * FROM products WHERE (is_active = 1 OR is_active IS NULL)";
            $params = [];
            
            if ($category_id) { 
                if ($category_id === 'physical') $sql .= " AND type = 'physical'";
                elseif ($category_id === 'digital') $sql .= " AND type = 'digital'";
                elseif (is_numeric($category_id)) { $sql .= " AND category_id = ?"; $params[] = $category_id; }
            }
            if ($search) { $sql .= " AND name LIKE ?"; $params[] = "%$search%"; }
            $sql .= " ORDER BY id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $products = $this->attachDiscounts($products); 
            $categories = $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
            $this->renderView('shop', ['products' => $products, 'categories' => $categories]);
        }

        public function productDetails() {
            $id = $_GET['id'] ?? 0;
            if (!$id) { header("Location: /shop"); exit; }
            $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND (p.is_active = 1 OR p.is_active IS NULL)");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) { header("Location: /shop"); exit; }
            $productWithDiscount = $this->attachDiscounts([$product]);
            $product = $productWithDiscount[0];
            $galleryStmt = $this->db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
            $galleryStmt->execute([$id]);
            $product['gallery'] = $galleryStmt->fetchAll(PDO::FETCH_COLUMN);
            $recommended = [];
            if ($product['category_id']) {
                $recStmt = $this->db->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND (is_active = 1 OR is_active IS NULL) LIMIT 4");
                $recStmt->execute([$product['category_id'], $id]);
                $recommended = $recStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            if (count($recommended) < 4) {
                $limit = 4 - count($recommended);
                $idsToExclude = array_column($recommended, 'id');
                $idsToExclude[] = $id;
                $placeholders = implode(',', array_fill(0, count($idsToExclude), '?'));
                $recStmt = $this->db->prepare("SELECT * FROM products WHERE id NOT IN ($placeholders) AND (is_active = 1 OR is_active IS NULL) ORDER BY RAND() LIMIT $limit");
                $recStmt->execute($idsToExclude);
                $moreRecs = $recStmt->fetchAll(PDO::FETCH_ASSOC);
                $recommended = array_merge($recommended, $moreRecs);
            }
            if(!empty($recommended)) $recommended = $this->attachDiscounts($recommended);
            require_once dirname(__DIR__) . '/views/product_details.php';
        }

        public function apiSearch() {
            header('Content-Type: application/json');
            $search = isset($_GET['q']) ? trim($_GET['q']) : '';
            $category = $_GET['cat'] ?? ''; 
            $sort = $_GET['sort'] ?? 'newest';
            $sql = "SELECT * FROM products WHERE (is_active = 1 OR is_active IS NULL)";
            $params = [];
            if (!empty($search)) {
                $words = explode(' ', $search);
                $searchClauses = [];
                foreach ($words as $word) {
                    $word = trim($word);
                    if (!empty($word)) { $searchClauses[] = "name LIKE ?"; $params[] = "%$word%"; }
                }
                if (!empty($searchClauses)) $sql .= " AND (" . implode(' OR ', $searchClauses) . ")";
            }
            if (!empty($category)) {
                if ($category === 'physical') $sql .= " AND type = 'physical'";
                elseif ($category === 'digital') $sql .= " AND type = 'digital'";
                elseif (is_numeric($category)) { $sql .= " AND category_id = ?"; $params[] = $category; }
            }
            switch ($sort) {
                case 'price_asc':  $sql .= " ORDER BY price ASC"; break;
                case 'price_desc': $sql .= " ORDER BY price DESC"; break;
                case 'name_asc':   $sql .= " ORDER BY name ASC"; break;
                default:           $sql .= " ORDER BY id DESC"; break;
            }
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $products = $this->attachDiscounts($products);
                echo json_encode(['success' => true, 'products' => $products]);
            } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
            exit;
        }

        public function cart() {
            $delivery = $this->db->query("SELECT * FROM delivery_methods WHERE is_active=1")->fetchAll();
            $payment = $this->db->query("SELECT * FROM payment_methods WHERE is_active=1 AND type='checkout'")->fetchAll();
            $settings = $this->db->query("SELECT free_shipping_min FROM settings WHERE id=1")->fetch();
            $this->renderView('cart', ['delivery_methods' => $delivery, 'payment_methods' => $payment, 'free_shipping_min' => $settings['free_shipping_min'] ?? 0]);
        }

        public function profile() {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit; }
            $userId = $_SESSION['user_id'];
            $stmt = $this->db->prepare("SELECT username, email, phone, address, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $orders = $stmt->fetchAll();
            foreach ($orders as &$order) {
                $stmtItems = $this->db->prepare("SELECT oi.*, p.name, p.type, p.download_link, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $stmtItems->execute([$order['id']]);
                $order['items'] = $stmtItems->fetchAll();
            }
            $this->renderView('profile', ['user' => $user, 'orders' => $orders]);
        }

        public function checkCoupon() {
            header('Content-Type: application/json');
            $input = json_decode(file_get_contents('php://input'), true);
            $code = strtoupper(trim($input['code'] ?? ''));
            $subtotal = floatval($input['subtotal'] ?? 0);
            
            $stmt = $this->db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
            $stmt->execute([$code]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$coupon) { echo json_encode(['success' => false, 'message' => 'Invalid or expired coupon code.']); exit; }
            if ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']) { echo json_encode(['success' => false, 'message' => 'Coupon usage limit reached.']); exit; }
            $today = date('Y-m-d');
            if (!empty($coupon['start_date']) && $today < $coupon['start_date']) { echo json_encode(['success' => false, 'message' => 'Coupon is not active yet.']); exit; }
            if (!empty($coupon['end_date']) && $today > $coupon['end_date']) { echo json_encode(['success' => false, 'message' => 'Coupon has expired.']); exit; }

            $discountAmount = 0;
            $message = "Coupon Applied";

            if ($coupon['type'] === 'tiered') {
                $tiers = json_decode($coupon['tier_data'], true);
                if (empty($tiers)) { echo json_encode(['success' => false, 'message' => 'Coupon configuration error.']); exit; }
                usort($tiers, function($a, $b) { return $b['min'] - $a['min']; });
                $appliedTier = null;
                foreach ($tiers as $tier) {
                    if ($subtotal >= $tier['min']) { $appliedTier = $tier; break; }
                }
                if ($appliedTier) {
                    if ($appliedTier['type'] === 'percent') {
                        $discountAmount = ($subtotal * $appliedTier['value']) / 100;
                        $message = "Tier Unlocked: {$appliedTier['value']}% Off";
                    } else {
                        $discountAmount = $appliedTier['value'];
                        $message = "Tier Unlocked: " . number_format($appliedTier['value']) . " Off";
                    }
                } else {
                    $nextTier = end($tiers);
                    echo json_encode(['success' => false, 'message' => 'Spend at least ' . number_format($nextTier['min']) . ' to use this coupon.']); exit;
                }
            } else {
                if ($subtotal < $coupon['min_spend']) { echo json_encode(['success' => false, 'message' => 'Minimum spend of ' . number_format($coupon['min_spend']) . ' required.']); exit; }
                if ($coupon['discount_type'] === 'percent') {
                    $discountAmount = ($subtotal * $coupon['value']) / 100;
                } else {
                    $discountAmount = $coupon['value'];
                }
            }
            echo json_encode(['success' => true, 'amount' => $discountAmount, 'type' => 'coupon', 'message' => $message, 'code' => $code]); exit;
        }

        // --- PROCESS CHECKOUT (OPTIMIZED: REPLY FIRST, WORK LATER) ---
        public function processCheckout() {
            // 1. Setup Background Processing Capabilities
            ignore_user_abort(true); // Don't stop script if user disconnects
            set_time_limit(300);     // Allow extra time for slow emails/telegram

            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json');
            
            try {
                // --- A. VALIDATION & UPLOAD (Fast) ---
                $receiptPath = null;
                if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = dirname(__DIR__) . '/../uploads/receipts/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $ext = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('rec_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadDir . $filename)) {
                        $receiptPath = 'uploads/receipts/' . $filename;
                    }
                }
                
                $cart = isset($_POST['cart']) ? json_decode($_POST['cart'], true) : [];
                if (empty($cart)) throw new Exception("Cart is empty");
                
                // --- B. DATABASE TRANSACTIONS (Fast) ---
                $userId = $_SESSION['user_id'] ?? null;
                $this->db->beginTransaction();
                
                $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_amount, shipping_cost, discount_amount, delivery_method_id, payment_method_id, payment_receipt, customer_name, customer_phone, customer_address, status, created_at) VALUES (:uid, :total, :shipping, :discount, :did, :pid, :rec, :name, :phone, :addr, 'pending', NOW())");
                $stmt->execute([
                    ':uid' => $userId, 
                    ':total' => $_POST['grand_total'], 
                    ':shipping' => $_POST['delivery_cost'] ?? 0, 
                    ':discount' => $_POST['discount_amount'] ?? 0, 
                    ':did' => $_POST['delivery_id'] ?? null, 
                    ':pid' => $_POST['payment_id'] ?? null, 
                    ':rec' => $receiptPath, 
                    ':name' => $_POST['name'], 
                    ':phone' => $_POST['phone'], 
                    ':addr' => $_POST['address']
                ]);
                $orderId = $this->db->lastInsertId();
                
                foreach ($cart as $item) {
                    if ($item['type'] === 'physical') {
                        $check = $this->db->prepare("SELECT stock FROM products WHERE id = ?"); 
                        $check->execute([$item['id']]); 
                        $stock = $check->fetchColumn();
                        if ($stock < $item['qty']) throw new Exception("Insufficient stock for " . $item['name']);
                        $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$item['qty'], $item['id']]);
                    }
                    $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)")->execute([$orderId, $item['id'], $item['qty'], $item['price']]); 
                }

                if (!empty($_POST['coupon_code'])) {
                    $this->db->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE code = ?")->execute([$_POST['coupon_code']]);
                }

                $this->db->commit();

                // --- C. SEND RESPONSE IMMEDIATELY (The Magic Part) ---
                $response = json_encode(['success' => true, 'order_id' => $orderId]);
                
                // Close session to prevent locking
                session_write_close();

                // Force browser to close connection
                header("Content-Encoding: none");
                header("Content-Length: " . strlen($response));
                header("Connection: close");
                
                echo $response;
                
                // Flush output buffers to the browser
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request(); // Optimized for Nginx/FPM
                } else {
                    ob_flush();
                    flush();
                }

                // =========================================================
                // 🚀 BACKGROUND PROCESS STARTS HERE (User has already left)
                // =========================================================

                // --- 1. TELEGRAM ---
                if (class_exists('TelegramService')) {
                    try {
                        $tg = new TelegramService();
                        $tg->sendOrderNotification($orderId, $_POST['name'], $_POST['grand_total'], $cart, $receiptPath);
                    } catch (Exception $e) { error_log("Telegram Error: " . $e->getMessage()); }
                }

                // --- 2. ADMIN EMAILS ---
                try {
                    $admins = ['admin@kkkledshop.com', 'thandartun@kkkledshop.com', 'sales@kkkledshop.com'];
                    $subject = "New Order #" . str_pad($orderId, 5, '0', STR_PAD_LEFT);
                    $body = "<h2>New Order Alert</h2><p>Order ID: #$orderId</p><p>Customer: ".htmlspecialchars($_POST['name'])."</p><p>Total: ".number_format($_POST['grand_total'])." MMK</p>";

                    foreach ($admins as $adminEmail) {
                        if ($this->emailService) {
                            try { $this->emailService->sendEmail($adminEmail, $subject, $body); } catch (Exception $e) {}
                        }
                    }
                } catch (Throwable $t) { error_log("Admin Email Error: " . $t->getMessage()); }

                // --- 3. CUSTOMER EMAIL ---
                try {
                    $customerEmail = null;
                    if ($userId) {
                        // Re-query needed sometimes if connection drops, but using same DB instance usually works
                        $uStmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
                        $uStmt->execute([$userId]);
                        $customerEmail = $uStmt->fetchColumn();
                    }

                    if ($customerEmail && $this->emailService) {
                        $orderData = [
                            'id' => $orderId,
                            'total_amount' => $_POST['grand_total'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'customer_phone' => $_POST['phone'],
                            'customer_address' => $_POST['address']
                        ];
                        
                        $emailItems = [];
                        foreach($cart as $cItem) {
                            $emailItems[] = [
                                'name' => $cItem['name'],
                                'quantity' => $cItem['qty'],
                                'price' => $cItem['price'],
                                'type' => $cItem['type'],
                                'download_link' => ''
                            ];
                        }

                        $this->emailService->sendOrderStatusUpdate($customerEmail, $_POST['name'], $orderData, $emailItems, 'pending');
                    }
                } catch (Throwable $t) { error_log("Customer Email Error: " . $t->getMessage()); }

                // Ensure script stops here
                exit;

            } catch (Exception $e) { 
                if($this->db->inTransaction()) $this->db->rollBack(); 
                echo json_encode(['success' => false, 'message' => $e->getMessage()]); 
                exit;
            } 
        }

        // --- CONTACT PAGE ---
        public function contact() {
            $siteSettings = $this->getSiteSettings();
            $pageSections = $this->getPageSections('contact');
            $this->renderView('contact', compact('siteSettings', 'pageSections'));
        }

        // --- CONTACT FORM SUBMISSION API ---
        public function submitContact() {
            header('Content-Type: application/json');
            ob_start();
            try {
                $input = json_decode(file_get_contents('php://input'), true) ?: [];
                $name    = trim($input['name'] ?? '');
                $email   = trim($input['email'] ?? '');
                $phone   = trim($input['phone'] ?? '');
                $message = trim($input['message'] ?? '');

                if (!$name || !$email || !$message) {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Name, email and message are required.']);
                    exit;
                }

                // Create table if needed (idempotent - uses CREATE TABLE IF NOT EXISTS)
                static $tableReady = false;
                if (!$tableReady) {
                    $this->db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255) NOT NULL,
                        phone VARCHAR(50) DEFAULT '',
                        message TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $tableReady = true;
                }

                $stmt = $this->db->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $message]);

                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Thank you! We will get back to you soon.']);
            } catch (Throwable $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
            }
            exit;
        }

        // --- HELPERS ---
        private function getSiteSettings(): array {
            try {
                $row = $this->db->query("SELECT * FROM settings WHERE id=1")->fetch();
                return $row ?: [];
            } catch (Throwable $e) {
                return [];
            }
        }

        private function getPageSections(string $page): array {
            try {
                $stmt = $this->db->prepare("SELECT * FROM page_sections WHERE page = ? ORDER BY sort_order ASC");
                $stmt->execute([$page]);
                $rows = $stmt->fetchAll();
                $indexed = [];
                foreach ($rows as $row) {
                    $indexed[$row['section_key']] = $row;
                }
                return $indexed;
            } catch (Throwable $e) {
                return [];
            }
        }

        private function renderView($viewName, $data = []) {
            // Always inject site settings and page sections so layout/views can use them
            if (!isset($data['siteSettings'])) {
                $data['siteSettings'] = $this->getSiteSettings();
            }
            if (!isset($data['pageSections'])) {
                $data['pageSections'] = $this->getPageSections($viewName);
            }
            extract($data);
            $childView = dirname(__DIR__) . "/views/$viewName.php";
            if(file_exists(dirname(__DIR__) . '/views/layout.php')) {
                require_once dirname(__DIR__) . '/views/layout.php';
            } else {
                require_once $childView;
            }
        }
    }
}
?>