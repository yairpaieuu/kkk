<?php
if (!class_exists('AdminController')) {

    class AdminController {
        private $db;
        private $emailService;

        public function __construct() {
            if (!class_exists('Database')) require_once dirname(__DIR__) . '/../config/database.php';
            
            // LOAD EMAIL SERVICE
            $emailServicePath = dirname(__DIR__) . '/helpers/EmailService.php';
            if (file_exists($emailServicePath)) {
                require_once $emailServicePath;
            }

            // LOAD SMTP HELPER (Backup)
            $smtpPath = dirname(__DIR__) . '/helpers/SimpleSMTP.php';
            if (file_exists($smtpPath)) {
                require_once $smtpPath;
            }

            $database = new Database();
            $this->db = $database->getConnection();
            
            // Initialize Service if class exists
            if (class_exists('EmailService')) {
                $this->emailService = new EmailService();
            }
        }

        // --- 1. DASHBOARD ---
        public function dashboard() {
            // All staff roles can view dashboard
            $this->requireAuth(); 
            $today = date('Y-m-d');
            
            // Stats
            $salesToday = $this->db->query("SELECT SUM(total_amount) FROM orders WHERE DATE(created_at) = '$today' AND status='completed'")->fetchColumn() ?: 0;
            $salesMonth = $this->db->query("SELECT SUM(total_amount) FROM orders WHERE MONTH(created_at) = MONTH('$today') AND YEAR(created_at) = YEAR('$today') AND status='completed'")->fetchColumn() ?: 0;
            $ordersPending = $this->db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn() ?: 0;
            $lowStock = $this->db->query("SELECT COUNT(*) FROM products WHERE type='physical' AND stock < 5")->fetchColumn() ?: 0;
            $recentOrders = $this->db->query("SELECT o.*, COALESCE(u.username, o.customer_name) as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 6")->fetchAll();

            $this->renderAdminView('dashboard', compact('salesToday', 'salesMonth', 'ordersPending', 'lowStock', 'recentOrders'));
        }

        // --- 2. CATEGORIES ---
        public function categories() {
            // Admin & Account
            $this->checkPermission(['admin', 'account']);
            $message = "";

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['add_category'])) {
                    try {
                        if(empty($_POST['name'])) throw new Exception("Category name required");
                        $stmt = $this->db->prepare("INSERT INTO categories (name) VALUES (?)");
                        $stmt->execute([$_POST['name']]);
                        $message = "Category added!";
                    } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
                } elseif (isset($_POST['delete_category'])) {
                    try {
                        $this->db->prepare("DELETE FROM categories WHERE id = ?")->execute([$_POST['id']]);
                        $message = "Category deleted!";
                    } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
                }
            }

            // Safety Table Check
            $this->db->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)");

            $categories = $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
            $this->renderAdminView('categories', ['categories' => $categories, 'message' => $message]);
        }

        // --- 3. PRODUCTS (UPDATED WITH DESCRIPTION & STATUS) ---
        public function products() {
            // Admin & Account
            $this->checkPermission(['admin', 'account']);
            $message = "";

            // Ensure DB Schema
            $this->db->exec("CREATE TABLE IF NOT EXISTS product_images (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, image_path VARCHAR(255) NOT NULL)");
            
            // Auto-Add Columns if missing
            try { $this->db->query("SELECT category_id FROM products LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE products ADD COLUMN category_id INT NULL AFTER type"); }
            
            try { $this->db->query("SELECT colors FROM products LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE products ADD COLUMN colors TEXT NULL AFTER stock"); }

            try { $this->db->query("SELECT description FROM products LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE products ADD COLUMN description TEXT NULL AFTER name"); }

            // Ensure is_active column exists
            try { $this->db->query("SELECT is_active FROM products LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1"); }

            // Handle POST (Create/Update)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $action = $_POST['action'] ?? 'create';
                    $productId = $_POST['product_id'] ?? null;

                    // 1. Handle Main Image Upload
                    $mainImagePath = null;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $mainImagePath = $this->uploadFile($_FILES['image']);
                    }

                    // Prepare Parameters
                    $params = [
                        $_POST['type'], 
                        !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                        $_POST['name'], 
                        $_POST['description'] ?? null,
                        $_POST['price'], 
                        $_POST['stock'] ?? 0, 
                        $_POST['barcode'] ?? null, 
                        $_POST['warranty'] ?? null,
                        $_POST['colors'] ?? null,
                        $_POST['download_link'] ?? null,
                        $_POST['is_active'] ?? 1 // Default to active if not sent
                    ];

                    if ($action === 'update' && $productId) {
                        // --- UPDATE LOGIC ---
                        $sql = "UPDATE products SET type=?, category_id=?, name=?, description=?, price=?, stock=?, barcode=?, warranty_period=?, colors=?, download_link=?, is_active=?";
                        
                        if ($mainImagePath) {
                            $sql .= ", image=?";
                            $params[] = $mainImagePath;
                        }
                        
                        $sql .= " WHERE id=?";
                        $params[] = $productId;

                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        $message = "Product updated successfully!";

                    } else {
                        // --- CREATE LOGIC ---
                        $sql = "INSERT INTO products (type, category_id, name, description, price, stock, barcode, warranty_period, colors, download_link, is_active, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $params[] = $mainImagePath;
                        
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        $productId = $this->db->lastInsertId();
                        $message = "Product created successfully!";
                    }

                    // 2. Handle Additional Gallery IMAGES
                    if (!empty($_FILES['gallery']['name'][0])) {
                        $files = $_FILES['gallery'];
                        $count = count($files['name']);
                        for ($i = 0; $i < $count && $i < 5; $i++) {
                            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                                $fileData = [
                                    'name' => $files['name'][$i],
                                    'type' => $files['type'][$i],
                                    'tmp_name' => $files['tmp_name'][$i],
                                    'error' => $files['error'][$i],
                                    'size' => $files['size'][$i]
                                ];
                                $path = $this->uploadFile($fileData);
                                if($path) {
                                    $this->db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)")
                                             ->execute([$productId, $path]);
                                }
                            }
                        }
                    }

                    // 3. Handle Product VIDEO
                    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                        $videoPath = $this->uploadFile($_FILES['video']);
                        if ($videoPath) {
                            $this->db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)")
                                     ->execute([$productId, $videoPath]);
                        }
                    }

                } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
            }

            // Handle Delete Request
            if (isset($_GET['delete'])) {
                try {
                    $pid = $_GET['delete'];
                    $p = $this->db->query("SELECT image FROM products WHERE id=$pid")->fetch();
                    if($p && $p['image'] && file_exists(dirname(__DIR__).'/../'.$p['image'])) {
                        unlink(dirname(__DIR__).'/../'.$p['image']);
                    }
                    $this->db->prepare("DELETE FROM products WHERE id = ?")->execute([$pid]);
                    header("Location: /products"); 
                    exit;
                } catch (Exception $e) { $message = "Error deleting: " . $e->getMessage(); }
            }

            // Fetch Data
            $products = $this->db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
            $categories = $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
            
            $this->renderAdminView('products', ['products' => $products, 'categories' => $categories, 'message' => $message]);
        }

        // --- 3.1 TOGGLE PRODUCT STATUS (CRITICAL) ---
        public function toggleProduct() {
            $this->checkPermission(['admin', 'account']);
            if (isset($_GET['id'])) {
                try {
                    $id = $_GET['id'];
                    // Ensure column exists
                    try { $this->db->query("SELECT is_active FROM products LIMIT 1"); } 
                    catch (Exception $e) { $this->db->exec("ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1"); }
                    
                    // Fetch current status
                    $stmt = $this->db->prepare("SELECT is_active FROM products WHERE id = ?");
                    $stmt->execute([$id]);
                    $current = $stmt->fetchColumn();

                    // Toggle: If 1 (active) make 0, else make 1
                    $newStatus = ($current == 0) ? 1 : 0;
                    
                    $update = $this->db->prepare("UPDATE products SET is_active = ? WHERE id = ?");
                    $update->execute([$newStatus, $id]);
                    
                } catch (Exception $e) {
                    // Silently fail to avoid breaking UX
                }
            }
            // Redirect back to products page
            header("Location: /products");
            exit;
        }

        // --- 3.2 BULK UPDATE PRODUCT STATUS (NEW) ---
        public function bulkUpdateProductStatus() {
            // Admin & Account permissions
            $this->checkPermission(['admin', 'account']);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $categoryId = $_POST['category_id'] ?? null;
                $status = $_POST['status'] ?? null;

                if ($categoryId && $status !== null) {
                    try {
                        // Ensure column exists first (Safety check)
                        try { $this->db->query("SELECT is_active FROM products LIMIT 1"); } 
                        catch (Exception $e) { $this->db->exec("ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1"); }

                        // Update all products in this category
                        $stmt = $this->db->prepare("UPDATE products SET is_active = ? WHERE category_id = ?");
                        $stmt->execute([$status, $categoryId]);
                        
                        $msg = $status == '1' ? "Enabled all products in category." : "Disabled all products in category.";
                        // Redirect with success message
                        header("Location: /products?message=" . urlencode($msg));
                        exit;
                    } catch (Exception $e) {
                        die("Error updating: " . $e->getMessage());
                    }
                }
            }
            // Fallback if accessed directly
            header("Location: /products");
            exit;
        }

        // --- 4. POS VIEW ---
        public function pos() {
            // Admin & Sales
            $this->checkPermission(['admin', 'sales']);
            
            // 1. Fetch Products with Category Names (ONLY ACTIVE ONES)
            $products = $this->db->query("
                SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE (p.is_active = 1 OR p.is_active IS NULL) AND (p.stock > 0 OR p.type='digital')
                ORDER BY p.name ASC
            ")->fetchAll();

            // 2. Fetch Customers
            $customers = $this->db->query("SELECT * FROM users WHERE role='customer' ORDER BY username ASC")->fetchAll();
            
            // 3. Fetch ALL Categories
            $categories = $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

            // Ensure Table & Column exists
            $this->db->exec("CREATE TABLE IF NOT EXISTS payment_methods (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL, type VARCHAR(20) DEFAULT 'checkout', account_number VARCHAR(100), account_name VARCHAR(100), is_active TINYINT(1) DEFAULT 1)");
            try { $this->db->query("SELECT type FROM payment_methods LIMIT 1"); } catch (Exception $e) { $this->db->exec("ALTER TABLE payment_methods ADD COLUMN type VARCHAR(20) DEFAULT 'checkout' AFTER name"); }

            // Force Cash to POS
            $this->db->exec("UPDATE payment_methods SET type='pos' WHERE name='Cash' AND type='checkout'");

            // Fetch Payment Methods
            $payment_methods = $this->db->query("SELECT * FROM payment_methods WHERE type = 'pos' AND is_active = 1")->fetchAll();
            
            if (empty($payment_methods)) {
                $this->db->exec("INSERT INTO payment_methods (name, type) VALUES ('Cash', 'pos')");
                $payment_methods = $this->db->query("SELECT * FROM payment_methods WHERE type = 'pos' AND is_active = 1")->fetchAll();
            }
            
            $this->renderAdminView('pos', [
                'products' => $products, 
                'customers' => $customers,
                'payment_methods' => $payment_methods,
                'categories' => $categories 
            ]);
        }

        // --- 5. ADD CUSTOMER API (FIXED FOR POS) ---
        public function apiAddCustomer() {
            header('Content-Type: application/json');
            // Admin & Sales
            $this->checkPermission(['admin', 'sales']);
            
            $input = json_decode(file_get_contents('php://input'), true);
            try {
                if(empty($input['name'])) throw new Exception("Name is required");
                
                $realEmail = $input['email'] ?? '';
                $address = $input['address'] ?? '';
                
                // If NO email provided, generate a POS dummy so DB constraint passes
                // If real email IS provided, save it so they get notifications
                if (empty($realEmail)) {
                    $uniqueId = uniqid();
                    $emailToSave = "walkin_{$uniqueId}@local.store";
                } else {
                    $emailToSave = $realEmail;
                }

                $stmt = $this->db->prepare("INSERT INTO users (username, email, phone, address, role, password) VALUES (?, ?, ?, ?, 'customer', ?)");
                $stmt->execute([
                    $input['name'], 
                    $emailToSave, 
                    $input['phone'] ?? null, 
                    $address,
                    password_hash('123456', PASSWORD_DEFAULT)
                ]);
                
                echo json_encode(['success' => true, 'customer' => ['id' => $this->db->lastInsertId(), 'name' => $input['name']]]);
            } catch (Exception $e) { 
                echo json_encode(['success' => false, 'message' => $e->getMessage()]); 
            }
            exit;
        }

        // --- 6. CHECKOUT API ---
        public function checkout() {
            header('Content-Type: application/json');
            // Admin & Sales
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'message'=>'Unauthorized']); exit; }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) { echo json_encode(['success'=>false, 'message'=>'Invalid data']); return; }

            try {
                $this->db->beginTransaction();
                
                $userId = null;
                $customerName = 'Walk-in Customer';
                $paymentMethod = $input['payment_method'] ?? 'Cash';
                $discount = $input['discount'] ?? 0;
                $note = $input['note'] ?? '';
                if (!empty($input['coupon'])) {
                    $note .= " [Coupon: " . $input['coupon'] . "]";
                }

                if (isset($input['customer_id']) && $input['customer_id'] !== 'walk_in') {
                    $userId = $input['customer_id'];
                    $user = $this->db->query("SELECT username FROM users WHERE id = $userId")->fetch();
                    if ($user) $customerName = $user['username'];
                } else {
                    $defaultPass = password_hash('123456', PASSWORD_DEFAULT);
                    $uniqueIdentifier = uniqid() . rand(1000, 9999);
                    $dummyEmail = "walkin_{$uniqueIdentifier}@local.store"; 

                    $stmt = $this->db->prepare("INSERT INTO users (username, email, role, password, created_at) VALUES (?, ?, 'customer', ?, NOW())");
                    $stmt->execute(['New Walk-in', $dummyEmail, $defaultPass]);
                    
                    $userId = $this->db->lastInsertId();
                    $uniqueName = "Customer #" . str_pad($userId, 4, '0', STR_PAD_LEFT); 
                    
                    $this->db->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$uniqueName, $userId]);
                    $customerName = $uniqueName;
                }

                // Ensure Columns Exist
                try { $this->db->query("SELECT payment_method FROM orders LIMIT 1"); } 
                catch (Exception $e) { $this->db->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'Cash' AFTER status"); }
                
                try { $this->db->query("SELECT notes FROM orders LIMIT 1"); } 
                catch (Exception $e) { $this->db->exec("ALTER TABLE orders ADD COLUMN notes TEXT NULL AFTER status"); }

                $stmt = $this->db->prepare("INSERT INTO orders (user_id, customer_name, total_amount, discount_amount, status, payment_method, notes, created_at) VALUES (:uid, :cname, :total, :disc, 'completed', :pm, :note, NOW())");
                $stmt->execute([
                    ':uid' => $userId, 
                    ':cname' => $customerName, 
                    ':total' => $input['total'], 
                    ':disc' => $discount,
                    ':pm' => $paymentMethod,
                    ':note' => $note
                ]);
                $orderId = $this->db->lastInsertId();

                foreach ($input['cart'] as $item) {
                    $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)")->execute([$orderId, $item['id'], $item['qty'], $item['price']]);
                    if ($item['type'] === 'physical') {
                        $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$item['qty'], $item['id']]);
                    }
                }
                
                $this->db->commit();
                echo json_encode(['success' => true, 'order_id' => $orderId, 'customer_name' => $customerName]);
            } catch (Exception $e) {
                $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        // --- 7. SALES LIST ---
        public function sales() {
            // Admin, Sales, Account
            $this->checkPermission(['admin', 'sales', 'account']);
            
            // Filters
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $source = $_GET['source'] ?? 'all';

            $sql = "SELECT o.*, 
                           COALESCE(u.username, o.customer_name) as username,
                           COALESCE(o.customer_phone, u.phone) as customer_phone
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    WHERE 1=1"; 
            
            $params = [];

            if ($startDate) {
                $sql .= " AND DATE(o.created_at) >= ?";
                $params[] = $startDate;
            }
            if ($endDate) {
                $sql .= " AND DATE(o.created_at) <= ?";
                $params[] = $endDate;
            }

            if ($source === 'pos') {
                $sql .= " AND (u.email LIKE 'walkin_%@local.store' OR o.customer_name LIKE 'Walk-in%' OR o.payment_method IN ('Cash', 'POS'))";
            } elseif ($source === 'ecommerce') {
                $sql .= " AND (u.email NOT LIKE 'walkin_%@local.store' OR u.email IS NULL) AND o.customer_name NOT LIKE 'Walk-in%'";
            }

            $sql .= " ORDER BY o.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();

            $this->renderAdminView('sales', [
                'orders' => $orders,
                'filters' => [
                    'start_date' => $startDate, 
                    'end_date' => $endDate,
                    'source' => $source
                ]
            ]);
        }

        // --- 8. SUPPLIERS ---
        public function suppliers() {
            // Admin & Account
            $this->checkPermission(['admin', 'account']);
            $message = "";
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    if (isset($_POST['add_supplier'])) {
                        $stmt = $this->db->prepare("INSERT INTO suppliers (name, phone, email, address) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['email'], $_POST['address']]);
                        $message = "Supplier added successfully!";
                    } 
                    elseif (isset($_POST['edit_supplier'])) {
                        $stmt = $this->db->prepare("UPDATE suppliers SET name=?, phone=?, email=?, address=? WHERE id=?");
                        $stmt->execute([$_POST['name'], $_POST['phone'], $_POST['email'], $_POST['address'], $_POST['id']]);
                        $message = "Supplier updated successfully!";
                    }
                    elseif (isset($_POST['delete_supplier'])) {
                        $check = $this->db->query("SELECT COUNT(*) FROM purchases WHERE supplier_id = " . intval($_POST['id']))->fetchColumn();
                        if ($check > 0) {
                            $message = "Error: Cannot delete supplier with existing purchase history.";
                        } else {
                            $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
                            $stmt->execute([$_POST['id']]);
                            $message = "Supplier deleted successfully!";
                        }
                    }
                } catch (Exception $e) { 
                    $message = "Error: " . $e->getMessage(); 
                }
            }
            
            $suppliers = $this->db->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
            $this->renderAdminView('suppliers', ['suppliers' => $suppliers, 'message' => $message]);
        }

        // --- 9. EXPENSES (UPDATED WITH EDIT & DELETE) ---
        public function expenses() {
            // Admin, Sales, Account
            $this->checkPermission(['admin', 'sales', 'account']);
            $message = "";
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    // --- ADD EXPENSE ---
                    if (isset($_POST['add_expense'])) {
                        if (empty($_POST['title']) || empty($_POST['amount'])) throw new Exception("Title and Amount required.");
                        
                        $stmt = $this->db->prepare("INSERT INTO expenses (title, amount, category, date, description) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['title'], 
                            $_POST['amount'], 
                            $_POST['category'], 
                            $_POST['date'], 
                            $_POST['description'] ?? ''
                        ]);
                        $message = "Expense added successfully!";
                    }
                    // --- EDIT EXPENSE ---
                    elseif (isset($_POST['edit_expense'])) {
                        if (empty($_POST['id']) || empty($_POST['title']) || empty($_POST['amount'])) throw new Exception("Invalid data.");

                        $stmt = $this->db->prepare("UPDATE expenses SET title=?, amount=?, category=?, date=?, description=? WHERE id=?");
                        $stmt->execute([
                            $_POST['title'], 
                            $_POST['amount'], 
                            $_POST['category'], 
                            $_POST['date'], 
                            $_POST['description'] ?? '',
                            $_POST['id']
                        ]);
                        $message = "Expense updated successfully!";
                    }
                    // --- DELETE EXPENSE ---
                    elseif (isset($_POST['delete_expense'])) {
                        if (empty($_POST['id'])) throw new Exception("Invalid ID.");
                        $this->db->prepare("DELETE FROM expenses WHERE id = ?")->execute([$_POST['id']]);
                        $message = "Expense deleted successfully!";
                    }

                } catch (Exception $e) { 
                    $message = "Error: " . $e->getMessage(); 
                }
            }
            
            $expenses = $this->db->query("SELECT * FROM expenses ORDER BY date DESC, id DESC")->fetchAll();
            $this->renderAdminView('expenses', ['expenses' => $expenses, 'message' => $message]);
        }

        // --- 10. PURCHASE (ADD NEW) - MULTI-ITEM SUPPORT ---
        public function purchase() {
            // Admin & Account
            $this->checkPermission(['admin', 'account']);
            $message = "";
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $this->db->beginTransaction();
                    
                    // Decode the JSON items list
                    $items = json_decode($_POST['items_json'], true);
                    if (empty($items)) throw new Exception("No items in purchase list.");

                    // Default to NOW if date not provided
                    $date = $_POST['purchase_date'] ?: date('Y-m-d H:i:s');
                    
                    // Create Main Purchase Record
                    $stmt = $this->db->prepare("INSERT INTO purchases (supplier_id, total_amount, status, created_at) VALUES (?, ?, 'completed', ?)");
                    $stmt->execute([$_POST['supplier_id'], $_POST['grand_total'], $date]);
                    $purchaseId = $this->db->lastInsertId();

                    // Loop through items and insert
                    foreach ($items as $item) {
                        $stmt = $this->db->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, cost) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$purchaseId, $item['id'], $item['qty'], $item['cost']]);
                        
                        // Update Stock
                        $stmt = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                        $stmt->execute([$item['qty'], $item['id']]);
                    }

                    $this->db->commit();
                    $message = "Purchase recorded successfully!";
                } catch (Exception $e) { 
                    if($this->db->inTransaction()) $this->db->rollBack(); 
                    $message = "Error: " . $e->getMessage(); 
                }
            }
            
            $products = $this->db->query("SELECT * FROM products WHERE type='physical'")->fetchAll();
            $suppliers = $this->db->query("SELECT * FROM suppliers")->fetchAll();
            $history = $this->db->query("SELECT p.*, s.name as supplier_name FROM purchases p JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.created_at DESC LIMIT 20")->fetchAll();
            
            $this->renderAdminView('purchase', ['products' => $products, 'suppliers' => $suppliers, 'history' => $history, 'message' => $message]);
        }

        // --- 10.1 PURCHASE LIST & MANAGEMENT ---
        public function purchaseList() {
            $this->checkPermission(['admin', 'account']);
            
            $purchases = $this->db->query("
                SELECT p.id, p.created_at, p.total_amount, p.status, p.supplier_id,
                       s.name as supplier_name,
                       pr.name as product_name,
                       pi.quantity, pi.cost, pi.product_id, pi.id as item_id
                FROM purchases p 
                JOIN suppliers s ON p.supplier_id = s.id 
                JOIN purchase_items pi ON p.id = pi.purchase_id 
                JOIN products pr ON pi.product_id = pr.id 
                ORDER BY p.created_at DESC
            ")->fetchAll();

            $suppliers = $this->db->query("SELECT * FROM suppliers")->fetchAll();
            $products = $this->db->query("SELECT * FROM products WHERE type='physical'")->fetchAll();

            $this->renderAdminView('purchase_list', [
                'purchases' => $purchases, 
                'suppliers' => $suppliers,
                'products' => $products
            ]);
        }

        public function editPurchase() {
            $this->checkPermission(['admin', 'account']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $this->db->beginTransaction();

                    $purchaseId = $_POST['purchase_id'];
                    $itemId = $_POST['item_id'];
                    $newQty = (int)$_POST['quantity'];
                    $newCost = (float)$_POST['cost']; 
                    $newSupplierId = $_POST['supplier_id'];

                    // 1. Get Old Data
                    $oldItem = $this->db->query("SELECT product_id, quantity FROM purchase_items WHERE id = $itemId")->fetch();
                    
                    if ($oldItem) {
                        // Revert old stock
                        $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                                 ->execute([$oldItem['quantity'], $oldItem['product_id']]);
                        
                        // Add new stock
                        $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                                 ->execute([$newQty, $oldItem['product_id']]);
                    }

                    // 2. Update Purchase Items
                    $stmt = $this->db->prepare("UPDATE purchase_items SET quantity=?, cost=? WHERE id=?");
                    $stmt->execute([$newQty, $newCost, $itemId]);

                    // 3. Update Main Purchase Record
                    $stmt = $this->db->prepare("UPDATE purchases SET supplier_id=?, total_amount=? WHERE id=?");
                    $stmt->execute([$newSupplierId, $newCost, $purchaseId]);

                    $this->db->commit();
                    header("Location: /admin/purchases?msg=updated");
                } catch (Exception $e) {
                    $this->db->rollBack();
                    die("Error updating: " . $e->getMessage());
                }
            }
        }

        public function deletePurchase() {
            $this->checkPermission(['admin']); // Only Admin can delete
            if (isset($_POST['id'])) {
                try {
                    $this->db->beginTransaction();
                    $purchaseId = $_POST['id'];

                    // 1. Revert Stock
                    $items = $this->db->query("SELECT product_id, quantity FROM purchase_items WHERE purchase_id = $purchaseId")->fetchAll();
                    foreach ($items as $item) {
                        $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                                 ->execute([$item['quantity'], $item['product_id']]);
                    }

                    // 2. Delete Records
                    $this->db->prepare("DELETE FROM purchase_items WHERE purchase_id = ?")->execute([$purchaseId]);
                    $this->db->prepare("DELETE FROM purchases WHERE id = ?")->execute([$purchaseId]);

                    $this->db->commit();
                    header("Location: /admin/purchases?msg=deleted");
                } catch (Exception $e) {
                    $this->db->rollBack();
                    die("Error deleting: " . $e->getMessage());
                }
            }
        }

        // --- 11. CUSTOMERS ---
        public function customers() {
            // Admin & Sales
            $this->checkPermission(['admin', 'sales']);
            $message = "";

            try { $this->db->query("SELECT default_discount FROM users LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE users ADD COLUMN default_discount DECIMAL(5,2) DEFAULT 0.00"); }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    if (isset($_POST['update_customer'])) {
                        // Discount security check: Only allow if user is POS (Walk-in)
                        $checkUser = $this->db->query("SELECT email FROM users WHERE id = " . intval($_POST['id']))->fetch();
                        $isPos = $checkUser && (strpos($checkUser['email'], 'walkin_') === 0 && strpos($checkUser['email'], '@local.store') !== false);

                        $discount = 0;
                        if ($isPos && isset($_POST['default_discount'])) {
                            $discount = $_POST['default_discount'];
                        }

                        $sql = "UPDATE users SET username=?, phone=?, address=?, default_discount=? WHERE id=?";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            $_POST['username'], 
                            $_POST['phone'], 
                            $_POST['address'], 
                            $discount, 
                            $_POST['id']
                        ]);
                        $message = "Customer updated successfully!";
                    } 
                    elseif (isset($_POST['delete_customer'])) {
                        $orderCount = $this->db->query("SELECT COUNT(*) FROM orders WHERE user_id=" . intval($_POST['id']))->fetchColumn();
                        if ($orderCount > 0) {
                            $message = "Error: Cannot delete customer with existing order history.";
                        } else {
                            $this->db->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['id']]);
                            $message = "Customer deleted successfully!";
                        }
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                }
            }

            $filter = $_GET['type'] ?? 'all';
            $whereClause = "WHERE u.role = 'customer'";

            if ($filter === 'pos') {
                $whereClause .= " AND u.email LIKE 'walkin_%@local.store'";
            } elseif ($filter === 'ecommerce') {
                $whereClause .= " AND u.email NOT LIKE 'walkin_%@local.store'";
            }

            $sql = "SELECT u.id, u.username, u.email, u.phone, u.address, u.default_discount, u.created_at, 
                           COUNT(o.id) as total_orders, 
                           SUM(o.total_amount) as total_spent 
                    FROM users u 
                    LEFT JOIN orders o ON u.id = o.user_id AND o.status='completed' 
                    $whereClause 
                    GROUP BY u.id 
                    ORDER BY u.created_at DESC";
            
            $customers = $this->db->query($sql)->fetchAll();
            $this->renderAdminView('customers', ['customers' => $customers, 'currentFilter' => $filter, 'message' => $message]);
        }

        // --- 12. REPORTS ---
        public function reports() {
            // Admin Only
            $this->checkPermission(['admin']);

            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');

            $income = $this->db->query("SELECT SUM(total_amount) FROM orders WHERE status='completed' AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'")->fetchColumn() ?: 0;
            $txCount = $this->db->query("SELECT COUNT(*) FROM orders WHERE status='completed' AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'")->fetchColumn() ?: 0;
            $expense = $this->db->query("SELECT SUM(amount) FROM expenses WHERE DATE(date) BETWEEN '$startDate' AND '$endDate'")->fetchColumn() ?: 0;
            $purchase = $this->db->query("SELECT SUM(total_amount) FROM purchases WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'")->fetchColumn() ?: 0;

            $topProducts = $this->db->query("
                SELECT p.name, SUM(oi.quantity) as sold 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.status='completed' AND DATE(o.created_at) BETWEEN '$startDate' AND '$endDate'
                GROUP BY p.id 
                ORDER BY sold DESC 
                LIMIT 5
            ")->fetchAll();

            $topCustomers = $this->db->query("
                SELECT 
                    COALESCE(NULLIF(u.username, ''), NULLIF(o.customer_name, ''), 'Unknown Customer') as name, 
                    COUNT(o.id) as visits, 
                    SUM(o.total_amount) as total_spend 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.status='completed' AND DATE(o.created_at) BETWEEN '$startDate' AND '$endDate'
                GROUP BY COALESCE(NULLIF(u.username, ''), NULLIF(o.customer_name, ''), 'Unknown Customer')
                ORDER BY total_spend DESC 
                LIMIT 20
            ")->fetchAll();

            $lowStockItems = $this->db->query("SELECT name, stock, type FROM products WHERE type='physical' AND stock <= 10 ORDER BY stock ASC LIMIT 10")->fetchAll();
            $salesData = $this->db->query("SELECT DATE(created_at) as date, SUM(total_amount) as total FROM orders WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC")->fetchAll();

            $this->renderAdminView('reports', [
                'salesData' => $salesData, 
                'topProducts' => $topProducts, 
                'topCustomers' => $topCustomers, 
                'lowStockItems' => $lowStockItems,
                'financials' => [
                    'income' => $income, 
                    'expense' => $expense, 
                    'purchase' => $purchase,
                    'net_profit' => $income - ($expense + $purchase),
                    'transactions' => $txCount
                ],
                'dateRange' => ['start' => $startDate, 'end' => $endDate]
            ]);
        }
        
        // --- 12.1 PRINT REPORT ---
        public function printReport() {
            // Admin Only
            $this->checkPermission(['admin']);
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');

            $income = $this->db->query("SELECT SUM(total_amount) FROM orders WHERE status='completed' AND DATE(created_at) BETWEEN '$startDate' AND '$endDate'")->fetchColumn() ?: 0;
            $expense = $this->db->query("SELECT SUM(amount) FROM expenses WHERE DATE(date) BETWEEN '$startDate' AND '$endDate'")->fetchColumn() ?: 0;
            $purchase = $this->db->query("SELECT SUM(total_amount) FROM purchases WHERE DATE(created_at) BETWEEN '$startDate' AND '$endDate'")->fetchColumn() ?: 0;
            $netProfit = $income - ($expense + $purchase);

            $topProducts = $this->db->query("
                SELECT p.name, SUM(oi.quantity) as sold, SUM(oi.price * oi.quantity) as revenue
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.status='completed' AND DATE(o.created_at) BETWEEN '$startDate' AND '$endDate'
                GROUP BY p.id 
                ORDER BY sold DESC 
                LIMIT 10
            ")->fetchAll();

            require_once dirname(__DIR__) . '/views/admin/report_print.php';
            exit;
        }

        // --- 13. SETTINGS & BANNERS ---
        public function settings() {
            // Admin Only
            $this->checkPermission(['admin']);
            $message = "";
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $stmt = $this->db->prepare("UPDATE settings SET site_name=?, phone=?, address=? WHERE id=1");
                    $stmt->execute([$_POST['site_name'], $_POST['phone'], $_POST['address']]);
                    if (!empty($_POST['new_password'])) {
                        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                        $this->db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $_SESSION['user_id']]);
                    }
                    $message = "Settings updated successfully!";
                } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
            }
            $settings = $this->db->query("SELECT * FROM settings WHERE id=1")->fetch();
            $banners = $this->db->query("SELECT * FROM banners ORDER BY id DESC")->fetchAll();
            $this->renderAdminView('settings', ['settings' => $settings, 'banners' => $banners, 'message' => $message]);
        }

        public function addBanner() {
            $this->checkPermission(['admin']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['banner_image'])) {
                $path = $this->uploadFile($_FILES['banner_image']);
                if ($path) {
                    $this->db->prepare("INSERT INTO banners (image_path, link_url) VALUES (?, ?)")->execute([$path, $_POST['link_url']??'']);
                }
            }
            header("Location: /admin/settings"); exit;
        }

        public function deleteBanner() {
            $this->checkPermission(['admin']);
            if (isset($_POST['id'])) {
                $banner = $this->db->query("SELECT image_path FROM banners WHERE id=".$_POST['id'])->fetch();
                if ($banner && file_exists(dirname(__DIR__).'/../'.$banner['image_path'])) unlink(dirname(__DIR__).'/../'.$banner['image_path']);
                $this->db->prepare("DELETE FROM banners WHERE id = ?")->execute([$_POST['id']]);
            }
            header("Location: /admin/settings"); exit;
        }
        
        // --- 14. CHECKOUT SETTINGS ---
        public function checkoutSettings() {
            // Admin Only
            $this->checkPermission(['admin']);
            $message = "";
            
            try { $this->db->query("SELECT type FROM payment_methods LIMIT 1"); } 
            catch (Exception $e) { 
                $this->db->exec("ALTER TABLE payment_methods ADD COLUMN type VARCHAR(20) DEFAULT 'checkout' AFTER name"); 
                $this->db->exec("UPDATE payment_methods SET type='checkout' WHERE type IS NULL");
            }
            $this->db->exec("UPDATE payment_methods SET type='pos' WHERE name='Cash' AND type='checkout'");

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    if (isset($_POST['add_delivery'])) { 
                        $this->db->prepare("INSERT INTO delivery_methods (name, cost) VALUES (?, ?)")->execute([$_POST['name'], $_POST['cost']]); 
                        $message = "Delivery area added!"; 
                    }
                    elseif (isset($_POST['add_payment'])) { 
                        $type = $_POST['type'];
                        if (strtolower($_POST['name']) === 'cash' && $type === 'checkout') { $type = 'pos'; }

                        $this->db->prepare("INSERT INTO payment_methods (name, type, account_number, account_name) VALUES (?, ?, ?, ?)")
                                 ->execute([$_POST['name'], $type, $_POST['number'] ?? null, $_POST['acc_name'] ?? null]); 
                        $message = "Payment method added!"; 
                    }
                    elseif (isset($_POST['add_coupon'])) { 
                        $this->db->prepare("INSERT INTO coupons (code, discount_amount) VALUES (?, ?)")->execute([$_POST['code'], $_POST['amount']]); 
                        $message = "Coupon added!"; 
                    }
                    elseif (isset($_POST['delete_item'])) { 
                        $this->db->prepare("DELETE FROM {$_POST['table']} WHERE id = ?")->execute([$_POST['id']]); 
                        $message = "Item deleted!"; 
                    }
                } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
            }

            $data = [
                'delivery' => $this->db->query("SELECT * FROM delivery_methods")->fetchAll(),
                'payment_checkout' => $this->db->query("SELECT * FROM payment_methods WHERE type='checkout'")->fetchAll(),
                'payment_pos' => $this->db->query("SELECT * FROM payment_methods WHERE type='pos'")->fetchAll(),
                'coupons' => $this->db->query("SELECT * FROM coupons")->fetchAll(),
                'message' => $message
            ];
            $this->renderAdminView('checkout_settings', $data);
        }

        // --- 15. ORDER MANAGEMENT (UPDATED for Digital Products) ---
        public function orders() {
            // All staff (Sales needs to see orders)
            $this->checkPermission(['admin', 'sales', 'account']);
            
            $source = $_GET['source'] ?? 'all';
            $where = "";

            if ($source === 'pos') {
                $where = "WHERE u.email LIKE 'walkin_%@local.store' OR o.customer_name LIKE 'Walk-in%'";
            } elseif ($source === 'ecommerce') {
                $where = "WHERE (u.email NOT LIKE 'walkin_%@local.store' OR u.email IS NULL) AND o.customer_name NOT LIKE 'Walk-in%'";
            }

            // *** UPDATED SQL: Check for physical items to handle Digital Product Logic in View ***
            // Returns >0 if order has physical items, 0 if purely digital
            $sql = "SELECT o.*, 
                           COALESCE(u.username, o.customer_name) as user_account_name, 
                           COALESCE(o.customer_phone, u.phone) as customer_phone, 
                           COALESCE(o.customer_address, u.address) as customer_address,
                           (SELECT COUNT(*) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id AND p.type = 'physical') as has_physical_items
                    FROM orders o 
                    LEFT JOIN users u ON o.user_id = u.id 
                    $where 
                    ORDER BY o.created_at DESC";
                    
            $orders = $this->db->query($sql)->fetchAll();
            $this->renderAdminView('orders', ['orders' => $orders, 'currentSource' => $source]);
        }

        // --- 15.1 UPDATE ORDER STATUS (UPDATED LOGIC) ---
        public function updateOrderStatus() {
            $this->checkPermission(['admin', 'sales', 'account']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $status = $_POST['status'];
                $orderId = $_POST['order_id'];

                // 1. Check if order is Purely Digital
                // If admin approves a purely digital order, we complete it immediately.
                if ($status === 'approved') {
                    $checkPhysical = $this->db->query("
                        SELECT COUNT(*) 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = $orderId AND p.type = 'physical'
                    ")->fetchColumn();

                    // If NO physical items, auto-complete
                    if ($checkPhysical == 0) {
                        $status = 'completed';
                    }
                }

                // 2. Update Status
                $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $orderId]);
                
                // 3. SEND EMAIL
                $this->sendOrderStatusEmail($orderId, $status);
                
                header('Location: /admin/orders');
            }
        }

        // --- 15.1.1 NEW: BULK UPDATE ORDER STATUS (FOR ORDERS) ---
        public function bulkUpdateStatus() {
            header('Content-Type: application/json');
            $this->checkPermission(['admin', 'sales', 'account']);
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['status']) || !isset($input['order_ids']) || !is_array($input['order_ids'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid input data']);
                exit;
            }

            $newStatus = $input['status'];
            $orderIds = $input['order_ids'];

            try {
                $this->db->beginTransaction();

                foreach ($orderIds as $orderId) {
                    $orderId = (int)$orderId;
                    
                    // Logic to handle digital items auto-complete if status is 'approved'
                    if ($newStatus === 'approved') {
                        $checkPhysical = $this->db->query("
                            SELECT COUNT(*) 
                            FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE oi.order_id = $orderId AND p.type = 'physical'
                        ")->fetchColumn();

                        // If order is purely digital, skip 'approved' and go straight to 'completed'
                        if ($checkPhysical == 0) {
                            $statusToSet = 'completed';
                        } else {
                            $statusToSet = 'approved';
                        }
                    } else {
                        $statusToSet = $newStatus;
                    }

                    // Perform Update
                    $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$statusToSet, $orderId]);

                    // Send Email Notification
                    $this->sendOrderStatusEmail($orderId, $statusToSet);
                }

                $this->db->commit();
                echo json_encode(['success' => true]);

            } catch (Exception $e) {
                if($this->db->inTransaction()) $this->db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        public function completeOrder() {
            $this->checkPermission(['admin', 'sales']);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
                try {
                    $proofPath = null;
                    if (isset($_FILES['delivery_proof']) && $_FILES['delivery_proof']['error'] === UPLOAD_ERR_OK) {
                        $proofPath = $this->uploadFile($_FILES['delivery_proof']);
                    }

                    try { $this->db->query("SELECT delivery_proof FROM orders LIMIT 1"); } 
                    catch (Exception $e) { $this->db->exec("ALTER TABLE orders ADD COLUMN delivery_proof VARCHAR(255) NULL AFTER payment_receipt"); }

                    $sql = "UPDATE orders SET status = 'completed', delivery_proof = ? WHERE id = ?";
                    $this->db->prepare($sql)->execute([$proofPath, $_POST['order_id']]);
                    
                    // SEND EMAIL
                    $this->sendOrderStatusEmail($_POST['order_id'], 'completed');
                    
                    header('Location: /admin/orders');
                    exit;
                } catch (Exception $e) { die("Error uploading proof: " . $e->getMessage()); }
            }
        }

        // --- 15.2 CANCEL ORDER (DB FIX & FORCE UPDATE) ---
        public function cancelOrder() {
            // Check permissions
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                die("Error: Permission denied.");
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id']) || !isset($_POST['reason'])) {
                die("Error: Invalid request data.");
            }

            $orderId = $_POST['order_id'];
            $reason = $_POST['reason'];

            try {
                // 0. AUTO-FIX: MODIFY ENUM IF NEEDED
                try {
                    $this->db->query("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','approved','preparing','delivering','completed','rejected','cancelled') NOT NULL DEFAULT 'pending'");
                } catch (Exception $e) {
                    // Ignore if it's not an ENUM or already correct
                }

                $this->db->beginTransaction();

                // 1. Check if order exists
                $check = $this->db->prepare("SELECT id, status FROM orders WHERE id = ?");
                $check->execute([$orderId]);
                $order = $check->fetch(PDO::FETCH_ASSOC);

                if (!$order) {
                    throw new Exception("Order #$orderId not found.");
                }

                // If already cancelled, just redirect back (don't error out)
                if ($order['status'] === 'cancelled') {
                    $this->db->rollBack();
                    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/admin/orders'));
                    exit;
                }

                // 2. Restore Stock
                $items = $this->db->query("
                    SELECT oi.quantity, oi.product_id, p.type 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = $orderId
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($items as $item) {
                    if ($item['type'] === 'physical') {
                        $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                                 ->execute([$item['quantity'], $item['product_id']]);
                    }
                }

                // 3. Ensure 'cancellation_reason' column exists
                try { 
                    $this->db->query("SELECT cancellation_reason FROM orders LIMIT 1"); 
                } catch (Exception $e) { 
                    $this->db->exec("ALTER TABLE orders ADD COLUMN cancellation_reason TEXT NULL AFTER notes"); 
                }

                // 4. Update Order Status
                $stmt = $this->db->prepare("UPDATE orders SET status = 'cancelled', cancellation_reason = ? WHERE id = ?");
                $stmt->execute([$reason, $orderId]);

                $this->db->commit();
                
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/admin/orders'));
                exit;

            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                echo "<div style='background:#1e293b; color:#ef4444; padding:50px; text-align:center; font-family:sans-serif;'>";
                echo "<h1>Cancellation Error</h1>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "<a href='/admin/orders' style='color:white; text-decoration:underline;'>Return to Orders</a>";
                echo "</div>";
                exit;
            }
        }

        // --- 15.3 DELETE ORDER (ADMIN ONLY) ---
        public function deleteOrder() {
            $this->checkPermission(['admin']); // Strict Admin Check
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
                $orderId = $_POST['order_id'];
                
                try {
                    $this->db->beginTransaction();

                    // Delete related items first
                    $this->db->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
                    // Delete the order
                    $this->db->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);

                    $this->db->commit();
                    header("Location: /admin/orders?msg=deleted");
                    exit;
                } catch (Exception $e) {
                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }
                    die("Error deleting order: " . $e->getMessage());
                }
            }
        }

            // --- 16. PROMOTIONS (FIXED CATEGORY LOADING) ---
        public function promotions() {
            $this->checkPermission(['admin']);
            $message = "";

            // 1. Ensure DB Columns Exist
            try { $this->db->query("SELECT applicable_products FROM promotions LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE promotions ADD COLUMN applicable_products TEXT NULL"); }
            
            try { $this->db->query("SELECT applicable_categories FROM promotions LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE promotions ADD COLUMN applicable_categories TEXT NULL AFTER applicable_products"); }

            // 2. Handle Form Submissions
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    if (isset($_POST['update_shipping'])) { 
                        $this->db->prepare("UPDATE settings SET free_shipping_min = ? WHERE id = 1")->execute([$_POST['min_amount']]); 
                        $message = "Updated!"; 
                    } 
                    elseif (isset($_POST['add_promotion'])) { 
                        // Logic for applicable products/categories
                        $applyTo = $_POST['apply_to'] ?? 'all';
                        $productIds = null;
                        $categoryIds = null;
                        
                        if ($applyTo === 'selected_products' && !empty($_POST['products'])) {
                            $productIds = implode(',', $_POST['products']);
                        } elseif ($applyTo === 'selected_categories' && !empty($_POST['categories'])) {
                            $categoryIds = implode(',', $_POST['categories']);
                        }

                        $stmt = $this->db->prepare("INSERT INTO promotions (name, type, value, requirement, start_date, end_date, applicable_products, applicable_categories) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['name'], 
                            $_POST['type'], 
                            $_POST['value'], 
                            $_POST['requirement'] ?? 0, 
                            $_POST['start_date'], 
                            $_POST['end_date'],
                            $productIds,
                            $categoryIds
                        ]); 
                        $message = "Promotion Added!"; 
                    } 
                    elseif (isset($_POST['delete_promotion'])) { 
                        $this->db->prepare("DELETE FROM promotions WHERE id = ?")->execute([$_POST['id']]); 
                        $message = "Deleted!"; 
                    } 
                    elseif (isset($_POST['toggle_status'])) { 
                        $this->db->prepare("UPDATE promotions SET is_active = NOT is_active WHERE id = ?")->execute([$_POST['id']]); 
                        header("Location: /admin/promotions"); 
                        exit; 
                    }
                } catch (Exception $e) { $message = "Error: " . $e->getMessage(); }
            }
            
            // 3. Fetch Data for View
            // Ensure settings table exists column
            try { $this->db->query("SELECT free_shipping_min FROM settings LIMIT 1"); } 
            catch (Exception $e) { $this->db->exec("ALTER TABLE settings ADD COLUMN free_shipping_min DECIMAL(10,2) DEFAULT 0"); }

            $settings = $this->db->query("SELECT free_shipping_min FROM settings WHERE id=1")->fetch();
            $promotions = $this->db->query("SELECT * FROM promotions ORDER BY is_active DESC, end_date DESC")->fetchAll();
            
            // *** CRITICAL FIX: Fetch Categories here ***
            $products = $this->db->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll();
            $categories = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
            
            // Debug check (Optional: removes itself if empty)
            if (empty($categories)) {
                // Ensure categories table exists just in case
                $this->db->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)");
            }

            $this->renderAdminView('promotions', [
                'promotions' => $promotions, 
                'products' => $products, 
                'categories' => $categories, // <--- Passing this is vital
                'free_shipping_min' => $settings['free_shipping_min'] ?? 0, 
                'message' => $message
            ]);
        }

        // --- 17. PRINT INVOICE ---
        public function printInvoice() {
            // All staff
            $this->checkPermission(['admin', 'sales', 'account']);
            $orderId = $_GET['id'] ?? 0;
            if (!$orderId) die("Invalid Order ID");
            $order = $this->db->query("SELECT o.*, u.username, o.customer_name as stored_name FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = $orderId")->fetch(PDO::FETCH_ASSOC);
            if (!$order) die("Order not found");
            $order['customer_name'] = $order['username'] ?? $order['stored_name'] ?? 'Walk-in Customer';
            $items = $this->db->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $orderId")->fetchAll(PDO::FETCH_ASSOC);
            require_once dirname(__DIR__) . '/views/admin/invoice_print.php';
            exit;
        }

        // --- 18. API ORDER DETAILS ---
        public function apiGetOrderDetails() {
            header('Content-Type: application/json');
            // All staff
            $this->checkPermission(['admin', 'sales', 'account']);
            
            $orderId = $_GET['id'] ?? 0;
            if (!$orderId) { echo json_encode(['success' => false]); exit; }

            $order = $this->db->query("
                SELECT o.*, 
                       COALESCE(u.username, o.customer_name) as customer_name,
                       COALESCE(u.email, 'N/A') as customer_email,
                       COALESCE(o.customer_phone, u.phone) as customer_phone,
                       COALESCE(o.customer_address, u.address) as customer_address,
                       dm.name as delivery_method_name,
                       pm.name as payment_method_name,
                       pm.account_number,
                       pm.account_name,
                       o.notes
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN delivery_methods dm ON o.delivery_method_id = dm.id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE o.id = $orderId
            ")->fetch(PDO::FETCH_ASSOC);

            if (!$order) { echo json_encode(['success' => false]); exit; }

            $items = $this->db->query("
                SELECT oi.*, p.name as product_name, p.image, p.type 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = $orderId
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
            exit;
        }

        // --- 19. SYSTEM USERS (NEW: ADMIN ONLY) ---
        public function systemUsers() {
            // Admin Only
            $this->checkPermission(['admin']);
            $message = "";

            // 1. AUTO-FIX DATABASE SCHEMA
            // This ensures the 'role' column accepts 'account'
            try {
                $this->db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer', 'sales', 'account') NOT NULL DEFAULT 'customer'");
            } catch (Exception $e) {
                // If it's not an ENUM or already correct, ignore error
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    if (isset($_POST['add_user'])) {
                        // Create User
                        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email'])) {
                            throw new Exception("Username, Email, and Password are required");
                        }
                        
                        $role = $_POST['role'];
                        // Strict check
                        if (!in_array($role, ['admin', 'sales', 'account'])) throw new Exception("Invalid role selected");
                        
                        $stmt = $this->db->prepare("INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $stmt->execute([
                            $_POST['username'], 
                            password_hash($_POST['password'], PASSWORD_DEFAULT),
                            $_POST['email'], 
                            $role
                        ]);
                        $message = "User added successfully!";
                    } 
                    elseif (isset($_POST['edit_user'])) {
                        // Update User
                        if (empty($_POST['email'])) throw new Exception("Email is required");

                        $role = $_POST['role'];
                        if (!in_array($role, ['admin', 'sales', 'account'])) throw new Exception("Invalid role selected");
                        
                        $sql = "UPDATE users SET username=?, email=?, role=?";
                        $params = [$_POST['username'], $_POST['email'], $role];
                        
                        if (!empty($_POST['password'])) {
                            $sql .= ", password=?";
                            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        }
                        $sql .= " WHERE id=?";
                        $params[] = $_POST['id'];
                        
                        $this->db->prepare($sql)->execute($params);
                        $message = "User updated successfully!";
                    }
                    elseif (isset($_POST['delete_user'])) {
                        // Delete User
                        if ($_POST['id'] == $_SESSION['user_id']) throw new Exception("Cannot delete yourself.");
                        $this->db->prepare("DELETE FROM users WHERE id=?")->execute([$_POST['id']]);
                        $message = "User deleted successfully!";
                    }
                } catch (Exception $e) { 
                    if (strpos($e->getMessage(), '1062 Duplicate entry') !== false) {
                        $message = "Error: That username or email is already taken.";
                    } else {
                        $message = "Error: " . $e->getMessage(); 
                    }
                }
            }

            // Fetch system users (exclude customers)
            $users = $this->db->query("SELECT * FROM users WHERE role != 'customer' ORDER BY role ASC, username ASC")->fetchAll();
            $this->renderAdminView('users', ['users' => $users, 'message' => $message]);
        }
        // --- 20. NEW COUPONS PAGE (TIERED & STANDARD) ---
        public function coupons() {
            $this->checkPermission(['admin', 'sales']);
            $message = "";

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    if (isset($_POST['add_coupon'])) {
                        $code = strtoupper(trim($_POST['code']));
                        if (empty($code)) throw new Exception("Coupon Code is required");

                        $type = $_POST['type']; // 'standard' or 'tiered'
                        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
                        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                        $limit = $_POST['usage_limit'] ?? 0;

                        // TIERED LOGIC JSON BUILDER
                        $tierData = null;
                        if ($type === 'tiered' && isset($_POST['tiers'])) {
                            // Sort tiers by min spend ascending to ensure logic works
                            $tiers = $_POST['tiers'];
                            usort($tiers, function($a, $b) { return $a['min'] - $b['min']; });
                            $tierData = json_encode($tiers);
                        }

                        // STANDARD VALUES
                        $val = $_POST['value'] ?? 0;
                        $discType = $_POST['discount_type'] ?? 'fixed';
                        $minSpend = $_POST['min_spend'] ?? 0;

                        $sql = "INSERT INTO coupons (code, type, discount_type, value, min_spend, tier_data, usage_limit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $this->db->prepare($sql)->execute([
                            $code, $type, $discType, $val, $minSpend, $tierData, $limit, $startDate, $endDate
                        ]);
                        $message = "Coupon created successfully!";
                    } 
                    elseif (isset($_POST['delete_coupon'])) {
                        $this->db->prepare("DELETE FROM coupons WHERE id = ?")->execute([$_POST['id']]);
                        $message = "Coupon deleted!";
                    }
                } catch (Exception $e) {
                    $message = "Error: " . $e->getMessage();
                }
            }

            $coupons = $this->db->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            $this->renderAdminView('coupons', ['coupons' => $coupons, 'message' => $message]);
        }

        // --- PAGE BUILDER ---
        public function pageBuilder() {
            $this->checkPermission(['admin']);
            $message = '';

            // Create table and seed defaults if needed
            $this->initPageSectionsTable();

            $pages = ['home', 'shop', 'contact'];
            $sections = [];
            foreach ($pages as $page) {
                $stmt = $this->db->prepare("SELECT * FROM page_sections WHERE page = ? ORDER BY sort_order ASC");
                $stmt->execute([$page]);
                $sections[$page] = $stmt->fetchAll();
            }

            if (isset($_GET['saved'])) {
                $message = 'Page sections saved successfully!';
            }

            $this->renderAdminView('page_builder', ['sections' => $sections, 'message' => $message]);
        }

        public function savePageSections() {
            header('Content-Type: application/json');
            ob_start();
            try {
                $this->checkPermission(['admin']);
                $input = json_decode(file_get_contents('php://input'), true);
                if (!isset($input['sections']) || !is_array($input['sections'])) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
                    exit;
                }

                $this->initPageSectionsTable();

                foreach ($input['sections'] as $s) {
                    $page       = $s['page'] ?? '';
                    $sectionKey = $s['section_key'] ?? '';
                    $title      = $s['title'] ?? '';
                    $content    = $s['content'] ?? '';
                    $isVisible  = (int)($s['is_visible'] ?? 1);
                    $sortOrder  = (int)($s['sort_order'] ?? 0);
                    $settings   = $s['settings'] ?? null;

                    if (!$page || !$sectionKey) continue;

                    $this->db->prepare(
                        "INSERT INTO page_sections (page, section_key, title, content, is_visible, sort_order, settings)
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content),
                             is_visible=VALUES(is_visible), sort_order=VALUES(sort_order), settings=VALUES(settings)"
                    )->execute([$page, $sectionKey, $title, $content, $isVisible, $sortOrder, $settings]);
                }

                ob_clean();
                echo json_encode(['success' => true]);
            } catch (Throwable $e) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }

        private function initPageSectionsTable() {
            $this->db->exec("CREATE TABLE IF NOT EXISTS page_sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                page VARCHAR(50) NOT NULL,
                section_key VARCHAR(100) NOT NULL,
                title VARCHAR(255) DEFAULT '',
                content TEXT DEFAULT '',
                is_visible TINYINT(1) DEFAULT 1,
                sort_order INT DEFAULT 0,
                settings TEXT DEFAULT NULL,
                UNIQUE KEY uq_page_section (page, section_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Seed defaults only if table is empty
            $count = $this->db->query("SELECT COUNT(*) FROM page_sections")->fetchColumn();
            if ($count == 0) {
                $defaults = [
                    // Home
                    ['home','hero','Welcome to Our Store','Discover premium LED products and more at the best prices.',1,10, json_encode(['cta_text'=>'Shop Now','cta_link'=>'/shop'])],
                    ['home','promo_strip','Why Shop With Us','',1,20,null],
                    ['home','featured_products','Featured Products','',1,30, json_encode(['limit'=>8])],
                    ['home','categories','Shop by Category','',1,40,null],
                    // Shop
                    ['shop','page_header','Our Products','Browse our full range of products.',1,10,null],
                    // Contact
                    ['contact','hero','Contact Us',"We'd love to hear from you. Reach out anytime.",1,10,null],
                    ['contact','contact_info','Get In Touch','',1,20,null],
                    ['contact','map','Find Us','',0,30, json_encode(['map_url'=>''])],
                    ['contact','contact_form','Send Us a Message','',1,40,null],
                ];
                $stmt = $this->db->prepare(
                    "INSERT IGNORE INTO page_sections (page, section_key, title, content, is_visible, sort_order, settings) VALUES (?,?,?,?,?,?,?)"
                );
                foreach ($defaults as $d) {
                    $stmt->execute($d);
                }
            }
        }

        // --- HELPERS & AUTH ---

        // Basic Authentication Check
        private function requireAuth() {
            if (session_status() === PHP_SESSION_NONE) session_start();
            if (!isset($_SESSION['user_id'])) { header("Location: /login"); exit; }
        }

        // Role-Based Access Control
        // Usage: $this->checkPermission(['admin', 'sales']);
        private function checkPermission($allowedRoles = []) {
            $this->requireAuth();
            $userRole = $_SESSION['user_role'] ?? '';

            // Admin always has access
            if ($userRole === 'admin') return;

            // Check if user role is in the allowed list
            if (in_array($userRole, $allowedRoles)) return;

            // Deny Access
            http_response_code(403);
            die("Access Denied: You do not have permission to view this page.");
        }

        // Deprecated: Kept for backward compatibility if any views call it, mapped to full admin check
        private function requireAdmin() {
            $this->checkPermission(['admin']);
        }

        // --- SEND ORDER STATUS EMAIL (UPDATED LOGIC) ---
        private function sendOrderStatusEmail($orderId, $status) {
             // 1. Fetch Order Details & User Info
             // Note: We select email directly from users table or fallback to orders table if needed
             $sql = "SELECT o.*, 
                            u.email, 
                            COALESCE(u.username, o.customer_name) as name,
                            dm.name as delivery_method
                     FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     LEFT JOIN delivery_methods dm ON o.delivery_method_id = dm.id
                     WHERE o.id = ?";
             
             $stmt = $this->db->prepare($sql);
             $stmt->execute([$orderId]);
             $order = $stmt->fetch(PDO::FETCH_ASSOC);
 
             // *** CRITICAL CHECK: SKIP IF EMAIL MISSING OR IS DUMMY POS EMAIL ***
             if (
                 !$order || 
                 empty($order['email']) || 
                 $order['email'] === 'N/A' ||
                 strpos($order['email'], '@local.store') !== false 
             ) {
                 return; // Stop immediately
             }
 
             // 2. Fetch Order Items (Including Download Links)
             $itemSql = "SELECT oi.*, p.name, p.type, p.download_link 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = ?";
             $stmtItems = $this->db->prepare($itemSql);
             $stmtItems->execute([$orderId]);
             $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
 
             // 3. USE EMAIL SERVICE
             if (isset($this->emailService)) {
                 $this->emailService->sendOrderStatusUpdate($order['email'], $order['name'], $order, $items, $status);
             }
        }

        private function renderAdminView($viewName, $data = []) {
            extract($data);
            $childView = dirname(__DIR__) . "/views/admin/$viewName.php";
            $layout = dirname(__DIR__) . '/views/admin_layout.php';
            if (file_exists($childView)) {
                if (file_exists($layout)) require_once $layout;
                else require_once $childView;
            } else { echo "View not found: $viewName"; }
        }

        public function bulkOptimizeImages() {
            // Set JSON header first so any error response is also JSON-typed
            header('Content-Type: application/json');
            // Buffer all output so PHP warnings (display_errors=1) cannot corrupt the JSON body
            ob_start();

            try {
                $this->checkPermission(['admin']);

                if (!extension_loaded('gd') || !function_exists('imagewebp')) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'GD extension with WebP support is not available.']);
                    exit;
                }

                // Allow plenty of time for large image batches
                set_time_limit(300);

                $uploadDir = dirname(__DIR__) . '/../uploads/';
                $maxDim    = 1200;

                // Map EXIF image type constants to GD loader functions
                $typeLoaders = [
                    IMAGETYPE_JPEG => 'imagecreatefromjpeg',
                    IMAGETYPE_PNG  => 'imagecreatefrompng',
                    IMAGETYPE_GIF  => 'imagecreatefromgif',
                    IMAGETYPE_BMP  => 'imagecreatefrombmp',
                    IMAGETYPE_WEBP => null, // already WebP — skip
                ];

                $stats = ['processed' => 0, 'skipped' => 0, 'failed' => 0, 'saved_bytes' => 0];

                if (!is_dir($uploadDir)) {
                    ob_clean();
                    echo json_encode(['success' => true, 'stats' => $stats, 'message' => 'No uploads directory found.']);
                    exit;
                }

                $files = scandir($uploadDir);
                foreach ($files as $fname) {
                    if ($fname === '.' || $fname === '..') continue;
                    $srcPath = $uploadDir . $fname;
                    if (!is_file($srcPath)) continue;

                    // Detect actual image type from file content (ignores misleading extensions)
                    $imageType = @exif_imagetype($srcPath);
                    if ($imageType === false || !array_key_exists($imageType, $typeLoaders)) {
                        // Not a supported image type (video, document, etc.) — skip silently
                        continue;
                    }
                    if ($imageType === IMAGETYPE_WEBP) {
                        // Already WebP — nothing to do
                        $stats['skipped']++;
                        continue;
                    }

                    $oldRelative = 'uploads/' . $fname;
                    $newFname    = pathinfo($fname, PATHINFO_FILENAME) . '.webp';
                    $dstPath     = $uploadDir . $newFname;
                    $newRelative = 'uploads/' . $newFname;

                    // If WebP already exists, sync DB references and remove the old original
                    if (file_exists($dstPath)) {
                        $this->db->prepare("UPDATE products SET image=? WHERE image=?")->execute([$newRelative, $oldRelative]);
                        $this->db->prepare("UPDATE product_images SET image_path=? WHERE image_path=?")->execute([$newRelative, $oldRelative]);
                        $this->db->prepare("UPDATE banners SET image_path=? WHERE image_path=?")->execute([$newRelative, $oldRelative]);
                        @unlink($srcPath);
                        $stats['skipped']++;
                        continue;
                    }

                    $loader = $typeLoaders[$imageType];
                    $src    = @$loader($srcPath);
                    if ($src === false) { $stats['failed']++; continue; }

                    $origWidth  = imagesx($src);
                    $origHeight = imagesy($src);

                    if ($origWidth > $maxDim || $origHeight > $maxDim) {
                        if ($origWidth >= $origHeight) {
                            $newWidth  = $maxDim;
                            $newHeight = (int) round($origHeight * ($maxDim / $origWidth));
                        } else {
                            $newHeight = $maxDim;
                            $newWidth  = (int) round($origWidth * ($maxDim / $origHeight));
                        }
                    } else {
                        $newWidth  = $origWidth;
                        $newHeight = $origHeight;
                    }

                    $dst = imagecreatetruecolor($newWidth, $newHeight);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

                    $originalSize = filesize($srcPath);

                    if (!imagewebp($dst, $dstPath, 85)) {
                        imagedestroy($src);
                        imagedestroy($dst);
                        $stats['failed']++;
                        continue;
                    }
                    imagedestroy($src);
                    imagedestroy($dst);

                    // Update DB references
                    $this->db->prepare("UPDATE products SET image=? WHERE image=?")->execute([$newRelative, $oldRelative]);
                    $this->db->prepare("UPDATE product_images SET image_path=? WHERE image_path=?")->execute([$newRelative, $oldRelative]);
                    $this->db->prepare("UPDATE banners SET image_path=? WHERE image_path=?")->execute([$newRelative, $oldRelative]);

                    $newSize = filesize($dstPath);
                    $stats['saved_bytes'] += max(0, $originalSize - $newSize);

                    // Remove original file
                    @unlink($srcPath);
                    $stats['processed']++;
                }

                ob_clean();
                echo json_encode(['success' => true, 'stats' => $stats]);

            } catch (Throwable $e) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;
        }

        private function uploadFile($file) {
            $uploadDir = dirname(__DIR__) . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $videoExts = ['mp4', 'webm', 'ogg', 'mov', 'avi'];
            $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            $isImage = in_array($ext, $imageExts);
            $isVideo = in_array($ext, $videoExts);

            // Optimize and convert images to WebP using GD
            if ($isImage && extension_loaded('gd') && function_exists('imagewebp')) {
                $loaders = [
                    'jpg'  => 'imagecreatefromjpeg',
                    'jpeg' => 'imagecreatefromjpeg',
                    'png'  => 'imagecreatefrompng',
                    'gif'  => 'imagecreatefromgif',
                    'bmp'  => 'imagecreatefrombmp',
                    'webp' => 'imagecreatefromwebp',
                ];
                $loader = $loaders[$ext] ?? null;
                $src = $loader ? @$loader($file['tmp_name']) : false;
                if ($src !== false) {
                    $origWidth  = imagesx($src);
                    $origHeight = imagesy($src);
                    $maxDim = 1200;

                    if ($origWidth > $maxDim || $origHeight > $maxDim) {
                        if ($origWidth >= $origHeight) {
                            $newWidth  = $maxDim;
                            $newHeight = (int) round($origHeight * ($maxDim / $origWidth));
                        } else {
                            $newHeight = $maxDim;
                            $newWidth  = (int) round($origWidth * ($maxDim / $origHeight));
                        }
                    } else {
                        $newWidth  = $origWidth;
                        $newHeight = $origHeight;
                    }

                    $dst = imagecreatetruecolor($newWidth, $newHeight);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

                    $filename = uniqid('img_') . '.webp';
                    if (imagewebp($dst, $uploadDir . $filename, 85)) {
                        imagedestroy($src);
                        imagedestroy($dst);
                        return 'uploads/' . $filename;
                    }
                    imagedestroy($src);
                    imagedestroy($dst);
                }
            }

            // Fallback: save file as-is (videos or if GD processing fails)
            $prefix = $isVideo ? 'video_' : 'file_';
            $filename = uniqid($prefix) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                return 'uploads/' . $filename;
            }
            return null;
        }
    }
}
?>