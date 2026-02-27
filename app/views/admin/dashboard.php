<div class="space-y-6">
    
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white">Dashboard Overview</h1>
            <p class="text-gray-400 text-sm">Welcome back, here's what's happening today.</p>
        </div>
        <a href="/pos" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-medium transition shadow-lg shadow-blue-500/20 flex items-center gap-2">
            <i class="fa-solid fa-cash-register"></i> Open POS
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Sales Today -->
        <div class="glass-panel p-5 rounded-xl border-l-4 border-emerald-500 relative overflow-hidden group">
            <div class="flex justify-between items-start z-10 relative">
                <div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Sales Today</p>
                    <h3 class="text-2xl font-bold text-white mt-1"><?= number_format($salesToday) ?> Ks</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-sack-dollar text-lg"></i>
                </div>
            </div>
            <div class="text-emerald-400 text-xs mt-3 flex items-center gap-1">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <span>Updated just now</span>
            </div>
        </div>

        <!-- Monthly Sales -->
        <div class="glass-panel p-5 rounded-xl border-l-4 border-blue-500 relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">This Month</p>
                    <h3 class="text-2xl font-bold text-white mt-1"><?= number_format($salesMonth) ?> Ks</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-check text-lg"></i>
                </div>
            </div>
            <p class="text-gray-500 text-xs mt-3">Total revenue this month</p>
        </div>

        <!-- Pending Orders -->
        <div class="glass-panel p-5 rounded-xl border-l-4 border-orange-500 relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Pending Orders</p>
                    <h3 class="text-2xl font-bold text-white mt-1"><?= $ordersPending ?></h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-orange-500/20 text-orange-400 flex items-center justify-center">
                    <i class="fa-solid fa-clipboard-list text-lg"></i>
                </div>
            </div>
            <a href="/admin/orders" class="text-orange-400 text-xs mt-3 block hover:underline">View pending orders &rarr;</a>
        </div>

        <!-- Low Stock Alert -->
        <div class="glass-panel p-5 rounded-xl border-l-4 border-red-500 relative overflow-hidden">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-wider">Low Stock</p>
                    <h3 class="text-2xl font-bold text-white mt-1"><?= $lowStock ?></h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
            </div>
            <a href="/products" class="text-red-400 text-xs mt-3 block hover:underline">Check inventory &rarr;</a>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Chart -->
        <div class="lg:col-span-2 glass-panel p-6 rounded-xl">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <i class="fa-solid fa-chart-area text-blue-500"></i> Sales Overview
            </h3>
            <div class="relative h-64 w-full">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Right Column: Recent Activity -->
        <div class="glass-panel p-0 rounded-xl overflow-hidden flex flex-col">
            <div class="p-4 border-b border-white/5 bg-white/5">
                <h3 class="text-md font-bold text-white">Recent Orders</h3>
            </div>
            <div class="flex-1 overflow-y-auto max-h-[300px] p-2">
                <?php if(empty($recentOrders)): ?>
                    <div class="text-center py-8 text-gray-500">No recent orders</div>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach($recentOrders as $order): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 hover:bg-white/10 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs font-bold">
                                    #<?= $order['id'] ?>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-white"><?= $order['customer_name'] ?: 'Guest' ?></div>
                                    <div class="text-[10px] text-gray-400"><?= date('M d, H:i', strtotime($order['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-emerald-400"><?= number_format($order['total_amount']) ?></div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded uppercase font-bold
                                    <?php 
                                        if($order['status']=='completed') echo 'bg-green-500/20 text-green-400';
                                        elseif($order['status']=='pending') echo 'bg-yellow-500/20 text-yellow-400';
                                        else echo 'bg-gray-500/20 text-gray-400';
                                    ?>">
                                    <?= $order['status'] ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-3 border-t border-white/5 text-center">
                <a href="/admin/orders" class="text-xs text-blue-400 hover:text-white transition">View All Orders</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple Chart.js Configuration
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            // Mock labels for the last 6 months - you can make this dynamic later
            labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Today'],
            datasets: [{
                label: 'Revenue (Ks)',
                // Just using the monthly total as a flat line for demo, strictly visual until dynamic data is passed
                data: [
                    <?= $salesMonth * 0.2 ?>, 
                    <?= $salesMonth * 0.5 ?>, 
                    <?= $salesMonth * 0.3 ?>, 
                    <?= $salesMonth * 0.8 ?>, 
                    <?= $salesMonth * 0.6 ?>, 
                    <?= $salesMonth ?>
                ],
                borderColor: '#3b82f6',
                backgroundColor: gradient,
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#1e293b',
                pointBorderColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
</script>