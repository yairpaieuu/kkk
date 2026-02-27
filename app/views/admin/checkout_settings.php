<div class="p-4 pb-24">
    <h2 class="text-2xl font-bold text-white mb-6">⚙️ Settings Configuration</h2>

    <?php if(!empty($message)): ?>
        <div class="bg-blue-500/20 text-blue-400 p-3 rounded-lg mb-4 border border-blue-500/30">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- 1. POS PAYMENT METHODS -->
        <div class="glass-panel p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4 flex items-center gap-2 text-lg">
                <span>🖥️</span> POS Payments
            </h3>
            
            <!-- Add POS Payment -->
            <form method="POST" class="mb-6 p-3 bg-gray-800/50 rounded-lg">
                <input type="hidden" name="add_payment" value="1">
                <input type="hidden" name="type" value="pos">
                <div class="space-y-2">
                    <input type="text" name="name" placeholder="Method (e.g. Cash, Card)" required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none">
                    <div class="flex justify-end">
                        <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-1 rounded text-sm font-bold">Add POS Method</button>
                    </div>
                </div>
            </form>

            <ul class="space-y-2 max-h-96 overflow-y-auto pr-1 scrollbar-hide">
                <?php foreach($payment_pos as $p): ?>
                <li class="flex justify-between items-center bg-gray-800/30 p-3 rounded-lg border border-white/5 group hover:border-purple-500/30 transition">
                    <div class="text-white font-bold text-sm"><?= htmlspecialchars($p['name']) ?></div>
                    <form method="POST" onsubmit="return confirm('Delete this method?');">
                        <input type="hidden" name="delete_item" value="1">
                        <input type="hidden" name="table" value="payment_methods">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="text-gray-600 hover:text-red-400 transition text-sm">✕</button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 2. E-COMMERCE PAYMENT METHODS -->
        <div class="glass-panel p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4 flex items-center gap-2 text-lg">
                <span>💳</span> Online Checkout
            </h3>
            
            <!-- Add Checkout Payment -->
            <form method="POST" class="mb-6 p-3 bg-gray-800/50 rounded-lg">
                <input type="hidden" name="add_payment" value="1">
                <input type="hidden" name="type" value="checkout">
                <div class="space-y-2">
                    <input type="text" name="name" placeholder="Bank Name (e.g. KBZ Pay)" required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none">
                    <input type="text" name="acc_name" placeholder="Account Name" required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none">
                    <input type="text" name="number" placeholder="Account No." required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none">
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1 rounded text-sm font-bold">Add Online Method</button>
                    </div>
                </div>
            </form>

            <ul class="space-y-2 max-h-96 overflow-y-auto pr-1 scrollbar-hide">
                <?php foreach($payment_checkout as $p): ?>
                <li class="bg-gray-800/30 p-3 rounded-lg border border-white/5 relative group hover:border-blue-500/30 transition">
                    <div class="text-white font-bold text-sm mb-1"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="flex justify-between items-end">
                        <div class="text-xs text-gray-500">
                            <div><?= htmlspecialchars($p['account_name']) ?></div>
                            <div class="font-mono text-gray-400"><?= htmlspecialchars($p['account_number']) ?></div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Delete this method?');">
                            <input type="hidden" name="delete_item" value="1">
                            <input type="hidden" name="table" value="payment_methods">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="text-gray-600 hover:text-red-400 transition text-sm">✕</button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 3. DELIVERY METHODS -->
        <div class="glass-panel p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4 flex items-center gap-2 text-lg">
                <span>🚚</span> Delivery Areas
            </h3>
            
            <form method="POST" class="mb-6 p-3 bg-gray-800/50 rounded-lg">
                <input type="hidden" name="add_delivery" value="1">
                <div class="space-y-2">
                    <input type="text" name="name" placeholder="City / Area Name" required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none">
                    <div class="flex gap-2">
                        <input type="number" name="cost" placeholder="Cost" required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none">
                        <button type="submit" class="bg-green-600 hover:bg-green-500 text-white px-4 rounded text-sm font-bold">Add</button>
                    </div>
                </div>
            </form>

            <ul class="space-y-2 max-h-96 overflow-y-auto pr-1 scrollbar-hide">
                <?php foreach($delivery as $d): ?>
                <li class="flex justify-between items-center bg-gray-800/30 p-3 rounded-lg border border-white/5 group hover:border-green-500/30 transition">
                    <div><div class="text-gray-200 text-sm font-medium"><?= htmlspecialchars($d['name']) ?></div></div>
                    <div class="flex items-center gap-3">
                        <span class="text-green-400 font-bold text-sm"><?= number_format($d['cost']) ?></span>
                        <form method="POST" onsubmit="return confirm('Delete?');">
                            <input type="hidden" name="delete_item" value="1">
                            <input type="hidden" name="table" value="delivery_methods">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button class="text-gray-600 hover:text-red-400 transition">✕</button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- 4. COUPON CODES -->
        <div class="glass-panel p-5 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4 flex items-center gap-2 text-lg">
                <span>🎟️</span> Coupons
            </h3>
            
            <form method="POST" class="mb-6 p-3 bg-gray-800/50 rounded-lg">
                <input type="hidden" name="add_coupon" value="1">
                <div class="space-y-2">
                    <input type="text" name="code" placeholder="Code (e.g. SAVE10)" required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none uppercase font-mono">
                    <div class="flex gap-2">
                        <input type="number" name="amount" placeholder="Amount" required class="w-full bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm focus:border-blue-500 outline-none">
                        <button type="submit" class="bg-yellow-600 hover:bg-yellow-500 text-white px-4 rounded text-sm font-bold">Add</button>
                    </div>
                </div>
            </form>

            <ul class="space-y-2 max-h-96 overflow-y-auto pr-1 scrollbar-hide">
                <?php foreach($coupons as $c): ?>
                <li class="flex justify-between items-center bg-gray-800/30 p-3 rounded-lg border border-white/5 group hover:border-yellow-500/30 transition">
                    <div><div class="text-yellow-400 font-mono font-bold text-sm tracking-wide"><?= htmlspecialchars($c['code']) ?></div></div>
                    <div class="flex items-center gap-3">
                        <span class="text-white font-bold text-sm">-<?= number_format($c['discount_amount']) ?></span>
                        <form method="POST" onsubmit="return confirm('Delete?');">
                            <input type="hidden" name="delete_item" value="1">
                            <input type="hidden" name="table" value="coupons">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button class="text-gray-600 hover:text-red-400 transition">✕</button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </div>
</div>