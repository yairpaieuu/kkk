<?php
    /* ── helpers ──────────────────────────────────────────────── */
    $pageSections = $pageSections ?? [];
    $heroSection     = $pageSections['hero']              ?? null;
    $promoSection    = $pageSections['promo_strip']       ?? null;
    $featSection     = $pageSections['featured_products'] ?? null;
    $catSection      = $pageSections['categories']        ?? null;

    function homeSection(array|null $s): bool {
        return $s && isset($s['is_visible']) && (int)$s['is_visible'] === 1;
    }

    function homeSettings(array|null $s): array {
        if (!$s) return [];
        $decoded = json_decode($s['settings'] ?? '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    function renderProductCard(array $p): string {
        $isDigital  = strtolower($p['type']) === 'digital';
        $typeLabel  = $isDigital ? 'DIGITAL' : 'PHYSICAL';
        $typeCls    = $isDigital
            ? 'bg-blue-100 text-blue-700'
            : 'bg-slate-100 text-slate-600';

        if ($p['image']) {
            $img = '<img src="/' . htmlspecialchars($p['image']) . '"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        alt="' . htmlspecialchars($p['name']) . '" loading="lazy" decoding="async">';
        } else {
            $img = '<div class="w-full h-full flex items-center justify-center bg-slate-100">
                        <i class="fa-regular fa-image text-4xl text-slate-300"></i>
                    </div>';
        }

        if ($isDigital) {
            $stock = '<span class="inline-flex items-center gap-1 text-xs text-blue-600 font-medium">
                          <i class="fa-solid fa-bolt text-[10px]"></i> Instant Delivery
                      </span>';
        } else {
            $inStock = (int)$p['stock'] > 0;
            $stock = $inStock
                ? '<span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span> In Stock
                   </span>'
                : '<span class="inline-flex items-center gap-1 text-xs text-rose-500 font-medium">
                       <span class="w-1.5 h-1.5 rounded-full bg-rose-400 inline-block"></span> Out of Stock
                   </span>';
        }

        if (!empty($p['has_discount']) && !empty($p['original_price']) && !empty($p['discount_percent']) && (int)$p['discount_percent'] > 0) {
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

        $cartBtn = '<button
                        onclick="addToCart(this)"
                        data-id="'    . $p['id']                         . '"
                        data-name="'  . htmlspecialchars($p['name'])      . '"
                        data-price="' . $p['price']                       . '"
                        data-image="' . htmlspecialchars($p['image'] ?? '') . '"
                        data-type="'  . $p['type']                        . '"
                        data-stock="' . $p['stock']                       . '"
                        class="flex items-center justify-center gap-1.5 w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-sm font-semibold transition-all duration-150 z-20 relative">
                        <i class="fa-solid fa-cart-plus text-xs"></i> Add to Cart
                    </button>';

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

<?php /* ── Admin shortcut ────────────────────────────────────── */ ?>
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<div class="max-w-7xl mx-auto px-4 pt-4 flex justify-end">
    <a href="/admin"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-xl shadow transition-all duration-150 hover:scale-105">
        <i class="fa-solid fa-screwdriver-wrench text-xs"></i> Admin Panel
    </a>
</div>
<?php endif; ?>

<?php /* ══════════════════════════════════════════════════════════
       1. HERO
       ══════════════════════════════════════════════════════════ */ ?>
<?php if (homeSection($heroSection)): ?>
<?php
    $heroSettings = homeSettings($heroSection);
    $ctaText = $heroSettings['cta_text'] ?? 'Shop Now';
    $ctaLink = $heroSettings['cta_link'] ?? '/shop';
?>
<?php if (!empty($banners)): ?>
<section class="relative w-full overflow-hidden bg-slate-900">
    <div class="swiper homeSwiperHero w-full h-[340px] md:h-[520px] lg:h-[600px]">
        <div class="swiper-wrapper">
            <?php foreach ($banners as $banner): ?>
            <div class="swiper-slide relative">
                <?php if (!empty($banner['link_url'])): ?>
                <a href="<?= htmlspecialchars($banner['link_url']) ?>" class="block w-full h-full">
                <?php endif; ?>
                    <img src="/<?= htmlspecialchars($banner['image_path']) ?>"
                         class="w-full h-full object-cover object-center"
                         alt="Banner" loading="eager" fetchpriority="high">
                <?php if (!empty($banner['link_url'])): ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="swiper-pagination !bottom-4"></div>
        <div class="swiper-button-prev !text-white !w-9 !h-9 after:!text-sm !left-3 bg-black/30 rounded-full backdrop-blur-sm"></div>
        <div class="swiper-button-next !text-white !w-9 !h-9 after:!text-sm !right-3 bg-black/30 rounded-full backdrop-blur-sm"></div>
    </div>
    <?php if (!empty($heroSection['title']) || !empty($heroSection['content'])): ?>
    <div class="absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-black/75 via-black/30 to-transparent pt-16 pb-8 px-4 pointer-events-none">
        <div class="max-w-7xl mx-auto pointer-events-auto">
            <?php if (!empty($heroSection['title'])): ?>
            <h1 class="text-white text-2xl md:text-4xl font-extrabold drop-shadow-lg mb-2">
                <?= htmlspecialchars($heroSection['title']) ?>
            </h1>
            <?php endif; ?>
            <?php if (!empty($heroSection['content'])): ?>
            <p class="text-slate-200 text-sm md:text-base mb-4 max-w-xl drop-shadow">
                <?= htmlspecialchars($heroSection['content']) ?>
            </p>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($ctaLink) ?>"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl shadow-lg transition-all duration-150 hover:scale-105 text-sm">
                <?= htmlspecialchars($ctaText) ?> <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>
</section>

<?php else: /* fallback gradient hero when no banners */ ?>
<section class="bg-gradient-to-br from-blue-600 to-blue-800 py-20 px-4">
    <div class="max-w-7xl mx-auto text-center">
        <?php if (!empty($heroSection['title'])): ?>
        <h1 class="text-white text-3xl md:text-5xl font-extrabold mb-4 drop-shadow-sm">
            <?= htmlspecialchars($heroSection['title']) ?>
        </h1>
        <?php endif; ?>
        <?php if (!empty($heroSection['content'])): ?>
        <p class="text-blue-100 text-base md:text-lg mb-8 max-w-2xl mx-auto">
            <?= htmlspecialchars($heroSection['content']) ?>
        </p>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($ctaLink) ?>"
           class="inline-flex items-center gap-2 bg-white text-blue-700 font-bold px-8 py-3 rounded-xl shadow-lg hover:bg-blue-50 transition-all duration-150 hover:scale-105">
            <?= htmlspecialchars($ctaText) ?> <i class="fa-solid fa-arrow-right text-sm"></i>
        </a>
    </div>
</section>
<?php endif; ?>
<?php endif; /* hero */ ?>

<?php /* ══════════════════════════════════════════════════════════
       2. PROMO STRIP
       ══════════════════════════════════════════════════════════ */ ?>
<?php if (homeSection($promoSection)): ?>
<section class="bg-slate-50 border-y border-slate-100 py-10">
    <div class="max-w-7xl mx-auto px-4">
        <?php if (!empty($promoSection['title'])): ?>
        <h2 class="sr-only"><?= htmlspecialchars($promoSection['title']) ?></h2>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            $promos = [
                ['icon' => 'fa-truck',          'title' => 'Free Shipping',    'desc'  => 'On all orders over a minimum spend.'],
                ['icon' => 'fa-shield-halved',  'title' => 'Secure Payment',   'desc'  => '100% secure & encrypted checkout.'],
                ['icon' => 'fa-rotate-left',    'title' => 'Easy Returns',     'desc'  => 'Hassle-free return policy.'],
            ];
            foreach ($promos as $promo): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 flex items-start gap-4 px-6 py-5">
                <div class="flex-shrink-0 w-11 h-11 rounded-full bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid <?= $promo['icon'] ?> text-blue-600 text-lg"></i>
                </div>
                <div>
                    <p class="font-semibold text-slate-800 text-sm"><?= $promo['title'] ?></p>
                    <p class="text-slate-500 text-xs mt-0.5"><?= $promo['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; /* promo_strip */ ?>

<?php /* ══════════════════════════════════════════════════════════
       3. FEATURED PRODUCTS
       ══════════════════════════════════════════════════════════ */ ?>
<?php if (homeSection($featSection)): ?>
<section class="bg-white py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-end justify-between mb-8 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-extrabold text-slate-800">
                    <?= htmlspecialchars($featSection['title'] ?: 'Featured Products') ?>
                </h2>
                <?php if (!empty($featSection['content'])): ?>
                <p class="text-slate-500 text-sm mt-1"><?= htmlspecialchars($featSection['content']) ?></p>
                <?php endif; ?>
            </div>
            <a href="/shop" class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center gap-1 transition whitespace-nowrap">
                View All <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        <?php if (!empty($products)): ?>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($products as $p): ?>
                <?= renderProductCard($p) ?>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-16 text-slate-400">
            <i class="fa-regular fa-box-open text-4xl mb-3"></i>
            <p class="font-medium">No products available yet.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; /* featured_products */ ?>

<?php /* ══════════════════════════════════════════════════════════
       4. CATEGORIES
       ══════════════════════════════════════════════════════════ */ ?>
<?php if (homeSection($catSection)): ?>
<section class="bg-slate-50 border-t border-slate-100 py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-slate-800">
                    <?= htmlspecialchars($catSection['title'] ?: 'Shop by Category') ?>
                </h2>
                <?php if (!empty($catSection['content'])): ?>
                <p class="text-slate-500 text-sm mt-1"><?= htmlspecialchars($catSection['content']) ?></p>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3">
                <a href="/shop?cat=physical"
                   class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:border-blue-500 hover:text-blue-600 font-semibold px-5 py-2.5 rounded-xl shadow-sm transition-all duration-150 text-sm">
                    <i class="fa-solid fa-box-open text-slate-400"></i> Physical Goods
                </a>
                <a href="/shop?cat=digital"
                   class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:border-blue-500 hover:text-blue-600 font-semibold px-5 py-2.5 rounded-xl shadow-sm transition-all duration-150 text-sm">
                    <i class="fa-solid fa-cloud-arrow-down text-slate-400"></i> Digital
                </a>
                <a href="/shop"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-sm transition-all duration-150 text-sm">
                    All Products <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; /* categories */ ?>

<?php /* ── Swiper init (only when banners are present) ─────── */ ?>
<?php if (homeSection($heroSection) && !empty($banners)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Swiper('.homeSwiperHero', {
        loop: true,
        effect: 'fade',
        fadeEffect: { crossFade: true },
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