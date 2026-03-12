<?php
    $shopHeader = $pageSections['page_header'] ?? null;
    $showHeader = $shopHeader && isset($shopHeader['is_visible']) && (int)$shopHeader['is_visible'] === 1;
    $headerTitle   = $showHeader ? (htmlspecialchars($shopHeader['title']   ?? 'Our Products')) : 'Our Products';
    $headerContent = $showHeader ? (htmlspecialchars($shopHeader['content'] ?? ''))             : '';

    function shopRenderCard(array $p): string {
        $isDigital = strtolower($p['type']) === 'digital';
        $typeCls   = $isDigital ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600';
        $typeLabel = $isDigital ? 'DIGITAL' : 'PHYSICAL';

        $img = $p['image']
            ? '<img src="/' . htmlspecialchars($p['image']) . '" alt="' . htmlspecialchars($p['name']) . '"
                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                   loading="lazy" decoding="async">'
            : '<div class="w-full h-full flex items-center justify-center bg-slate-100">
                   <i class="fa-regular fa-image text-4xl text-slate-300"></i>
               </div>';

        if ($isDigital) {
            $stock = '<span class="inline-flex items-center gap-1 text-xs text-blue-600 font-medium">
                          <i class="fa-solid fa-bolt text-[10px]"></i> Instant Delivery
                      </span>';
        } else {
            $inStock = (int)($p['stock'] ?? 0) > 0;
            $stock = $inStock
                ? '<span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span> In Stock
                   </span>'
                : '<span class="inline-flex items-center gap-1 text-xs text-rose-500 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span> Out of Stock
                   </span>';
        }

        $hasDiscount = !empty($p['has_discount']) && !empty($p['original_price']) && (int)($p['discount_percent'] ?? 0) > 0;
        if ($hasDiscount) {
            $priceHtml = '<div class="flex items-baseline gap-1.5">
                <span class="text-base font-bold text-blue-600">' . number_format($p['price']) . ' Ks</span>
                <span class="text-xs text-slate-400 line-through">' . number_format($p['original_price']) . ' Ks</span>
              </div>';
            $discountBadge = '<span class="absolute top-2 left-2 bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md z-10">
                                  -' . (int)$p['discount_percent'] . '%
                              </span>';
        } else {
            $priceHtml     = '<span class="text-base font-bold text-blue-600">' . number_format($p['price']) . ' Ks</span>';
            $discountBadge = '';
        }

        $outOfStock = !$isDigital && (int)($p['stock'] ?? 0) <= 0;
        if ($outOfStock) {
            $cartBtn = '<button disabled
                            class="flex items-center justify-center gap-1.5 w-full py-2 rounded-lg bg-slate-200 text-slate-400 text-sm font-semibold cursor-not-allowed z-20 relative">
                            <i class="fa-solid fa-ban text-xs"></i> Out of Stock
                        </button>';
        } else {
            $cartBtn = '<button
                            onclick="addToCart(this)"
                            data-id="'    . $p['id']                           . '"
                            data-name="'  . htmlspecialchars($p['name'])        . '"
                            data-price="' . $p['price']                         . '"
                            data-image="' . htmlspecialchars($p['image'] ?? '') . '"
                            data-type="'  . $p['type']                          . '"
                            data-stock="' . ($p['stock'] ?? 0)                  . '"
                            class="flex items-center justify-center gap-1.5 w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-sm font-semibold transition-all duration-150 z-20 relative">
                            <i class="fa-solid fa-cart-plus text-xs"></i> Add to Cart
                        </button>';
        }

        return '
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 overflow-hidden flex flex-col group relative border border-slate-100">
            <a href="/product?id=' . $p['id'] . '" class="absolute inset-0 z-10" aria-label="' . htmlspecialchars($p['name']) . '"></a>
            <div class="aspect-square overflow-hidden bg-slate-50 relative">
                ' . $img . '
                ' . $discountBadge . '
                <span class="absolute top-2 right-2 text-[10px] font-bold px-2 py-0.5 rounded-md ' . $typeCls . ' z-10">' . $typeLabel . '</span>
            </div>
            <div class="p-3 flex flex-col flex-grow gap-2">
                <h3 class="text-slate-800 font-semibold text-sm leading-snug line-clamp-2">' . htmlspecialchars($p['name']) . '</h3>
                ' . $stock . '
                <div class="mt-auto pt-2 border-t border-slate-100 flex flex-col gap-2 pointer-events-auto">
                    ' . $priceHtml . '
                    ' . $cartBtn . '
                </div>
            </div>
        </div>';
    }
?>

<div class="min-h-screen bg-slate-50 pb-16">

    <?php /* ── Page Header ─────────────────────────────────────── */ ?>
    <?php if ($showHeader): ?>
    <div class="bg-white border-b border-slate-200 py-8">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $headerTitle ?></h1>
            <?php if ($headerContent): ?>
            <p class="mt-1 text-slate-500 text-sm"><?= $headerContent ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white border-b border-slate-200 py-8">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Our Products</h1>
        </div>
    </div>
    <?php endif; ?>

    <?php /* ── Sticky Filter Bar ──────────────────────────────── */ ?>
    <div class="sticky top-0 z-30 bg-white shadow-sm border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <form id="filterForm" onsubmit="event.preventDefault(); fetchProducts();" class="flex flex-wrap gap-3 items-center">

                <!-- Search -->
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" id="searchInput" placeholder="Search products..."
                           oninput="shopDebounce()"
                           class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 rounded-lg py-2.5 pl-10 pr-4 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                </div>

                <!-- Category -->
                <div class="relative min-w-[160px]">
                    <select id="catSelect" onchange="fetchProducts()"
                            class="w-full appearance-none bg-white border border-slate-300 text-slate-700 text-sm rounded-lg pl-9 pr-8 py-2.5 outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer transition">
                        <option value="">All Categories</option>
                        <optgroup label="Main Types">
                            <option value="physical">Physical Items</option>
                            <option value="digital">Digital Goods</option>
                        </optgroup>
                        <?php if (!empty($categories)): ?>
                        <optgroup label="Specific Categories">
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                    <i class="fa-solid fa-layer-group absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                </div>

                <!-- Sort -->
                <div class="relative min-w-[165px]">
                    <select id="sortSelect" onchange="fetchProducts()"
                            class="w-full appearance-none bg-white border border-slate-300 text-slate-700 text-sm rounded-lg pl-9 pr-8 py-2.5 outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer transition">
                        <option value="newest">Newest First</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="name_asc">Name: A–Z</option>
                    </select>
                    <i class="fa-solid fa-arrow-up-wide-short absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                </div>

                <!-- Clear -->
                <button type="button" onclick="shopResetFilters()"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 hover:border-slate-400 text-sm font-medium transition whitespace-nowrap">
                    <i class="fa-solid fa-xmark text-xs"></i> Clear
                </button>
            </form>
        </div>
    </div>

    <?php /* ── Product Grid ────────────────────────────────────── */ ?>
    <div class="max-w-7xl mx-auto px-4 py-8">

        <!-- Loading -->
        <div id="shopLoading" class="hidden py-24 flex justify-center">
            <i class="fa-solid fa-circle-notch fa-spin text-3xl text-blue-500"></i>
        </div>

        <!-- No Results -->
        <div id="shopNoResults" class="hidden py-24 text-center">
            <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-magnifying-glass text-3xl text-slate-400"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-700">No products found</h3>
            <p class="text-slate-400 text-sm mt-2">Try adjusting your search or filters.</p>
        </div>

        <!-- Grid -->
        <div id="productGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <?= shopRenderCard($p) ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
(function () {
    let shopDebounceTimer;

    window.shopDebounce = function () {
        clearTimeout(shopDebounceTimer);
        shopDebounceTimer = setTimeout(fetchProducts, 300);
    };

    window.shopResetFilters = function () {
        document.getElementById('searchInput').value = '';
        document.getElementById('catSelect').value   = '';
        document.getElementById('sortSelect').value  = 'newest';
        fetchProducts();
    };

    window.fetchProducts = async function () {
        const q    = document.getElementById('searchInput').value;
        const cat  = document.getElementById('catSelect').value;
        const sort = document.getElementById('sortSelect').value;

        const grid      = document.getElementById('productGrid');
        const loading   = document.getElementById('shopLoading');
        const noResults = document.getElementById('shopNoResults');

        grid.innerHTML = '';
        loading.classList.remove('hidden');
        noResults.classList.add('hidden');

        try {
            const res  = await fetch(`/api/shop/search?q=${encodeURIComponent(q)}&cat=${encodeURIComponent(cat)}&sort=${encodeURIComponent(sort)}`);
            const data = await res.json();

            loading.classList.add('hidden');

            if (!data.products || data.products.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            data.products.forEach(p => {
                const isDigital = p.type === 'digital';

                const typeCls   = isDigital ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600';
                const typeLabel = isDigital ? 'DIGITAL' : 'PHYSICAL';

                const imgHtml = p.image
                    ? `<img src="/${p.image}" alt="${escHtml(p.name)}"
                           class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                           loading="lazy" decoding="async">`
                    : `<div class="w-full h-full flex items-center justify-center bg-slate-100">
                           <i class="fa-regular fa-image text-4xl text-slate-300"></i>
                       </div>`;

                const stockHtml = isDigital
                    ? `<span class="inline-flex items-center gap-1 text-xs text-blue-600 font-medium">
                           <i class="fa-solid fa-bolt" style="font-size:10px"></i> Instant Delivery
                       </span>`
                    : (p.stock > 0
                        ? `<span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                               <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#10b981"></span> In Stock
                           </span>`
                        : `<span class="inline-flex items-center gap-1 text-xs text-rose-500 font-medium">
                               <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#f87171"></span> Out of Stock
                           </span>`);

                let priceHtml = '', discountBadge = '';
                if (p.has_discount && p.original_price && p.discount_percent > 0) {
                    priceHtml = `<div class="flex items-baseline gap-1.5">
                        <span class="text-base font-bold text-blue-600">${parseInt(p.price).toLocaleString()} Ks</span>
                        <span class="text-xs text-slate-400 line-through">${parseInt(p.original_price).toLocaleString()} Ks</span>
                    </div>`;
                    discountBadge = `<span class="absolute top-2 left-2 bg-rose-500 text-white z-10"
                        style="font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px">
                        -${p.discount_percent}%
                    </span>`;
                } else {
                    priceHtml = `<span class="text-base font-bold text-blue-600">${parseInt(p.price).toLocaleString()} Ks</span>`;
                }

                const outOfStock = !isDigital && p.stock <= 0;
                const cartBtn = outOfStock
                    ? `<button disabled
                           class="flex items-center justify-center gap-1.5 w-full py-2 rounded-lg text-sm font-semibold cursor-not-allowed z-20 relative"
                           style="background:#e2e8f0;color:#94a3b8">
                           <i class="fa-solid fa-ban" style="font-size:11px"></i> Out of Stock
                       </button>`
                    : `<button onclick="event.preventDefault(); addToCart(this)"
                           data-id="${p.id}"
                           data-name="${escHtml(p.name)}"
                           data-price="${p.price}"
                           data-image="${escHtml(p.image ?? '')}"
                           data-type="${p.type}"
                           data-stock="${p.stock}"
                           class="flex items-center justify-center gap-1.5 w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-sm font-semibold transition-all duration-150 z-20 relative">
                           <i class="fa-solid fa-cart-plus" style="font-size:11px"></i> Add to Cart
                       </button>`;

                const card = `
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-200 overflow-hidden flex flex-col group relative border border-slate-100">
                    <a href="/product?id=${p.id}" class="absolute inset-0 z-10" aria-label="${escHtml(p.name)}"></a>
                    <div class="aspect-square overflow-hidden bg-slate-50 relative">
                        ${imgHtml}
                        ${discountBadge}
                        <span class="absolute top-2 right-2 text-[10px] font-bold px-2 py-0.5 rounded-md z-10 ${typeCls}">${typeLabel}</span>
                    </div>
                    <div class="p-3 flex flex-col flex-grow gap-2">
                        <h3 class="text-slate-800 font-semibold text-sm leading-snug line-clamp-2">${escHtml(p.name)}</h3>
                        ${stockHtml}
                        <div class="mt-auto pt-2 border-t border-slate-100 flex flex-col gap-2 pointer-events-auto">
                            ${priceHtml}
                            ${cartBtn}
                        </div>
                    </div>
                </div>`;
                grid.insertAdjacentHTML('beforeend', card);
            });

        } catch (err) {
            console.error('Shop fetch error:', err);
            loading.classList.add('hidden');
        }
    };

    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    document.addEventListener('DOMContentLoaded', fetchProducts);
}());
</script>