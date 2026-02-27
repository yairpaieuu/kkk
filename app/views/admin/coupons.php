<div class="p-6 pb-24">
    <h2 class="text-2xl font-bold text-white mb-6">🎟️ Advanced Coupon Manager</h2>

    <?php if(!empty($message)): ?>
        <div class="bg-blue-500/20 text-blue-400 p-3 rounded-lg mb-6 border border-blue-500/30"><?= $message ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- CREATE FORM -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 h-fit lg:col-span-1">
            <h3 class="text-white font-bold mb-4 border-b border-white/10 pb-2">Create New Coupon</h3>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="add_coupon" value="1">
                
                <div>
                    <label class="text-gray-400 text-xs uppercase font-bold">Coupon Code</label>
                    <input type="text" name="code" placeholder="e.g. SUMMER2025" required class="w-full bg-gray-800 text-white p-3 rounded font-mono text-lg tracking-widest border border-gray-700 focus:border-blue-500 uppercase">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-gray-400 text-xs">Usage Limit</label>
                        <input type="number" name="usage_limit" placeholder="0 = Unlimited" class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700">
                    </div>
                    <div>
                        <label class="text-gray-400 text-xs">Start Date</label>
                        <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700">
                    </div>
                    <div>
                        <label class="text-gray-400 text-xs">End Date</label>
                        <input type="date" name="end_date" class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700">
                    </div>
                </div>

                <!-- TYPE SWITCHER -->
                <div>
                    <label class="text-gray-400 text-xs mb-2 block">Coupon Strategy</label>
                    <div class="flex bg-gray-800 rounded p-1">
                        <button type="button" onclick="setType('standard')" id="btn-standard" class="flex-1 py-2 text-sm rounded bg-blue-600 text-white font-bold">Standard</button>
                        <button type="button" onclick="setType('tiered')" id="btn-tiered" class="flex-1 py-2 text-sm rounded text-gray-400 hover:text-white">Mix Tiers</button>
                    </div>
                    <input type="hidden" name="type" id="couponType" value="standard">
                </div>

                <!-- STANDARD SECTION -->
                <div id="standardSection" class="space-y-3 bg-gray-800/50 p-3 rounded border border-white/5">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-gray-400 text-xs">Discount Type</label>
                            <select name="discount_type" class="w-full bg-gray-900 text-white p-2 rounded text-sm border border-gray-700">
                                <option value="fixed">Fixed Amount</option>
                                <option value="percent">Percentage (%)</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-gray-400 text-xs">Value</label>
                            <input type="number" name="value" placeholder="e.g. 5000" class="w-full bg-gray-900 text-white p-2 rounded text-sm border border-gray-700">
                        </div>
                    </div>
                    <div>
                        <label class="text-gray-400 text-xs">Min Spend Requirement</label>
                        <input type="number" name="min_spend" placeholder="0" class="w-full bg-gray-900 text-white p-2 rounded text-sm border border-gray-700">
                    </div>
                </div>

                <!-- TIERED SECTION -->
                <div id="tieredSection" class="hidden space-y-3">
                    <p class="text-xs text-blue-300 bg-blue-900/20 p-2 rounded">
                        Define spending levels. The system will automatically apply the best discount based on cart total.
                    </p>
                    
                    <div id="tiersContainer" class="space-y-2">
                        <!-- Tiers injected via JS -->
                    </div>
                    
                    <button type="button" onclick="addTier()" class="w-full py-2 border border-dashed border-gray-500 text-gray-400 text-xs rounded hover:border-white hover:text-white transition">+ Add Tier Level</button>
                </div>

                <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded shadow-lg mt-4">Save Coupon</button>
            </form>
        </div>

        <!-- LIST -->
        <div class="lg:col-span-2 glass-panel p-6 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4">Active Coupons</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-gray-400 text-sm">
                    <thead class="bg-gray-800 text-gray-200 uppercase">
                        <tr>
                            <th class="p-3">Code</th>
                            <th class="p-3">Logic</th>
                            <th class="p-3">Details</th>
                            <th class="p-3">Usage</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach($coupons as $c): ?>
                        <tr class="hover:bg-white/5">
                            <td class="p-3 font-mono font-bold text-white text-lg"><?= htmlspecialchars($c['code']) ?></td>
                            <td class="p-3">
                                <?php if($c['type'] == 'standard'): ?>
                                    <span class="bg-blue-900 text-blue-300 px-2 py-1 rounded text-xs">Standard</span>
                                <?php else: ?>
                                    <span class="bg-purple-900 text-purple-300 px-2 py-1 rounded text-xs">Tiered</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?php if($c['type'] == 'standard'): ?>
                                    <?= $c['discount_type'] == 'fixed' ? number_format($c['value']) . ' MMK' : $c['value'] . '%' ?> Off
                                    <div class="text-xs text-gray-500">Min: <?= number_format($c['min_spend']) ?></div>
                                <?php else: ?>
                                    <?php 
                                        $tiers = json_decode($c['tier_data'], true);
                                        if($tiers) {
                                            foreach($tiers as $t) {
                                                echo "<div class='text-xs'>Spend " . number_format($t['min']) . " 👉 " . ($t['type']=='fixed'? number_format($t['value']) : $t['value'].'%') . " Off</div>";
                                            }
                                        }
                                    ?>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-xs">
                                <?= $c['usage_count'] ?> / <?= $c['usage_limit'] == 0 ? '∞' : $c['usage_limit'] ?>
                            </td>
                            <td class="p-3 text-right">
                                <form method="POST" onsubmit="return confirm('Delete this coupon?');">
                                    <input type="hidden" name="delete_coupon" value="1">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="text-red-400 hover:text-white">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function setType(type) {
    document.getElementById('couponType').value = type;
    if(type === 'standard') {
        document.getElementById('standardSection').classList.remove('hidden');
        document.getElementById('tieredSection').classList.add('hidden');
        document.getElementById('btn-standard').className = "flex-1 py-2 text-sm rounded bg-blue-600 text-white font-bold transition";
        document.getElementById('btn-tiered').className = "flex-1 py-2 text-sm rounded text-gray-400 hover:text-white transition";
    } else {
        document.getElementById('standardSection').classList.add('hidden');
        document.getElementById('tieredSection').classList.remove('hidden');
        document.getElementById('btn-tiered').className = "flex-1 py-2 text-sm rounded bg-purple-600 text-white font-bold transition";
        document.getElementById('btn-standard').className = "flex-1 py-2 text-sm rounded text-gray-400 hover:text-white transition";
        if(document.getElementById('tiersContainer').children.length === 0) addTier();
    }
}

function addTier() {
    const container = document.getElementById('tiersContainer');
    const index = container.children.length;
    const html = `
    <div class="flex gap-2 items-center bg-gray-800 p-2 rounded border border-gray-700 tier-row">
        <div class="flex-1">
            <input type="number" name="tiers[${index}][min]" placeholder="Min Spend" required class="w-full bg-gray-900 text-white p-1 rounded text-xs border border-gray-600">
        </div>
        <div class="flex-1">
            <input type="number" name="tiers[${index}][value]" placeholder="Discount" required class="w-full bg-gray-900 text-white p-1 rounded text-xs border border-gray-600">
        </div>
        <div class="w-20">
            <select name="tiers[${index}][type]" class="w-full bg-gray-900 text-white p-1 rounded text-xs border border-gray-600">
                <option value="fixed">Fixed</option>
                <option value="percent">%</option>
            </select>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300">×</button>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}
</script>