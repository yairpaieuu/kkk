<?php
/* ── Section helpers ───────────────────────────────────────── */
$pageSections = $pageSections ?? [];
$heroSection  = $pageSections['hero']              ?? null;
$promoSection = $pageSections['promo_strip']       ?? null;
$featSection  = $pageSections['featured_products'] ?? null;
$catSection   = $pageSections['categories']        ?? null;

function homeSection(array|null $s): bool {
    return $s && isset($s['is_visible']) && (int)$s['is_visible'] === 1;
}

function homeSettings(array|null $s): array {
    if (!$s) return [];
    $decoded = json_decode($s['settings'] ?? '{}', true);
    return is_array($decoded) ? $decoded : [];
}

function renderProductCard(array $p): string {
    $isDigital = strtolower($p['type']) === 'digital';
    $typeLabel = $isDigital ? 'DIGITAL' : 'PHYSICAL';
    $typeCls   = $isDigital
        ? 'bg-violet-100 text-violet-700'
        : 'bg-slate-100 text-slate-600';

    if (!empty($p['image'])) {
        $img = '<img src="/' . htmlspecialchars($p['image']) . '"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     alt="' . htmlspecialchars($p['name']) . '" loading="lazy" decoding="async">';
    } else {
        $img = '<div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 gap-2">
                    <i class="fa-regular fa-image text-3xl text-slate-300"></i>
                    <span class="text-[10px] text-slate-400 font-medium">No Image</span>
                </div>';
    }

    if ($isDigital) {
        $stockBadge = '<span class="inline-flex items-center gap-1 text-xs text-violet-600 font-semibold">
                           <i class="fa-solid fa-bolt text-[9px]"></i>Instant Delivery
                       </span>';
    } else {
        $inStock    = (int)$p['stock'] > 0;
        $stockBadge = $inStock
            ? '<span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-semibold">
                   <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span>In Stock
               </span>'
            : '<span class="inline-flex items-center gap-1.5 text-xs text-rose-500 font-semibold">
                   <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span>Out of Stock
               </span>';
    }

    $hasDiscount = !empty($p['has_discount'])
                   && !empty($p['original_price'])
                   && !empty($p['discount_percent'])
                   && (int)$p['discount_percent'] > 0;

    if ($hasDiscount) {
        $topLeftBadge = '<span class="absolute top-2.5 left-2.5 z-10 inline-flex items-center gap-0.5
                                      bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow">
                             <i class="fa-solid fa-tag text-[8px]"></i>&nbsp;-' . (int)$p['discount_percent'] . '%
                         </span>';
        $priceHtml    = '<div class="flex items-baseline gap-2">
                             <span class="text-lg font-bold text-blue-600">' . number_format($p['price']) . ' Ks</span>
                             <span class="text-xs text-slate-400 line-through font-medium">' . number_format($p['original_price']) . ' Ks</span>
                         </div>';
    } else {
        $topLeftBadge = '<span class="absolute top-2.5 left-2.5 z-10 inline-flex items-center
                                      bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow">
                             NEW
                         </span>';
        $priceHtml    = '<span class="text-lg font-bold text-blue-600">' . number_format($p['price']) . ' Ks</span>';
    }

    $cartBtn = '<button
                    onclick="addToCart(this)"
                    data-id="'    . (int)$p['id']                        . '"
                    data-name="'  . htmlspecialchars($p['name'])          . '"
                    data-price="' . (float)$p['price']                   . '"
                    data-image="' . htmlspecialchars($p['image'] ?? '')   . '"
                    data-type="'  . htmlspecialchars($p['type'])          . '"
                    data-stock="' . (int)$p['stock']                     . '"
                    class="relative z-20 flex items-center justify-center gap-1.5 w-full py-2.5 rounded-xl
                           bg-blue-600 hover:bg-blue-700 active:scale-[.97] text-white text-sm font-semibold
                           shadow-sm hover:shadow-md transition-all duration-150">
                    <i class="fa-solid fa-cart-plus text-xs"></i> Add to Cart
                </button>';

    return '
    <div class="bg-white rounded-2xl shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200
                overflow-hidden flex flex-col group relative border border-slate-100">
        <a href="/product?id=' . (int)$p['id'] . '" class="absolute inset-0 z-10"
           aria-label="' . htmlspecialchars($p['name']) . '"></a>
        <div class="aspect-[4/3] overflow-hidden bg-slate-50 relative">
            ' . $img . '
            ' . $topLeftBadge . '
            <span class="absolute top-2.5 right-2.5 z-10 text-[10px] font-bold px-2 py-0.5 rounded-full
                         ' . $typeCls . '">' . $typeLabel . '</span>
        </div>
        <div class="p-4 flex flex-col flex-grow gap-2">
            <h3 class="text-slate-900 font-semibold text-sm leading-snug line-clamp-2"
                title="' . htmlspecialchars($p['name']) . '">
                ' . htmlspecialchars($p['name']) . '
            </h3>
            <div class="flex items-center gap-0.5 text-amber-400 text-xs" aria-label="4.5 out of 5 stars">
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star-half-stroke"></i>
                <span class="text-slate-400 ml-1 font-normal">(4.5)</span>
            </div>
            ' . $stockBadge . '
            <div class="mt-auto pt-3 border-t border-slate-100 flex flex-col gap-2.5 pointer-events-auto">
                ' . $priceHtml . '
                ' . $cartBtn . '
            </div>
        </div>
    </div>';
}
?>

<?php /* ── Admin shortcut ──────────────────────────────────── */ ?>
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<div class="max-w-7xl mx-auto px-4 pt-4 flex justify-end">
    <a href="/admin"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold
              px-5 py-2 rounded-xl shadow transition-all duration-150 hover:scale-105">
        <i class="fa-solid fa-screwdriver-wrench text-xs"></i> Admin Panel
    </a>
</div>
<?php endif; ?>

<?php
/* ═══════════════════════════════════════════════════════════════
   1. HERO
   ═══════════════════════════════════════════════════════════════ */
if (homeSection($heroSection)):
    $heroSettings = homeSettings($heroSection);
    $ctaText  = $heroSettings['cta_text'] ?? 'Shop Now';
    $ctaLink  = $heroSettings['cta_link'] ?? '/shop';
    $heroTitle    = $heroSection['title']   ?? 'New Season. New Arrivals.';
    $heroSubtitle = $heroSection['content'] ?? 'Discover our curated collection of premium products, handpicked just for you.';
?>

<?php if (!empty($banners)): ?>
<!-- HERO: Banner Swiper -->
<section class="relative w-full overflow-hidden bg-slate-900">
    <div class="swiper homeSwiperHero w-full h-[360px] md:h-[560px]">
        <div class="swiper-wrapper">
            <?php foreach ($banners as $banner): ?>
            <div class="swiper-slide relative">
                <?php if (!empty($banner['link_url'])): ?>
                <a href="<?= htmlspecialchars($banner['link_url']) ?>" class="block w-full h-full">
                <?php endif; ?>
                    <img src="/<?= htmlspecialchars($banner['image_path']) ?>"
                         class="w-full h-full object-cover object-center"
                         alt="Promotional Banner" loading="eager" fetchpriority="high">
                <?php if (!empty($banner['link_url'])): ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Bottom scrim + content overlay -->
        <div class="absolute inset-x-0 bottom-0 z-20 pointer-events-none
                    bg-gradient-to-t from-black/80 via-black/40 to-transparent pt-20 pb-10 px-6">
            <div class="max-w-7xl mx-auto pointer-events-auto">
                <span class="inline-block mb-3 px-3 py-1 rounded-full bg-blue-600/90 text-white
                             text-[11px] font-bold tracking-widest uppercase backdrop-blur-sm">
                    ✦ New Arrivals
                </span>
                <?php if (!empty($heroTitle)): ?>
                <h1 class="text-white text-2xl md:text-5xl font-extrabold drop-shadow-lg mb-2 max-w-2xl leading-tight">
                    <?= htmlspecialchars($heroTitle) ?>
                </h1>
                <?php endif; ?>
                <?php if (!empty($heroSubtitle)): ?>
                <p class="text-slate-200 text-sm md:text-base mb-5 max-w-xl leading-relaxed">
                    <?= htmlspecialchars($heroSubtitle) ?>
                </p>
                <?php endif; ?>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="<?= htmlspecialchars($ctaLink) ?>"
                       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold
                              px-7 py-3 rounded-xl shadow-lg transition-all duration-150 hover:scale-105 text-sm">
                        <?= htmlspecialchars($ctaText) ?> <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                    <a href="/shop"
                       class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/40
                              text-white font-semibold px-7 py-3 rounded-xl backdrop-blur-sm
                              transition-all duration-150 hover:scale-105 text-sm">
                        View Collections
                    </a>
                </div>
            </div>
        </div>

        <!-- Pagination & Navigation -->
        <div class="swiper-pagination !bottom-5 [&_.swiper-pagination-bullet]:bg-white
                    [&_.swiper-pagination-bullet-active]:bg-blue-400 [&_.swiper-pagination-bullet]:opacity-60
                    [&_.swiper-pagination-bullet-active]:opacity-100"></div>
        <button class="swiper-button-prev !text-white after:!text-base !w-10 !h-10 !left-4
                       !bg-black/30 hover:!bg-black/50 !rounded-full !backdrop-blur-sm
                       !transition-all !duration-150" aria-label="Previous slide"></button>
        <button class="swiper-button-next !text-white after:!text-base !w-10 !h-10 !right-4
                       !bg-black/30 hover:!bg-black/50 !rounded-full !backdrop-blur-sm
                       !transition-all !duration-150" aria-label="Next slide"></button>
    </div>
</section>

<?php else: /* HERO: Split-screen fallback (no banners) */ ?>
<section class="min-h-[500px] bg-gradient-to-br from-blue-700 via-blue-600 to-blue-800 relative overflow-hidden">
    <!-- Decorative circles -->
    <div class="absolute -top-20 -right-20 w-96 h-96 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-16 -left-16 w-72 h-72 bg-blue-400/20 rounded-full blur-2xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 py-16 md:py-24 flex flex-col md:flex-row items-center gap-12 relative z-10">
        <!-- Left: Text content -->
        <div class="flex-1 text-center md:text-left">
            <span class="inline-block mb-4 px-4 py-1.5 rounded-full bg-white/15 text-white
                         text-xs font-bold tracking-widest uppercase border border-white/20">
                ✦ New Arrivals
            </span>
            <h1 class="text-white text-4xl md:text-6xl font-extrabold leading-tight mb-5 drop-shadow-sm">
                <?= htmlspecialchars($heroTitle) ?>
            </h1>
            <p class="text-blue-100 text-base md:text-lg mb-8 max-w-lg mx-auto md:mx-0 leading-relaxed">
                <?= htmlspecialchars($heroSubtitle) ?>
            </p>
            <div class="flex flex-wrap items-center gap-4 justify-center md:justify-start">
                <a href="<?= htmlspecialchars($ctaLink) ?>"
                   class="inline-flex items-center gap-2 bg-white text-blue-700 font-bold
                          px-8 py-3.5 rounded-xl shadow-xl hover:bg-blue-50 transition-all duration-150 hover:scale-105">
                    <?= htmlspecialchars($ctaText) ?> <i class="fa-solid fa-arrow-right text-sm"></i>
                </a>
                <a href="/shop"
                   class="inline-flex items-center gap-2 border-2 border-white/50 hover:border-white
                          text-white font-semibold px-8 py-3.5 rounded-xl transition-all duration-150 hover:scale-105">
                    View Collections
                </a>
            </div>
        </div>

        <!-- Right: Decorative category icon grid -->
        <div class="flex-shrink-0 grid grid-cols-2 gap-4 w-full max-w-xs">
            <a href="/shop?cat=physical"
               class="group flex flex-col items-center justify-center gap-3 bg-white/10 hover:bg-white/20
                      border border-white/20 rounded-2xl p-6 transition-all duration-200 hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-blue-400/30 flex items-center justify-center
                            group-hover:bg-blue-400/50 transition-colors">
                    <i class="fa-solid fa-box-open text-white text-xl"></i>
                </div>
                <span class="text-white text-xs font-semibold text-center">Physical<br>Goods</span>
            </a>
            <a href="/shop?cat=digital"
               class="group flex flex-col items-center justify-center gap-3 bg-white/10 hover:bg-white/20
                      border border-white/20 rounded-2xl p-6 transition-all duration-200 hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-violet-400/30 flex items-center justify-center
                            group-hover:bg-violet-400/50 transition-colors">
                    <i class="fa-solid fa-cloud-arrow-down text-white text-xl"></i>
                </div>
                <span class="text-white text-xs font-semibold text-center">Digital<br>Products</span>
            </a>
            <a href="/shop"
               class="group flex flex-col items-center justify-center gap-3 bg-white/10 hover:bg-white/20
                      border border-white/20 rounded-2xl p-6 transition-all duration-200 hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-emerald-400/30 flex items-center justify-center
                            group-hover:bg-emerald-400/50 transition-colors">
                    <i class="fa-solid fa-store text-white text-xl"></i>
                </div>
                <span class="text-white text-xs font-semibold text-center">All<br>Products</span>
            </a>
            <a href="/shop?sort=popular"
               class="group flex flex-col items-center justify-center gap-3 bg-white/10 hover:bg-white/20
                      border border-white/20 rounded-2xl p-6 transition-all duration-200 hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl bg-amber-400/30 flex items-center justify-center
                            group-hover:bg-amber-400/50 transition-colors">
                    <i class="fa-solid fa-fire text-white text-xl"></i>
                </div>
                <span class="text-white text-xs font-semibold text-center">Best<br>Sellers</span>
            </a>
        </div>
    </div>
</section>
<?php endif; /* banners check */ ?>
<?php endif; /* homeSection hero */ ?>

<?php
/* ═══════════════════════════════════════════════════════════════
   2. TRUST BADGES (always visible)
   ═══════════════════════════════════════════════════════════════ */ ?>
<div class="bg-white border-y border-slate-100 py-6">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-slate-100">
            <div class="bg-white p-6 flex flex-col items-center text-center gap-2">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mb-1">
                    <i class="fa-solid fa-truck-fast text-blue-600 text-xl"></i>
                </div>
                <p class="font-semibold text-slate-800 text-sm">Free Shipping</p>
                <p class="text-slate-400 text-xs leading-tight">On all qualifying orders worldwide</p>
            </div>
            <div class="bg-white p-6 flex flex-col items-center text-center gap-2">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mb-1">
                    <i class="fa-solid fa-shield-halved text-blue-600 text-xl"></i>
                </div>
                <p class="font-semibold text-slate-800 text-sm">Secure Payment</p>
                <p class="text-slate-400 text-xs leading-tight">100% secure & encrypted checkout</p>
            </div>
            <div class="bg-white p-6 flex flex-col items-center text-center gap-2">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mb-1">
                    <i class="fa-solid fa-rotate-left text-blue-600 text-xl"></i>
                </div>
                <p class="font-semibold text-slate-800 text-sm">Easy Returns</p>
                <p class="text-slate-400 text-xs leading-tight">Hassle-free 30-day return policy</p>
            </div>
            <div class="bg-white p-6 flex flex-col items-center text-center gap-2">
                <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center mb-1">
                    <i class="fa-solid fa-star text-blue-600 text-xl"></i>
                </div>
                <p class="font-semibold text-slate-800 text-sm">Premium Quality</p>
                <p class="text-slate-400 text-xs leading-tight">Curated products you can trust</p>
            </div>
        </div>
    </div>
</div>

<?php
/* ═══════════════════════════════════════════════════════════════
   3. FEATURED PRODUCTS
   ═══════════════════════════════════════════════════════════════ */
if (homeSection($featSection)):
    $featTitle    = $featSection['title']   ?? 'Featured Products';
    $featSubtitle = $featSection['content'] ?? 'Hand-picked favourites just for you.';
?>
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Section header -->
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-slate-800 relative inline-block">
                    <?= htmlspecialchars($featTitle) ?>
                    <span class="absolute -bottom-2 left-0 w-10 h-[3px] bg-blue-600 rounded-full"></span>
                </h2>
                <?php if (!empty($featSubtitle)): ?>
                <p class="text-slate-400 text-sm mt-4"><?= htmlspecialchars($featSubtitle) ?></p>
                <?php endif; ?>
            </div>
            <a href="/shop"
               class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-800 text-sm font-semibold
                      whitespace-nowrap transition-colors duration-150">
                View All <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        <!-- Product grid -->
        <?php if (!empty($products)): ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($products as $p): ?>
                <?= renderProductCard($p) ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center py-20 text-slate-400">
            <i class="fa-regular fa-box-open text-5xl mb-4 opacity-50"></i>
            <p class="font-semibold text-slate-500 text-lg">No products yet</p>
            <p class="text-sm mt-1">Check back soon for amazing new arrivals.</p>
        </div>
        <?php endif; ?>

    </div>
</section>
<?php endif; /* featured_products */ ?>

<?php
/* ═══════════════════════════════════════════════════════════════
   4. CATEGORY TILES
   ═══════════════════════════════════════════════════════════════ */
if (homeSection($catSection)):
    $catTitle    = $catSection['title']   ?? 'Shop by Category';
    $catSubtitle = $catSection['content'] ?? 'Find exactly what you\'re looking for.';
?>
<section class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Section header -->
        <div class="text-center mb-12">
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 relative inline-block">
                <?= htmlspecialchars($catTitle) ?>
                <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-10 h-[3px] bg-blue-600 rounded-full"></span>
            </h2>
            <?php if (!empty($catSubtitle)): ?>
            <p class="text-slate-400 text-sm mt-5"><?= htmlspecialchars($catSubtitle) ?></p>
            <?php endif; ?>
        </div>

        <!-- Tiles grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Physical Goods -->
            <a href="/shop?cat=physical"
               class="group bg-white rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1
                      transition-all duration-200 p-8 flex flex-col items-center text-center border border-slate-100">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center
                            mb-5 transition-colors duration-200">
                    <i class="fa-solid fa-box-open text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-slate-900 font-bold text-lg mb-2">Physical Goods</h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-5">
                    Tangible products shipped right to your door. Quality items for everyday life.
                </p>
                <span class="inline-flex items-center gap-1.5 text-blue-600 font-semibold text-sm
                             group-hover:gap-3 transition-all duration-200">
                    Browse <i class="fa-solid fa-arrow-right text-xs"></i>
                </span>
            </a>

            <!-- Digital Products -->
            <a href="/shop?cat=digital"
               class="group bg-white rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1
                      transition-all duration-200 p-8 flex flex-col items-center text-center border border-slate-100">
                <div class="w-16 h-16 rounded-2xl bg-violet-50 group-hover:bg-violet-100 flex items-center justify-center
                            mb-5 transition-colors duration-200">
                    <i class="fa-solid fa-cloud-arrow-down text-violet-600 text-2xl"></i>
                </div>
                <h3 class="text-slate-900 font-bold text-lg mb-2">Digital Products</h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-5">
                    Instant downloads — software, media, and more. Access yours in seconds.
                </p>
                <span class="inline-flex items-center gap-1.5 text-violet-600 font-semibold text-sm
                             group-hover:gap-3 transition-all duration-200">
                    Browse <i class="fa-solid fa-arrow-right text-xs"></i>
                </span>
            </a>

            <!-- All Products -->
            <a href="/shop"
               class="group bg-white rounded-2xl shadow-sm hover:shadow-md hover:-translate-y-1
                      transition-all duration-200 p-8 flex flex-col items-center text-center border border-slate-100">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center
                            mb-5 transition-colors duration-200">
                    <i class="fa-solid fa-store text-emerald-600 text-2xl"></i>
                </div>
                <h3 class="text-slate-900 font-bold text-lg mb-2">All Products</h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-5">
                    Explore the full catalogue. Hundreds of products waiting to be discovered.
                </p>
                <span class="inline-flex items-center gap-1.5 text-emerald-600 font-semibold text-sm
                             group-hover:gap-3 transition-all duration-200">
                    Browse <i class="fa-solid fa-arrow-right text-xs"></i>
                </span>
            </a>

        </div>
    </div>
</section>
<?php endif; /* categories */ ?>

<?php
/* ═══════════════════════════════════════════════════════════════
   5. NEWSLETTER (always visible)
   ═══════════════════════════════════════════════════════════════ */ ?>
<section class="bg-gradient-to-r from-blue-700 to-blue-900 py-16">
    <div class="max-w-xl mx-auto px-4 text-center">
        <i class="fa-solid fa-envelope-open-text text-blue-300 text-3xl mb-4"></i>
        <h2 class="text-white text-2xl md:text-3xl font-bold mb-2">Stay in the Loop</h2>
        <p class="text-blue-200 text-sm mb-6 leading-relaxed">
            Get exclusive deals, new arrivals, and insider-only discounts delivered straight to your inbox.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center" id="newsletter-form">
            <input type="email" id="newsletter-email" placeholder="Enter your email address"
                   class="flex-1 sm:w-80 bg-white rounded-xl px-5 py-3 text-slate-800 text-sm
                          placeholder-slate-400 outline-none focus:ring-2 focus:ring-blue-300
                          transition-shadow duration-150">
            <button onclick="handleNewsletterSubscribe()"
                    class="bg-white text-blue-700 font-bold rounded-xl px-6 py-3 text-sm
                           hover:bg-blue-50 active:scale-95 transition-all duration-150 whitespace-nowrap shadow">
                Subscribe
            </button>
        </div>
        <p id="newsletter-success" class="hidden mt-4 text-emerald-300 font-semibold text-sm">
            ✓ Subscribed! Thank you for joining us.
        </p>
        <p class="text-blue-300/70 text-xs mt-5">We respect your privacy. Unsubscribe anytime.</p>
    </div>
</section>

<?php /* ── Swiper init ─────────────────────────────────────── */ ?>
<?php if (homeSection($heroSection) && !empty($banners)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.homeSwiperHero', {
        loop: true,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        speed: 800,
        autoplay: { delay: 4500, disableOnInteraction: false },
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
});
</script>
<?php endif; ?>

<script>
function handleNewsletterSubscribe() {
    var email = document.getElementById('newsletter-email');
    var success = document.getElementById('newsletter-success');
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        email.classList.add('ring-2', 'ring-rose-400');
        return;
    }
    email.classList.remove('ring-2', 'ring-rose-400');
    document.getElementById('newsletter-form').style.display = 'none';
    success.classList.remove('hidden');
}
</script>
