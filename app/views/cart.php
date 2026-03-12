<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="pb-24 max-w-lg mx-auto">

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Checkout Summary</h2>
        <a href="/shop" class="text-slate-400 hover:text-slate-700 text-xl leading-none transition">✕</a>
    </div>

    <!-- 1. Cart Items -->
    <div id="cartItemsList" class="space-y-3 mb-6"></div>

    <!-- FORM (hidden when cart is empty via JS) -->
    <form id="checkoutForm" onsubmit="event.preventDefault(); submitOrder();">

        <!-- 2. Delivery Method -->
        <label class="block text-slate-500 text-xs mb-2 uppercase font-bold tracking-wider">Delivery Method</label>
        <div class="glass-panel bg-white border border-slate-200 p-4 rounded-xl mb-6 shadow-sm">
            <div class="relative">
                <select name="delivery_id" id="deliverySelect" required onchange="calculateTotal()"
                        class="w-full bg-white text-slate-800 border border-slate-300 rounded-lg p-3 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                    <option value="" disabled selected>-- Select Delivery Area --</option>
                    <?php foreach ($delivery_methods as $dm): ?>
                        <option value="<?= (int) $dm['id'] ?>" data-cost="<?= htmlspecialchars((string)(float) $dm['cost'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($dm['name'], ENT_QUOTES, 'UTF-8') ?> (+<?= number_format($dm['cost']) ?> Ks)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 3. Payment Method -->
        <label class="block text-slate-500 text-xs mb-2 uppercase font-bold tracking-wider">Payment Method</label>
        <div class="glass-panel bg-white border border-slate-200 p-4 rounded-xl mb-6 shadow-sm">
            <div class="relative">
                <select name="payment_id" id="paymentSelect" required onchange="showPaymentDetails()"
                        class="w-full bg-white text-slate-800 border border-slate-300 rounded-lg p-3 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                    <option value="" disabled selected>-- Select Payment --</option>
                    <?php foreach ($payment_methods as $pm): ?>
                        <option value="<?= (int) $pm['id'] ?>"
                                data-acc="<?= htmlspecialchars($pm['account_number'], ENT_QUOTES, 'UTF-8') ?>"
                                data-name="<?= htmlspecialchars($pm['account_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($pm['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>

            <!-- Payment account details -->
            <div id="paymentDetails" class="hidden mt-4 pt-4 border-t border-slate-100">
                <div class="flex justify-between items-center">
                    <div>
                        <div id="payAccName" class="text-xs text-slate-500 mb-0.5">Account Name</div>
                        <div id="payAccNum" class="text-lg font-mono text-slate-800 font-bold tracking-wider">00000</div>
                    </div>
                    <button type="button" onclick="copyPayment()"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs px-3 py-2 rounded-lg transition border border-slate-200">
                        📋 Copy
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Order Summary -->
        <div class="glass-panel bg-white border border-slate-200 p-4 rounded-xl mb-6 shadow-sm">
            <div class="flex justify-between text-slate-500 text-sm mb-2">
                <span>Subtotal</span>
                <span id="displaySubtotal" class="text-slate-700 font-medium">0 Ks</span>
            </div>
            <div class="flex justify-between text-sm mb-2">
                <span class="text-slate-500">Discount</span>
                <span id="displayDiscount" class="text-red-500 font-medium">- 0 Ks</span>
            </div>
            <div class="flex justify-between text-sm mb-4 pb-4 border-b border-slate-100">
                <span class="text-slate-500">Delivery</span>
                <span id="displayDelivery" class="text-blue-600 font-medium">+ 0 Ks</span>
            </div>
            <div class="flex justify-between text-slate-800 text-xl font-bold">
                <span>Grand Total</span>
                <span id="displayGrandTotal">0 Ks</span>
            </div>
        </div>

        <!-- 5. Coupon -->
        <div class="mb-6">
            <div class="flex gap-2">
                <input type="text" id="couponCode" placeholder="PROMO CODE"
                       class="flex-1 bg-white border border-slate-300 text-slate-800 placeholder-slate-400 p-3 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase">
                <button type="button" onclick="applyCoupon()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 rounded-lg font-bold text-sm transition">
                    Apply
                </button>
            </div>
            <p id="couponMsg" class="text-xs mt-1 h-4"></p>
        </div>

        <!-- 6. Customer Info -->
        <div class="space-y-4 mb-6">
            <input type="text" name="name" placeholder="Full Name *" required
                   class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <input type="tel" name="phone" placeholder="Phone Number *" required
                   class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <textarea name="address" placeholder="Delivery Address *" rows="2" required
                      class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
        </div>

        <!-- 7. Payment Slip Upload -->
        <div class="mb-8">
            <label class="block text-slate-500 text-xs mb-2 uppercase font-bold tracking-wider">
                Upload Payment Receipt
            </label>
            <input type="file" name="receipt" required accept="image/*"
                   class="w-full bg-white border border-slate-300 text-slate-500 p-2 rounded-lg
                          file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                          file:bg-blue-600 file:text-white file:font-semibold
                          hover:file:bg-blue-700 file:transition cursor-pointer">
        </div>

        <!-- Place Order Button -->
        <button type="submit" id="submitBtn"
                class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-md transition text-lg tracking-wide">
            Confirm Order
        </button>

    </form>
</div>

<script>
    // --- 0. HTML ESCAPE HELPER ---
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // --- 1. SETUP ---
    const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    const CART_KEY = 'cart';

    let cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];
    let subtotal = 0;
    let deliveryCost = 0;
    let discountAmount = 0;
    let activeCouponCode = null;
    const FREE_SHIPPING_LIMIT = <?= (int) ($free_shipping_min ?? 0) ?>;

    // --- 2. RENDER CART ---
    function initCheckout() {
        cart = JSON.parse(localStorage.getItem(CART_KEY)) || [];

        const container = document.getElementById('cartItemsList');
        const form = document.getElementById('checkoutForm');

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="text-center py-16 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <div class="text-6xl mb-4">🛒</div>
                    <p class="text-slate-600 font-semibold text-lg mb-1">Your cart is empty</p>
                    <p class="text-slate-400 text-sm mb-6">Looks like you haven't added anything yet.</p>
                    <a href="/shop"
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-lg transition text-sm">
                        Shop Now
                    </a>
                </div>`;
            form.classList.add('hidden');
            return;
        }

        form.classList.remove('hidden');
        let html = '';
        subtotal = 0;

        cart.forEach((item, index) => {
            subtotal += item.price * item.qty;
            const imgTag = item.image
                ? `<img src="${escHtml(item.image)}" alt="" class="w-14 h-14 object-cover rounded-lg border border-slate-100 flex-shrink-0">`
                : `<div class="w-14 h-14 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0 text-2xl">🛍️</div>`;
            html += `
                <div class="flex items-center gap-3 bg-white border border-slate-200 p-3 rounded-xl shadow-sm">
                    ${imgTag}
                    <div class="flex-1 min-w-0">
                        <div class="text-slate-800 text-sm font-semibold leading-snug truncate">${escHtml(item.name)}</div>
                        <div class="text-slate-400 text-xs mt-0.5">${item.price.toLocaleString()} Ks each</div>
                    </div>
                    <div class="text-blue-600 font-bold text-sm flex-shrink-0 mr-2">
                        ${(item.price * item.qty).toLocaleString()} Ks
                    </div>
                    <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden flex-shrink-0">
                        <button type="button" onclick="modQty(${index}, -1)"
                                class="px-2 py-1 text-slate-600 hover:bg-slate-100 transition text-base leading-none">−</button>
                        <span class="px-2 text-xs font-mono text-slate-800 border-x border-slate-200">${item.qty}</span>
                        <button type="button" onclick="modQty(${index}, 1)"
                                class="px-2 py-1 text-slate-600 hover:bg-slate-100 transition text-base leading-none">+</button>
                    </div>
                </div>`;
        });

        container.innerHTML = html;

        if (activeCouponCode) {
            recheckCoupon();
        } else {
            calculateTotal();
        }
    }

    // --- 3. MODIFY QTY ---
    function modQty(index, change) {
        cart[index].qty += change;
        if (cart[index].qty < 1) cart.splice(index, 1);
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        if (window.updateCartCount) window.updateCartCount();
        initCheckout();
    }

    // --- 4. CALCULATE TOTALS ---
    function calculateTotal() {
        const delSelect = document.getElementById('deliverySelect');
        let selectedDeliveryCost = 0;

        if (delSelect.selectedIndex > 0) {
            selectedDeliveryCost = parseFloat(delSelect.options[delSelect.selectedIndex].getAttribute('data-cost'));
        }

        let isFree = false;
        if (FREE_SHIPPING_LIMIT > 0 && subtotal >= FREE_SHIPPING_LIMIT) {
            deliveryCost = 0;
            isFree = true;
        } else {
            deliveryCost = selectedDeliveryCost;
        }

        let grandTotal = subtotal + deliveryCost - discountAmount;
        if (grandTotal < 0) grandTotal = 0;

        document.getElementById('displaySubtotal').innerText = subtotal.toLocaleString() + ' Ks';
        document.getElementById('displayDiscount').innerText = '- ' + discountAmount.toLocaleString() + ' Ks';

        const delDisplay = document.getElementById('displayDelivery');
        if (isFree) {
            delDisplay.innerHTML = `<span class="line-through text-slate-400 mr-1 text-xs">${selectedDeliveryCost.toLocaleString()} Ks</span><span class="text-green-600 font-bold">FREE</span>`;
        } else {
            delDisplay.innerText = '+ ' + deliveryCost.toLocaleString() + ' Ks';
        }

        document.getElementById('displayGrandTotal').innerText = grandTotal.toLocaleString() + ' Ks';
        document.getElementById('submitBtn').innerText = `Confirm Order — ${grandTotal.toLocaleString()} Ks`;
    }

    // --- 5. PAYMENT DETAILS ---
    function showPaymentDetails() {
        const select = document.getElementById('paymentSelect');
        const opt = select.options[select.selectedIndex];
        const details = document.getElementById('paymentDetails');

        if (opt.value) {
            document.getElementById('payAccNum').innerText = opt.getAttribute('data-acc');
            document.getElementById('payAccName').innerText = opt.getAttribute('data-name');
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
            timer: 2000
        });
    }

    // --- 6. COUPON LOGIC ---
    async function applyCoupon() {
        const code = document.getElementById('couponCode').value;
        if (!code) return;
        activeCouponCode = code;
        await recheckCoupon(true);
    }

    async function recheckCoupon(showAlerts = false) {
        if (!activeCouponCode) return;

        const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
        const msg = document.getElementById('couponMsg');

        try {
            const res = await fetch('/api/coupon/check', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: activeCouponCode, subtotal: subtotal, qty: totalQty })
            });
            const data = await res.json();

            if (data.success) {
                discountAmount = parseFloat(data.amount);
                msg.innerText = data.message || 'Coupon Applied';
                msg.className = 'text-xs mt-1 h-4 text-green-600';
                if (showAlerts) {
                    Swal.fire({
                        icon: 'success', title: 'Success!',
                        text: data.message || 'Discount Applied',
                        toast: true, position: 'top-end',
                        showConfirmButton: false, timer: 2000
                    });
                }
            } else {
                discountAmount = 0;
                msg.innerText = data.message;
                msg.className = 'text-xs mt-1 h-4 text-red-500';
                if (showAlerts) {
                    Swal.fire({
                        icon: 'error', title: 'Oops...',
                        text: data.message,
                        toast: true, position: 'top-end',
                        showConfirmButton: false, timer: 3000
                    });
                }
            }
            calculateTotal();
        } catch (e) { console.error(e); }
    }

    // --- 7. SUBMIT ORDER ---
    async function submitOrder() {
        if (!isLoggedIn) {
            Swal.fire({
                icon: 'warning',
                title: 'Login Required',
                text: 'Please login to place an order.',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Login Now'
            }).then(result => {
                if (result.isConfirmed) window.location.href = '/login';
            });
            return;
        }

        if (cart.length === 0) {
            Swal.fire({ icon: 'error', title: 'Cart is Empty' });
            return;
        }

        const delSelect = document.getElementById('deliverySelect');
        if (delSelect.selectedIndex === 0) {
            Swal.fire({ icon: 'error', title: 'Delivery Method', text: 'Please select a delivery method.' });
            return;
        }

        const result = await Swal.fire({
            title: 'Confirm Order?',
            text: 'Are you sure you want to place this order?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Place Order'
        });

        if (!result.isConfirmed) return;

        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Processing…';

        const formData = new FormData(document.getElementById('checkoutForm'));
        formData.append('cart', JSON.stringify(cart));
        formData.append('subtotal', subtotal);
        formData.append('delivery_cost', deliveryCost);
        formData.append('discount_amount', discountAmount);
        formData.append('grand_total', subtotal + deliveryCost - discountAmount);
        formData.append('delivery_id', delSelect.value);
        if (activeCouponCode && discountAmount > 0) {
            formData.append('coupon_code', activeCouponCode);
        }

        try {
            const res = await fetch('/api/shop/checkout', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                localStorage.removeItem(CART_KEY);
                if (window.updateCartCount) window.updateCartCount();
                await Swal.fire({
                    icon: 'success',
                    title: 'Order Placed!',
                    text: 'Your Order ID is #' + data.order_id,
                    confirmButtonColor: '#2563eb'
                });
                window.location.href = '/profile';
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                btn.disabled = false;
                btn.innerText = originalText;
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Please try again.' });
            btn.disabled = false;
            btn.innerText = originalText;
        }
    }

    initCheckout();
</script>