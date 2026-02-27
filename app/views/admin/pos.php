<!-- TOAST CONTAINER -->
<div id="toast-container" class="fixed top-5 right-5 z-[100] flex flex-col gap-3 pointer-events-none"></div>

<div class="flex flex-col md:flex-row h-[calc(100vh-100px)] gap-4 pb-20 p-2 relative">
    
    <!-- LEFT: Product Grid -->
    <div class="flex-1 flex flex-col overflow-hidden pr-2">
        
        <!-- 1. Search & Categories -->
        <div class="bg-[#0f172a] z-10 pb-4 flex-shrink-0 space-y-3">
            <input type="text" id="search" onkeyup="filterProducts()" placeholder="Search product or scan barcode..." 
                class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none shadow-lg transition-all">
            
            <!-- Category Buttons -->
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide" id="categoryList">
                <button onclick="filterCategory('all', this)" class="cat-btn active bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap border border-blue-500 transition">
                    All Items
                </button>
                
                <?php if(!empty($categories)): ?>
                    <?php foreach($categories as $cat): ?>
                        <button onclick="filterCategory('<?= htmlspecialchars($cat['name']) ?>', this)" 
                                class="cat-btn bg-gray-800 text-gray-400 border border-gray-700 hover:text-white px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition">
                            <?= htmlspecialchars($cat['name']) ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Grid -->
        <div class="overflow-y-auto scrollbar-hide flex-1">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3 pb-20" id="productGrid">
                <?php foreach($products as $p): ?>
                <div class="product-card glass-panel p-2 rounded-xl cursor-pointer hover:border-blue-500 transition border border-white/5 relative group hover:-translate-y-1 duration-200"
                     onclick='initiateAddToCart(<?= json_encode($p) ?>)'
                     data-name="<?= strtolower($p['name']) ?>"
                     data-barcode="<?= $p['barcode'] ?? '' ?>"
                     data-category="<?= htmlspecialchars($p['category_name'] ?? '') ?>">
                    
                    <div class="aspect-square w-full bg-gray-800/50 rounded-lg mb-2 flex items-center justify-center overflow-hidden relative">
                        <?php if(!empty($p['image'])): ?>
                            <img src="/<?= $p['image'] ?>" class="w-full h-full object-cover transition duration-300 group-hover:scale-110">
                        <?php else: ?>
                            <span class="text-3xl"><?= $p['type']=='digital'?'💻':'📦' ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="text-white text-[10px] md:text-xs font-bold truncate"><?= htmlspecialchars($p['name']) ?></h4>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-green-400 font-bold text-xs"><?= number_format($p['price'], 0) ?></span>
                        <?php if($p['type']=='physical'): ?>
                            <span class="text-[9px] bg-gray-700 px-1 rounded text-gray-300">Qt: <?= $p['stock'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($p['type']=='physical' && $p['stock'] <= 0): ?>
                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-xl z-20">
                            <span class="text-red-500 font-bold text-[10px] transform -rotate-12 border border-red-500 px-1 rounded">OUT OF STOCK</span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart Panel -->
    <div class="w-full md:w-80 lg:w-96 glass-panel rounded-xl flex flex-col border border-white/10 h-full shadow-2xl">
        
        <!-- Cart Header -->
        <div class="p-4 border-b border-white/10 bg-gray-900/50 rounded-t-xl flex-shrink-0">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-white font-bold flex items-center gap-2">
                    <i class="fa-solid fa-cart-shopping text-blue-500"></i> Current Sale
                </h3>
                <button onclick="clearCart()" class="text-red-400 text-xs hover:text-red-300 hover:underline">Clear All</button>
            </div>
            
            <div class="flex gap-2">
                <!-- UPDATED: Added selectCustomer() call and data-discount attribute -->
                <select id="customerSelect" onchange="selectCustomer()" class="flex-1 bg-gray-800 text-white text-sm border border-gray-600 rounded-lg p-2 outline-none focus:border-blue-500 transition-colors">
                    <option value="walk_in" data-discount="0" selected>👤 Walk-in (Auto-Register)</option>
                    <?php if(!empty($customers)): ?>
                        <?php foreach($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" data-discount="<?= $c['default_discount'] ?? 0 ?>">
                                <?= htmlspecialchars($c['username']) ?> (<?= $c['phone'] ?? '-' ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button onclick="document.getElementById('addCustomerModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-500 text-white w-10 rounded-lg flex items-center justify-center transition-colors" title="Add New Customer">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-2 space-y-2" id="cartItems">
            <div id="emptyCartMsg" class="h-full flex flex-col items-center justify-center text-gray-500 opacity-50">
                <span class="text-4xl mb-2">🛒</span>
                <p>Cart is empty</p>
            </div>
        </div>

        <!-- Inputs: Discount, Coupon, Note -->
        <div class="p-3 bg-gray-800/30 border-t border-white/5 space-y-2 flex-shrink-0">
            <!-- Row 1: Discount & Coupon -->
            <div class="flex gap-2">
                
                <!-- DISCOUNT GROUP -->
                <div class="flex-1 flex relative">
                    <input type="number" id="manualDiscount" oninput="renderCart()" placeholder="Discount" class="w-full bg-gray-900 border border-gray-600 rounded-l-lg p-2 pl-7 text-xs text-white outline-none focus:border-yellow-500">
                    <i class="fa-solid fa-tag absolute left-2.5 top-2.5 text-gray-500 text-xs"></i>
                    
                    <!-- Type Toggle -->
                    <select id="discountType" onchange="renderCart()" class="bg-gray-700 text-white text-[10px] border border-gray-600 border-l-0 rounded-r-lg px-1 outline-none focus:border-yellow-500 cursor-pointer">
                        <option value="amount">Ks</option>
                        <option value="percent">%</option>
                    </select>
                </div>

                <div class="flex-1 relative">
                    <input type="text" id="couponCode" placeholder="Coupon Code" class="w-full bg-gray-900 border border-gray-600 rounded-lg p-2 pl-7 text-xs text-white outline-none focus:border-green-500 uppercase">
                    <i class="fa-solid fa-ticket absolute left-2.5 top-2.5 text-gray-500 text-xs"></i>
                </div>
            </div>
            <!-- Row 2: Note -->
            <div class="relative">
                <input type="text" id="orderNote" placeholder="Add Order Note..." class="w-full bg-gray-900 border border-gray-600 rounded-lg p-2 pl-7 text-xs text-white outline-none focus:border-blue-500">
                <i class="fa-solid fa-pen absolute left-2.5 top-2.5 text-gray-500 text-xs"></i>
            </div>
        </div>

        <!-- Cart Footer -->
        <div class="p-4 bg-gray-900/80 border-t border-white/10 rounded-b-xl flex-shrink-0">
            <div class="flex justify-between mb-1 text-xs text-gray-400">
                <span>Subtotal</span>
                <span id="subtotal">0</span>
            </div>
            <div class="flex justify-between mb-3 text-xs text-red-400">
                <span>Discount</span>
                <span class="text-right">- <span id="discountDisplay">0</span></span>
            </div>
            <div class="flex justify-between mb-4 text-xl font-bold text-white border-t border-white/10 pt-2">
                <span>Total</span>
                <span><span id="total">0</span> MMK</span>
            </div>
            
            <button onclick="openCheckoutModal()" id="checkoutBtn" disabled
                class="w-full bg-green-600 hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl shadow-lg transition flex justify-center gap-2 active:scale-95 duration-100">
                <span>Checkout</span>
                <span>💸</span>
            </button>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. COLOR SELECTION MODAL -->
<div id="colorModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('colorModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl animate-bounce-in">
        <h3 class="text-lg font-bold text-white mb-4">Select Variation</h3>
        <p class="text-sm text-gray-400 mb-4" id="colorProductName"></p>
        
        <div id="colorOptions" class="grid grid-cols-2 gap-3 mb-6"></div>

        <button onclick="closeModal('colorModal')" class="w-full bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
    </div>
</div>

<!-- 2. ADD CUSTOMER MODAL -->
<div id="addCustomerModal" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('addCustomerModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-user-plus text-blue-400"></i> Add New Customer
        </h3>
        <form id="addCustomerForm" onsubmit="event.preventDefault(); addNewCustomer();" class="space-y-4">
            <div>
                <label class="block text-gray-400 text-xs mb-1">Name <span class="text-red-500">*</span></label>
                <input type="text" id="newCusName" required class="w-full bg-gray-800 border border-gray-600 rounded-lg p-2.5 text-white focus:border-blue-500 outline-none transition">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-gray-400 text-xs mb-1">Phone</label>
                    <input type="text" id="newCusPhone" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-2.5 text-white focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-gray-400 text-xs mb-1">Email</label>
                    <input type="email" id="newCusEmail" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-2.5 text-white focus:border-blue-500 outline-none transition">
                </div>
            </div>
            <div>
                <label class="block text-gray-400 text-xs mb-1">Address</label>
                <textarea id="newCusAddress" class="w-full bg-gray-800 border border-gray-600 rounded-lg p-2.5 text-white focus:border-blue-500 outline-none transition" rows="2"></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeModal('addCustomerModal')" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2.5 rounded-lg transition">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-2.5 rounded-lg font-bold shadow-lg transition">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. CHECKOUT MODAL -->
<div id="checkoutModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal('checkoutModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl animate-fade-in-up">
        
        <div class="flex justify-between items-center mb-6 border-b border-white/10 pb-4">
            <h3 class="text-xl font-bold text-white flex items-center gap-2"><span>💵</span> Payment</h3>
            <div class="text-right">
                <p class="text-gray-400 text-xs">Total Payable</p>
                <p class="text-2xl font-bold text-green-400"><span id="modalTotal">0</span> <span class="text-sm">MMK</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Select Method</label>
                    <div class="grid grid-cols-3 gap-2" id="paymentMethodsGrid">
                        <?php 
                        $firstMethod = $payment_methods[0]['name'] ?? 'Cash';
                        foreach($payment_methods as $index => $pm): 
                            $isActive = $index === 0 ? 'active bg-blue-600 text-white border-blue-500' : 'bg-gray-800 text-gray-400 border-gray-600';
                            $icon = 'fa-money-bill-wave';
                            if (stripos($pm['name'], 'kbz') !== false || stripos($pm['name'], 'mobile') !== false) $icon = 'fa-mobile-screen';
                            elseif (stripos($pm['name'], 'wave') !== false || stripos($pm['name'], 'wallet') !== false) $icon = 'fa-wallet';
                            elseif (stripos($pm['name'], 'card') !== false || stripos($pm['name'], 'visa') !== false) $icon = 'fa-credit-card';
                        ?>
                            <button onclick="selectPaymentMethod('<?= htmlspecialchars($pm['name']) ?>', this)" 
                                class="pay-btn <?= $isActive ?> p-3 rounded-xl flex flex-col items-center gap-1 border transition hover:bg-gray-700">
                                <i class="fa-solid <?= $icon ?> text-lg"></i> 
                                <span class="text-xs font-bold"><?= htmlspecialchars($pm['name']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selectedPaymentMethod" value="<?= $firstMethod ?>">
                </div>

                <div class="bg-black/30 p-4 rounded-xl border border-white/5 space-y-4">
                    <div>
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-1">Amount Received</label>
                        <input type="number" id="amountPaid" oninput="calculateChange()" onfocus="this.select()" class="w-full bg-gray-900 border border-gray-600 text-white text-xl font-bold p-3 rounded-lg focus:border-blue-500 outline-none" placeholder="0">
                    </div>
                    
                    <div class="flex justify-between items-center pt-2 border-t border-white/10">
                        <span class="text-gray-400 font-bold">Change To Give:</span>
                        <span id="changeAmount" class="text-2xl font-bold text-yellow-400">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button onclick="closeModal('checkoutModal')" class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-gray-300 font-bold rounded-xl transition">Cancel</button>
            <button onclick="processPayment()" id="confirmPayBtn" class="flex-[2] py-3 bg-green-600 hover:bg-green-500 text-white font-bold rounded-xl shadow-lg transition flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <span>Confirm Sale</span>
                <span id="spinner" class="hidden animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
            </button>
        </div>
    </div>
</div>

<!-- 4. SUCCESS MODAL -->
<div id="successModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/90 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-[#1e293b] w-full max-w-sm p-8 rounded-3xl border border-green-500/30 shadow-2xl text-center transform scale-100 transition-all animate-bounce-in">
            <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6 ring-4 ring-green-500/10">
                <i class="fa-solid fa-check text-4xl text-green-500"></i>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Sale Completed!</h2>
            <div class="text-gray-400 mb-6 space-y-1">
                <p>Order <span id="successOrderId" class="text-blue-400 font-mono font-bold">#000</span></p>
                <p class="text-sm">Change: <span id="successChange" class="text-yellow-400 font-bold">0</span> MMK</p>
            </div>
            <div class="bg-black/30 p-4 rounded-xl mb-4">
                <p class="text-gray-400 text-sm mb-3 font-bold uppercase tracking-wider">Print Receipt</p>
                <div class="grid grid-cols-3 gap-2">
                    <button onclick="printInvoice('slip')" class="bg-gray-700 hover:bg-blue-600 hover:text-white text-gray-300 py-2 rounded-lg text-xs font-bold transition border border-white/5"><i class="fa-solid fa-receipt block text-lg mb-1"></i> Slip</button>
                    <button onclick="printInvoice('a5')" class="bg-gray-700 hover:bg-blue-600 hover:text-white text-gray-300 py-2 rounded-lg text-xs font-bold transition border border-white/5"><i class="fa-solid fa-file-invoice block text-lg mb-1"></i> A5</button>
                    <button onclick="printInvoice('a4')" class="bg-gray-700 hover:bg-blue-600 hover:text-white text-gray-300 py-2 rounded-lg text-xs font-bold transition border border-white/5"><i class="fa-solid fa-file-lines block text-lg mb-1"></i> A4</button>
                </div>
            </div>
            <button onclick="finishOrder()" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-xl font-bold transition transform hover:scale-105 flex items-center justify-center gap-2">Next Sale <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </div>
</div>

<style>
    .animate-fade-in-up { animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
    .animate-bounce-in { animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
    @keyframes fadeInUp { from { opacity: 0; transform: translate(-50%, -40%); } to { opacity: 1; transform: translate(-50%, -50%); } }
    @keyframes bounceIn { 0% { opacity: 0; transform: scale(0.3); } 50% { opacity: 1; transform: scale(1.05); } 70% { transform: scale(0.9); } 100% { transform: scale(1); } }
    .toast-enter { animation: slideInRight 0.3s ease-out forwards; }
    .toast-leave { animation: slideOutRight 0.3s ease-in forwards; }
    @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
    @keyframes slideOutRight { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100%); } }
</style>

<script>
    let cart = [];
    let currentTotal = 0;
    let selectedProductForColor = null;
    let activeCategory = 'all';

    function alertToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const bgClass = type === 'success' ? 'bg-green-600' : 'bg-red-600';
        const icon = type === 'success' ? '<i class="fa-solid fa-check-circle"></i>' : '<i class="fa-solid fa-circle-exclamation"></i>';
        toast.className = `${bgClass} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px] toast-enter pointer-events-auto`;
        toast.innerHTML = `<div class="text-xl">${icon}</div><div class="font-medium text-sm">${message}</div>`;
        container.appendChild(toast);
        setTimeout(() => { toast.classList.add('toast-leave'); setTimeout(() => toast.remove(), 300); }, 3000);
    }

    // --- NEW: AUTO-APPLY CUSTOMER DISCOUNT ---
    function selectCustomer() {
        const select = document.getElementById('customerSelect');
        const selectedOption = select.options[select.selectedIndex];
        
        // 1. Get discount value
        const defaultDiscount = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
        
        // 2. Set the input value
        const discountInput = document.getElementById('manualDiscount');
        const discountType = document.getElementById('discountType');
        
        if(discountInput) {
            discountInput.value = defaultDiscount > 0 ? defaultDiscount : '';
            // Assume default discounts stored in DB are PERCENTAGES by default
            // If they are fixed amounts, change 'percent' to 'amount' below
            if(defaultDiscount > 0) {
                discountType.value = 'percent'; 
            }
        }

        // 3. Recalculate
        renderCart();
    }

    // --- CATEGORY FILTER LOGIC ---
    function filterCategory(catName, btn) {
        activeCategory = catName;
        document.querySelectorAll('.cat-btn').forEach(b => {
            b.classList.remove('bg-blue-600', 'text-white', 'border-blue-500');
            b.classList.add('bg-gray-800', 'text-gray-400', 'border-gray-700');
        });
        if(btn) {
            btn.classList.remove('bg-gray-800', 'text-gray-400', 'border-gray-700');
            btn.classList.add('bg-blue-600', 'text-white', 'border-blue-500');
        }
        filterProducts();
    }

    function filterProducts() {
        const term = document.getElementById('search').value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.getAttribute('data-name');
            const barcode = card.getAttribute('data-barcode');
            const category = card.getAttribute('data-category');
            const matchSearch = name.includes(term) || barcode.includes(term);
            const matchCat = activeCategory === 'all' || category === activeCategory;
            card.style.display = (matchSearch && matchCat) ? 'block' : 'none';
        });
    }

    // --- CUSTOMER LOGIC ---
    async function addNewCustomer() {
        const name = document.getElementById('newCusName').value;
        const phone = document.getElementById('newCusPhone').value;
        const email = document.getElementById('newCusEmail').value;
        const address = document.getElementById('newCusAddress').value;
        if(!name) return alertToast('Name is required', 'error');
        try {
            const res = await fetch('/api/admin/customer/add', { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({name, phone, email, address})
            });
            const data = await res.json();
            if(data.success) {
                const select = document.getElementById('customerSelect');
                const option = document.createElement('option');
                option.value = data.customer.id;
                option.text = `${data.customer.name} ${phone ? '('+phone+')' : ''}`;
                option.selected = true;
                // Newly added customers usually have 0 discount initially
                option.setAttribute('data-discount', 0); 
                select.appendChild(option); 
                select.value = data.customer.id;
                
                selectCustomer(); // Auto-select logic

                closeModal('addCustomerModal');
                document.getElementById('addCustomerForm').reset();
                alertToast('Customer Added!', 'success');
            } else { alertToast(data.message, 'error'); }
        } catch(e) { alertToast('Server Error', 'error'); }
    }

    // --- CART LOGIC ---
    function initiateAddToCart(product) {
        if (product.type === 'physical' && product.stock <= 0) return alertToast("Out of stock!", "error");

        if (product.colors) {
            let colorList = [];
            if (typeof product.colors === 'string') {
                colorList = product.colors.split(',').map(c => c.trim()).filter(c => c !== '');
            } else if (Array.isArray(product.colors)) {
                colorList = product.colors;
            }

            if (colorList.length > 0) {
                selectedProductForColor = product;
                const container = document.getElementById('colorOptions');
                container.innerHTML = '';
                document.getElementById('colorProductName').innerText = product.name;
                
                colorList.forEach(color => {
                    const btn = document.createElement('button');
                    btn.className = 'bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg transition capitalize';
                    btn.innerText = color;
                    btn.onclick = () => { addToCart(selectedProductForColor, color); closeModal('colorModal'); };
                    container.appendChild(btn);
                });
                document.getElementById('colorModal').classList.remove('hidden');
                return;
            }
        }
        addToCart(product);
    }

    function addToCart(product, selectedColor = null) {
        const uniqueId = selectedColor ? `${product.id}-${selectedColor}` : product.id;
        const displayName = selectedColor ? `${product.name} (${selectedColor})` : product.name;
        const existing = cart.find(item => item.uniqueId === uniqueId);
        
        if (existing) {
            if (product.type === 'physical' && existing.qty >= product.stock) return alertToast("Stock limit reached!", "error");
            existing.qty++;
        } else {
            cart.push({ 
                uniqueId: uniqueId, id: product.id, name: displayName, 
                price: parseFloat(product.price), type: product.type, stock: product.stock, 
                qty: 1, color: selectedColor 
            });
        }
        renderCart();
    }

    function changeQty(index, change) {
        const item = cart[index];
        const newQty = item.qty + change;
        if (change > 0 && item.type === 'physical' && newQty > item.stock) return alertToast("Stock limit reached!", "error");
        if (newQty < 1) cart.splice(index, 1);
        else item.qty = newQty;
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cartItems');
        const emptyMsg = document.getElementById('emptyCartMsg');
        const btn = document.getElementById('checkoutBtn');
        
        let discountInput = parseFloat(document.getElementById('manualDiscount').value) || 0;
        const discountType = document.getElementById('discountType').value;
        
        container.innerHTML = '';
        let subtotal = 0;

        if (cart.length === 0) {
            if(emptyMsg) container.appendChild(emptyMsg);
            btn.disabled = true;
            document.getElementById('total').innerText = '0';
            document.getElementById('subtotal').innerText = '0';
            document.getElementById('discountDisplay').innerText = '0';
            return;
        }
        btn.disabled = false;
        
        cart.forEach((item, index) => {
            subtotal += item.price * item.qty;
            const div = document.createElement('div');
            div.className = 'bg-gray-800/50 p-2 rounded-lg flex justify-between items-center text-sm animate-fade-in border border-white/5';
            div.innerHTML = `
                <div class="flex-1 mr-2">
                    <div class="text-white font-bold truncate w-28 md:w-32" title="${item.name}">${item.name}</div>
                    <div class="text-gray-400 text-[10px]">${item.price.toLocaleString()} per unit</div>
                </div>
                <div class="flex items-center bg-gray-700 rounded mr-3">
                    <button onclick="changeQty(${index}, -1)" class="px-2 py-1 hover:bg-gray-600 text-white rounded-l transition">-</button>
                    <span class="w-6 text-center font-bold text-white text-xs">${item.qty}</span>
                    <button onclick="changeQty(${index}, 1)" class="px-2 py-1 hover:bg-gray-600 text-white rounded-r transition">+</button>
                </div>
                <div class="flex items-center gap-2 text-right">
                    <span class="text-green-400 font-bold min-w-[60px]">${(item.price * item.qty).toLocaleString()}</span>
                    <button onclick="removeItem(${index})" class="text-red-400 hover:bg-red-900/30 p-1.5 rounded-md transition"><i class="fa-solid fa-trash text-xs"></i></button>
                </div>
            `;
            container.appendChild(div);
        });

        // Discount Logic
        let finalDiscount = 0;
        if (discountType === 'percent') {
            if(discountInput > 100) discountInput = 100;
            finalDiscount = subtotal * (discountInput / 100);
        } else {
            finalDiscount = discountInput;
        }

        currentTotal = Math.max(0, subtotal - finalDiscount);
        
        document.getElementById('subtotal').innerText = subtotal.toLocaleString();
        
        const discSymbol = discountType === 'percent' ? `(${discountInput}%) ` : '';
        document.getElementById('discountDisplay').innerText = discSymbol + finalDiscount.toLocaleString();
        
        document.getElementById('total').innerText = currentTotal.toLocaleString();
    }

    function removeItem(index) { cart.splice(index, 1); renderCart(); }
    
    function clearCart() { 
        cart = []; 
        // Reset discount ONLY if the customer isn't selected, or generally reset everything
        // document.getElementById('manualDiscount').value = '';
        document.getElementById('couponCode').value = '';
        document.getElementById('orderNote').value = '';
        renderCart(); 
    }

    function openCheckoutModal() {
        if(cart.length === 0) return;
        document.getElementById('modalTotal').innerText = currentTotal.toLocaleString();
        document.getElementById('amountPaid').value = currentTotal;
        calculateChange();
        setTimeout(() => document.getElementById('amountPaid').focus(), 100);
        document.getElementById('checkoutModal').classList.remove('hidden');
    }

    function calculateChange() {
        const paid = parseFloat(document.getElementById('amountPaid').value) || 0;
        const change = paid - currentTotal;
        const changeEl = document.getElementById('changeAmount');
        const confirmBtn = document.getElementById('confirmPayBtn');
        changeEl.innerText = change.toLocaleString();
        
        if (change < 0) {
            changeEl.classList.replace('text-yellow-400', 'text-red-500');
            confirmBtn.disabled = true;
            confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            changeEl.classList.replace('text-red-500', 'text-yellow-400');
            confirmBtn.disabled = false;
            confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    function selectPaymentMethod(method, btn) {
        document.getElementById('selectedPaymentMethod').value = method;
        document.querySelectorAll('.pay-btn').forEach(b => {
            b.classList.remove('bg-blue-600', 'text-white', 'border-blue-500', 'active');
            b.classList.add('bg-gray-800', 'text-gray-400', 'border-gray-600');
        });
        btn.classList.remove('bg-gray-800', 'text-gray-400', 'border-gray-600');
        btn.classList.add('bg-blue-600', 'text-white', 'border-blue-500', 'active');
    }

    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    async function processPayment() {
        const btn = document.getElementById('confirmPayBtn');
        const spinner = document.getElementById('spinner');
        const customerId = document.getElementById('customerSelect').value;
        const paymentMethod = document.getElementById('selectedPaymentMethod').value;
        const paidAmount = parseFloat(document.getElementById('amountPaid').value) || 0;
        const couponCode = document.getElementById('couponCode').value;
        const note = document.getElementById('orderNote').value;

        // Recalculate Final Discount for Safety
        let discountInput = parseFloat(document.getElementById('manualDiscount').value) || 0;
        const discountType = document.getElementById('discountType').value;
        let subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        let finalDiscount = 0;

        if (discountType === 'percent') {
            if(discountInput > 100) discountInput = 100;
            finalDiscount = subtotal * (discountInput / 100);
        } else {
            finalDiscount = discountInput;
        }

        if (paidAmount < currentTotal) return alertToast("Insufficient Amount", "error");

        btn.disabled = true;
        spinner.classList.remove('hidden');

        try {
            const res = await fetch('/api/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    cart: cart, 
                    total: currentTotal,
                    customer_id: customerId,
                    payment_method: paymentMethod,
                    discount: finalDiscount, // Sending Value (Ks)
                    coupon: couponCode,
                    note: note
                })
            });

            const text = await res.text();
            let data;
            try { data = JSON.parse(text); } catch (e) { throw new Error("Server Error"); }
            
            if (data.success) {
                closeModal('checkoutModal');
                document.getElementById('successOrderId').innerText = '#' + data.order_id;
                document.getElementById('successChange').innerText = (paidAmount - currentTotal).toLocaleString();
                document.getElementById('successModal').classList.remove('hidden');
                btn.disabled = false;
                spinner.classList.add('hidden');
            } else {
                alertToast(data.message || 'Payment failed', 'error');
                btn.disabled = false;
                spinner.classList.add('hidden');
            }
        } catch (err) {
            alertToast('Connection Error', 'error');
            btn.disabled = false;
            spinner.classList.add('hidden');
        }
    }

    function finishOrder() { closeModal('successModal'); clearCart(); }

    function printInvoice(format) {
        const orderId = document.getElementById('successOrderId').innerText.replace('#', '');
        window.open(`/admin/invoice/print?id=${orderId}&format=${format}`, 'PrintInvoice', 'width=800,height=800');
    }
</script>