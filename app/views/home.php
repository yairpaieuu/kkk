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
    $typeCls   = $isDigital ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600';

    if (!empty($p['image'])) {
        $img = '<img src="' . imgSrc($p['image']) . '"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     alt="' . htmlspecialchars($p['name']) . '" loading="lazy" decoding="async">';
    } else {
        $img = '<div class="w-full h-full flex flex-col items-center justify-center bg-slate-100 gap-2">
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

    $hasDiscount = !empty($p['has_discount']) && !empty($p['original_price'])
                   && !empty($p['discount_percent']) && (int)$p['discount_percent'] > 0;

    if ($hasDiscount) {
        $badge     = '<span class="absolute top-2.5 left-2.5 z-10 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow">
                          -' . (int)$p['discount_percent'] . '%
                      </span>';
        $priceHtml = '<div class="flex items-baseline gap-2">
                          <span class="text-lg font-bold text-slate-900">' . number_format($p['price']) . ' Ks</span>
                          <span class="text-xs text-slate-400 line-through">' . number_format($p['original_price']) . ' Ks</span>
                      </div>';
    } else {
        $badge     = '<span class="absolute top-2.5 left-2.5 z-10 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow">NEW</span>';
        $priceHtml = '<span class="text-lg font-bold text-slate-900">' . number_format($p['price']) . ' Ks</span>';
    }

    $cartBtn = '<button
                    onclick="addToCart(this)"
                    data-id="'    . (int)$p['id']                      . '"
                    data-name="'  . htmlspecialchars($p['name'])        . '"
                    data-price="' . (float)$p['price']                 . '"
                    data-image="' . htmlspecialchars($p['image'] ?? '') . '"
                    data-type="'  . htmlspecialchars($p['type'])        . '"
                    data-stock="' . (int)$p['stock']                   . '"
                    class="relative z-20 flex items-center justify-center gap-1.5 w-full py-2.5 rounded-xl
                           bg-rose-500 hover:bg-rose-600 active:scale-[.97] text-white text-sm font-bold
                           shadow-sm transition-all duration-150">
                    <i class="fa-solid fa-cart-plus text-xs"></i> Add to Cart
                </button>';

    return '
    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200
                overflow-hidden flex flex-col group relative border border-slate-100">
        <a href="/product?id=' . (int)$p['id'] . '" class="absolute inset-0 z-10"
           aria-label="' . htmlspecialchars($p['name']) . '"></a>
        <div class="aspect-square overflow-hidden bg-slate-50 relative">
            ' . $img . '
            ' . $badge . '
            <span class="absolute top-2.5 right-2.5 z-10 text-[10px] font-bold px-2 py-0.5 rounded-full ' . $typeCls . '">' . $typeLabel . '</span>
        </div>
        <div class="p-4 flex flex-col flex-grow gap-2">
            <h3 class="text-slate-800 font-semibold text-sm leading-snug line-clamp-2">' . htmlspecialchars($p['name']) . '</h3>
            <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star-half-stroke"></i>
            </div>
            ' . $stockBadge . '
            <div class="mt-auto pt-3 border-t border-slate-100 flex flex-col gap-2 pointer-events-auto">
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
       class="inline-flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white text-sm font-bold
              px-5 py-2 rounded-xl shadow transition-all duration-150 hover:scale-105">
        <i class="fa-solid fa-screwdriver-wrench text-xs"></i> Admin Panel
    </a>
</div>
<?php endif; ?>

<?php
/* ═══════════════════════════════════════════════════════════════
   1. HERO
   ═══════════════════════════════════════════════════════════════ */
$heroSettings = homeSettings($heroSection);
$ctaLink      = $heroSettings['cta_link'] ?? '/shop';
$heroTitle    = $heroSection['title']   ?? 'Premium Products';
$heroSubtitle = $heroSection['content'] ?? 'Discover top-quality items handpicked for you.';
?>

<?php if (!empty($banners)): ?>
<!-- ── HERO: Swiper slider with text overlay ───────────────── -->
<?php
// Slide content overlay: one entry per banner (cycled if fewer entries than banners)
$slideContent = [
    [
        'tag'     => 'Summer Sale — Up to 30% Off',
        'title'   => 'Shop the Best in Electronics',
        'sub'     => 'Headphones, laptops, smartwatches & more — all at unbeatable prices.',
        'cta'     => 'Shop the Sale',
        'align'   => 'left',
    ],
    [
        'tag'     => 'New Arrivals',
        'title'   => 'Just Landed — Fresh Picks',
        'sub'     => 'Explore the latest VR headsets, smart speakers & wearables.',
        'cta'     => 'View New In',
        'align'   => 'center',
    ],
    [
        'tag'     => 'Free Shipping',
        'title'   => 'Free Delivery on Orders 50,000+ Ks',
        'sub'     => 'Quality tech delivered to your door. Use code DEMO10 for 10% off.',
        'cta'     => 'Get the Deal',
        'align'   => 'right',
    ],
];
?>
<section class="relative w-full overflow-hidden bg-slate-900">
    <div class="swiper homeSwiperHero w-full h-[360px] md:h-[560px]">
        <div class="swiper-wrapper">
            <?php foreach ($banners as $idx => $banner):
                $sc = $slideContent[$idx % count($slideContent)];
                $alignCls = $sc['align'] === 'center' ? 'items-center text-center' : ($sc['align'] === 'right' ? 'items-end text-right' : 'items-start text-left');
            ?>
            <div class="swiper-slide relative overflow-hidden">
                <!-- Background image -->
                <img src="<?= imgSrc($banner['image_path']) ?>"
                     class="absolute inset-0 w-full h-full object-cover object-center swiper-slide-img"
                     alt="Banner" loading="eager" fetchpriority="high">

                <!-- Dark gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/40 to-black/20"></div>

                <!-- Text content -->
                <div class="relative z-10 h-full flex flex-col justify-center px-8 md:px-20 max-w-7xl mx-auto w-full">
                    <div class="flex flex-col <?= $alignCls ?> max-w-xl <?= $sc['align'] === 'right' ? 'ml-auto' : ($sc['align'] === 'center' ? 'mx-auto' : '') ?>">
                        <!-- Tag pill -->
                        <span class="inline-block bg-blue-600 text-white text-[11px] font-bold uppercase tracking-widest px-3 py-1 rounded-full mb-3 self-<?= $sc['align'] === 'center' ? 'center' : ($sc['align'] === 'right' ? 'end' : 'start') ?>">
                            <?= htmlspecialchars($sc['tag']) ?>
                        </span>

                        <!-- Headline -->
                        <h2 class="text-white text-3xl md:text-5xl font-black leading-tight mb-3 drop-shadow-lg">
                            <?= htmlspecialchars($sc['title']) ?>
                        </h2>

                        <!-- Subtitle -->
                        <p class="text-white/80 text-sm md:text-base leading-relaxed mb-6 max-w-sm <?= $sc['align'] === 'right' ? 'ml-auto' : ($sc['align'] === 'center' ? 'mx-auto' : '') ?>">
                            <?= htmlspecialchars($sc['sub']) ?>
                        </p>

                        <!-- CTA button -->
                        <a href="<?= htmlspecialchars($banner['link_url'] ?? '/shop') ?>"
                           class="inline-flex items-center gap-2 bg-white text-slate-900 hover:bg-blue-600 hover:text-white font-bold text-sm px-6 py-3 rounded-full shadow-lg transition-all duration-200 hover:scale-105 self-<?= $sc['align'] === 'center' ? 'center' : ($sc['align'] === 'right' ? 'end' : 'start') ?>">
                            <?= htmlspecialchars($sc['cta']) ?>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="swiper-pagination !bottom-6"></div>

        <!-- Navigation arrows -->
        <button class="swiper-button-prev !text-white after:!text-sm !w-11 !h-11 !left-4
                       !bg-white/20 hover:!bg-white/40 !rounded-full !backdrop-blur-sm
                       !border !border-white/20 !transition-all !duration-150" aria-label="Previous"></button>
        <button class="swiper-button-next !text-white after:!text-sm !w-11 !h-11 !right-4
                       !bg-white/20 hover:!bg-white/40 !rounded-full !backdrop-blur-sm
                       !border !border-white/20 !transition-all !duration-150" aria-label="Next"></button>
    </div>
</section>

<?php else: /* ── HERO: Static (no banners) ── */ ?>
<section class="relative overflow-hidden bg-[#ebebeb] min-h-[320px] md:min-h-[460px]">

    <!-- Huge watermark text -->
    <span class="absolute inset-x-0 bottom-0 text-center font-black uppercase leading-none
                 text-[80px] md:text-[150px] text-white/60 select-none pointer-events-none z-0
                 tracking-wider">
        PRODUCTS
    </span>

    <div class="relative z-10 max-w-7xl mx-auto px-6 py-12 md:py-16 flex flex-col md:flex-row items-center gap-8">

        <!-- Left content -->
        <div class="flex-1 md:text-left text-center">
            <p class="text-slate-500 text-sm font-medium mb-2"><?= htmlspecialchars($siteSettings['site_name'] ?? 'Our Store') ?></p>
            <h1 class="text-slate-900 text-4xl md:text-6xl font-black leading-tight mb-4">
                <?= htmlspecialchars($heroTitle) ?>
            </h1>
            <a href="<?= htmlspecialchars($ctaLink) ?>"
               class="inline-flex items-center gap-2 bg-rose-500 hover:bg-rose-600 text-white font-bold
                      px-6 py-2.5 rounded-lg shadow-md transition-all duration-150 hover:scale-105 text-sm mt-2">
                Shop By Category
            </a>
        </div>

        <!-- Right description box -->
        <div class="flex-shrink-0 max-w-xs w-full md:text-right text-center">
            <p class="text-slate-500 font-semibold text-xs mb-1 uppercase tracking-wide">Description</p>
            <p class="text-slate-500 text-sm leading-relaxed"><?= htmlspecialchars($heroSubtitle) ?></p>
        </div>

    </div>
</section>
<?php endif; ?>

<?php
/* ═══════════════════════════════════════════════════════════════
   2. CATEGORY MOSAIC GRID (always shown — decorative, not driven by page sections)
   ═══════════════════════════════════════════════════════════════ */
?>
<section class="bg-[#f5f5f5] py-6 px-4">
    <div class="max-w-7xl mx-auto">

        <!-- Mosaic: Row 1 — 3 tiles (col-spans: 1, 1, 2) on a 4-col grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">

            <!-- Tile 1: Earphone — dark/black -->
            <a href="/shop?cat=physical"
               class="group relative overflow-hidden rounded-2xl min-h-[160px] md:min-h-[200px] flex flex-col justify-end p-5"
               style="background-color:#1a1a1a;">
                <div class="absolute inset-0 bg-gradient-to-br from-zinc-800 to-black opacity-80"></div>
                <div class="absolute inset-0 flex items-center justify-center opacity-20">
                    <i class="fa-solid fa-headphones text-[80px] text-white"></i>
                </div>
                <div class="relative z-10">
                    <p class="text-slate-400 text-[10px] font-medium uppercase tracking-widest mb-0.5">Enjoy With</p>
                    <p class="text-white font-black text-xl uppercase leading-none tracking-tight">EARPHONE</p>
                    <button class="mt-3 bg-rose-500 hover:bg-rose-600 text-white text-[11px] font-bold px-4 py-1.5 rounded-lg transition-colors">
                        Browse
                    </button>
                </div>
            </a>

            <!-- Tile 2: Gadgets/Watch — yellow -->
            <a href="/shop?cat=physical"
               class="group relative overflow-hidden rounded-2xl min-h-[160px] md:min-h-[200px] flex flex-col justify-end p-5"
               style="background-color:#f5a623;">
                <div class="absolute inset-0 flex items-center justify-center opacity-25">
                    <i class="fa-solid fa-clock text-[80px] text-white"></i>
                </div>
                <div class="relative z-10">
                    <p class="text-white/80 text-[10px] font-medium uppercase tracking-widest mb-0.5">Trend</p>
                    <p class="text-white font-black text-xl uppercase leading-none tracking-tight">GADGETS</p>
                    <button class="mt-3 bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold px-4 py-1.5 rounded-lg transition-colors border border-white/30">
                        Browse
                    </button>
                </div>
            </a>

            <!-- Tile 3: Devices — red (spans 2 cols on desktop) -->
            <a href="/shop?cat=physical"
               class="col-span-2 group relative overflow-hidden rounded-2xl min-h-[160px] md:min-h-[200px] flex flex-col justify-end p-5"
               style="background-color:#e53e3e;">
                <div class="absolute inset-0 flex items-center justify-end pr-6 opacity-30">
                    <i class="fa-solid fa-laptop text-[120px] text-white"></i>
                </div>
                <div class="relative z-10">
                    <p class="text-white/80 text-[10px] font-medium uppercase tracking-widest mb-0.5">Trend</p>
                    <p class="text-white font-black text-2xl leading-none">Devices</p>
                    <p class="text-white/60 font-black text-3xl uppercase leading-none tracking-tight">LAPTOP</p>
                    <button class="mt-3 bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold px-4 py-1.5 rounded-lg transition-colors border border-white/30">
                        Browse
                    </button>
                </div>
            </a>

        </div>

        <!-- Mosaic: Row 2 — 3 tiles (col-spans: 2, 1, 1) on a 4-col grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">

            <!-- Tile 4: Console — light/white (spans 2 cols on desktop) -->
            <a href="/shop?cat=physical"
               class="col-span-2 group relative overflow-hidden rounded-2xl min-h-[160px] md:min-h-[200px] flex flex-col justify-end p-5"
               style="background-color:#f0f0f0;">
                <div class="absolute inset-0 flex items-center justify-end pr-6 opacity-20">
                    <i class="fa-solid fa-gamepad text-[120px] text-slate-600"></i>
                </div>
                <div class="relative z-10">
                    <p class="text-slate-500 text-[10px] font-medium uppercase tracking-widest mb-0.5">Best</p>
                    <p class="text-slate-800 font-black text-2xl leading-none">Gaming</p>
                    <p class="text-slate-300 font-black text-3xl uppercase leading-none tracking-tight">CONSOLE</p>
                    <button class="mt-3 bg-rose-500 hover:bg-rose-600 text-white text-[11px] font-bold px-4 py-1.5 rounded-lg transition-colors">
                        Browse
                    </button>
                </div>
            </a>

            <!-- Tile 5: VR/Oculus — green -->
            <a href="/shop?cat=digital"
               class="group relative overflow-hidden rounded-2xl min-h-[160px] md:min-h-[200px] flex flex-col justify-end p-5"
               style="background-color:#38a169;">
                <div class="absolute inset-0 flex items-center justify-center opacity-20">
                    <i class="fa-solid fa-vr-cardboard text-[80px] text-white"></i>
                </div>
                <div class="relative z-10">
                    <p class="text-white/80 text-[10px] font-medium uppercase tracking-widest mb-0.5">Play</p>
                    <p class="text-white font-black text-lg leading-none">Game</p>
                    <p class="text-white/60 font-black text-xl uppercase leading-none tracking-tight">OCULUS</p>
                    <button class="mt-3 bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold px-4 py-1.5 rounded-lg transition-colors border border-white/30">
                        Browse
                    </button>
                </div>
            </a>

            <!-- Tile 6: Smart Speaker — blue -->
            <a href="/shop?cat=physical"
               class="group relative overflow-hidden rounded-2xl min-h-[160px] md:min-h-[200px] flex flex-col justify-end p-5"
               style="background-color:#3182ce;">
                <div class="absolute inset-0 flex items-center justify-center opacity-20">
                    <i class="fa-solid fa-volume-high text-[80px] text-white"></i>
                </div>
                <div class="relative z-10">
                    <p class="text-white/80 text-[10px] font-medium uppercase tracking-widest mb-0.5">New</p>
                    <p class="text-white font-black text-lg leading-none">Smart</p>
                    <p class="text-white/60 font-black text-xl uppercase leading-none tracking-tight">SPEAKER</p>
                    <button class="mt-3 bg-white/20 hover:bg-white/30 text-white text-[11px] font-bold px-4 py-1.5 rounded-lg transition-colors border border-white/30">
                        Browse
                    </button>
                </div>
            </a>

        </div>

    </div>
</section>

<?php
/* ═══════════════════════════════════════════════════════════════
   3. TRUST BADGES (always visible — matches image layout)
   ═══════════════════════════════════════════════════════════════ */ ?>
<div class="bg-white border-y border-slate-100">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-slate-100">
            <div class="flex items-center gap-4 px-6 py-5">
                <i class="fa-solid fa-truck text-slate-600 text-2xl flex-shrink-0"></i>
                <div>
                    <p class="font-bold text-slate-800 text-sm">Free Shipping</p>
                    <p class="text-slate-400 text-xs">Free Shipping On All Order</p>
                </div>
            </div>
            <div class="flex items-center gap-4 px-6 py-5">
                <i class="fa-solid fa-shield-halved text-slate-600 text-2xl flex-shrink-0"></i>
                <div>
                    <p class="font-bold text-slate-800 text-sm">Money Guarantee</p>
                    <p class="text-slate-400 text-xs">30 Day Money Back</p>
                </div>
            </div>
            <div class="flex items-center gap-4 px-6 py-5">
                <i class="fa-solid fa-headset text-slate-600 text-2xl flex-shrink-0"></i>
                <div>
                    <p class="font-bold text-slate-800 text-sm">Online Support 24/7</p>
                    <p class="text-slate-400 text-xs">Technical Support 24/7</p>
                </div>
            </div>
            <div class="flex items-center gap-4 px-6 py-5">
                <i class="fa-solid fa-credit-card text-slate-600 text-2xl flex-shrink-0"></i>
                <div>
                    <p class="font-bold text-slate-800 text-sm">Secure Payment</p>
                    <p class="text-slate-400 text-xs">All Cards Accepted</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/* ═══════════════════════════════════════════════════════════════
   4. PROMO BANNER (Summer Sale — matches image style)
   ═══════════════════════════════════════════════════════════════ */ ?>
<section class="bg-[#f5f5f5] py-8 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="relative overflow-hidden rounded-2xl min-h-[220px] md:min-h-[280px]"
             style="background-color:#e53e3e;">

            <!-- Left large text -->
            <div class="absolute inset-y-0 left-0 flex flex-col justify-center pl-8 md:pl-14 z-10">
                <p class="text-white/60 text-xs font-bold uppercase tracking-widest mb-1">20% OFF</p>
                <p class="text-white font-black text-4xl md:text-6xl leading-none uppercase">FIND</p>
                <p class="text-white font-black text-4xl md:text-6xl leading-none uppercase">YOUR</p>
                <p class="text-white font-black text-4xl md:text-6xl leading-none uppercase">DEAL</p>
                <p class="text-white/60 text-xs mt-2">15 Nov To 7 Dec</p>
            </div>

            <!-- Center: decorative product icon (overlapping top) -->
            <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none opacity-20">
                <i class="fa-solid fa-headphones text-white" style="font-size:220px;"></i>
            </div>

            <!-- Right content -->
            <div class="absolute inset-y-0 right-0 flex flex-col justify-center pr-8 md:pr-14 z-10 text-right">
                <p class="text-white/80 text-xs font-medium mb-1">Exclusive Collection</p>
                <h3 class="text-white font-extrabold text-2xl md:text-3xl mb-2">Summer Sale</h3>
                <p class="text-white/70 text-xs mb-5 max-w-[180px] ml-auto leading-relaxed">
                    Incredible deals on premium products. Limited time only.
                </p>
                <div class="flex justify-end">
                    <a href="/shop"
                       class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 border border-white/40
                              text-white font-bold text-sm px-5 py-2 rounded-lg transition-all duration-150">
                        Shop Now
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<?php
/* ═══════════════════════════════════════════════════════════════
   5. BEST SELLER PRODUCTS
   ═══════════════════════════════════════════════════════════════ */
$featTitle    = $featSection['title']   ?? 'Best Seller Products';
$featSubtitle = $featSection['content'] ?? 'There are many variations passages';
?>
<section class="bg-white py-14">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Section header — centered, matching image -->
        <div class="text-center mb-10">
            <h2 class="text-2xl md:text-3xl font-black text-slate-900">
                <?= htmlspecialchars(homeSection($featSection) ? $featTitle : 'Best Seller Products') ?>
            </h2>
            <p class="text-slate-400 text-sm mt-2">
                <?= htmlspecialchars(homeSection($featSection) ? $featSubtitle : 'There are many variations passages') ?>
            </p>
        </div>

        <!-- Product grid -->
        <?php if (!empty($products)): ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-5">
            <?php foreach ($products as $p): ?>
                <?= renderProductCard($p) ?>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-8">
            <a href="/shop"
               class="inline-flex items-center gap-2 border-2 border-rose-500 text-rose-500 hover:bg-rose-500
                      hover:text-white font-bold px-8 py-3 rounded-xl transition-all duration-150 text-sm">
                View All Products <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
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

<?php
/* ═══════════════════════════════════════════════════════════════
   6. NEWSLETTER (always visible)
   ═══════════════════════════════════════════════════════════════ */ ?>
<section class="bg-[#1a1a1a] py-14">
    <div class="max-w-xl mx-auto px-4 text-center">
        <i class="fa-solid fa-envelope-open-text text-rose-400 text-3xl mb-4"></i>
        <h2 class="text-white text-2xl md:text-3xl font-black mb-2">Stay in the Loop</h2>
        <p class="text-slate-400 text-sm mb-6 leading-relaxed">
            Get exclusive deals, new arrivals, and insider-only discounts delivered to your inbox.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center" id="newsletter-form">
            <input type="email" id="newsletter-email" placeholder="Enter your email address"
                   class="flex-1 sm:w-80 bg-zinc-800 border border-zinc-700 rounded-xl px-5 py-3
                          text-white text-sm placeholder-slate-500 outline-none
                          focus:ring-2 focus:ring-rose-400 transition-shadow duration-150">
            <button onclick="handleNewsletterSubscribe()"
                    class="bg-rose-500 hover:bg-rose-600 text-white font-bold rounded-xl px-6 py-3 text-sm
                           active:scale-95 transition-all duration-150 whitespace-nowrap shadow">
                Subscribe
            </button>
        </div>
        <p id="newsletter-success" class="hidden mt-4 text-emerald-400 font-semibold text-sm">
            ✓ Subscribed! Thank you for joining us.
        </p>
        <p class="text-slate-600 text-xs mt-5">We respect your privacy. Unsubscribe anytime.</p>
    </div>
</section>

<?php /* ── Swiper init ─────────────────────────────────────── */ ?>
<?php if (!empty($banners)): ?>
<style>
/* Pagination bullets — white to match dark slider overlay */
.homeSwiperHero .swiper-pagination-bullet { background: rgba(255,255,255,0.5); opacity: 1; }
.homeSwiperHero .swiper-pagination-bullet-active { background: #fff; transform: scale(1.25); }
/* Ken Burns zoom on active slide image */
.homeSwiperHero .swiper-slide-img { transform: scale(1.08); transition: transform 5s ease-out; }
.homeSwiperHero .swiper-slide-active .swiper-slide-img { transform: scale(1.0); }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.homeSwiperHero', {
        loop: true,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        speed: 900,
        autoplay: { delay: 5000, disableOnInteraction: false },
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
