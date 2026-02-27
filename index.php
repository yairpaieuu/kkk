<?php
// 1. Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// FIX: Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Require Configuration
require_once 'config/database.php';

// 3. Require Controllers
$controllers = [
    'app/controllers/HomeController.php',
    'app/controllers/AuthController.php',
    'app/controllers/AdminController.php'
];

foreach ($controllers as $file) {
    if (file_exists($file)) require_once $file;
    else die("Critical Error: Missing controller file: $file");
}

// 4. Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// =================================================================
// 🛡️ ROLE RESTRICTION LOGIC (Sales & Account)
// =================================================================
if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['sales', 'account'])) {
    
    $isAdminRoute = strpos($uri, '/admin') === 0;
    $isApiRoute = strpos($uri, '/api') === 0;
    
    $allowedPages = [
        '/logout', '/pos', '/customers', '/sales', 
        '/products', '/purchase', '/suppliers', '/expenses'
    ];

    if (!$isAdminRoute && !$isApiRoute && !in_array($uri, $allowedPages)) {
        header("Location: /admin"); 
        exit;
    }
}
// =================================================================

try {
    switch ($uri) {
        // --- PUBLIC ROUTES ---
        case '/':
        case '/index.php':
        case '/home':
            (new HomeController())->index();
            break;
        
        case '/shop':
            (new HomeController())->shop();
            break;
            
        case '/product': 
            (new HomeController())->productDetails();
            break;

        case '/api/shop/search':
            (new HomeController())->apiSearch();
            break;

        case '/cart':
            (new HomeController())->cart();
            break;

        case '/api/shop/checkout':
            (new HomeController())->processCheckout();
            break;

        case '/api/coupon/check':
            (new HomeController())->checkCoupon();
            break;

        case '/profile':
            (new HomeController())->profile();
            break;

        // --- AUTH ROUTES ---
        case '/login':
            $c = new AuthController();
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $c->login() : $c->showLogin();
            break;

        case '/register':
            $c = new AuthController();
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $c->register() : $c->showRegister();
            break;
            
        case '/verify-otp':
            $c = new AuthController();
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $c->verifyOtp() : $c->showVerifyOtp();
            break;

        case '/logout':
            if (class_exists('AuthController')) {
                (new AuthController())->logout();
            } else {
                session_destroy();
                header("Location: /login");
                exit;
            }
            break;

        // --- ADMIN DASHBOARD & CORE ---
        
        case '/admin':
            requireAuth();
            (new AdminController())->dashboard();
            break;

        case '/products':
            requireAuth();
            (new AdminController())->products();
            break;

        case '/admin/product/toggle':
            requireAuth();
            (new AdminController())->toggleProduct();
            break;

        // *** NEW: Route specifically for Product Bulk Updates ***
        // This prevents conflict with the Order API
        case '/admin/product/bulk-status':
            requireAuth();
            (new AdminController())->bulkUpdateProductStatus();
            break;

        case '/admin/categories':
            requireAuth();
            (new AdminController())->categories();
            break;

        case '/pos':
            requireAuth();
            (new AdminController())->pos();
            break;

        case '/api/checkout': // POS Checkout API
            requireAuth();
            (new AdminController())->checkout();
            break;

        case '/sales':
        case '/admin/sales':
            requireAuth();
            (new AdminController())->sales();
            break;

        case '/suppliers': 
        case '/admin/suppliers':
            requireAuth();
            (new AdminController())->suppliers();
            break;

        case '/expenses': 
        case '/admin/expenses':
            requireAuth();
            (new AdminController())->expenses();
            break;

        // --- PURCHASE & STOCK MANAGEMENT ---
        case '/purchase':
            requireAuth();
            (new AdminController())->purchase();
            break;

        case '/admin/purchases': 
            requireAuth();
            (new AdminController())->purchaseList(); 
            break;

        case '/admin/purchases/edit': 
            requireAuth();
            (new AdminController())->editPurchase(); 
            break;

        case '/admin/purchases/delete': 
            requireAuth();
            (new AdminController())->deletePurchase(); 
            break;

        // --- CUSTOMERS ---
        case '/customers':
            requireAuth();
            (new AdminController())->customers();
            break;

        // --- REPORTS ---
        case '/reports':
            requireAuth();
            (new AdminController())->reports();
            break;

        case '/admin/reports/print':
            requireAuth();
            (new AdminController())->printReport();
            break;

        // --- SETTINGS ---
        case '/settings':
        case '/admin/settings':
            requireAuth();
            (new AdminController())->settings();
            break;

        // --- BANNER ROUTES ---
        case '/admin/banner/add':
            requireAuth();
            (new AdminController())->addBanner();
            break;

        case '/admin/images/optimize':
            requireAuth();
            (new AdminController())->bulkOptimizeImages();
            break;

        case '/admin/banner/delete':
            requireAuth();
            (new AdminController())->deleteBanner();
            break;

        case '/admin/checkout-settings':
            requireAuth();
            (new AdminController())->checkoutSettings();
            break;

        case '/admin/promotions':
            requireAuth();
            (new AdminController())->promotions();
            break;

        case '/admin/coupons':
            requireAuth();
            (new AdminController())->coupons();
            break;

        // --- SYSTEM USERS ---
        case '/admin/users':
            requireAuth();
            (new AdminController())->systemUsers();
            break;

        // --- ORDER MANAGEMENT ---
        case '/admin/orders':
            requireAuth();
            (new AdminController())->orders();
            break;
            
        case '/api/admin/order-details':
            requireAuth();
            (new AdminController())->apiGetOrderDetails();
            break;

        case '/admin/order/update-status':
            requireAuth();
            (new AdminController())->updateOrderStatus();
            break;
        
        // This is strictly for ORDERS (JSON API)
        case '/admin/bulk-update-status':
            requireAuth();
            (new AdminController())->bulkUpdateStatus();
            break;

        case '/admin/order/complete':
            requireAuth();
            (new AdminController())->completeOrder();
            break;

        case '/admin/order/delete':
            requireAuth();
            (new AdminController())->deleteOrder();
            break;

        case '/admin/order/cancel':
            requireAuth();
            (new AdminController())->cancelOrder();
            break;

        // --- POS EXTRAS ---
        case '/api/admin/customer/add':
        case '/admin/customer/add': 
            requireAuth();
            (new AdminController())->apiAddCustomer();
            break;

        case '/admin/invoice/print':
            requireAuth();
            (new AdminController())->printInvoice();
            break;

        default:
            http_response_code(404);
            echo "<div style='text-align:center; padding:50px; color:white; background:#0f172a; height:100vh; font-family:sans-serif;'>";
            echo "<h1>404 - Page Not Found</h1>";
            echo "<p>The page '$uri' does not exist.</p>";
            echo "<a href='/' style='color:#6366f1'>Go Home</a>";
            echo "</div>";
            break;
    }
} catch (Exception $e) {
    echo "Application Error: " . $e->getMessage();
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
}
?>