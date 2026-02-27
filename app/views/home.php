<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<!-- MAIN WRAPPER -->
<div class="pb-24 space-y-12 max-w-full mx-auto px-0 md:px-0"> 

    <!-- 0. ADMIN BUTTON (Restricted to container width) -->
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <div class="max-w-7xl mx-auto px-4 w-full flex justify-end pt-4">
            <a href="/admin" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-3 transition-all hover:scale-105 group border border-indigo-400/30 backdrop-blur-md">
                <span class="text-sm md:text-base">Go to Admin Panel</span>
            </a>
        </div>
    <?php endif; ?>
    
    <!-- 1. SLIDER BANNER (UPDATED: ASPECT RATIO) -->
    <?php if(!empty($banners)): ?>
        <!-- 
           Mobile: h-56 (Fixed height)
           Desktop: md:h-auto md:aspect-[16/9] (16:9 Ratio - Shows full 1366x768 image without crop)
        -->
        <div class="swiper mySwiper w-full h-56 md:h-auto md:aspect-[16/9] overflow-hidden relative group shadow-2xl">
            <div class="swiper-wrapper">
                <?php foreach($banners as $banner): ?>
                    <div class="swiper-slide relative bg-gray-900">
                        <?php if($banner['link_url']): ?>
                            <a href="<?= htmlspecialchars($banner['link_url']) ?>" class="block w-full h-full">
                                <img src="/<?= $banner['image_path'] ?>" class="w-full h-full object-cover object-center" alt="Banner">
                            </a>
                        <?php else: ?>
                            <img src="/<?= $banner['image_path'] ?>" class="w-full h-full object-cover object-center" alt="Banner">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination !bottom-6"></div>
        </div>
    <?php endif; ?>

    <!-- CONTENT CONTAINER (Restricts width for products) -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 space-y-12">

        <?php
            // --- FIX: ROBUST SORTING LOGIC ---
            $physical = [];
            $digital = [];
            
            foreach($products as $p) {
                if(strtolower($p['type']) === 'digital') {
                    $digital[] = $p;
                } else {
                    $physical[] = $p;
                }
            }
            
            function renderCard($p) {
                $imageHtml = $p['image'] 
                    ? '<img src="/'.htmlspecialchars($p['image']).'" class="w-full h-full object-cover group-hover:scale-110 transition duration-700 ease-in-out" alt="'.htmlspecialchars($p['name']).'">' 
                    : '<div class="w-full h-full flex items-center justify-center bg-gray-800/50"><span class="text-3xl opacity-50">🖼️</span></div>';

                $typeLabel = strtolower($p['type']) === 'digital' ? 'DIGITAL' : 'PHYSICAL PRODUCT';
                
                // Stock Status Logic
                if (strtolower($p['type']) === 'physical') {
                    $stockStatus = '<div class="text-[10px] md:text-xs inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-black/20 border border-white/5 '.($p['stock'] > 0 ? 'text-emerald-400' : 'text-rose-400').' font-medium"><span class="w-1.5 h-1.5 rounded-full '.($p['stock'] > 0 ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400').'"></span>'.($p['stock'] > 0 ? 'In Stock' : 'Out of Stock').'</div>';
                } else {
                    $stockStatus = '<div class="text-[10px] md:text-xs inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 font-medium"><span class="text-xs">⚡</span> Instant Delivery</div>';
                }

                return '
                <div class="bg-[#1e293b]/50 backdrop-blur-sm border border-white/5 rounded-2xl overflow-hidden hover:border-blue-500/50 transition-all duration-300 group flex flex-col relative hover:-translate-y-2 shadow-lg">
                    <a href="/product?id='.$p['id'].'" class="absolute inset-0 z-10"></a>
                    <div class="aspect-square bg-gray-800 relative overflow-hidden">
                        '.$imageHtml.'
                        <div class="absolute top-2 right-2 z-0">
                            <div class="bg-black/70 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded-md border border-white/10">'.$typeLabel.'</div>
                        </div>
                    </div>
                    <div class="p-3 md:p-4 flex flex-col flex-grow relative pointer-events-none">
                        <h3 class="text-gray-100 font-bold text-sm md:text-base truncate mb-1">'.htmlspecialchars($p['name']).'</h3>
                        <div class="mb-3">'.$stockStatus.'</div>
                        <div class="mt-auto flex items-center justify-between pointer-events-auto pt-2 border-t border-white/5">
                            <span class="text-yellow-400 font-bold">'.number_format($p['price']).' <span class="text-xs">Ks</span></span>
                            <button onclick="addToCart(this)" data-id="'.$p['id'].'" data-name="'.htmlspecialchars($p['name']).'" data-price="'.$p['price'].'" data-image="'.$p['image'].'" data-type="'.$p['type'].'" data-stock="'.$p['stock'].'" class="bg-blue-600 hover:bg-blue-500 text-white w-9 h-9 flex items-center justify-center rounded-xl z-20 relative">
                                <i class="fa-solid fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>';
            }
        ?>

        <!-- 2. PHYSICAL PRODUCTS SECTION -->
        <?php if(!empty($physical)): ?>
        <div>
            <div class="flex justify-between items-end border-b border-white/5 pb-4 mb-6">
                <h2 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                    Physical Goods <span class="text-xl md:text-2xl text-yellow-500"><i class="fa-solid fa-box-open"></i></span>
                </h2>
                <a href="/shop?cat=physical" class="text-blue-400 text-sm font-bold bg-blue-500/10 px-4 py-2 rounded-full">View All →</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                <?php foreach($physical as $p) { echo renderCard($p); } ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. DIGITAL PRODUCTS SECTION -->
        <?php if(!empty($digital)): ?>
        <div class="mt-12">
            <div class="flex justify-between items-end border-b border-white/5 pb-4 mb-6">
                <h2 class="text-2xl md:text-3xl font-bold text-white flex items-center gap-3">
                    Digital Downloads <span class="text-xl md:text-2xl text-cyan-400"><i class="fa-solid fa-cloud-arrow-down"></i></span>
                </h2>
                <a href="/shop?cat=digital" class="text-blue-400 text-sm font-bold bg-blue-500/10 px-4 py-2 rounded-full">View All →</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                <?php foreach($digital as $p) { echo renderCard($p); } ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if(empty($physical) && empty($digital)): ?>
            <div class="text-center py-12 text-gray-500">
                <p>No products found.</p>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <p class="text-xs mt-2 text-red-400">Admin Tip: Check if your database query allows `type='digital'` or items with `stock=0`.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- DEVELOPER CREDIT -->
        <div class="mt-12 text-center pb-8 border-t border-white/5 pt-8">
            <p class="text-[10px] text-gray-600 uppercase tracking-widest">
                Developed By 
                <a href="https://areativedigital.com/" target="_blank" class="text-gray-500 hover:text-blue-400 transition font-bold">Areative</a>
            </p>
        </div>

    </div>

</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 0,
        effect: "fade", 
        fadeEffect: { crossFade: true },
        centeredSlides: true,
        autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        loop: true,
    });
</script>