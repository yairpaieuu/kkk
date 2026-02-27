<div class="p-6 pb-24">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-white flex items-center gap-2">
            👥 Customer Database
            <span class="bg-gray-800 text-gray-400 text-sm py-1 px-3 rounded-full"><?= count($customers) ?></span>
        </h2>

        <!-- FILTER BUTTONS -->
        <div class="flex bg-gray-800 p-1 rounded-lg border border-white/10">
            <a href="/customers?type=all" 
               class="px-4 py-2 text-xs font-bold rounded-md transition <?= $currentFilter === 'all' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">
               All
            </a>
            <a href="/customers?type=ecommerce" 
               class="px-4 py-2 text-xs font-bold rounded-md transition <?= $currentFilter === 'ecommerce' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">
               🌐 E-commerce
            </a>
            <a href="/customers?type=pos" 
               class="px-4 py-2 text-xs font-bold rounded-md transition <?= $currentFilter === 'pos' ? 'bg-orange-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">
               🏪 POS / Walk-in
            </a>
        </div>
    </div>

    <?php if(!empty($message)): ?>
        <div class="<?= strpos($message, 'Error') !== false ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400' ?> p-3 rounded-lg mb-4 text-sm font-bold">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="glass-panel rounded-xl overflow-hidden border border-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-gray-400 text-sm">
                <thead class="bg-gray-800 text-gray-200 uppercase text-xs">
                    <tr>
                        <th class="p-4">Customer Info</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Joined</th>
                        <th class="p-4 text-center">Orders</th>
                        <th class="p-4 text-right">Total Spent</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if(empty($customers)): ?>
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">No customers found.</td></tr>
                    <?php else: ?>
                        <?php foreach($customers as $c): ?>
                        <?php 
                            // Determine type based on email pattern
                            $isPos = strpos($c['email'], 'walkin_') === 0 && strpos($c['email'], '@local.store') !== false;
                        ?>
                        <tr class="hover:bg-white/5 transition group">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white <?= $isPos ? 'bg-orange-500/20 text-orange-500' : 'bg-purple-500/20 text-purple-500' ?>">
                                        <?= strtoupper(substr($c['username'], 0, 1)) ?>
                                    </div>
                                    <div onclick='openEditModal(<?= json_encode($c) ?>, <?= $isPos ? "true" : "false" ?>)' class="cursor-pointer">
                                        <div class="font-bold text-white group-hover:text-blue-400 transition"><?= htmlspecialchars($c['username']) ?></div>
                                        <?php if(!$isPos): ?>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($c['email']) ?></div>
                                        <?php else: ?>
                                            <div class="text-xs text-gray-600 italic">No email provided</div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($c['phone'])): ?>
                                            <div class="text-xs text-blue-400 mt-0.5"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($c['phone']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <?php if($isPos): ?>
                                    <span class="bg-orange-500/10 text-orange-400 border border-orange-500/20 px-2 py-1 rounded text-[10px] uppercase font-bold tracking-wider">
                                        🏪 POS
                                    </span>
                                <?php else: ?>
                                    <span class="bg-purple-500/10 text-purple-400 border border-purple-500/20 px-2 py-1 rounded text-[10px] uppercase font-bold tracking-wider">
                                        🌐 Online
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-xs">
                                <?= date('d M Y', strtotime($c['created_at'])) ?>
                                <div class="text-[10px] text-gray-600"><?= date('h:i A', strtotime($c['created_at'])) ?></div>
                            </td>
                            <td class="p-4 text-center">
                                <button onclick="openOrderHistory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['username']) ?>')" class="bg-gray-700 hover:bg-blue-600 text-white px-3 py-1 rounded-md text-xs font-bold transition">
                                    <?= $c['total_orders'] ?>
                                </button>
                            </td>
                            <td class="p-4 text-right">
                                <span class="text-green-400 font-bold font-mono text-base">
                                    <?= number_format($c['total_spent'] ?? 0, 0) ?>
                                </span>
                                <span class="text-xs text-gray-500">MMK</span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                                    <button onclick='openEditModal(<?= json_encode($c) ?>, <?= $isPos ? "true" : "false" ?>)' class="p-2 bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-white rounded-lg transition">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Delete this customer? This cannot be undone.');">
                                        <input type="hidden" name="delete_customer" value="1">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="p-2 bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white rounded-lg transition">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- EDIT CUSTOMER MODAL -->
<div id="editCustomerModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4">Edit Customer</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="update_customer" value="1">
            <input type="hidden" name="id" id="editId">
            
            <div>
                <label class="block text-gray-400 text-xs mb-1">Name</label>
                <input type="text" name="username" id="editName" required class="w-full bg-gray-800 border border-gray-600 text-white p-2 rounded focus:border-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-gray-400 text-xs mb-1">Phone</label>
                <input type="text" name="phone" id="editPhone" class="w-full bg-gray-800 border border-gray-600 text-white p-2 rounded focus:border-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-gray-400 text-xs mb-1">Address</label>
                <textarea name="address" id="editAddress" rows="2" class="w-full bg-gray-800 border border-gray-600 text-white p-2 rounded focus:border-blue-500 outline-none"></textarea>
            </div>

            <!-- POS ONLY FIELD -->
            <div id="posDiscountField" class="hidden">
                <label class="block text-orange-400 text-xs mb-1 font-bold">Default POS Discount (%)</label>
                <input type="number" step="0.01" name="default_discount" id="editDiscount" class="w-full bg-gray-800 border border-orange-500/50 text-white p-2 rounded focus:border-orange-500 outline-none">
                <p class="text-[10px] text-gray-500 mt-1">Automatically applied during POS checkout for this customer.</p>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ORDER HISTORY MODAL -->
<div id="orderHistoryModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeHistoryModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 pb-4 border-b border-white/10">
            <h3 class="text-lg font-bold text-white">Order History: <span id="historyCustomerName" class="text-blue-400"></span></h3>
            <button onclick="closeHistoryModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="historyList" class="space-y-2">
            <!-- Loaded via JS -->
            <div class="text-center text-gray-500 py-8">Loading...</div>
        </div>
    </div>
</div>

<!-- ORDER DETAIL MODAL (Reused from Sales Page logic) -->
<div id="orderDetailsModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/90 backdrop-blur-sm" onclick="document.getElementById('orderDetailsModal').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl p-6 bg-[#0f172a] rounded-2xl border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
        <!-- Reuse structure from sales page, populated via JS -->
        <div class="flex justify-between items-start mb-6">
            <h3 class="text-xl font-bold text-white">Order <span id="detailOrderId" class="text-blue-400"></span></h3>
            <button onclick="document.getElementById('orderDetailsModal').classList.add('hidden')" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <div id="detailContent" class="space-y-4 text-gray-300"></div>
    </div>
</div>

<script>
    // 1. EDIT CUSTOMER
    function openEditModal(c, isPos) {
        document.getElementById('editId').value = c.id;
        document.getElementById('editName').value = c.username;
        document.getElementById('editPhone').value = c.phone || '';
        document.getElementById('editAddress').value = c.address || '';
        document.getElementById('editDiscount').value = c.default_discount || 0;

        // Toggle Discount Field
        const discField = document.getElementById('posDiscountField');
        if(isPos) {
            discField.classList.remove('hidden');
        } else {
            discField.classList.add('hidden');
        }

        document.getElementById('editCustomerModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editCustomerModal').classList.add('hidden');
    }

    // 2. ORDER HISTORY LIST
    async function openOrderHistory(userId, name) {
        document.getElementById('orderHistoryModal').classList.remove('hidden');
        document.getElementById('historyCustomerName').innerText = name;
        const list = document.getElementById('historyList');
        list.innerHTML = '<div class="text-center text-gray-500 py-4"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

        try {
            const res = await fetch(`/admin/api/customer-orders?user_id=${userId}`); // You might need to add this route or reuse logic
            // Note: Since I didn't add a specific route in index.php for this new api, let's use the main admin/orders query logic or add a small helper. 
            // **Correction:** I added `apiGetCustomerOrders` in controller. We need to ensure route exists. 
            // Assuming route: /api/admin/customer-orders
            
            const data = await res.json();
            
            list.innerHTML = '';
            if(data.success && data.orders.length > 0) {
                data.orders.forEach(o => {
                    const date = new Date(o.created_at).toLocaleDateString();
                    const total = parseFloat(o.total_amount).toLocaleString();
                    const html = `
                        <div onclick="openOrderDetails(${o.id})" class="bg-white/5 hover:bg-white/10 p-3 rounded-lg flex justify-between items-center cursor-pointer transition border border-white/5 hover:border-blue-500/30">
                            <div>
                                <span class="text-blue-400 font-mono font-bold text-sm">#${String(o.id).padStart(5,'0')}</span>
                                <div class="text-[10px] text-gray-500">${date}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-white font-bold">${total} <span class="text-[10px] text-gray-500">MMK</span></div>
                                <span class="text-[10px] uppercase font-bold text-green-400">${o.status}</span>
                            </div>
                        </div>
                    `;
                    list.innerHTML += html;
                });
            } else {
                list.innerHTML = '<div class="text-center text-gray-500 py-4">No orders found.</div>';
            }
        } catch(e) {
            console.error(e);
            list.innerHTML = '<div class="text-center text-red-400 py-4">Error loading history.</div>';
        }
    }

    function closeHistoryModal() {
        document.getElementById('orderHistoryModal').classList.add('hidden');
    }

    // 3. ORDER DETAILS (Reusing logic from Sales page generally)
    async function openOrderDetails(id) {
        document.getElementById('orderDetailsModal').classList.remove('hidden');
        const content = document.getElementById('detailContent');
        document.getElementById('detailOrderId').innerText = '#' + String(id).padStart(5,'0');
        content.innerHTML = '<div class="text-center py-10"><i class="fa-solid fa-spinner fa-spin text-2xl text-blue-500"></i></div>';

        try {
            const res = await fetch(`/api/admin/order-details?id=${id}`);
            const data = await res.json();

            if(data.success) {
                const o = data.order;
                let itemsHtml = '';
                data.items.forEach(i => {
                    itemsHtml += `
                        <div class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-700 rounded bg-cover bg-center" style="background-image:url('/${i.image || 'assets/placeholder.png'}')"></div>
                                <div>
                                    <div class="text-sm font-bold text-white">${i.product_name}</div>
                                    <div class="text-xs text-gray-500">Qty: ${i.quantity}</div>
                                </div>
                            </div>
                            <div class="font-mono text-sm">${parseFloat(i.price * i.quantity).toLocaleString()}</div>
                        </div>
                    `;
                });

                content.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 text-xs mb-4">
                        <div class="bg-white/5 p-3 rounded">
                            <div class="text-gray-500 mb-1">Status</div>
                            <div class="font-bold text-green-400 uppercase">${o.status}</div>
                        </div>
                        <div class="bg-white/5 p-3 rounded">
                            <div class="text-gray-500 mb-1">Payment</div>
                            <div class="font-bold text-white">${o.payment_method_name || o.payment_method}</div>
                        </div>
                    </div>
                    <div class="bg-black/20 p-4 rounded-xl border border-white/5">
                        ${itemsHtml}
                    </div>
                    <div class="flex justify-between items-center pt-4 text-lg font-bold text-white">
                        <span>Total</span>
                        <span>${parseFloat(o.total_amount).toLocaleString()} MMK</span>
                    </div>
                `;
            }
        } catch(e) {
            content.innerHTML = '<p class="text-red-400">Failed to load details</p>';
        }
    }
</script>