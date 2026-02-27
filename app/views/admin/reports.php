<div class="p-6 pb-24">
    
    <!-- HEADER & ACTIONS -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <h2 class="text-2xl font-bold text-white flex items-center gap-2">
            📊 Business Reports
        </h2>
        
        <div class="flex flex-wrap gap-2">
            <!-- DATE FILTER FORM -->
            <form class="flex bg-gray-800 p-1 rounded-lg border border-white/10">
                <input type="date" name="start_date" value="<?= $dateRange['start'] ?>" class="bg-transparent text-white text-xs px-3 py-2 outline-none border-r border-gray-700">
                <input type="date" name="end_date" value="<?= $dateRange['end'] ?>" class="bg-transparent text-white text-xs px-3 py-2 outline-none">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-md transition shadow-lg">Filter</button>
            </form>

            <!-- PRINT BUTTONS (NEW) -->
            <div class="flex bg-gray-800 p-1 rounded-lg border border-white/10">
                <button onclick="printReport('sales')" class="px-3 py-2 text-gray-400 hover:text-white hover:bg-white/10 rounded transition" title="Print Sales Report">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Sales
                </button>
                <button onclick="printReport('inventory')" class="px-3 py-2 text-gray-400 hover:text-white hover:bg-white/10 rounded transition" title="Print Inventory List">
                    <i class="fa-solid fa-boxes-stacked"></i> Stock
                </button>
                <button onclick="printReport('purchase')" class="px-3 py-2 text-gray-400 hover:text-white hover:bg-white/10 rounded transition" title="Print Purchase Report">
                    <i class="fa-solid fa-truck-ramp-box"></i> Purchase
                </button>
            </div>
        </div>
    </div>

    <!-- ... (Keep the rest of your Financial Cards, Top Products, etc. exactly as they are) ... -->
    <!-- 1. FINANCIAL SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- ... existing content ... -->
        <!-- Total Income -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl text-green-500"><i class="fa-solid fa-sack-dollar"></i></div>
            <h3 class="text-gray-400 text-xs font-bold uppercase mb-1">Total Income</h3>
            <div class="text-2xl font-bold text-white"><?= number_format($financials['income']) ?> <span class="text-sm font-normal text-gray-500">MMK</span></div>
            <div class="text-[10px] text-green-400 mt-2 flex items-center gap-1"><i class="fa-solid fa-arrow-trend-up"></i> <?= number_format($financials['transactions']) ?> Transactions</div>
        </div>

        <!-- Total Expenses -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl text-red-500"><i class="fa-solid fa-wallet"></i></div>
            <h3 class="text-gray-400 text-xs font-bold uppercase mb-1">Total Expenses</h3>
            <div class="text-2xl font-bold text-white"><?= number_format($financials['expense']) ?> <span class="text-sm font-normal text-gray-500">MMK</span></div>
            <div class="text-[10px] text-red-400 mt-2">Operational Costs</div>
        </div>

        <!-- Total Purchases (Stock) -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl text-orange-500"><i class="fa-solid fa-truck-ramp-box"></i></div>
            <h3 class="text-gray-400 text-xs font-bold uppercase mb-1">Stock Purchases</h3>
            <div class="text-2xl font-bold text-white"><?= number_format($financials['purchase']) ?> <span class="text-sm font-normal text-gray-500">MMK</span></div>
            <div class="text-[10px] text-orange-400 mt-2">Inventory Restock</div>
        </div>

        <!-- Net Profit -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 relative overflow-hidden group">
            <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition text-6xl text-blue-500"><i class="fa-solid fa-chart-pie"></i></div>
            <h3 class="text-gray-400 text-xs font-bold uppercase mb-1">Net Profit</h3>
            <div class="text-2xl font-bold <?= $financials['net_profit'] >= 0 ? 'text-blue-400' : 'text-red-500' ?>">
                <?= number_format($financials['net_profit']) ?> <span class="text-sm font-normal text-gray-500">MMK</span>
            </div>
            <div class="text-[10px] text-gray-500 mt-2">(Income - Expenses - Purchases)</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 2. TOP PRODUCTS -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 lg:col-span-1">
            <h3 class="text-gray-400 text-sm font-bold uppercase mb-6 flex items-center gap-2"><i class="fa-solid fa-trophy text-yellow-500"></i> Top Best Sellers</h3>
            <ul class="space-y-4">
                <?php if(empty($topProducts)): ?>
                    <li class="text-center text-gray-600 py-4 text-xs">No sales data in this period.</li>
                <?php else: ?>
                    <?php foreach($topProducts as $idx => $p): ?>
                    <li class="flex items-center justify-between group cursor-default">
                        <div class="flex items-center gap-3 w-full">
                            <span class="w-6 h-6 rounded-full <?= $idx < 3 ? 'bg-yellow-500/20 text-yellow-500' : 'bg-gray-700 text-gray-400' ?> flex items-center justify-center text-xs font-bold flex-shrink-0"><?= $idx + 1 ?></span>
                            <span class="text-gray-300 text-sm truncate flex-1 group-hover:text-white transition">
                                <?= htmlspecialchars($p['name'] ?? 'Unknown Product') ?>
                            </span>
                        </div>
                        <span class="text-blue-400 font-bold text-xs bg-blue-500/10 px-2 py-1 rounded ml-2 whitespace-nowrap"><?= $p['sold'] ?> units</span>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- 3. TOP CUSTOMERS -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 lg:col-span-2">
            <h3 class="text-gray-400 text-sm font-bold uppercase mb-6 flex items-center gap-2"><i class="fa-solid fa-users text-purple-500"></i> Top Spenders</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-gray-400 text-xs">
                    <thead class="bg-gray-800 text-gray-500 uppercase">
                        <tr>
                            <th class="p-3 w-10">#</th>
                            <th class="p-3">Customer Name</th>
                            <th class="p-3 text-center">Visits</th>
                            <th class="p-3 text-right">Total Spend</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php if(empty($topCustomers)): ?>
                            <tr><td colspan="4" class="p-6 text-center text-gray-600">No customer data available.</td></tr>
                        <?php else: ?>
                            <?php foreach($topCustomers as $idx => $c): ?>
                            <tr class="hover:bg-white/5 transition">
                                <td class="p-3 font-bold text-gray-600"><?= $idx + 1 ?></td>
                                <td class="p-3 font-bold text-white">
                                    <?= htmlspecialchars($c['name'] ?? 'Guest/Walk-in') ?>
                                </td>
                                <td class="p-3 text-center"><?= $c['visits'] ?></td>
                                <td class="p-3 text-right text-green-400 font-mono font-bold"><?= number_format($c['total_spend']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 4. LOW STOCK & CHART ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        
        <!-- Low Stock Alerts -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 lg:col-span-1">
            <h3 class="text-gray-400 text-sm font-bold uppercase mb-6 flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation text-red-500"></i> Low Stock Alerts</h3>
            <div class="space-y-3">
                <?php if(empty($lowStockItems)): ?>
                    <div class="text-center text-green-500 py-4 text-xs flex flex-col items-center gap-2">
                        <i class="fa-solid fa-check-circle text-2xl"></i> Stock levels are healthy.
                    </div>
                <?php else: ?>
                    <?php foreach($lowStockItems as $item): ?>
                    <div class="flex justify-between items-center bg-red-500/5 border border-red-500/10 p-3 rounded-lg">
                        <div>
                            <div class="text-white text-xs font-bold"><?= htmlspecialchars($item['name'] ?? 'Unknown Item') ?></div>
                            <div class="text-[10px] text-gray-500"><i class="fa-solid fa-warehouse"></i> In Inventory</div>
                        </div>
                        <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg shadow-red-500/20">
                            <?= $item['stock'] ?> Left
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sales Chart -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 lg:col-span-2 flex flex-col">
            <h3 class="text-gray-400 text-sm font-bold uppercase mb-6">Sales Trend (Selected Range)</h3>
            
            <div class="flex-1 flex items-end justify-between gap-2 h-48 relative px-2">
                <?php if(empty($salesData)): ?>
                    <div class="absolute inset-0 flex items-center justify-center text-gray-600 text-xs">No chart data available</div>
                <?php else: ?>
                    <?php 
                        $maxSale = 0;
                        foreach($salesData as $d) if($d['total'] > $maxSale) $maxSale = $d['total'];
                        if($maxSale == 0) $maxSale = 1; 
                    ?>
                    
                    <?php foreach($salesData as $d): ?>
                    <?php $height = ($d['total'] / $maxSale) * 100; ?>
                    <div class="flex flex-col items-center flex-1 group relative h-full justify-end">
                        <!-- Tooltip -->
                        <div class="absolute -top-8 bg-white text-black text-[10px] font-bold px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap z-10 shadow-xl pointer-events-none">
                            <?= number_format($d['total']) ?>
                        </div>
                        <!-- Bar -->
                        <div class="w-full max-w-[30px] bg-blue-600 hover:bg-blue-400 rounded-t transition-all duration-500 relative" style="height: <?= $height ?>%"></div>
                        <!-- Date -->
                        <span class="text-[9px] text-gray-500 mt-2 truncate w-full text-center border-t border-white/5 pt-1"><?= date('d/m', strtotime($d['date'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
    function printReport(type) {
        // Get current date range from the form inputs
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;
        
        // Open print window
        const url = `/admin/reports/print?type=${type}&start_date=${startDate}&end_date=${endDate}`;
        window.open(url, '_blank', 'width=1000,height=800');
    }
</script>