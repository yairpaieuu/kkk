<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KKK LED SHOP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #e2e8f0; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="min-h-screen">

    <!-- NAVBAR -->
    <nav class="bg-gray-900/80 backdrop-blur-md sticky top-0 z-50 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="font-bold text-xl tracking-wider text-blue-500">KKK LED SHOP</a>
                <div class="flex items-center gap-4">
                    <a href="/" class="text-gray-300 hover:text-white text-sm">Home</a>
                    <a href="/shop" class="text-gray-300 hover:text-white text-sm">Shop</a>
                    <a href="/logout" class="text-red-400 hover:text-red-300 text-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">
        
        <!-- 1. USER INFO CARD -->
        <div class="glass-panel rounded-2xl p-6 mb-8 flex flex-col md:flex-row items-center gap-6">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-3xl font-bold text-white shadow-lg">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <div class="flex-1 text-center md:text-left">
                <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($user['username']) ?></h1>
                <p class="text-gray-400 text-sm mb-4"><?= htmlspecialchars($user['email']) ?></p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-black/20 p-4 rounded-xl border border-white/5">
                    <div>
                        <span class="text-gray-500 block text-xs uppercase font-bold">Phone</span>
                        <span class="text-gray-200"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not set' ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs uppercase font-bold">Address</span>
                        <span class="text-gray-200"><?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not set' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. ORDER HISTORY -->
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-blue-500"></i> Order History
        </h2>

        <?php if(empty($orders)): ?>
            <div class="glass-panel p-12 text-center rounded-2xl">
                <div class="text-5xl mb-4">🛒</div>
                <h3 class="text-lg font-bold text-white">No orders yet</h3>
                <p class="text-gray-400 mb-6">Looks like you haven't bought anything yet.</p>
                <a href="/shop" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2 rounded-full font-bold transition">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach($orders as $order): ?>
                    <div class="glass-panel p-5 rounded-xl border border-white/5 hover:border-white/10 transition group">
                        
                        <!-- Header: ID, Date, Status -->
                        <div class="flex flex-wrap justify-between items-start gap-4 mb-4 pb-4 border-b border-white/5">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="text-blue-400 font-mono font-bold text-lg">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                    <span class="text-xs text-gray-500"><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></span>
                                </div>
                                <div class="text-sm text-gray-300 mt-1">
                                    Total: <span class="font-bold text-white"><?= number_format($order['total_amount']) ?> MMK</span>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <?php 
                                $statusColors = [
                                    'pending' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
                                    'approved' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                    'preparing' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                    'delivering' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                    'completed' => 'bg-green-500/10 text-green-400 border-green-500/20',
                                    'rejected' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                ];
                                $cls = $statusColors[$order['status']] ?? 'bg-gray-700 text-gray-300';
                            ?>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-lg text-xs uppercase font-bold border <?= $cls ?>">
                                    <?= $order['status'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- 3. DIGITAL DOWNLOADS SECTION (New) -->
                        <?php if(in_array($order['status'], ['approved', 'completed']) && !empty($order['items'])): ?>
                            <div class="mb-4">
                                <?php 
                                    $hasDownloads = false;
                                    foreach($order['items'] as $item) {
                                        if($item['type'] === 'digital' && !empty($item['download_link'])) {
                                            $hasDownloads = true;
                                            break;
                                        }
                                    }
                                ?>
                                <?php if($hasDownloads): ?>
                                    <h4 class="text-xs font-bold text-green-400 uppercase mb-2 flex items-center gap-2">
                                        <i class="fa-solid fa-download"></i> Digital Downloads
                                    </h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <?php foreach($order['items'] as $item): ?>
                                            <?php if($item['type'] === 'digital' && !empty($item['download_link'])): ?>
                                                <a href="<?= htmlspecialchars($item['download_link']) ?>" target="_blank" class="flex justify-between items-center bg-green-900/20 hover:bg-green-900/40 border border-green-500/30 p-3 rounded-lg group/dl transition">
                                                    <span class="text-sm text-gray-200 font-medium"><?= htmlspecialchars($item['name']) ?></span>
                                                    <i class="fa-solid fa-cloud-arrow-down text-green-400 group-hover/dl:scale-110 transition"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Documents & Proofs -->
                        <div class="flex flex-wrap gap-3">
                            
                            <!-- 1. My Payment Slip -->
                            <?php if(!empty($order['payment_receipt'])): ?>
                                <a href="/<?= $order['payment_receipt'] ?>" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-xs font-medium text-gray-300 transition border border-white/5">
                                    <i class="fa-solid fa-receipt text-blue-400"></i> My Payment Slip
                                </a>
                            <?php endif; ?>

                            <!-- 2. ADMIN DELIVERY PROOF -->
                            <?php if(!empty($order['delivery_proof'])): ?>
                                <a href="/<?= $order['delivery_proof'] ?>" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-green-900/20 hover:bg-green-900/30 rounded-lg text-xs font-bold text-green-400 transition border border-green-500/30 animate-pulse">
                                    <i class="fa-solid fa-box-open"></i> View Delivery Proof
                                </a>
                            <?php elseif($order['status'] === 'completed'): ?>
                                <span class="flex items-center gap-2 px-4 py-2 bg-gray-800/50 rounded-lg text-xs text-gray-500 border border-white/5 cursor-not-allowed">
                                    <i class="fa-solid fa-box"></i> No Delivery Photo
                                </span>
                            <?php endif; ?>

                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- DEVELOPER CREDIT -->
        <div class="mt-12 text-center pb-8 border-t border-white/5 pt-8">
            <p class="text-[10px] text-gray-600 uppercase tracking-widest">
                Developed By 
                <a href="https://areativedigital.com/" target="_blank" class="text-gray-500 hover:text-blue-400 transition font-bold">Areative</a>
            </p>
        </div>

    </div>

</body>
</html>