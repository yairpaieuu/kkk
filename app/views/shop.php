<?php
    $pageSections  = $pageSections  ?? [];
    $siteSettings  = $siteSettings  ?? [];
    $categories    = $categories    ?? [];
    $products      = $products      ?? [];

    $shopHeader    = $pageSections['page_header'] ?? null;
    $showHeader    = $shopHeader && isset($shopHeader['is_visible']) && (int)$shopHeader['is_visible'] === 1;
    $headerTitle   = htmlspecialchars(($showHeader ? ($shopHeader['title']   ?? '') : '') ?: 'All Products');
    $headerContent = $showHeader ? htmlspecialchars($shopHeader['content'] ?? '') : '';

    /* ── PHP card renderer (initial SSR pass) ── */
    function shopRenderCard(array $p): string {
        $id        = (int)$p['id'];
        $name      = htmlspecialchars($p['name']         ?? '');
        $image     = $p['image'] ?? '';
        $type      = htmlspecialchars($p['type']         ?? 'physical');
        $price     = (float)($p['price']                 ?? 0);
        $stock     = (int)($p['stock']                   ?? 0);
        $isDigital = strtolower($p['type'] ?? '') === 'digital';

        $typeCls   = $isDigital ? 'bg-blue-500/10 text-blue-600' : 'bg-slate-500/10 text-slate-600';
        $typeLabel = $isDigital ? 'Digital' : 'Physical';

        $imgHtml = $image
            ? '<img src="' . imgSrc($image) . '" alt="' . $name . '"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                   loading="lazy" decoding="async">'
            : '<div class="w-full h-full flex items-center justify-center bg-slate-100">
                   <i class="fa-regular fa-image text-4xl text-slate-300"></i>
               </div>';

        if ($isDigital) {
            $stockHtml = '<span class="inline-flex items-center gap-1 text-xs text-blue-600 font-medium">
                              <i class="fa-solid fa-bolt text-[9px]"></i> Instant Delivery
                          </span>';
        } else {
            $inStock   = $stock > 0;
            $stockHtml = $inStock
                ? '<span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span> In Stock
                   </span>'
                : '<span class="inline-flex items-center gap-1 text-xs text-rose-500 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span> Out of Stock
                   </span>';
        }

        $hasDiscount   = !empty($p['has_discount']) && !empty($p['original_price']) && (int)($p['discount_percent'] ?? 0) > 0;
        $origPrice     = (float)($p['original_price'] ?? 0);
        $discPct       = (int)($p['discount_percent'] ?? 0);

        if ($hasDiscount) {
            $priceHtml = '<div class="flex items-baseline gap-1.5">
                <span class="font-bold text-blue-600">' . number_format($price) . ' Ks</span>
                <span class="text-xs text-slate-400 line-through">' . number_format($origPrice) . ' Ks</span>
            </div>';
            $discBadge = '<span class="absolute top-2 left-2 bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full z-10">-' . $discPct . '%</span>';
        } else {
            $priceHtml = '<span class="font-bold text-blue-600">' . number_format($price) . ' Ks</span>';
            $discBadge = '';
        }

        $outOfStock = !$isDigital && $stock <= 0;
        if ($outOfStock) {
            $cartBtn = '<button disabled
                class="flex items-center justify-center gap-1.5 w-full py-2 rounded-xl bg-slate-100 text-slate-400 text-xs font-semibold cursor-not-allowed z-20 relative">
                <i class="fa-solid fa-ban text-[10px]"></i> Out of Stock
            </button>';
        } else {
            $cartBtn = '<button onclick="addToCart(this)"
                data-id="'    . $id    . '"
                data-name="'  . $name  . '"
                data-price="' . $price . '"
                data-image="' . htmlspecialchars($image, ENT_QUOTES) . '"
                data-type="'  . $type  . '"
                data-stock="' . $stock . '"
                class="flex items-center justify-center gap-1.5 w-full py-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-xs font-semibold transition-all duration-150 z-20 relative">
                <i class="fa-solid fa-cart-plus text-[10px]"></i> Add to Cart
            </button>';
        }

        return '
        <div class="shop-card bg-white rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col group relative border border-slate-100" data-view-target="grid">
            <a href="/product?id=' . $id . '" class="absolute inset-0 z-10" aria-label="' . $name . '"></a>
            <!-- Image -->
            <div class="aspect-[4/3] overflow-hidden bg-slate-50 relative">
                ' . $imgHtml . '
                ' . $discBadge . '
                <span class="absolute top-2 right-8 text-[9px] font-semibold px-2 py-0.5 rounded-full backdrop-blur-sm ' . $typeCls . ' z-10">' . $typeLabel . '</span>
                <!-- Wishlist -->
                <button onclick="event.preventDefault(); shopToggleWishlist(this)"
                    class="wishlist-btn absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-slate-400 hover:text-rose-500 transition-colors z-20 shadow-sm">
                    <i class="fa-regular fa-heart text-xs"></i>
                </button>
            </div>
            <!-- Body -->
            <div class="p-4 flex flex-col flex-grow gap-2">
                <h3 class="text-slate-900 font-semibold text-sm leading-snug line-clamp-2">' . $name . '</h3>
                <!-- Stars -->
                <div class="flex items-center gap-0.5 text-amber-400 text-[10px]">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-regular fa-star"></i>
                </div>
                ' . $stockHtml . '
                <!-- Price + Cart -->
                <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between gap-2 pointer-events-auto">
                    <div class="flex flex-col leading-tight">' . $priceHtml . '</div>
                    ' . $cartBtn . '
                </div>
            </div>
        </div>';
    }
?>

<style>
/* List view card override */
#productGrid.list-view .shop-card {
    flex-direction: row;
    border-radius: 1rem;
}
#productGrid.list-view .shop-card [data-view-target="grid"] { display: flex; }
#productGrid.list-view .shop-card .aspect-\[4\/3\] {
    width: 160px;
    min-width: 160px;
    aspect-ratio: auto;
    height: auto;
    border-radius: 1rem 0 0 1rem;
}
#productGrid.list-view {
    grid-template-columns: 1fr !important;
}
</style>

<div class="min-h-screen bg-slate-50 pb-20">

    <?php /* ══════════════════════════════════════════════════════
           ── 1. PAGE HEADER (breadcrumb)
           ══════════════════════════════════════════════════════ */ ?>
    <div class="bg-white border-b border-slate-200 py-6">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

            <!-- Left: breadcrumb + title -->
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-1.5">
                    <a href="/" class="hover:text-blue-600 transition-colors">Home</a>
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    <span class="text-slate-600 font-medium">Shop</span>
                </nav>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight"><?= $headerTitle ?></h1>
                <?php if ($headerContent): ?>
                <p class="mt-1 text-slate-500 text-sm"><?= $headerContent ?></p>
                <?php endif; ?>
            </div>

            <!-- Right: count + view toggle -->
            <div class="flex items-center gap-3">
                <p class="text-sm text-slate-500">
                    <span id="resultsCount" class="font-semibold text-slate-800">—</span> products
                </p>
                <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-lg">
                    <button id="btnGrid" onclick="shopSetView('grid')"
                        class="view-toggle-btn w-8 h-8 rounded-md flex items-center justify-center text-sm transition-all bg-white text-blue-600 shadow-sm"
                        title="Grid view" aria-label="Grid view">
                        <i class="fa-solid fa-grip"></i>
                    </button>
                    <button id="btnList" onclick="shopSetView('list')"
                        class="view-toggle-btn w-8 h-8 rounded-md flex items-center justify-center text-sm transition-all text-slate-400 hover:text-slate-700"
                        title="List view" aria-label="List view">
                        <i class="fa-solid fa-list"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <?php /* ══════════════════════════════════════════════════════
           ── 4. MOBILE FILTER BAR (md:hidden sticky)
           ══════════════════════════════════════════════════════ */ ?>
    <div class="md:hidden sticky top-0 z-30 bg-white border-b border-slate-200 shadow-sm">
        <div class="px-4 pt-3 pb-2">

            <!-- Row 1: Search -->
            <div class="relative mb-2">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" id="mobileSearchInput" placeholder="Search products…"
                    oninput="shopDebounce()"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
            </div>

            <!-- Row 2: Pills + Sort -->
            <div class="flex items-center gap-2">
                <div class="flex-1 overflow-x-auto no-scrollbar">
                    <div class="flex items-center gap-1.5 pb-1" id="mobilePillRow">
                        <button class="mob-pill whitespace-nowrap text-xs font-medium px-3 py-1.5 rounded-full border transition-colors bg-blue-600 text-white border-blue-600"
                            data-cat="" onclick="shopMobilePill(this)">All</button>
                        <button class="mob-pill whitespace-nowrap text-xs font-medium px-3 py-1.5 rounded-full border transition-colors bg-white border-slate-200 text-slate-600 hover:border-blue-300"
                            data-cat="physical" onclick="shopMobilePill(this)">Physical</button>
                        <button class="mob-pill whitespace-nowrap text-xs font-medium px-3 py-1.5 rounded-full border transition-colors bg-white border-slate-200 text-slate-600 hover:border-blue-300"
                            data-cat="digital" onclick="shopMobilePill(this)">Digital</button>
                        <?php foreach ($categories as $c): ?>
                        <button class="mob-pill whitespace-nowrap text-xs font-medium px-3 py-1.5 rounded-full border transition-colors bg-white border-slate-200 text-slate-600 hover:border-blue-300"
                            data-cat="<?= (int)$c['id'] ?>" onclick="shopMobilePill(this)"><?= htmlspecialchars($c['name']) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Sort (mobile) -->
                <div class="relative shrink-0">
                    <select id="mobileSortSelect" onchange="shopSyncSort('mobile'); fetchProducts()"
                        class="appearance-none bg-white border border-slate-200 text-slate-700 text-xs rounded-xl pl-3 pr-7 py-2 outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="newest">Newest</option>
                        <option value="price_asc">Price ↑</option>
                        <option value="price_desc">Price ↓</option>
                        <option value="name_asc">A–Z</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-[9px] text-slate-400 pointer-events-none"></i>
                </div>
            </div>

        </div>
    </div>

    <?php /* ══════════════════════════════════════════════════════
           ── 2. SHOP LAYOUT (sidebar + content)
           ══════════════════════════════════════════════════════ */ ?>
    <div class="max-w-7xl mx-auto px-4 py-6 flex gap-6 items-start">

        <?php /* ── 3. LEFT SIDEBAR (desktop only) ── */ ?>
        <aside class="hidden md:block shrink-0 w-[240px] sticky top-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-col gap-6">

                <!-- Search (desktop) -->
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" id="desktopSearchInput" placeholder="Search products…"
                        oninput="shopDebounce()"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <!-- Product Type -->
                <div>
                    <p class="font-semibold text-slate-800 text-sm mb-3">Product Type</p>
                    <div class="flex flex-col gap-2">
                        <?php foreach (['' => 'All', 'physical' => 'Physical', 'digital' => 'Digital'] as $val => $label): ?>
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="radio" name="sidebarType" value="<?= htmlspecialchars($val) ?>"
                                <?= $val === '' ? 'checked' : '' ?>
                                onchange="shopSyncType(this.value); fetchProducts()"
                                class="accent-blue-600 w-4 h-4 cursor-pointer">
                            <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors"><?= htmlspecialchars($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                <div>
                    <p class="font-semibold text-slate-800 text-sm mb-3">Categories</p>
                    <div class="flex flex-col gap-2 max-h-52 overflow-y-auto pr-1 no-scrollbar">
                        <?php foreach ($categories as $c): ?>
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="radio"
                                name="sidebarCat"
                                value="<?= (int)$c['id'] ?>"
                                onchange="fetchProducts()"
                                class="accent-blue-600 w-4 h-4 cursor-pointer">
                            <span class="text-sm text-slate-600 group-hover:text-slate-900 transition-colors"><?= htmlspecialchars($c['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sort By -->
                <div>
                    <p class="font-semibold text-slate-800 text-sm mb-3">Sort By</p>
                    <div class="relative">
                        <select id="desktopSortSelect" onchange="shopSyncSort('desktop'); fetchProducts()"
                            class="w-full appearance-none bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-xl pl-3 pr-8 py-2.5 outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer transition">
                            <option value="newest">Newest First</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="name_asc">Name: A–Z</option>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Price Range (cosmetic) -->
                <div>
                    <p class="font-semibold text-slate-800 text-sm mb-3">Price Range</p>
                    <div class="flex items-center gap-2">
                        <input type="number" id="priceMin" placeholder="Min"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 placeholder-slate-400 outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <span class="text-slate-400 text-xs shrink-0">–</span>
                        <input type="number" id="priceMax" placeholder="Max"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 placeholder-slate-400 outline-none focus:ring-2 focus:ring-blue-500 transition">
                    </div>
                    <button onclick="fetchProducts()"
                        class="mt-2.5 w-full py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                        Apply Price
                    </button>
                </div>

                <!-- Reset All -->
                <button onclick="shopResetFilters()"
                    class="w-full py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-rotate-left text-xs"></i> Reset All
                </button>

            </div>
        </aside>

        <?php /* ── RIGHT CONTENT AREA ── */ ?>
        <div class="flex-1 min-w-0">

            <?php /* ── 6. LOADING STATE ── */ ?>
            <div id="shopLoading" class="hidden">
                <div class="flex flex-col items-center justify-center py-28 gap-4">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-500"></i>
                    <p class="text-sm text-slate-400">Loading products…</p>
                </div>
            </div>

            <?php /* ── 6. EMPTY STATE ── */ ?>
            <div id="shopNoResults" class="hidden">
                <div class="flex flex-col items-center justify-center py-28 gap-5 text-center">
                    <div class="w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center">
                        <i class="fa-solid fa-box-open text-4xl text-slate-300"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-700">No products found</h3>
                        <p class="text-slate-400 text-sm mt-1">Try adjusting your filters or search terms.</p>
                    </div>
                    <button onclick="shopResetFilters()"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition-colors">
                        Clear Filters
                    </button>
                </div>
            </div>

            <?php /* ── 5. PRODUCT GRID ── */ ?>
            <div id="productGrid" class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($products as $p): ?>
                    <?= shopRenderCard($p) ?>
                <?php endforeach; ?>
            </div>

        </div>
    </div><!-- /.shop-layout -->

</div><!-- /.min-h-screen -->

<script>
(function () {
    'use strict';

    /* ── Shared filter state ──
     * activeFilter: '' = all | 'physical' | 'digital' | numeric string (category id)
     * This single variable covers both product type and category filters.
     */
    let activeFilter = '';
    let currentView = 'grid';
    let debounceTimer;

    /* ── Helpers ── */
    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function byId(id) { return document.getElementById(id); }

    /* ── Search debounce ── */
    window.shopDebounce = function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchProducts, 300);
    };

    /* ── View toggle ── */
    window.shopSetView = function (view) {
        currentView = view;
        const grid  = byId('productGrid');
        const btnGrid = byId('btnGrid');
        const btnList = byId('btnList');

        if (view === 'list') {
            grid.classList.add('list-view');
            btnList.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
            btnList.classList.remove('text-slate-400', 'hover:text-slate-700');
            btnGrid.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
            btnGrid.classList.add('text-slate-400', 'hover:text-slate-700');
        } else {
            grid.classList.remove('list-view');
            btnGrid.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
            btnGrid.classList.remove('text-slate-400', 'hover:text-slate-700');
            btnList.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
            btnList.classList.add('text-slate-400', 'hover:text-slate-700');
        }
    };

    /* ── Wishlist toggle (cosmetic) ── */
    window.shopToggleWishlist = function (btn) {
        const icon = btn.querySelector('i');
        if (icon.classList.contains('fa-regular')) {
            icon.classList.replace('fa-regular', 'fa-solid');
            btn.classList.add('text-rose-500');
            btn.classList.remove('text-slate-400');
        } else {
            icon.classList.replace('fa-solid', 'fa-regular');
            btn.classList.remove('text-rose-500');
            btn.classList.add('text-slate-400');
        }
    };

    /* ── Update pill styles to reflect activeFilter ── */
    function _updatePillStyles() {
        document.querySelectorAll('.mob-pill').forEach(p => {
            const active = p.dataset.cat === activeFilter;
            p.classList.toggle('bg-blue-600',    active);
            p.classList.toggle('text-white',     active);
            p.classList.toggle('border-blue-600', active);
            p.classList.toggle('bg-white',       !active);
            p.classList.toggle('text-slate-600', !active);
            p.classList.toggle('border-slate-200', !active);
        });
    }

    /* ── Mobile pill click ── */
    window.shopMobilePill = function (pill) {
        activeFilter = pill.dataset.cat ?? '';
        _updatePillStyles();
        _syncSidebarFromFilter(activeFilter);
        fetchProducts();
    };

    /* Sync desktop sidebar controls to match activeFilter */
    function _syncSidebarFromFilter(filter) {
        if (filter === '' || filter === 'physical' || filter === 'digital') {
            /* Type filter — update type radios, deselect category radios */
            document.querySelectorAll('input[name="sidebarType"]').forEach(r => {
                r.checked = r.value === filter;
            });
            document.querySelectorAll('input[name="sidebarCat"]').forEach(c => c.checked = false);
        } else {
            /* Category filter — reset type to All, check matching category radio */
            document.querySelectorAll('input[name="sidebarType"]').forEach(r => {
                r.checked = r.value === '';
            });
            document.querySelectorAll('input[name="sidebarCat"]').forEach(c => {
                c.checked = c.value === filter;
            });
        }
    }

    /* ── Sync from desktop sidebar type radio ── */
    window.shopSyncType = function (val) {
        activeFilter = val;
        /* Update mobile pills */
        _updatePillStyles();
        /* Deselect category radios when a type is selected */
        document.querySelectorAll('input[name="sidebarCat"]').forEach(c => c.checked = false);
    };

    /* ── Sync sort between desktop & mobile ── */
    window.shopSyncSort = function (source) {
        const dsk = byId('desktopSortSelect');
        const mob = byId('mobileSortSelect');
        if (!dsk || !mob) return;
        if (source === 'desktop') mob.value = dsk.value;
        else                      dsk.value = mob.value;
    };

    /* ── Reset all filters ── */
    window.shopResetFilters = function () {
        activeFilter = '';
        /* Desktop */
        const deskSearch = byId('desktopSearchInput');
        if (deskSearch) deskSearch.value = '';
        const mobSearch  = byId('mobileSearchInput');
        if (mobSearch)  mobSearch.value  = '';
        document.querySelectorAll('input[name="sidebarType"]').forEach(r => r.checked = r.value === '');
        document.querySelectorAll('input[name="sidebarCat"]').forEach(c => c.checked = false);
        const dsk = byId('desktopSortSelect'); if (dsk) dsk.value = 'newest';
        const mob = byId('mobileSortSelect');  if (mob) mob.value = 'newest';
        const pMin = byId('priceMin'); if (pMin) pMin.value = '';
        const pMax = byId('priceMax'); if (pMax) pMax.value = '';
        _updatePillStyles();
        fetchProducts();
    };

    /* ── Build API params ── */
    function _buildParams() {
        /* Search: prefer whichever input has content */
        const deskSearch = byId('desktopSearchInput');
        const mobSearch  = byId('mobileSearchInput');
        const q = (deskSearch && deskSearch.value)
            ? deskSearch.value
            : (mobSearch ? mobSearch.value : '');

        /* Category: start from activeFilter (pill/sidebar type selection) */
        let cat = activeFilter;

        /* If a sidebar category radio is selected, it takes precedence */
        const selectedCat = document.querySelector('input[name="sidebarCat"]:checked');
        if (selectedCat) cat = selectedCat.value;

        /* Sort */
        const dsk  = byId('desktopSortSelect');
        const sort = dsk ? dsk.value : 'newest';

        /* Price */
        const pMin = byId('priceMin')  ? byId('priceMin').value  : '';
        const pMax = byId('priceMax')  ? byId('priceMax').value  : '';

        return { q, cat, sort, price_min: pMin, price_max: pMax };
    }

    /* ── JS card renderer (must match PHP shopRenderCard) ── */
    function renderCard(p) {
        const isDigital  = p.type === 'digital';
        const typeCls    = isDigital ? 'bg-blue-500/10 text-blue-600' : 'bg-slate-500/10 text-slate-600';
        const typeLabel  = isDigital ? 'Digital' : 'Physical';

        const imgHtml = p.image
            ? `<img src="${imgSrc(p.image)}" alt="${escHtml(p.name)}"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                   loading="lazy" decoding="async">`
            : `<div class="w-full h-full flex items-center justify-center bg-slate-100">
                   <i class="fa-regular fa-image text-4xl text-slate-300"></i>
               </div>`;

        const stockHtml = isDigital
            ? `<span class="inline-flex items-center gap-1 text-xs text-blue-600 font-medium">
                   <i class="fa-solid fa-bolt text-[9px]"></i> Instant Delivery
               </span>`
            : (p.stock > 0
                ? `<span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span> In Stock
                   </span>`
                : `<span class="inline-flex items-center gap-1 text-xs text-rose-500 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span> Out of Stock
                   </span>`);

        let priceHtml = '', discBadge = '';
        if (p.has_discount && p.original_price && p.discount_percent > 0) {
            priceHtml = `<div class="flex items-baseline gap-1.5">
                <span class="font-bold text-blue-600">${parseInt(p.price).toLocaleString()} Ks</span>
                <span class="text-xs text-slate-400 line-through">${parseInt(p.original_price).toLocaleString()} Ks</span>
            </div>`;
            discBadge = `<span class="absolute top-2 left-2 bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full z-10">-${p.discount_percent}%</span>`;
        } else {
            priceHtml = `<span class="font-bold text-blue-600">${parseInt(p.price).toLocaleString()} Ks</span>`;
        }

        const outOfStock = !isDigital && p.stock <= 0;
        const cartBtn = outOfStock
            ? `<button disabled class="flex items-center justify-center gap-1.5 w-full py-2 rounded-xl bg-slate-100 text-slate-400 text-xs font-semibold cursor-not-allowed z-20 relative">
                   <i class="fa-solid fa-ban text-[10px]"></i> Out of Stock
               </button>`
            : `<button onclick="event.preventDefault(); addToCart(this)"
                   data-id="${escHtml(String(p.id))}"
                   data-name="${escHtml(p.name)}"
                   data-price="${escHtml(String(p.price))}"
                   data-image="${escHtml(p.image ?? '')}"
                   data-type="${escHtml(p.type)}"
                   data-stock="${escHtml(String(p.stock))}"
                   class="flex items-center justify-center gap-1.5 w-full py-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-xs font-semibold transition-all duration-150 z-20 relative">
                   <i class="fa-solid fa-cart-plus text-[10px]"></i> Add to Cart
               </button>`;

        return `
        <div class="shop-card bg-white rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col group relative border border-slate-100">
            <a href="/product?id=${escHtml(String(p.id))}" class="absolute inset-0 z-10" aria-label="${escHtml(p.name)}"></a>
            <div class="aspect-[4/3] overflow-hidden bg-slate-50 relative">
                ${imgHtml}
                ${discBadge}
                <span class="absolute top-2 right-8 text-[9px] font-semibold px-2 py-0.5 rounded-full backdrop-blur-sm z-10 ${typeCls}">${typeLabel}</span>
                <button onclick="event.preventDefault(); shopToggleWishlist(this)"
                    class="wishlist-btn absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center text-slate-400 hover:text-rose-500 transition-colors z-20 shadow-sm">
                    <i class="fa-regular fa-heart text-xs"></i>
                </button>
            </div>
            <div class="p-4 flex flex-col flex-grow gap-2">
                <h3 class="text-slate-900 font-semibold text-sm leading-snug line-clamp-2">${escHtml(p.name)}</h3>
                <div class="flex items-center gap-0.5 text-amber-400 text-[10px]">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-regular fa-star"></i>
                </div>
                ${stockHtml}
                <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between gap-2 pointer-events-auto">
                    <div class="flex flex-col leading-tight">${priceHtml}</div>
                    ${cartBtn}
                </div>
            </div>
        </div>`;
    }

    /* ── Core fetch function ── */
    window.fetchProducts = async function () {
        const grid      = byId('productGrid');
        const loading   = byId('shopLoading');
        const noResults = byId('shopNoResults');
        const countEl   = byId('resultsCount');

        grid.innerHTML = '';
        loading.classList.remove('hidden');
        noResults.classList.add('hidden');

        const p = _buildParams();
        const url = `/api/shop/search?q=${encodeURIComponent(p.q)}&cat=${encodeURIComponent(p.cat)}&sort=${encodeURIComponent(p.sort)}&price_min=${encodeURIComponent(p.price_min)}&price_max=${encodeURIComponent(p.price_max)}`;

        try {
            const res  = await fetch(url);
            const data = await res.json();

            loading.classList.add('hidden');

            const products = data.products ?? [];
            if (countEl) countEl.textContent = products.length;

            if (products.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            products.forEach(prod => {
                grid.insertAdjacentHTML('beforeend', renderCard(prod));
            });

            /* Re-apply list/grid view class after re-render */
            if (currentView === 'list') grid.classList.add('list-view');

        } catch (err) {
            console.error('Shop fetch error:', err);
            loading.classList.add('hidden');
            noResults.classList.remove('hidden');
        }
    };

    /* ── Initial load ── */
    document.addEventListener('DOMContentLoaded', function () {
        /* Update initial results count from SSR cards */
        const countEl = byId('resultsCount');
        if (countEl) {
            countEl.textContent = document.querySelectorAll('#productGrid .shop-card').length;
        }
        /* Trigger fresh fetch to get accurate, filtered data */
        fetchProducts();
    });

}());
</script>
