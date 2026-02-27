<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="pb-24 max-w-lg mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white">Checkout Summary</h2>
        <a href="/shop" class="text-gray-400 hover:text-white">✕</a>
    </div>

    <!-- 1. Cart Items -->
    <div id="cartItemsList" class="space-y-3 mb-6"></div>

    <!-- FORM -->
    <form id="checkoutForm" onsubmit="event.preventDefault(); submitOrder();">
        
        <!-- 2. Delivery Method (Dropdown) -->
        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold tracking-wider">Delivery Method</label>
        <div class="glass-panel p-4 rounded-xl border border-blue-500/30 bg-blue-900/10 mb-6">
            <div class="relative">
                <select name="delivery_id" id="deliverySelect" required onchange="calculateTotal()" class="w-full bg-gray-800 text-white border border-gray-600 rounded-lg p-3 appearance-none focus:border-blue-500 outline-none cursor-pointer">
                    <option value="" disabled selected>-- Select Delivery Area --</option>
                    <?php foreach($delivery_methods as $dm): ?>
                        <option value="<?= $dm['id'] ?>" data-cost="<?= $dm['cost'] ?>">
                            <?= htmlspecialchars($dm['name']) ?> (+<?= number_format($dm['cost']) ?> Ks)
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Arrow Icon -->
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <!-- 3. Payment Method (Dropdown) -->
        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold tracking-wider">Payment Method</label>
        <div class="glass-panel p-4 rounded-xl border border-green-500/30 bg-green-900/10 mb-6">
            <div class="relative">
                <select name="payment_id" id="paymentSelect" required onchange="showPaymentDetails()" class="w-full bg-gray-800 text-white border border-gray-600 rounded-lg p-3 appearance-none focus:border-green-500 outline-none cursor-pointer">
                    <option value="" disabled selected>-- Select Payment --</option>
                    <?php foreach($payment_methods as $pm): ?>
                        <option value="<?= $pm['id'] ?>" data-acc="<?= htmlspecialchars($pm['account_number']) ?>" data-name="<?= htmlspecialchars($pm['account_name']) ?>">
                            <?= htmlspecialchars($pm['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Arrow Icon -->
                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            <!-- Selected Payment Details Display -->
            <div id="paymentDetails" class="hidden mt-4 pt-4 border-t border-white/10">
                <div class="flex justify-between items-center">
                    <div>
                        <div id="payAccName" class="text-xs text-gray-400">Account Name</div>
                        <div id="payAccNum" class="text-lg font-mono text-white font-bold tracking-wider">00000</div>
                    </div>
                    <button type="button" onclick="copyPayment()" class="bg-gray-700 hover:bg-gray-600 text-xs text-white px-3 py-2 rounded transition">
                        📋 Copy
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Summary -->
        <div class="glass-panel p-4 rounded-xl mb-6">
            <div class="flex justify-between text-gray-400 text-sm mb-1">
                <span>Subtotal:</span>
                <span id="displaySubtotal">0 Ks</span>
            </div>
            <div class="flex justify-between text-red-400 text-sm mb-1">
                <span>Discount:</span>
                <span id="displayDiscount">- 0 Ks</span>
            </div>
            <div class="flex justify-between text-blue-400 text-sm mb-4 pb-4 border-b border-white/10">
                <span>Delivery:</span>
                <span id="displayDelivery">+ 0 Ks</span>
            </div>
            <div class="flex justify-between text-white text-xl font-bold">
                <span>Grand Total:</span>
                <span id="displayGrandTotal">0 Ks</span>
            </div>
        </div>

        <!-- 5. Coupon / Promo -->
        <div class="mb-6">
            <div class="flex gap-2">
                <input type="text" id="couponCode" placeholder="PROMO CODE" class="flex-1 bg-gray-800 border border-gray-700 text-white p-3 rounded-lg text-sm outline-none focus:border-blue-500 uppercase">
                <button type="button" onclick="applyCoupon()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 rounded-lg font-bold text-sm">Apply</button>
            </div>
            <p id="couponMsg" class="text-xs mt-1 h-4"></p>
        </div>

        <!-- 6. Details -->
        <div class="space-y-4 mb-6">
            <input type="text" name="name" placeholder="Full Name" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg outline-none focus:border-blue-500">
            <input type="tel" name="phone" placeholder="Phone Number" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg outline-none focus:border-blue-500">
            <textarea name="address" placeholder="Address" rows="2" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg outline-none focus:border-blue-500"></textarea>
        </div>

        <!-- 7. Receipt -->
        <div class="mb-8">
            <label class="block text-gray-400 text-xs mb-2">Payment Receipt (Screenshot)</label>
            <input type="file" name="receipt" required accept="image/*" class="w-full bg-gray-800 border border-gray-700 text-gray-400 p-2 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-500">
        </div>

        <button type="submit" id="submitBtn" class="w-full bg-blue-500 hover:bg-blue-400 text-white font-bold py-4 rounded-xl shadow-lg transition text-lg">
            Confirm Order
        </button>

    </form>
</div>

<script>
    // --- 1. SETUP ---
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    const CART_KEY = 'cart'; 
    
    let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
    let subtotal = 0;
    let deliveryCost = 0;
    let discountAmount = 0;
    let activeCouponCode = null; // Store active code for recalculation
    const FREE_SHIPPING_LIMIT = <?= $free_shipping_min ?? 0 ?>;

    // --- 2. RENDER CART ---
    function initCheckout() {
        // Refresh cart from storage
        cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
        
        const container = document.getElementById('cartItemsList');
        if(cart.length === 0) {
            container.innerHTML = '<div class="text-center py-10"><div class="text-4xl mb-2">🛒</div><p class="text-gray-500">Cart is empty</p><a href="/shop" class="text-blue-400 text-sm mt-2 block">Go Shopping</a></div>';
            return;
        }
        
        let html = '';
        subtotal = 0;
        
        cart.forEach((item, index) => {
            subtotal += item.price * item.qty;
            html += `
                <div class="flex justify-between items-center bg-gray-800/50 p-3 rounded-lg border border-white/5">
                    <div class="text-white text-sm font-bold flex-1 mr-2 leading-tight">
                        ${item.name}
                    </div>
                    <div class="text-white font-bold text-sm flex-shrink-0 mr-4 text-blue-400">
                        ${(item.price * item.qty).toLocaleString()} Ks
                    </div>
                    <div class="flex bg-gray-700 rounded-md flex-shrink-0">
                        <button type="button" onclick="modQty(${index}, -1)" class="px-2 text-white hover:bg-gray-600 rounded-l">-</button>
                        <span class="px-1 text-xs flex items-center w-6 justify-center font-mono text-white">${item.qty}</span>
                        <button type="button" onclick="modQty(${index}, 1)" class="px-2 text-white hover:bg-gray-600 rounded-r">+</button>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;

        // If a coupon is active, recheck it (for tiered logic or min spend)
        if (activeCouponCode) {
            recheckCoupon();
        } else {
            calculateTotal();
        }
    }

    // --- 3. MODIFY QTY ---
    function modQty(index, change) {
        cart[index].qty += change;
        if(cart[index].qty < 1) cart.splice(index, 1);
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        
        // Update header badge if function exists
        if(window.updateCartCount) window.updateCartCount();
        
        initCheckout();
    }

    // --- 4. CALCULATE TOTALS ---
    function calculateTotal() {
        const delSelect = document.getElementById('deliverySelect');
        let selectedDeliveryCost = 0;
        
        if (delSelect.selectedIndex > 0) {
            const option = delSelect.options[delSelect.selectedIndex];
            selectedDeliveryCost = parseFloat(option.getAttribute('data-cost'));
        }

        let isFree = false;
        if (FREE_SHIPPING_LIMIT > 0 && subtotal >= FREE_SHIPPING_LIMIT) {
            deliveryCost = 0;
            isFree = true;
        } else {
            deliveryCost = selectedDeliveryCost;
        }

        // Prevent negative
        let grandTotal = subtotal + deliveryCost - discountAmount;
        if (grandTotal < 0) grandTotal = 0;

        document.getElementById('displaySubtotal').innerText = subtotal.toLocaleString() + ' Ks';
        
        const delDisplay = document.getElementById('displayDelivery');
        if (isFree) {
            delDisplay.innerHTML = `<span class="text-green-400 line-through mr-2 text-xs opacity-70">${selectedDeliveryCost.toLocaleString()} Ks</span> <span class="text-green-400 font-bold">FREE</span>`;
        } else {
            delDisplay.innerText = '+ ' + deliveryCost.toLocaleString() + ' Ks';
        }

        document.getElementById('displayDiscount').innerText = '- ' + discountAmount.toLocaleString() + ' Ks';
        document.getElementById('displayGrandTotal').innerText = grandTotal.toLocaleString() + ' Ks';
        document.getElementById('submitBtn').innerText = `Confirm Order - ${grandTotal.toLocaleString()} Ks`;
    }

    function showPaymentDetails() {
        const select = document.getElementById('paymentSelect');
        const selectedOption = select.options[select.selectedIndex];
        const details = document.getElementById('paymentDetails');
        
        if (selectedOption.value) {
            document.getElementById('payAccNum').innerText = selectedOption.getAttribute('data-acc');
            document.getElementById('payAccName').innerText = selectedOption.getAttribute('data-name');
            details.classList.remove('hidden');
        } else {
            details.classList.add('hidden');
        }
    }

    function copyPayment() {
        const num = document.getElementById('payAccNum').innerText;
        navigator.clipboard.writeText(num);
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Account number copied to clipboard',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            background: '#1f2937',
            color: '#fff'
        });
    }

    // --- 5. COUPON LOGIC (UPDATED) ---
    async function applyCoupon() {
        const code = document.getElementById('couponCode').value;
        if(!code) return;
        
        activeCouponCode = code;
        await recheckCoupon(true); // true = Show alert popup
    }

    // Helper to silently re-verify coupon when qty changes
    async function recheckCoupon(showAlerts = false) {
        if (!activeCouponCode) return;

        const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
        const msg = document.getElementById('couponMsg');

        try {
            const res = await fetch('/api/coupon/check', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ code: activeCouponCode, subtotal: subtotal, qty: totalQty })
            });
            const data = await res.json();
            
            if(data.success) {
                discountAmount = parseFloat(data.amount);
                msg.innerText = data.message || "Coupon Applied";
                msg.className = "text-xs mt-1 h-4 text-green-400";
                
                if (showAlerts) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Discount Applied',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        background: '#1f2937',
                        color: '#fff'
                    });
                }
            } else {
                // If they dip below tier requirement, reset discount to 0 but keep code
                discountAmount = 0;
                msg.innerText = data.message;
                msg.className = "text-xs mt-1 h-4 text-red-400";
                
                if (showAlerts) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        background: '#1f2937',
                        color: '#fff'
                    });
                }
            }
            calculateTotal(); // Update UI
        } catch(e) { console.error(e); }
    }

    // --- 6. SUBMIT ORDER ---
    async function submitOrder() {
        if (!isLoggedIn) {
            Swal.fire({
                icon: 'warning',
                title: 'Login Required',
                text: 'Please login to place an order.',
                background: '#1f2937',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Login Now'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/login';
                }
            });
            return;
        }

        if(cart.length === 0) return Swal.fire({icon: 'error', title:'Cart is Empty', background: '#1f2937', color: '#fff'});

        const result = await Swal.fire({
            title: 'Confirm Order?',
            text: "Are you sure you want to place this order?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Place Order',
            background: '#1f2937',
            color: '#fff'
        });

        if (!result.isConfirmed) return;

        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = "Processing...";

        const formData = new FormData(document.getElementById('checkoutForm'));
        formData.append('cart', JSON.stringify(cart));
        formData.append('subtotal', subtotal);
        formData.append('delivery_cost', deliveryCost);
        formData.append('discount_amount', discountAmount);
        formData.append('grand_total', subtotal + deliveryCost - discountAmount);
        
        // Pass coupon code so backend can increment usage
        if (activeCouponCode && discountAmount > 0) {
            formData.append('coupon_code', activeCouponCode);
        }
        
        const delSelect = document.getElementById('deliverySelect');
        if (delSelect.selectedIndex === 0) {
            Swal.fire({icon: 'error', title:'Delivery Method', text: 'Please select a delivery method', background: '#1f2937', color: '#fff'});
            btn.disabled = false;
            btn.innerText = originalText;
            return;
        }
        formData.append('delivery_id', delSelect.value);

        try {
            const res = await fetch('/api/shop/checkout', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(data.success) {
                localStorage.removeItem(CART_KEY);
                if(window.updateCartCount) window.updateCartCount();
                
                await Swal.fire({
                    icon: 'success',
                    title: 'Order Placed!',
                    text: 'Your Order ID is #' + data.order_id,
                    background: '#1f2937',
                    color: '#fff',
                    confirmButtonColor: '#3b82f6'
                });
                window.location.href = '/profile'; 
            } else {
                Swal.fire({icon: 'error', title: 'Error', text: data.message, background: '#1f2937', color: '#fff'});
                btn.disabled = false;
                btn.innerText = originalText;
            }
        } catch(e) {
            Swal.fire({icon: 'error', title: 'Connection Error', text: 'Please try again.', background: '#1f2937', color: '#fff'});
            btn.disabled = false;
            btn.innerText = originalText;
        }
    }

    initCheckout();
</script>