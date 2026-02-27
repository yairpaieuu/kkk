<div class="p-4 pb-24">
    <h2 class="text-2xl font-bold text-white mb-6">🔥 Promotion Management</h2>

    <?php if(!empty($message)): ?>
        <div class="bg-blue-500/20 text-blue-400 p-3 rounded-lg mb-4 border border-blue-500/30"><?= $message ?></div>
    <?php endif; ?>

    <!-- FREE SHIPPING -->
    <div class="glass-panel p-5 rounded-xl border border-white/10 mb-6 flex items-center justify-between bg-blue-900/10">
        <div>
            <h3 class="text-white font-bold text-lg flex items-center gap-2"><span>🚀</span> Free Shipping Threshold</h3>
            <p class="text-gray-400 text-xs">Orders above this amount get free delivery.</p>
        </div>
        <form method="POST" class="flex gap-2">
            <input type="hidden" name="update_shipping" value="1">
            <input type="number" name="min_amount" value="<?= $free_shipping_min ?? 0 ?>" class="bg-gray-900 border border-gray-700 text-white p-2 rounded text-sm w-32 focus:border-green-500 outline-none">
            <button type="submit" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded text-sm font-bold">Save</button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- CREATE FORM -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 h-fit">
            <h3 class="text-white font-bold mb-4">Create New Offer</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="add_promotion" value="1">
                
                <div>
                    <label class="text-gray-400 text-xs">Promotion Name</label>
                    <input type="text" name="name" placeholder="e.g. Flash Sale" required class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700 focus:border-blue-500">
                </div>

                <div>
                    <label class="text-gray-400 text-xs">Discount Type</label>
                    <select name="type" id="promoType" onchange="updateLabels()" class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700 focus:border-blue-500">
                        <option value="percentage">Percentage Discount (%)</option>
                        <option value="fixed_amount">Fixed Amount Off (Currency)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-gray-400 text-xs" id="valueLabel">Value</label>
                        <input type="number" step="0.01" name="value" placeholder="10" required class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-gray-400 text-xs">Min Spend (0 for none)</label>
                        <input type="number" step="0.01" name="requirement" placeholder="0" class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700 focus:border-blue-500">
                    </div>
                </div>

                <!-- NEW: Apply To Logic -->
                <div>
                    <label class="text-gray-400 text-xs">Apply To</label>
                    <select name="apply_to" id="applyTo" onchange="toggleScopeSelect()" class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700 focus:border-blue-500">
                        <option value="all">All Products</option>
                        <option value="selected_products">Specific Products</option>
                        <option value="selected_categories">Specific Categories</option>
                    </select>
                </div>

                <!-- Product Select -->
                <div id="productSelectContainer" class="hidden">
                    <label class="text-gray-400 text-xs">Select Products (Hold Ctrl/Cmd for multiple)</label>
                    <select name="products[]" multiple class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 h-32 border border-gray-700 focus:border-blue-500">
                        <?php foreach($products as $prod): ?>
                            <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category Select (NEW) -->
                <div id="categorySelectContainer" class="hidden">
                    <label class="text-gray-400 text-xs">Select Categories (Hold Ctrl/Cmd for multiple)</label>
                    <select name="categories[]" multiple class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 h-32 border border-gray-700 focus:border-blue-500">
                        <?php 
                        // Assuming $categories is passed from controller along with products
                        if(isset($categories)): foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-gray-400 text-xs">Start Date</label>
                        <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="text-gray-400 text-xs">End Date</label>
                        <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required class="w-full bg-gray-800 text-white p-2 rounded text-sm mt-1 border border-gray-700 focus:border-blue-500">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded mt-2">Create Promotion</button>
            </form>
        </div>

        <!-- LIST -->
        <div class="lg:col-span-2 glass-panel p-6 rounded-xl border border-white/10">
            <h3 class="text-white font-bold mb-4">Active Promotions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-gray-400 text-sm">
                    <thead class="bg-gray-800 text-gray-200 uppercase">
                        <tr>
                            <th class="p-3">Name</th>
                            <th class="p-3">Discount</th>
                            <th class="p-3">Scope</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach($promotions as $p): ?>
                        <tr class="hover:bg-white/5">
                            <td class="p-3 font-bold text-white"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="p-3">
                                <?php if($p['type'] == 'percentage'): ?>
                                    <span class="text-blue-400"><?= $p['value'] ?>% OFF</span>
                                <?php else: ?>
                                    <span class="text-green-400"><?= number_format($p['value']) ?> OFF</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-xs">
                                <?php 
                                    if (!empty($p['applicable_categories'])) echo '<span class="text-purple-400">By Category</span>';
                                    elseif (!empty($p['applicable_products'])) echo '<span class="text-yellow-400">Selected Items</span>';
                                    else echo 'All Products';
                                ?>
                            </td>
                            <td class="p-3">
                                <form method="POST">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button class="px-2 py-1 rounded text-xs font-bold <?= $p['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' ?>">
                                        <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="p-3 text-right">
                                <form method="POST" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="delete_promotion" value="1">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
    function updateLabels() {
        const type = document.getElementById('promoType').value;
        const valLabel = document.getElementById('valueLabel');
        valLabel.innerText = type === 'percentage' ? 'Discount %' : 'Discount Amount';
    }
    
    function toggleScopeSelect() {
        const type = document.getElementById('applyTo').value;
        const prodContainer = document.getElementById('productSelectContainer');
        const catContainer = document.getElementById('categorySelectContainer');
        
        prodContainer.classList.add('hidden');
        catContainer.classList.add('hidden');

        if (type === 'selected_products') {
            prodContainer.classList.remove('hidden');
        } else if (type === 'selected_categories') {
            catContainer.classList.remove('hidden');
        }
    }
</script>