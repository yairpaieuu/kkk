<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $title ?? 'Store' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #e2e8f0; }
        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar { -ms-overflow-style: none;  scrollbar-width: none; }
        .glass-nav { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); border-top: 1px solid rgba(255,255,255,0.05); }
        .glass-header { background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255,255,255,0.05); }
    </style>
</head>
<body class="flex flex-col min-h-screen relative">

    <!-- ========================================== -->
    <!-- 🖥️ DESKTOP HEADER (Hidden on Mobile)       -->
    <!-- ========================================== -->
    <header class="hidden md:flex fixed top-0 w-full z-50 glass-header transition-all duration-300">
        <div class="max-w-7xl mx-auto w-full px-6 h-20 flex items-center justify-between">
            
            <!-- 1. Logo (UPDATED) -->
            <a href="/" class="flex items-center gap-3 group">
                <img src="/assets/logo.png" alt="Logo" class="h-10 w-auto object-contain group-hover:scale-105 transition duration-300" loading="eager">
                <div class="flex flex-col">
                    <span class="text-xl font-bold text-white tracking-tight leading-none group-hover:text-blue-400 transition">KKK LED</span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-widest">Shop</span>
                </div>
            </a>

            <!-- 2. Desktop Navigation -->
            <nav class="flex items-center gap-8 bg-white/5 px-8 py-2.5 rounded-full border border-white/5 backdrop-blur-md">
                <a href="/" class="text-sm font-medium <?= $_SERVER['REQUEST_URI'] == '/' ? 'text-white' : 'text-gray-400 hover:text-white' ?> transition">Home</a>
                <a href="/shop" class="text-sm font-medium <?= $_SERVER['REQUEST_URI'] == '/shop' ? 'text-white' : 'text-gray-400 hover:text-white' ?> transition">Shop</a>
            </nav>

            <!-- 3. Right Actions (Search, Profile, Cart) -->
            <div class="flex items-center gap-4">
                <!-- Search Icon -->
                <button onclick="toggleSearch()" class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center text-gray-400 hover:text-white transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                <!-- Profile -->
                <a href="/profile" class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center text-gray-400 hover:text-white transition relative group">
                    <i class="fa-regular fa-user text-lg"></i>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="absolute bottom-2 right-2 w-2.5 h-2.5 bg-green-500 rounded-full border-2 border-[#0f172a]"></div>
                    <?php endif; ?>
                </a>

                <!-- Cart Button -->
                <a href="/cart" class="group relative bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-full font-semibold text-sm flex items-center gap-2 transition-all shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Cart</span>
                    <span id="desktop-cart-count" class="bg-white text-blue-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center hidden">0</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ========================================== -->
    <!-- 📱 MOBILE HEADER (Top Bar)                 -->
    <!-- ========================================== -->
    <header class="md:hidden flex items-center justify-between px-4 py-4 bg-[#0f172a] sticky top-0 z-40 border-b border-white/5">
        <!-- Logo (UPDATED) -->
        <a href="/" class="flex items-center gap-2">
            <img src="/assets/logo.png" alt="Logo" class="h-8 w-auto object-contain" loading="eager">
            <span class="font-bold text-lg text-white">KKK LED</span>
        </a>
        <div class="flex items-center gap-3">
            <a href="/profile" class="w-8 h-8 bg-white/5 rounded-full flex items-center justify-center text-white">
                <i class="fa-regular fa-user text-xs"></i>
            </a>
        </div>
    </header>


    <!-- ========================================== -->
    <!-- MAIN CONTENT AREA                          -->
    <!-- ========================================== -->
    <!-- Added padding-top for desktop header spacing -->
    <main class="flex-grow w-full max-w-7xl mx-auto p-4 md:px-6 md:pt-28 pb-24 md:pb-12">
        <?php if (file_exists($childView)) require_once $childView; ?>
    </main>


    <!-- ========================================== -->
    <!-- 📱 MOBILE BOTTOM NAVIGATION (Hidden Desktop) -->
    <!-- ========================================== -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full glass-nav z-50 pb-safe">
        <div class="flex justify-around items-center h-16 px-2">
            
            <a href="/" class="flex flex-col items-center justify-center w-full h-full space-y-1 group">
                <div class="relative p-1.5 rounded-xl group-hover:bg-white/5 transition-colors <?= $_SERVER['REQUEST_URI'] == '/' ? 'text-blue-500' : 'text-gray-500' ?>">
                    <i class="fa-solid fa-house text-xl mb-0.5"></i>
                    <?php if($_SERVER['REQUEST_URI'] == '/'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-blue-500 rounded-full"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] font-medium <?= $_SERVER['REQUEST_URI'] == '/' ? 'text-blue-500' : 'text-gray-500' ?>">Home</span>
            </a>

            <a href="/shop" class="flex flex-col items-center justify-center w-full h-full space-y-1 group">
                <div class="relative p-1.5 rounded-xl group-hover:bg-white/5 transition-colors <?= $_SERVER['REQUEST_URI'] == '/shop' ? 'text-blue-500' : 'text-gray-500' ?>">
                    <i class="fa-solid fa-store text-xl mb-0.5"></i>
                </div>
                <span class="text-[10px] font-medium <?= $_SERVER['REQUEST_URI'] == '/shop' ? 'text-blue-500' : 'text-gray-500' ?>">Shop</span>
            </a>

            <!-- Floating Action Button (Cart) -->
            <div class="relative -top-5">
                <a href="/cart" class="w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-full flex items-center justify-center shadow-lg shadow-blue-500/40 border-4 border-[#0f172a] transform active:scale-95 transition">
                    <i class="fa-solid fa-cart-shopping text-xl text-white"></i>
                    <span id="mobile-cart-count" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full border-2 border-[#0f172a] hidden">0</span>
                </a>
            </div>

            <a href="/profile" class="flex flex-col items-center justify-center w-full h-full space-y-1 group">
                <div class="relative p-1.5 rounded-xl group-hover:bg-white/5 transition-colors <?= $_SERVER['REQUEST_URI'] == '/profile' ? 'text-blue-500' : 'text-gray-500' ?>">
                    <i class="fa-solid fa-user text-xl mb-0.5"></i>
                </div>
                <span class="text-[10px] font-medium <?= $_SERVER['REQUEST_URI'] == '/profile' ? 'text-blue-500' : 'text-gray-500' ?>">Profile</span>
            </a>

            <!-- More Menu (Optional) -->
            <button onclick="toggleMobileMenu()" class="flex flex-col items-center justify-center w-full h-full space-y-1 group md:hidden">
                <div class="relative p-1.5 rounded-xl group-hover:bg-white/5 transition-colors text-gray-500">
                    <i class="fa-solid fa-bars text-xl mb-0.5"></i>
                </div>
                <span class="text-[10px] font-medium text-gray-500">Menu</span>
            </button>

        </div>
    </nav>

    <!-- ========================================== -->
    <!-- 🖥️ DESKTOP FOOTER (Hidden Mobile)          -->
    <!-- ========================================== -->
    <footer class="hidden md:block bg-[#020617] border-t border-white/5 mt-auto">
        <div class="max-w-7xl mx-auto px-6 py-12">
            <div class="grid grid-cols-4 gap-8 mb-8">
                <div class="col-span-1">
                    <!-- Logo (UPDATED) -->
                    <div class="flex items-center gap-3 mb-4">
                        <img src="/assets/logo.png" alt="Logo" class="h-8 w-auto object-contain" loading="eager">
                        <span class="font-bold text-xl text-white">KKK LED</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Your one-stop shop for premium LED lights, electrical components, and digital goods. Quality guaranteed.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="/" class="hover:text-blue-400 transition">Home</a></li>
                        <li><a href="/shop" class="hover:text-blue-400 transition">Shop Products</a></li>
                        <li><a href="/cart" class="hover:text-blue-400 transition">My Cart</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-4">Contact Us</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><i class="fa-solid fa-phone mr-2"></i> +95 97676 2 7676</li>
                        <li><i class="fa-solid fa-envelope mr-2"></i> support@kkkled.com</li>
                        
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/5 pt-8 text-center text-gray-500 text-xs">
                &copy; <?= date('Y') ?> KKK LED Shop. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- CART SCRIPT -->
    <script>
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const count = cart.reduce((sum, item) => sum + item.qty, 0);
            
            // Update Mobile
            const mobileBadge = document.getElementById('mobile-cart-count');
            if(mobileBadge) {
                mobileBadge.innerText = count;
                mobileBadge.classList.toggle('hidden', count === 0);
            }

            // Update Desktop
            const desktopBadge = document.getElementById('desktop-cart-count');
            if(desktopBadge) {
                desktopBadge.innerText = count;
                desktopBadge.classList.toggle('hidden', count === 0);
            }
        }

        // Global Add to Cart
        window.addToCart = function(btn) {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const price = parseFloat(btn.dataset.price);
            const image = btn.dataset.image;
            const type = btn.dataset.type;
            const maxStock = parseInt(btn.dataset.stock || 0);

            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            let existing = cart.find(item => item.id === id);

            if (existing) {
                if(type === 'physical' && existing.qty + 1 > maxStock) {
                    alert("Sorry, out of stock!");
                    return;
                }
                existing.qty += 1;
            } else {
                if(type === 'physical' && maxStock < 1) {
                    alert("Sorry, out of stock!");
                    return;
                }
                cart.push({ id, name, price, image, type, qty: 1 });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            
            // Animation Feedback
            const originalContent = btn.innerHTML;
            btn.innerHTML = `<i class="fa-solid fa-check"></i>`;
            btn.classList.add('bg-green-500', 'border-green-400');
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.classList.remove('bg-green-500', 'border-green-400');
            }, 1000);
        };

        // Init
        document.addEventListener('DOMContentLoaded', updateCartCount);
    </script>
</body>
</html>