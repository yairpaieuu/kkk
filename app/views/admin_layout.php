<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #e2e8f0; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .sidebar-link { display: flex; items-center; gap: 12px; padding: 12px 16px; border-radius: 8px; transition: all 0.2s; color: #94a3b8; font-size: 0.9rem; font-weight: 500; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }
        .sidebar-link i { width: 20px; text-align: center; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#1e293b] border-r border-white/5 flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-white/5">
            <div class="w-8 h-8 rounded bg-blue-600 flex items-center justify-center text-white font-bold mr-3">A</div>
            <span class="font-bold text-lg tracking-tight text-white">Admin Panel</span>
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            <?php 
            if (session_status() === PHP_SESSION_NONE) session_start();
            $uri = $_SERVER['REQUEST_URI'];
            $role = $_SESSION['user_role'] ?? '';

            $menu = [
                ['url' => '/admin', 'icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'roles' => ['admin', 'sales', 'account']],
                ['url' => '/products', 'icon' => 'fa-box-open', 'label' => 'Products', 'roles' => ['admin', 'account']],
                ['url' => '/admin/categories', 'icon' => 'fa-layer-group', 'label' => 'Categories', 'roles' => ['admin', 'account']],
                ['url' => '/pos', 'icon' => 'fa-cash-register', 'label' => 'POS Terminal', 'roles' => ['admin', 'sales']],
                ['url' => '/admin/orders', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Orders', 'roles' => ['admin', 'sales', 'account']],
                ['url' => '/sales', 'icon' => 'fa-chart-line', 'label' => 'Sales Record', 'roles' => ['admin', 'sales', 'account']],
                
                // --- Purchase Links ---
                ['url' => '/purchase', 'icon' => 'fa-cart-plus', 'label' => 'New Purchase', 'roles' => ['admin', 'account']],
                ['url' => '/admin/purchases', 'icon' => 'fa-clock-rotate-left', 'label' => 'Purchase History', 'roles' => ['admin', 'account']],
                
                ['url' => '/admin/suppliers', 'icon' => 'fa-truck-field', 'label' => 'Suppliers', 'roles' => ['admin', 'account']],
                ['url' => '/admin/expenses', 'icon' => 'fa-wallet', 'label' => 'Expenses', 'roles' => ['admin', 'sales', 'account']],
                ['url' => '/customers', 'icon' => 'fa-users', 'label' => 'Customers', 'roles' => ['admin', 'sales']],
                
                ['url' => '/reports', 'icon' => 'fa-chart-pie', 'label' => 'Reports', 'roles' => ['admin']], 
                
                // --- Marketing ---
                ['url' => '/admin/promotions', 'icon' => 'fa-tags', 'label' => 'Promotions', 'roles' => ['admin']],
                
                // *** NEW COUPONS LINK ***
                ['url' => '/admin/coupons', 'icon' => 'fa-ticket', 'label' => 'Coupons', 'roles' => ['admin', 'sales']],
                
                ['url' => '/admin/users', 'icon' => 'fa-user-shield', 'label' => 'System Users', 'roles' => ['admin']],
                ['url' => '/admin/checkout-settings', 'icon' => 'fa-credit-card', 'label' => 'Checkout Config', 'roles' => ['admin']],
                ['url' => '/admin/settings', 'icon' => 'fa-gear', 'label' => 'Settings', 'roles' => ['admin']],
            ];

            foreach($menu as $item): 
                // Skip if user role is not allowed
                if (!in_array($role, $item['roles'])) continue;

                $active = ($uri === $item['url'] || ($item['url'] !== '/admin' && strpos($uri, $item['url']) === 0)) ? 'active' : '';
            ?>
                <a href="<?= $item['url'] ?>" class="sidebar-link <?= $active ?>">
                    <i class="fa-solid <?= $item['icon'] ?>"></i>
                    <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-white/5">
            <div class="px-4 py-2 mb-2 text-xs text-gray-500 uppercase font-bold text-center">
                Logged in as <span class="text-blue-400"><?= ucfirst($role) ?></span>
            </div>
            <a href="/logout" class="sidebar-link hover:!bg-red-500/10 hover:!text-red-400">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="md:hidden h-16 bg-[#1e293b] border-b border-white/5 flex items-center justify-between px-4">
            <span class="font-bold">Admin Panel</span>
            <button onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('absolute'); document.querySelector('aside').classList.toggle('z-50'); document.querySelector('aside').classList.toggle('h-full');" class="text-gray-400">
                <i class="fa-solid fa-bars text-xl"></i>
            </button>
        </header>

        <main class="flex-1 overflow-y-auto bg-[#0f172a] p-4 md:p-8">
            <?php if (file_exists($childView)) require_once $childView; ?>
        </main>
    </div>

</body>
</html>