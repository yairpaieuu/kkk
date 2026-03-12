<div class="p-6 pb-24">
    
    <!-- HEADER & TABS -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-white flex items-center gap-2">
            📦 Order Management
            <span id="orderCount" class="bg-gray-800 text-gray-400 text-sm py-1 px-3 rounded-full"><?= count($orders) ?></span>
        </h2>

        <!-- SOURCE TABS -->
        <div class="flex bg-gray-800 p-1 rounded-xl border border-white/10">
            <a href="/admin/orders?source=all" class="px-5 py-2 text-xs font-bold rounded-lg transition <?= $currentSource === 'all' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">All</a>
            <a href="/admin/orders?source=ecommerce" class="px-5 py-2 text-xs font-bold rounded-lg transition <?= $currentSource === 'ecommerce' ? 'bg-purple-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">🌐 E-com</a>
            <a href="/admin/orders?source=pos" class="px-5 py-2 text-xs font-bold rounded-lg transition <?= $currentSource === 'pos' ? 'bg-orange-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">🏪 POS</a>
        </div>
    </div>

    <!-- SEARCH & FILTER TOOLBAR -->
    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
            <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Search ID, Customer, Phone..." class="w-full bg-gray-800 border border-gray-700 text-white pl-10 pr-4 py-2.5 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none transition">
        </div>
        <div class="w-full md:w-48">
            <select id="statusFilter" onchange="applyFilters()" class="w-full bg-gray-800 border border-gray-700 text-white px-4 py-2.5 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer">
                <option value="all">All Statuses</option>
                <option value="pending">🟡 Pending</option>
                <option value="approved">🔵 Approved</option>
                <option value="preparing">🟣 Preparing</option>
                <option value="delivering">🚀 Delivering</option>
                <option value="completed">✅ Completed</option>
                <option value="rejected">✕ Rejected</option>
                <option value="cancelled">🚫 Cancelled</option>
            </select>
        </div>
    </div>

    <!-- ORDERS TABLE -->
    <?php if(empty($orders)): ?>
        <div class="p-16 text-center text-gray-500 glass-panel rounded-xl border border-dashed border-white/10">
            <div class="text-4xl mb-3">📭</div>
            <p>No orders found.</p>
        </div>
    <?php else: ?>
        <div class="glass-panel overflow-hidden rounded-xl border border-white/10 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-gray-400 text-sm" id="ordersTable">
                    <thead class="bg-gray-800 text-gray-200 uppercase font-bold text-xs tracking-wider cursor-pointer">
                        <tr>
                            <th class="p-4 w-24 hover:text-white transition group" onclick="sortTable(0)">ID <i class="fa-solid fa-sort ml-1"></i></th>
                            <th class="p-4 hover:text-white transition group" onclick="sortTable(1)">Customer <i class="fa-solid fa-sort ml-1"></i></th>
                            <th class="p-4 cursor-default">Proofs</th>
                            <th class="p-4 hover:text-white transition group" onclick="sortTable(3)">Total <i class="fa-solid fa-sort ml-1"></i></th>
                            <th class="p-4 hover:text-white transition group" onclick="sortTable(4)">Status <i class="fa-solid fa-sort ml-1"></i></th>
                            <th class="p-4 w-48 text-center cursor-default">Print</th>
                            <th class="p-4 text-right cursor-default">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700" id="ordersBody">
                        <?php foreach($orders as $o): ?>
                        <tr class="hover:bg-white/5 transition duration-150 order-row" 
                            data-status="<?= strtolower($o['status'] ?? '') ?>" 
                            data-search="<?= strtolower(($o['id'] ?? '') . ' ' . ($o['user_account_name']??'') . ' ' . ($o['customer_phone']??'') . ' ' . ($o['total_amount']??'')) ?>">
                            
                            <!-- 0. ID -->
                            <td class="p-4 align-middle" data-val="<?= $o['id'] ?>">
                                <button onclick="openOrderDetails(<?= $o['id'] ?>)" class="text-blue-400 hover:text-blue-300 font-mono font-bold text-base border-b border-dashed border-blue-500/50 hover:border-blue-400 transition">
                                    #<?= str_pad($o['id'], 5, '0', STR_PAD_LEFT) ?>
                                </button>
                                <div class="text-[10px] text-gray-500 mt-1"><?= date('M d, H:i', strtotime($o['created_at'])) ?></div>
                            </td>
                            
                            <!-- 1. CUSTOMER -->
                            <td class="p-4 align-middle" data-val="<?= strtolower($o['user_account_name'] ?? '') ?>">
                                <div class="text-white font-bold text-sm mb-1"><?= htmlspecialchars($o['user_account_name'] ?? 'Guest') ?></div>
                                <?php 
                                    $phoneDisplay = '-';
                                    if (!empty($o['customer_phone'])) $phoneDisplay = htmlspecialchars($o['customer_phone']);
                                    elseif (!empty($o['phone'])) $phoneDisplay = htmlspecialchars($o['phone']);
                                ?>
                                <div class="text-xs text-yellow-500 font-mono flex items-center gap-1">
                                    <i class="fa-solid fa-phone text-[10px]"></i> <?= $phoneDisplay ?>
                                </div>
                            </td>

                            <!-- 2. PROOFS -->
                            <td class="p-4 align-middle">
                                <div class="flex gap-2">
                                    <?php if(!empty($o['payment_receipt'])): ?>
                                        <div class="relative w-10 h-10 group/img cursor-pointer" onclick="viewImage('/<?= $o['payment_receipt'] ?>')">
                                            <img src="/<?= $o['payment_receipt'] ?>" class="w-full h-full object-cover rounded border border-white/10">
                                        </div>
                                    <?php endif; ?>
                                    <?php if(!empty($o['delivery_proof'])): ?>
                                        <div class="relative w-10 h-10 group/img cursor-pointer" onclick="viewImage('/<?= $o['delivery_proof'] ?>')">
                                            <img src="/<?= $o['delivery_proof'] ?>" class="w-full h-full object-cover rounded border border-white/10">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- 3. TOTAL -->
                            <td class="p-4 align-middle" data-val="<?= $o['total_amount'] ?>">
                                <div class="text-white font-bold font-mono"><?= number_format($o['total_amount'] ?? 0) ?></div>
                                <div class="text-[10px] text-gray-500 uppercase"><?= $o['payment_method'] ?? 'Cash' ?></div>
                            </td>

                            <!-- 4. STATUS (ROBUST FIX) -->
                            <td class="p-4 align-middle" data-val="<?= $o['status'] ?? '' ?>">
                                <?php
                                    // 1. Get raw status or default to 'unknown'
                                    $rawStatus = isset($o['status']) && $o['status'] !== '' ? $o['status'] : 'Unknown';
                                    $statusKey = strtolower(trim($rawStatus));
                                    
                                    // 2. Map colors
                                    $statusColor = match($statusKey) {
                                        'completed' => 'bg-green-500/10 text-green-400 border-green-500/20',
                                        'pending' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
                                        'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        'rejected' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        'preparing' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                        'delivering' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                        'approved' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                        'unknown' => 'bg-gray-700/50 text-gray-500 border-gray-600/30',
                                        default => 'bg-gray-700 text-gray-300'
                                    };
                                ?>
                                <span class="px-2.5 py-1 rounded-md text-[10px] uppercase font-bold border <?= $statusColor ?>">
                                    <?= htmlspecialchars($rawStatus) ?>
                                </span>
                                <?php if($statusKey === 'cancelled' && !empty($o['cancellation_reason'])): ?>
                                    <div class="text-[9px] text-red-400 mt-1 max-w-[120px] truncate" title="<?= htmlspecialchars($o['cancellation_reason']) ?>">
                                        Reason: <?= htmlspecialchars($o['cancellation_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- 5. PRINT -->
                            <td class="p-4 align-middle">
                                <div class="flex justify-center gap-2">
                                    <button onclick="printOrder(<?= $o['id'] ?>, 'slip')" class="bg-gray-700 hover:bg-gray-600 text-white w-8 h-8 rounded flex items-center justify-center"><i class="fa-solid fa-receipt text-xs"></i></button>
                                    <button onclick="printOrder(<?= $o['id'] ?>, 'a4')" class="bg-blue-600 hover:bg-blue-500 text-white w-8 h-8 rounded flex items-center justify-center"><i class="fa-solid fa-file-pdf text-xs"></i></button>
                                </div>
                            </td>

                            <!-- 6. ACTIONS -->
                            <td class="p-4 align-middle text-right">
                                <div class="flex justify-end gap-2">
                                    <?php if($statusKey === 'pending'): ?>
                                        <form action="/admin/order/update-status" method="POST" class="flex gap-1 justify-end">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <button name="status" value="approved" class="bg-green-600/20 hover:bg-green-600 text-green-500 hover:text-white w-8 h-8 rounded transition flex items-center justify-center" title="Approve"><i class="fa-solid fa-check"></i></button>
                                            <button name="status" value="rejected" class="bg-red-600/20 hover:bg-red-600 text-red-500 hover:text-white w-8 h-8 rounded transition flex items-center justify-center" onclick="return confirm('Reject?')" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                        </form>
                                    <?php elseif($statusKey === 'approved'): ?>
                                        <div class="flex flex-col gap-1 items-end">
                                            <form action="/admin/order/update-status" method="POST">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <button name="status" value="preparing" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded text-[10px] font-bold w-24">Start Packing</button>
                                            </form>
                                            <form action="/admin/order/update-status" method="POST">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <button name="status" value="completed" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded text-[10px] font-bold w-24">Complete Now</button>
                                            </form>
                                        </div>
                                    <?php elseif($statusKey === 'preparing'): ?>
                                        <form action="/admin/order/update-status" method="POST"><input type="hidden" name="order_id" value="<?= $o['id'] ?>"><button name="status" value="delivering" class="bg-purple-600 text-white px-3 py-1.5 rounded text-xs font-bold">Start Delivery</button></form>
                                    <?php elseif($statusKey === 'delivering'): ?>
                                        <button onclick="openDeliveryModal(<?= $o['id'] ?>)" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1.5 rounded text-xs font-bold flex items-center gap-2 ml-auto"><i class="fa-solid fa-camera"></i> Complete</button>
                                    <?php endif; ?>

                                    <!-- CANCEL BUTTON (ADMIN ONLY) -->
                                    <?php 
                                        $finalStatuses = ['cancelled', 'rejected'];
                                        if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' && !in_array($statusKey, $finalStatuses)): 
                                    ?>
                                        <button onclick="openCancelModal(<?= $o['id'] ?>)" class="bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/30 w-8 h-8 rounded flex items-center justify-center transition" title="Cancel Order">
                                            <i class="fa-solid fa-ban text-xs"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. ORDER DETAILS MODAL -->
<div id="orderDetailsModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeOrderModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
        
        <!-- Header -->
        <div class="flex justify-between items-start mb-6 pb-4 border-b border-white/10">
            <div>
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-blue-400">#<span id="detailOrderId"></span></span>
                    <span id="detailStatusBadge" class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300 uppercase"></span>
                </h3>
                <p id="detailDate" class="text-gray-400 text-xs mt-1"></p>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <form action="/admin/order/delete" method="POST" onsubmit="return confirm('⚠️ DANGER: Are you sure you want to permanently delete this order? This cannot be undone.');">
                        <input type="hidden" name="order_id" id="deleteOrderId">
                        <button type="submit" class="text-red-500 hover:text-red-400 hover:bg-red-500/10 p-2 rounded transition" title="Delete Order">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                <?php endif; ?>
                <button onclick="closeOrderModal()" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Customer Info -->
            <div class="bg-black/20 p-4 rounded-xl border border-white/5">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">Customer Information</h4>
                <div class="space-y-2 text-sm text-gray-300">
                    <p class="flex justify-between"><span class="text-gray-500">Name:</span> <span id="detailName" class="font-bold text-white"></span></p>
                    <p class="flex justify-between"><span class="text-gray-500">Phone:</span> <span id="detailPhone" class="font-mono text-yellow-500"></span></p>
                    <div class="border-t border-white/5 my-2"></div>
                    <p><span class="text-gray-500 block mb-1">Address:</span> <span id="detailAddress" class="block bg-gray-800 p-2 rounded text-xs text-gray-300"></span></p>
                </div>
            </div>

            <!-- Payment & Delivery -->
            <div class="bg-black/20 p-4 rounded-xl border border-white/5">
                <h4 class="text-xs font-bold text-gray-500 uppercase mb-3">Payment & Delivery</h4>
                <div class="space-y-2 text-sm text-gray-300">
                    <p class="flex justify-between"><span class="text-gray-500">Payment:</span> <span id="detailPaymentMethod" class="text-yellow-400 font-bold"></span></p>
                    <p class="flex justify-between"><span class="text-gray-500">Delivery:</span> <span id="detailDeliveryMethod"></span></p>
                    <div class="border-t border-white/5 my-2 pt-2">
                        <p class="flex justify-between text-xs"><span class="text-gray-500">Subtotal:</span> <span id="detailSubtotal">0</span></p>
                        <p class="flex justify-between text-xs text-red-400"><span class="text-gray-500">Discount:</span> <span id="detailDiscount">-0</span></p>
                        <p class="flex justify-between text-xs text-blue-400"><span class="text-gray-500">Shipping:</span> <span id="detailShipping">+0</span></p>
                        <div class="border-t border-white/10 mt-2 pt-2 flex justify-between text-lg font-bold text-white">
                            <span>Total:</span> <span id="detailTotal">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ORDER NOTES SECTION -->
        <div id="detailNoteContainer" class="bg-yellow-500/10 border border-yellow-500/20 p-4 rounded-xl mb-6 hidden">
            <h4 class="text-xs font-bold text-yellow-500 uppercase mb-1"><i class="fa-solid fa-note-sticky mr-1"></i> Order Note</h4>
            <p id="detailNote" class="text-sm text-gray-300 italic"></p>
        </div>

        <!-- Items Table -->
        <div class="bg-black/20 rounded-xl border border-white/5 overflow-hidden">
            <h4 class="text-xs font-bold text-gray-500 uppercase p-4 bg-gray-800/50">Order Items</h4>
            <div class="max-h-60 overflow-y-auto">
                <table class="w-full text-left text-xs text-gray-400">
                    <thead class="bg-gray-800 text-gray-500">
                        <tr>
                            <th class="p-3">Product</th>
                            <th class="p-3 text-center">Qty</th>
                            <th class="p-3 text-right">Price</th>
                            <th class="p-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="detailItemsBody" class="divide-y divide-white/5">
                        <!-- Items Injected Here -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- 2. IMAGE PREVIEW MODAL -->
<div id="imageModal" class="fixed inset-0 z-[60] hidden bg-black/90 backdrop-blur-sm flex items-center justify-center p-4" onclick="this.classList.add('hidden')">
    <img id="modalImage" src="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl border border-white/20">
</div>

<!-- 3. DELIVERY PROOF MODAL -->
<div id="deliveryModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="document.getElementById('deliveryModal').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2"><i class="fa-solid fa-camera text-green-400"></i> Attach Delivery Proof</h3>
        <form action="/admin/order/complete" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="order_id" id="deliveryOrderId">
            <div class="border-2 border-dashed border-gray-600 rounded-xl p-4 text-center hover:border-blue-500 transition bg-gray-800/50 relative">
                <input type="file" name="delivery_proof" id="proofInput" required accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewProof(this)">
                <div id="uploadPlaceholder">
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-500 mb-2"></i>
                    <div class="text-xs text-gray-400 font-medium">Click to upload photo</div>
                </div>
                <img id="proofPreview" class="hidden w-full h-40 object-cover rounded-lg mx-auto border border-white/10">
            </div>
            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-xl shadow-lg transition">Confirm & Complete Order</button>
        </form>
    </div>
</div>

<!-- 4. CANCEL MODAL -->
<div id="cancelModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="document.getElementById('cancelModal').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-6 bg-[#1e293b] rounded-2xl border border-red-500/30 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2"><i class="fa-solid fa-ban text-red-500"></i> Cancel Order</h3>
        <p class="text-gray-400 text-xs mb-4">This will revert stock for physical items and mark sales as void.</p>
        
        <form action="/admin/order/cancel" method="POST">
            <input type="hidden" name="order_id" id="cancelOrderId">
            <div class="mb-4">
                <label class="block text-gray-400 text-xs mb-1">Reason for Cancellation <span class="text-red-500">*</span></label>
                <textarea name="reason" required rows="3" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-red-500 outline-none text-sm" placeholder="e.g. Customer requested refund..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg text-sm font-bold">Back</button>
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-500 text-white py-2 rounded-lg text-sm font-bold shadow-lg shadow-red-600/20">Confirm Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function openOrderDetails(id) {
        document.getElementById('orderDetailsModal').classList.remove('hidden');
        document.getElementById('detailOrderId').innerText = id + ' (Loading...)';
        
        const deleteInput = document.getElementById('deleteOrderId');
        if (deleteInput) deleteInput.value = id;

        try {
            const res = await fetch(`/api/admin/order-details?id=${id}`);
            const data = await res.json();

            if (data.success) {
                const o = data.order;
                document.getElementById('detailOrderId').innerText = String(o.id).padStart(5, '0');
                document.getElementById('detailDate').innerText = new Date(o.created_at).toLocaleString();
                document.getElementById('detailStatusBadge').innerText = o.status;
                document.getElementById('detailName').innerText = o.customer_name || 'Guest';
                
                let contactInfo = o.customer_phone || o.customer_email || '-';
                if (contactInfo.includes('@local.store')) contactInfo = '-';
                document.getElementById('detailPhone').innerText = contactInfo;
                document.getElementById('detailAddress').innerText = o.customer_address || o.customer_address_text || 'No Address Provided';

                const noteContainer = document.getElementById('detailNoteContainer');
                if (o.notes) {
                    document.getElementById('detailNote').innerText = o.notes;
                    noteContainer.classList.remove('hidden');
                } else {
                    noteContainer.classList.add('hidden');
                }

                const total = parseFloat(o.total_amount);
                const discount = parseFloat(o.discount_amount || 0);
                const shipping = parseFloat(o.shipping_cost || 0);
                const subtotal = total + discount - shipping;

                document.getElementById('detailPaymentMethod').innerText = o.payment_method_name || o.payment_method || 'Cash';
                document.getElementById('detailDeliveryMethod').innerText = o.delivery_method_name || 'Standard';
                document.getElementById('detailSubtotal').innerText = subtotal.toLocaleString() + ' Ks';
                document.getElementById('detailDiscount').innerText = '-' + discount.toLocaleString() + ' Ks';
                document.getElementById('detailShipping').innerText = '+' + shipping.toLocaleString() + ' Ks';
                document.getElementById('detailTotal').innerText = total.toLocaleString() + ' Ks';

                const tbody = document.getElementById('detailItemsBody');
                tbody.innerHTML = '';
                data.items.forEach(item => {
                    const price = parseFloat(item.price);
                    const qty = parseInt(item.quantity);
                    const row = `
                        <tr class="hover:bg-white/5">
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-gray-700 overflow-hidden flex-shrink-0">
                                        ${item.image ? `<img src="${imgSrc(item.image)}" class="w-full h-full object-cover">` : '<div class="w-full h-full flex items-center justify-center text-[8px]">IMG</div>'}
                                    </div>
                                    <div class="font-bold text-white truncate max-w-[150px]">${item.product_name}</div>
                                </div>
                            </td>
                            <td class="p-3 text-center text-white">${qty}</td>
                            <td class="p-3 text-right">${price.toLocaleString()}</td>
                            <td class="p-3 text-right font-mono text-white">${(price * qty).toLocaleString()}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            } else { alert('Failed to load order details'); closeOrderModal(); }
        } catch (e) { console.error(e); alert('Error loading details'); closeOrderModal(); }
    }

    function closeOrderModal() { document.getElementById('orderDetailsModal').classList.add('hidden'); }

    function applyFilters() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('statusFilter').value.toLowerCase();
        const rows = document.querySelectorAll('.order-row');
        let visibleCount = 0;
        rows.forEach(row => {
            const rowSearch = row.getAttribute('data-search');
            const rowStatus = row.getAttribute('data-status');
            const matchSearch = rowSearch.includes(search);
            const matchStatus = status === 'all' || rowStatus === status;
            if (matchSearch && matchStatus) { row.style.display = ''; visibleCount++; } else { row.style.display = 'none'; }
        });
        document.getElementById('orderCount').innerText = visibleCount;
    }

    let sortDir = true;
    function sortTable(colIndex) {
        const tbody = document.getElementById("ordersBody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        rows.sort((a, b) => {
            let valA = a.children[colIndex].getAttribute('data-val');
            let valB = b.children[colIndex].getAttribute('data-val');
            if (!isNaN(parseFloat(valA)) && isFinite(valA)) { valA = parseFloat(valA); valB = parseFloat(valB); } else { valA = valA.toLowerCase(); valB = valB.toLowerCase(); }
            if (valA < valB) return sortDir ? -1 : 1; if (valA > valB) return sortDir ? 1 : -1; return 0;
        });
        rows.forEach(row => tbody.appendChild(row));
        sortDir = !sortDir;
    }

    function viewImage(src) { document.getElementById('modalImage').src = src; document.getElementById('imageModal').classList.remove('hidden'); }
    function printOrder(id, format) { window.open(`/admin/invoice/print?id=${id}&format=${format}`, 'Print', 'width=800,height=800'); }
    
    function openDeliveryModal(orderId) {
        document.getElementById('deliveryOrderId').value = orderId;
        document.getElementById('deliveryModal').classList.remove('hidden');
        document.getElementById('proofInput').value = '';
        document.getElementById('uploadPlaceholder').classList.remove('hidden');
        document.getElementById('proofPreview').classList.add('hidden');
    }

    function openCancelModal(id) {
        document.getElementById('cancelOrderId').value = id;
        document.getElementById('cancelModal').classList.remove('hidden');
    }

    function previewProof(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('uploadPlaceholder').classList.add('hidden');
                document.getElementById('proofPreview').src = e.target.result;
                document.getElementById('proofPreview').classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>