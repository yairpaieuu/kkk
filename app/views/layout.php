<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title ?? ($siteSettings['site_name'] ?? 'Store')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Light card utility used by child views */
        .glass-panel {
            background: white;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        /* Desktop header shadow */
        .site-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        /* Active nav underline */
        .nav-active {
            color: #2563eb !important;
            position: relative;
        }
        .nav-active::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 100%;
            height: 2px;
            background-color: #2563eb;
            border-radius: 9999px;
        }

        /* Search overlay slide-down (mobile only) */
        #mobile-search-overlay {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }
        #mobile-search-overlay.open {
            max-height: 80px;
            opacity: 1;
        }

        /* Mobile menu overlay */
        #mobile-menu-overlay {
            display: none;
        }
        #mobile-menu-overlay.open {
            display: flex;
        }

        /* Mobile bottom nav */
        .mobile-bottom-nav {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        }

        /* Mobile bottom nav active pill */
        .mobile-nav-pill {
            display: inline-block;
            width: 28px;
            height: 3px;
            background-color: #2563eb;
            border-radius: 9999px;
            margin-top: 3px;
        }

        /* Cart FAB border matches page bg */
        .cart-fab {
            border: 4px solid #f8fafc;
        }

        /* Announcement bar transition */
        .ann-bar { transition: height 0.2s ease, padding 0.2s ease; overflow: hidden; }

        /* Header search bar */
        .header-search {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 9999px;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            font-size: 0.875rem;
            outline: none;
            width: 280px;
            transition: all 0.2s;
        }
        .header-search:focus {
            background: white;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

<?php
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    $siteSettings = $siteSettings ?? [];
    $siteName = $siteSettings['site_name'] ?? 'Store';
    $phone    = $siteSettings['phone']     ?? '';
    $address  = $siteSettings['address']   ?? '';
    $email    = $siteSettings['email']     ?? '';

    function navClass(string $path, string $uri): string {
        return $uri === $path ? 'nav-active text-blue-600 font-semibold text-sm transition' : 'text-slate-600 hover:text-blue-600 font-medium text-sm transition';
    }
    function mobileNavClass(string $path, string $uri): string {
        return $uri === $path ? 'text-blue-600' : 'text-slate-500';
    }
?>

    <!-- ============================================================ -->
    <!-- 📢  ANNOUNCEMENT BAR  (desktop only)                         -->
    <!-- ============================================================ -->
    <div class="ann-bar hidden md:block bg-blue-700 text-white text-xs text-center relative" style="padding:8px 48px;">
        🚚 Free shipping on orders above 50,000 Ks &nbsp;|&nbsp; 📦 Quality Guaranteed
        <button onclick="this.parentElement.style.display='none'"
                aria-label="Dismiss announcement"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition text-base leading-none">
            &times;
        </button>
    </div>


    <!-- ============================================================ -->
    <!-- 🖥️  DESKTOP HEADER  (hidden on mobile)                       -->
    <!-- ============================================================ -->
    <header class="hidden md:flex flex-col fixed top-0 w-full z-50 site-header">

        <!-- Announcement bar inside fixed header so it scrolls with sticky offset -->
        <div class="ann-bar bg-blue-700 text-white text-xs text-center relative" style="padding:8px 48px;" id="desk-ann-bar">
            🚚 Free shipping on orders above 50,000 Ks &nbsp;|&nbsp; 📦 Quality Guaranteed
            <button onclick="document.getElementById('desk-ann-bar').style.display='none'; document.getElementById('desk-ann-spacer').style.display='none';"
                    aria-label="Dismiss announcement"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition text-base leading-none">
                &times;
            </button>
        </div>

        <div class="max-w-7xl mx-auto w-full px-6 h-16 flex items-center justify-between gap-6">

            <!-- Logo -->
            <a href="/" class="flex items-center gap-2.5 group flex-shrink-0">
                <img src="/assets/logo.png" alt="<?= htmlspecialchars($siteName) ?> Logo"
                     class="h-9 w-auto object-contain group-hover:scale-105 transition duration-300" loading="eager">
                <div class="flex flex-col leading-none">
                    <span class="text-lg font-bold text-slate-800 tracking-tight group-hover:text-blue-600 transition">
                        <?= htmlspecialchars($siteName) ?>
                    </span>
                    <span class="text-[9px] text-slate-400 uppercase tracking-widest">Shop</span>
                </div>
            </a>

            <!-- Inline Search Bar (desktop) -->
            <form action="/shop" method="get" class="relative flex-shrink-0">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="q" placeholder="Search products…" class="header-search"
                       value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            </form>

            <!-- Center Nav -->
            <nav class="flex items-center gap-7">
                <a href="/" class="<?= navClass('/', $uri) ?> pb-0.5 relative">Home</a>
                <a href="/shop" class="<?= navClass('/shop', $uri) ?> pb-0.5 relative">Shop</a>
                <a href="/contact" class="<?= navClass('/contact', $uri) ?> pb-0.5 relative">Contact</a>
            </nav>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 flex-shrink-0">

                <!-- Profile -->
                <a href="/profile" aria-label="Profile"
                   class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-slate-100 transition relative">
                    <i class="fa-regular fa-user text-sm"></i>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="absolute bottom-1.5 right-1.5 w-2 h-2 bg-green-500 rounded-full border-2 border-white"></span>
                    <?php endif; ?>
                </a>

                <!-- Cart -->
                <a href="/cart"
                   class="relative flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-full transition shadow-sm shadow-blue-200 ml-1">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Cart</span>
                    <span id="desktop-cart-count"
                          class="hidden bg-white text-blue-600 text-[10px] font-bold min-w-[18px] h-[18px] flex items-center justify-center rounded-full px-1">0</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Spacer for fixed desktop header (announcement ~33px + header 64px) -->
    <div class="hidden md:block" id="desk-ann-spacer" style="height:97px;"></div>


    <!-- ============================================================ -->
    <!-- 📱  MOBILE TOP BAR  (hidden on desktop)                      -->
    <!-- ============================================================ -->
    <header class="md:hidden sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
        <div class="flex items-center justify-between px-4 h-14">

            <!-- Logo -->
            <a href="/" class="flex items-center gap-2">
                <img src="/assets/logo.png" alt="<?= htmlspecialchars($siteName) ?>"
                     class="h-8 w-auto object-contain" loading="eager">
                <span class="font-bold text-base text-slate-800"><?= htmlspecialchars($siteName) ?></span>
            </a>

            <div class="flex items-center gap-2">
                <!-- Search (mobile) -->
                <button onclick="toggleSearch()" aria-label="Search"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 transition">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </button>

                <!-- Cart icon (mobile top bar) -->
                <a href="/cart" class="relative w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 transition">
                    <i class="fa-solid fa-cart-shopping text-sm"></i>
                    <span id="mobile-cart-count-top"
                          class="hidden absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[9px] font-bold min-w-[16px] h-4 flex items-center justify-center rounded-full px-0.5">0</span>
                </a>
            </div>
        </div>

        <!-- Mobile Search Slide-Down -->
        <div id="mobile-search-overlay" class="w-full bg-white border-t border-slate-100">
            <form action="/shop" method="get" class="px-4 py-2.5 flex items-center gap-3">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input id="search-input-mobile" type="text" name="q" placeholder="Search products…"
                       class="flex-grow outline-none text-sm text-slate-800 placeholder-slate-400 bg-transparent">
                <button type="submit" class="text-slate-500 hover:text-blue-600 transition text-sm">
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
                <button type="button" onclick="toggleSearch()" class="text-slate-400">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </form>
        </div>
    </header>


    <!-- ============================================================ -->
    <!-- ✅  TRUST BADGES STRIP  (desktop only, after spacer)         -->
    <!-- ============================================================ -->
    <div class="bg-white border-b border-slate-100 py-2 hidden md:block">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-center gap-8">
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-truck-fast text-blue-600 text-xs"></i>
                <span class="text-slate-500 text-xs font-medium">Free Shipping on 50K+ orders</span>
            </div>
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-blue-600 text-xs"></i>
                <span class="text-slate-500 text-xs font-medium">Secure Checkout</span>
            </div>
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-rotate-left text-blue-600 text-xs"></i>
                <span class="text-slate-500 text-xs font-medium">Easy Returns</span>
            </div>
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-headset text-blue-600 text-xs"></i>
                <span class="text-slate-500 text-xs font-medium">24/7 Support</span>
            </div>
        </div>
    </div>


    <!-- ============================================================ -->
    <!-- MAIN CONTENT                                                  -->
    <!-- ============================================================ -->
    <!-- Child views manage their own containers; add bottom padding   -->
    <!-- for mobile bottom nav                                         -->
    <main class="flex-grow pb-20 md:pb-0">
        <?php if (file_exists($childView)) require_once $childView; ?>
    </main>


    <!-- ============================================================ -->
    <!-- 🖥️  FOOTER  (hidden on mobile)                               -->
    <!-- ============================================================ -->
    <footer class="hidden md:block bg-slate-900 text-slate-300 mt-auto">

        <!-- Newsletter strip -->
        <div class="border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <p class="text-white font-semibold text-base">Get exclusive deals &amp; updates</p>
                    <p class="text-slate-400 text-sm mt-0.5">Subscribe to our newsletter and never miss an offer.</p>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <input type="email" placeholder="Your email address"
                           id="newsletter-email"
                           class="bg-slate-800 border border-slate-700 text-white placeholder-slate-500 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-blue-500 transition w-full sm:w-64">
                    <button onclick="(function(){var v=document.getElementById('newsletter-email').value.trim();if(!v){return;}alert('Thank you! You\'re now subscribed.');document.getElementById('newsletter-email').value='';})()"
                            class="bg-blue-600 hover:bg-blue-500 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition whitespace-nowrap">
                        Subscribe
                    </button>
                </div>
            </div>
        </div>

        <!-- 4-column grid -->
        <div class="max-w-7xl mx-auto px-6 py-14">
            <div class="grid grid-cols-4 gap-10 mb-10">

                <!-- Brand / About -->
                <div class="col-span-1">
                    <div class="flex items-center gap-2.5 mb-4">
                        <img src="/assets/logo.png" alt="<?= htmlspecialchars($siteName) ?>"
                             class="h-8 w-auto object-contain brightness-200" loading="lazy">
                        <span class="font-bold text-lg text-white"><?= htmlspecialchars($siteName) ?></span>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Your one-stop shop for premium products. Quality guaranteed, fast delivery.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-widest">Quick Links</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="/" class="text-slate-400 hover:text-blue-400 transition">Home</a></li>
                        <li><a href="/shop" class="text-slate-400 hover:text-blue-400 transition">Shop</a></li>
                        <li><a href="/cart" class="text-slate-400 hover:text-blue-400 transition">My Cart</a></li>
                        <li><a href="/profile" class="text-slate-400 hover:text-blue-400 transition">My Account</a></li>
                        <li><a href="/contact" class="text-slate-400 hover:text-blue-400 transition">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-widest">Contact</h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <?php if ($phone): ?>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-phone mt-0.5 text-blue-400 w-4 flex-shrink-0"></i>
                            <span><?= htmlspecialchars($phone) ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if ($email): ?>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-envelope mt-0.5 text-blue-400 w-4 flex-shrink-0"></i>
                            <a href="mailto:<?= htmlspecialchars($email) ?>" class="hover:text-blue-400 transition">
                                <?= htmlspecialchars($email) ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ($address): ?>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-location-dot mt-0.5 text-blue-400 w-4 flex-shrink-0"></i>
                            <span><?= htmlspecialchars($address) ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Social -->
                <div>
                    <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-widest">Follow Us</h4>
                    <div class="flex flex-wrap gap-3">
                        <a href="#" aria-label="Facebook"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-blue-600 flex items-center justify-center text-slate-400 hover:text-white transition">
                            <i class="fa-brands fa-facebook-f text-sm"></i>
                        </a>
                        <a href="#" aria-label="Instagram"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-pink-600 flex items-center justify-center text-slate-400 hover:text-white transition">
                            <i class="fa-brands fa-instagram text-sm"></i>
                        </a>
                        <a href="#" aria-label="Telegram"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-sky-500 flex items-center justify-center text-slate-400 hover:text-white transition">
                            <i class="fa-brands fa-telegram text-sm"></i>
                        </a>
                        <a href="#" aria-label="YouTube"
                           class="w-9 h-9 rounded-full bg-slate-800 hover:bg-red-600 flex items-center justify-center text-slate-400 hover:text-white transition">
                            <i class="fa-brands fa-youtube text-sm"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="text-xs text-slate-500">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.
                </p>
                <p class="text-xs text-slate-600">Built with ❤️ for you</p>
            </div>
        </div>
    </footer>


    <!-- ============================================================ -->
    <!-- 📱  MOBILE BOTTOM NAVIGATION  (hidden on desktop)            -->
    <!-- ============================================================ -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full z-50 mobile-bottom-nav">
        <div class="flex items-end justify-around h-16 px-2">

            <!-- Home -->
            <a href="/" class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 <?= mobileNavClass('/', $uri) ?>">
                <i class="fa-solid fa-house text-xl"></i>
                <span class="text-[10px] font-medium">Home</span>
                <?php if ($uri === '/'): ?>
                    <span class="mobile-nav-pill"></span>
                <?php endif; ?>
            </a>

            <!-- Shop -->
            <a href="/shop" class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 <?= mobileNavClass('/shop', $uri) ?>">
                <i class="fa-solid fa-store text-xl"></i>
                <span class="text-[10px] font-medium">Shop</span>
                <?php if ($uri === '/shop'): ?>
                    <span class="mobile-nav-pill"></span>
                <?php endif; ?>
            </a>

            <!-- Cart FAB -->
            <div class="flex flex-col items-center justify-center flex-1 -mt-5">
                <a href="/cart" aria-label="Cart"
                   class="cart-fab relative w-14 h-14 bg-blue-600 hover:bg-blue-700 rounded-full flex items-center justify-center shadow-lg shadow-blue-300 transition active:scale-95">
                    <i class="fa-solid fa-cart-shopping text-xl text-white"></i>
                    <span id="mobile-cart-count"
                          class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold min-w-[18px] h-[18px] flex items-center justify-center rounded-full px-0.5 border-2 border-white">0</span>
                </a>
                <span class="text-[10px] font-medium text-slate-500 mt-0.5">Cart</span>
            </div>

            <!-- Profile -->
            <a href="/profile" class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 <?= mobileNavClass('/profile', $uri) ?>">
                <i class="fa-solid fa-user text-xl"></i>
                <span class="text-[10px] font-medium">Profile</span>
                <?php if ($uri === '/profile'): ?>
                    <span class="mobile-nav-pill"></span>
                <?php endif; ?>
            </a>

            <!-- Contact -->
            <a href="/contact" class="flex flex-col items-center justify-center flex-1 h-full gap-0.5 <?= mobileNavClass('/contact', $uri) ?>">
                <i class="fa-solid fa-headset text-xl"></i>
                <span class="text-[10px] font-medium">Contact</span>
                <?php if ($uri === '/contact'): ?>
                    <span class="mobile-nav-pill"></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>


    <!-- ============================================================ -->
    <!-- 📱  MOBILE MENU OVERLAY  (triggered by toggleMobileMenu())   -->
    <!-- ============================================================ -->
    <div id="mobile-menu-overlay"
         class="fixed inset-0 z-[60] flex-col bg-white hidden">
        <!-- Backdrop (click outside panel to close) -->
        <div class="absolute inset-0 bg-black/20" onclick="toggleMobileMenu()" aria-hidden="true"></div>
        <!-- Panel -->
        <div class="relative flex flex-col h-full max-w-xs w-full bg-white shadow-xl">
        <div class="flex items-center justify-between px-4 py-4 border-b border-slate-200">
            <span class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($siteName) ?></span>
            <button onclick="toggleMobileMenu()" class="w-8 h-8 flex items-center justify-center text-slate-500" aria-label="Close menu">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <nav class="flex flex-col px-4 py-6 gap-1">
            <a href="/" class="flex items-center gap-3 px-3 py-3 rounded-xl <?= $uri === '/' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' ?> transition">
                <i class="fa-solid fa-house w-5 text-center"></i> Home
            </a>
            <a href="/shop" class="flex items-center gap-3 px-3 py-3 rounded-xl <?= $uri === '/shop' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' ?> transition">
                <i class="fa-solid fa-store w-5 text-center"></i> Shop
            </a>
            <a href="/cart" class="flex items-center gap-3 px-3 py-3 rounded-xl <?= $uri === '/cart' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' ?> transition">
                <i class="fa-solid fa-cart-shopping w-5 text-center"></i> Cart
            </a>
            <a href="/profile" class="flex items-center gap-3 px-3 py-3 rounded-xl <?= $uri === '/profile' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' ?> transition">
                <i class="fa-solid fa-user w-5 text-center"></i> Profile
            </a>
            <a href="/contact" class="flex items-center gap-3 px-3 py-3 rounded-xl <?= $uri === '/contact' ? 'bg-blue-50 text-blue-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' ?> transition">
                <i class="fa-solid fa-headset w-5 text-center"></i> Contact
            </a>
        </nav>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="px-4 mt-auto pb-8">
            <a href="/login" class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition">
                Sign In
            </a>
        </div>
        <?php endif; ?>
        </div>
    </div>


    <!-- ============================================================ -->
    <!-- JAVASCRIPT                                                    -->
    <!-- ============================================================ -->
    <script>
        /* ── Mobile search overlay ───────────────────────────────── */
        let searchOpen = false;
        function toggleSearch() {
            searchOpen = !searchOpen;
            const overlay = document.getElementById('mobile-search-overlay');
            if (overlay) overlay.classList.toggle('open', searchOpen);
            if (searchOpen) {
                const inp = document.getElementById('search-input-mobile');
                if (inp) inp.focus();
            }
        }

        /* ── Mobile menu overlay ─────────────────────────────────── */
        function toggleMobileMenu() {
            const overlay = document.getElementById('mobile-menu-overlay');
            overlay.classList.toggle('hidden');
            overlay.classList.toggle('open');
        }

        /* ── Cart count ──────────────────────────────────────────── */
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const count = cart.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);

            ['desktop-cart-count', 'mobile-cart-count', 'mobile-cart-count-top'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = count;
                    el.classList.toggle('hidden', count === 0);
                }
            });
        }

        /* ── Global addToCart ─────────────────────────────────────── */
        window.addToCart = function(btn) {
            const id       = btn.dataset.id;
            const name     = btn.dataset.name;
            const price    = parseFloat(btn.dataset.price);
            const image    = btn.dataset.image  || '';
            const type     = btn.dataset.type   || 'physical';
            const maxStock = parseInt(btn.dataset.stock || 0);

            let cart     = JSON.parse(localStorage.getItem('cart')) || [];
            let existing = cart.find(item => item.id === id);

            if (existing) {
                if (type === 'physical' && existing.qty + 1 > maxStock) {
                    alert('Sorry, maximum stock reached!');
                    return;
                }
                existing.qty += 1;
            } else {
                if (type === 'physical' && maxStock < 1) {
                    alert('Sorry, this item is out of stock!');
                    return;
                }
                cart.push({ id, name, price, image, type, qty: 1 });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();

            /* Visual feedback */
            const original = btn.innerHTML;
            const originalClass = btn.className;
            btn.innerHTML = '<i class="fa-solid fa-check"></i>';
            btn.style.cssText = 'background-color:#22c55e !important; border-color:#16a34a !important; color:#fff !important;';
            btn.disabled = true;
            setTimeout(() => {
                btn.innerHTML = original;
                btn.className = originalClass;
                btn.style.cssText = '';
                btn.disabled = false;
            }, 1000);
        };

        /* ── Init ─────────────────────────────────────────────────── */
        document.addEventListener('DOMContentLoaded', updateCartCount);
    </script>
</body>
</html>
