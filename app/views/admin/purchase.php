<div class="flex flex-col h-[calc(100vh-80px)]">
    
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Purchase Stock</h1>
            <p class="text-gray-400 text-sm">Manage inventory purchases from suppliers</p>
        </div>
        <div class="flex gap-3">
            <a href="/admin/purchases" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left"></i> History
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="flex flex-1 gap-6 overflow-hidden">
        
        <!-- LEFT: PRODUCT SELECTION -->
        <div class="w-2/3 flex flex-col gap-4">
            <!-- Search Bar -->
            <div class="relative">
                <i class="fa-solid fa-search absolute left-4 top-3.5 text-gray-400"></i>
                <input type="text" id="productSearch" onkeyup="filterProducts()" placeholder="Search products by name..." 
                    class="w-full bg-[#1e293b] border border-gray-700 text-white pl-11 pr-4 py-3 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder-gray-500">
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-3 xl:grid-cols-4 gap-3 overflow-y-auto pr-2 custom-scrollbar" id="productGrid">
                <?php foreach($products as $p): ?>
                <div onclick="addToCart(<?= htmlspecialchars(json_encode($p)) ?>)" 
                     class="product-card bg-[#1e293b] border border-gray-700 hover:border-blue-500 rounded-xl p-3 cursor-pointer transition-all hover:shadow-lg hover:-translate-y-1 group relative">
                    
                    <div class="aspect-square bg-gray-800 rounded-lg mb-3 overflow-hidden relative">
                        <?php if($p['image']): ?>
                            <img src="/<?= $p['image'] ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-600">
                                <i class="fa-solid fa-box text-2xl"></i>
                            </div>
                        <?php endif; ?>
                        <div class="absolute top-1 right-1 bg-black/60 text-white text-xs px-1.5 py-0.5 rounded backdrop-blur-sm">
                            Stock: <?= $p['stock'] ?>
                        </div>
                    </div>
                    
                    <h3 class="text-gray-200 font-semibold text-sm truncate group-hover:text-blue-400"><?= $p['name'] ?></h3>
                    <p class="text-gray-500 text-xs mt-1">Cost: <?= number_format($p['price']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIGHT: PURCHASE CART -->
        <div class="w-1/3 flex flex-col bg-[#1e293b] rounded-2xl border border-gray-700 h-full shadow-2xl">
            
            <!-- Cart Header -->
            <div class="p-4 border-b border-gray-700 bg-gray-800/50 rounded-t-2xl">
                <div class="flex justify-between items-center mb-3">
                    <span class="font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-cart-flatbed"></i> Purchase List
                    </span>
                    <span id="cartCount" class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full">0 items</span>
                </div>
                
                <!-- Supplier & Date Inputs -->
                <div class="space-y-2">
                    <select id="supplierSelect" class="w-full bg-[#0f172a] border border-gray-600 text-white rounded-lg p-2 text-xs focus:border-blue-500 outline-none">
                        <option value="" disabled selected>-- Select Supplier --</option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="datetime-local" id="purchaseDate" class="w-full bg-[#0f172a] border border-gray-600 text-white rounded-lg p-2 text-xs focus:border-blue-500 outline-none" value="<?= date('Y-m-d\TH:i') ?>">
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="flex-1 overflow-y-auto p-2 space-y-2" id="cartContainer">
                <div class="h-full flex flex-col items-center justify-center text-gray-500 opacity-50">
                    <i class="fa-solid fa-basket-shopping text-4xl mb-3"></i>
                    <p class="text-sm">No items added</p>
                </div>
            </div>

            <!-- Footer & Actions -->
            <div class="p-4 bg-gray-800/50 border-t border-gray-700 rounded-b-2xl">
                <div class="flex justify-between items-end mb-4">
                    <span class="text-gray-400 text-sm">Grand Total</span>
                    <span class="text-2xl font-bold text-emerald-400" id="grandTotal">0</span>
                </div>
                
                <form method="POST" id="purchaseForm" onsubmit="submitForm(event)">
                    <input type="hidden" name="supplier_id" id="hiddenSupplier">
                    <input type="hidden" name="purchase_date" id="hiddenDate">
                    <input type="hidden" name="grand_total" id="hiddenTotal">
                    <input type="hidden" name="items_json" id="hiddenItems">

                    <button type="submit" id="submitBtn" disabled class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-xl font-bold text-lg shadow-lg shadow-emerald-500/20 transition transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        Confirm Purchase
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- TOAST -->
<?php if(!empty($message)): ?>
<div id="toast" class="fixed top-5 right-5 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-xl z-50 animate-bounce">
    <?= $message ?>
</div>
<script>setTimeout(() => document.getElementById('toast').remove(), 3000);</script>
<?php endif; ?>

<script>
    // Shopping Cart State
    let cart = [];

    function filterProducts() {
        const input = document.getElementById('productSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');
        cards.forEach(card => {
            const name = card.querySelector('h3').innerText.toLowerCase();
            card.style.display = name.includes(input) ? 'block' : 'none';
        });
    }

    function addToCart(product) {
        // Check if exists
        const existing = cart.find(item => item.id === product.id);
        
        if (existing) {
            existing.qty++;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                image: product.image,
                stock: product.stock,
                cost: parseFloat(product.price), // Default cost to current selling price
                qty: 1
            });
        }
        renderCart();
    }

    function updateItem(index, field, value) {
        if (field === 'qty') {
            cart[index].qty = parseInt(value);
            if (cart[index].qty < 1) cart[index].qty = 1;
        } else if (field === 'cost') {
            cart[index].cost = parseFloat(value);
            if (cart[index].cost < 0) cart[index].cost = 0;
        }
        renderCart(); // Re-render to update subtotals
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cartContainer');
        const countBadge = document.getElementById('cartCount');
        const totalDisplay = document.getElementById('grandTotal');
        const submitBtn = document.getElementById('submitBtn');

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-gray-500 opacity-50">
                    <i class="fa-solid fa-basket-shopping text-4xl mb-3"></i>
                    <p class="text-sm">No items added</p>
                </div>`;
            countBadge.innerText = "0 items";
            totalDisplay.innerText = "0";
            submitBtn.disabled = true;
            return;
        }

        let html = '';
        let grandTotal = 0;

        cart.forEach((item, index) => {
            const subtotal = item.qty * item.cost;
            grandTotal += subtotal;

            html += `
                <div class="bg-[#0f172a] rounded-lg p-2 border border-gray-700 relative group flex gap-2">
                    <!-- Image -->
                    <div class="w-12 h-12 bg-gray-700 rounded overflow-hidden flex-shrink-0">
                        ${item.image ? `<img src="/${item.image}" class="w-full h-full object-cover">` : ''}
                    </div>
                    
                    <!-- Details & Inputs -->
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="text-white font-bold text-xs truncate max-w-[120px]">${item.name}</h4>
                            <button onclick="removeItem(${index})" class="text-gray-500 hover:text-red-400 text-xs"><i class="fa-solid fa-times"></i></button>
                        </div>
                        
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="text-[9px] text-gray-500 block uppercase">Qty</label>
                                <input type="number" min="1" value="${item.qty}" onchange="updateItem(${index}, 'qty', this.value)" class="w-full bg-gray-800 border border-gray-600 rounded px-1 py-0.5 text-xs text-white text-center focus:border-blue-500 outline-none">
                            </div>
                            <div class="flex-1">
                                <label class="text-[9px] text-gray-500 block uppercase">Unit Cost</label>
                                <input type="number" min="0" step="0.01" value="${item.cost}" onchange="updateItem(${index}, 'cost', this.value)" class="w-full bg-gray-800 border border-gray-600 rounded px-1 py-0.5 text-xs text-white text-center focus:border-blue-500 outline-none">
                            </div>
                        </div>
                        <div class="text-right mt-1 text-[10px] text-emerald-400 font-bold">= ${subtotal.toLocaleString()}</div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        countBadge.innerText = cart.length + " items";
        totalDisplay.innerText = grandTotal.toLocaleString();
        submitBtn.disabled = false;
    }

    function submitForm(e) {
        const supplier = document.getElementById('supplierSelect').value;
        const date = document.getElementById('purchaseDate').value;
        
        if (!supplier) {
            e.preventDefault();
            alert("Please select a supplier");
            return;
        }
        if (!date) {
            e.preventDefault();
            alert("Please select a date");
            return;
        }

        // Calculate final total again just to be sure
        const total = cart.reduce((sum, item) => sum + (item.qty * item.cost), 0);

        // Fill Hidden Inputs
        document.getElementById('hiddenSupplier').value = supplier;
        document.getElementById('hiddenDate').value = date;
        document.getElementById('hiddenTotal').value = total;
        document.getElementById('hiddenItems').value = JSON.stringify(cart);

        // Form submits naturally now
    }
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
</style>