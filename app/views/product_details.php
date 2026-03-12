<?php
// Get current URL for sharing
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$shareText = urlencode("Check out " . $product['name'] . " on Areative Shop!");
$shareUrl = urlencode($currentUrl);

// Helper to check if file is video
function isVideo($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}
// Returns a valid img src for both local uploads paths and external URLs
if (!function_exists('imgSrc')) {
    function imgSrc(?string $path): string {
        if (empty($path)) return '';
        return (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))
            ? htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE)
            : '/' . htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Areative Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <meta property="og:title" content="<?= htmlspecialchars($product['name']) ?>" />
    <meta property="og:image" content="<?= htmlspecialchars(!empty($product['image']) ? imgSrc($product['image']) : '') ?>" />
    <meta property="og:description" content="<?= number_format($product['price']) ?> MMK" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <a href="/shop" class="flex items-center gap-2 text-slate-600 hover:text-blue-600 font-medium transition text-sm">
                <i class="fa-solid fa-arrow-left text-xs"></i> Back to Shop
            </a>
            <a href="/" class="text-xl font-black text-blue-600 tracking-tight">Areative Shop</a>
            <a href="/cart" class="text-slate-600 hover:text-blue-600 relative transition">
                <i class="fa-solid fa-cart-shopping text-xl"></i>
                <span id="cartBadge" class="absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center hidden">0</span>
            </a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-10">
        
        <!-- MAIN PRODUCT GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-16 mb-10">
            
            <!-- LEFT: MEDIA GALLERY -->
            <div class="space-y-4">
                <div id="mainMediaContainer" class="aspect-square bg-slate-100 rounded-2xl overflow-hidden border border-slate-200 shadow-sm relative group flex items-center justify-center">
                    <!-- Media will be injected here by JS -->
                </div>

                <!-- Thumbnails -->
                <?php 
                    $allMedia = [];
                    if($product['image']) $allMedia[] = $product['image'];
                    if(!empty($product['gallery'])) $allMedia = array_merge($allMedia, $product['gallery']);
                ?>

                <?php if(count($allMedia) > 0): ?>
                <div class="flex gap-3 overflow-x-auto pb-2 no-scrollbar">
                    <?php foreach($allMedia as $index => $media): ?>
                        <?php $isVid = isVideo($media); ?>
                        <button onclick="renderMainMedia('<?= imgSrc($media) ?>')" 
                                class="w-20 h-20 flex-shrink-0 rounded-xl overflow-hidden border-2 border-slate-200 hover:border-blue-500 transition relative">
                            <?php if($isVid): ?>
                                <video src="<?= imgSrc($media) ?>" class="w-full h-full object-cover"></video>
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30"><i class="fa-solid fa-play text-white text-xs"></i></div>
                            <?php else: ?>
                                <img src="<?= imgSrc($media) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: DETAILS -->
            <div>
                <div class="text-blue-600 text-xs font-bold uppercase tracking-widest mb-2">
                    <?= htmlspecialchars($product['category_name'] ?? 'General') ?>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4 leading-tight"><?= htmlspecialchars($product['name']) ?></h1>
                
                <div class="flex items-center gap-4 mb-6">
                    <?php if(!empty($product['has_discount'])): ?>
                        <div class="flex flex-col">
                            <span class="text-slate-400 line-through text-base"><?= number_format($product['original_price']) ?> MMK</span>
                            <div class="text-3xl font-mono text-blue-600 font-bold">
                                <?= number_format($product['price']) ?> <span class="text-base text-slate-400">MMK</span>
                            </div>
                        </div>
                        <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold">-<?= $product['discount_percent'] ?>% OFF</span>
                    <?php else: ?>
                        <div class="text-3xl font-mono text-blue-600 font-bold">
                            <?= number_format($product['price']) ?> <span class="text-base text-slate-400">MMK</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="h-px bg-slate-200 w-full mb-6"></div>

                <!-- COLOR SELECTION -->
                <?php 
                    $colors = !empty($product['colors']) ? array_map('trim', explode(',', $product['colors'])) : [];
                ?>
                <?php if(!empty($colors) && $colors[0] !== ''): ?>
                <div class="mb-6">
                    <label class="block text-sm text-slate-500 font-medium mb-3">Select Color:</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($colors as $index => $color): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="<?= $color ?>" class="peer sr-only" <?= $index===0 ? 'checked' : '' ?>>
                                <div class="px-4 py-2 bg-white border border-slate-200 rounded-lg peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white text-slate-700 hover:border-blue-400 transition capitalize text-sm font-medium shadow-sm">
                                    <?= htmlspecialchars($color) ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ACTIONS -->
                <div class="mb-8">
                    <?php if($product['type'] == 'physical' && $product['stock'] <= 0): ?>
                        <div class="bg-red-50 text-red-600 p-4 rounded-xl text-center font-bold border border-red-200">
                            🚫 Out of Stock
                        </div>
                    <?php else: ?>
                        <div class="flex gap-3">
                            <!-- Qty -->
                            <div class="flex items-center bg-white rounded-xl border border-slate-200 shadow-sm">
                                <button onclick="updateQty(-1)" class="px-4 py-3 text-slate-400 hover:text-slate-700 transition">−</button>
                                <input type="number" id="qty" value="1" min="1" max="10" class="w-12 bg-transparent text-center text-slate-800 outline-none font-bold" readonly>
                                <button onclick="updateQty(1)" class="px-4 py-3 text-slate-400 hover:text-slate-700 transition">+</button>
                            </div>

                            <!-- Add Button -->
                            <button onclick="addToCartDetailed()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-md transition transform active:scale-95 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- INFO LIST -->
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm space-y-3 text-sm text-slate-600 mb-6">
                    <div class="flex justify-between items-center"><span class="text-slate-400 font-medium">Type</span><span class="font-bold text-slate-800 uppercase bg-slate-100 px-2 py-0.5 rounded text-xs"><?= $product['type'] ?></span></div>
                    <?php if(!empty($product['barcode'])): ?><div class="flex justify-between items-center"><span class="text-slate-400 font-medium">Barcode</span><span class="font-mono text-slate-700"><?= htmlspecialchars($product['barcode']) ?></span></div><?php endif; ?>
                    <?php if(!empty($product['warranty_period'])): ?><div class="flex justify-between items-center"><span class="text-slate-400 font-medium">Warranty</span><span class="text-blue-600 font-semibold"><?= htmlspecialchars($product['warranty_period']) ?></span></div><?php endif; ?>
                    <?php if($product['type'] === 'physical'): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400 font-medium">Availability</span>
                        <?php if((int)$product['stock'] > 0): ?>
                            <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span>In Stock</span>
                        <?php else: ?>
                            <span class="text-rose-500 font-semibold text-xs">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- SOCIAL SHARE -->
                <div>
                    <p class="text-xs text-slate-400 uppercase font-bold mb-3 tracking-widest">Share this Product</p>
                    <div class="flex gap-2">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center hover:scale-110 transition shadow-md"><i class="fa-brands fa-facebook-f text-sm"></i></a>
                        <a href="https://t.me/share/url?url=<?= $shareUrl ?>&text=<?= $shareText ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#229ED9] text-white flex items-center justify-center hover:scale-110 transition shadow-md"><i class="fa-brands fa-telegram text-sm"></i></a>
                        <a href="viber://forward?text=<?= $shareText ?>%20<?= $shareUrl ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#7360f2] text-white flex items-center justify-center hover:scale-110 transition shadow-md"><i class="fa-brands fa-viber text-sm"></i></a>
                        <button onclick="copyLink()" class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-200 transition shadow-md"><i class="fa-solid fa-link text-sm"></i></button>
                    </div>
                </div>

            </div>
        </div>

        <!-- FULL WIDTH DESCRIPTION BOX -->
        <?php if(!empty($product['description'])): ?>
        <div class="mb-16">
            <h3 class="text-slate-800 text-xl font-bold mb-4 flex items-center gap-2 border-b border-slate-200 pb-3">
                <i class="fa-solid fa-align-left text-blue-500"></i> Description
            </h3>
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm text-slate-600 text-base leading-relaxed whitespace-pre-wrap">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- SEPARATOR & RECOMMENDED -->
        <?php if(!empty($recommended)): ?>
        <div class="flex items-center gap-4 mb-8">
            <div class="flex-grow h-px bg-slate-200"></div>
            <h2 class="text-xl font-bold text-slate-700 whitespace-nowrap">You Might Also Like</h2>
            <div class="flex-grow h-px bg-slate-200"></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            <?php foreach($recommended as $rec): ?>
            <a href="/product?id=<?= $rec['id'] ?>" class="group block bg-white rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 border border-slate-200 relative hover:-translate-y-1">
                <span class="absolute inset-0 z-10"></span>
                <div class="aspect-square overflow-hidden relative bg-slate-100">
                    <?php if($rec['image']): ?>
                        <img src="<?= imgSrc($rec['image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" decoding="async">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-regular fa-image text-3xl"></i></div>
                    <?php endif; ?>
                    
                    <?php if(!empty($rec['has_discount'])): ?>
                        <div class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow z-10">
                            -<?= $rec['discount_percent'] ?>%
                        </div>
                    <?php endif; ?>

                    <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm text-slate-600 text-[9px] font-bold px-2 py-0.5 rounded-full">
                        <?= $rec['type'] === 'digital' ? 'DIGITAL' : 'PHYSICAL' ?>
                    </div>
                </div>
                <div class="p-3">
                    <h3 class="font-semibold text-slate-800 text-sm truncate mb-1"><?= htmlspecialchars($rec['name']) ?></h3>
                    <div class="flex justify-between items-center">
                        <?php if(!empty($rec['has_discount'])): ?>
                            <div class="flex flex-col leading-tight">
                                <span class="text-[10px] text-slate-400 line-through"><?= number_format($rec['original_price']) ?> Ks</span>
                                <span class="text-blue-600 font-mono font-bold text-sm"><?= number_format($rec['price']) ?> Ks</span>
                            </div>
                        <?php else: ?>
                            <span class="text-blue-600 font-mono font-bold text-sm"><?= number_format($rec['price']) ?> Ks</span>
                        <?php endif; ?>
                        
                        <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-blue-600 group-hover:text-white transition">
                            <i class="fa-solid fa-arrow-right text-xs transform group-hover:-rotate-45 transition"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- LIGHTBOX MODAL -->
    <div id="lightboxModal" class="fixed inset-0 z-[60] hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 cursor-zoom-out" onclick="closeLightbox()">
        <img id="lightboxImage" src="" class="max-w-full max-h-[95vh] rounded-2xl shadow-2xl transition-transform duration-300">
        <button onclick="closeLightbox()" class="absolute top-5 right-5 text-white/70 hover:text-white transition p-2">
            <i class="fa-solid fa-xmark text-3xl"></i>
        </button>
    </div>

    <!-- TOAST -->
    <div id="toast" class="fixed bottom-5 right-5 bg-blue-600 text-white px-6 py-3 rounded-xl shadow-xl transform translate-y-20 opacity-0 transition duration-300 z-50 font-bold flex items-center gap-2">
        <i class="fa-solid fa-check-circle"></i> <span>Added to Cart!</span>
    </div>

    <script>
        const product = <?= json_encode($product) ?>;
        const CART_KEY = 'cart'; // Use 'cart' to match global standard
        
        function renderMainMedia(src) {
            const container = document.getElementById('mainMediaContainer');
            container.innerHTML = ''; 
            const ext = src.split('.').pop().toLowerCase();
            const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(ext);
            if (isVideo) {
                const video = document.createElement('video');
                video.src = src; video.controls = true; video.autoplay = true; video.className = "w-full h-full object-contain"; 
                container.appendChild(video);
            } else {
                const img = document.createElement('img');
                img.src = src; img.className = "w-full h-full object-cover cursor-zoom-in";
                img.onclick = () => openLightbox(src);
                container.appendChild(img);
            }
        }

        function openLightbox(src) { document.getElementById('lightboxImage').src = src; document.getElementById('lightboxModal').classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
        function closeLightbox() { document.getElementById('lightboxModal').classList.add('hidden'); document.getElementById('lightboxImage').src = ''; document.body.style.overflow = ''; }

        const initialMedia = product.image ? ((/^https?:\/\//.test(product.image)) ? product.image : '/' + product.image) : null;
        if(initialMedia) renderMainMedia(initialMedia); else document.getElementById('mainMediaContainer').innerHTML = '<div class="w-full h-full flex items-center justify-center text-slate-300"><i class=\"fa-regular fa-image text-5xl\"></i></div>';

        function updateQty(change) {
            const input = document.getElementById('qty');
            let val = parseInt(input.value) + change;
            if (val < 1) val = 1; if (val > 10) val = 10;
            input.value = val;
        }

        // --- ADD TO CART LOGIC ---
        function addToCartDetailed() {
            const colorInput = document.querySelector('input[name="color"]:checked');
            const selectedColor = colorInput ? colorInput.value : null;
            const quantity = parseInt(document.getElementById('qty').value);

            let cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
            
            // Check for existing item with same ID
            // Note: If you want to distinguish by color, you need to update cart.php logic too
            // For now, simpler matching by ID to keep consistent with existing simple cart logic
            const existing = cart.find(item => item.id == product.id);

            if (existing) {
                existing.qty += quantity;
            } else {
                cart.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    image: product.image,
                    type: product.type,
                    qty: quantity,
                    stock: product.stock // Added for validation in cart
                });
            }

            localStorage.setItem(CART_KEY, JSON.stringify(cart));
            
            // Update Global Badge if function exists
            if(window.updateCartCount) window.updateCartCount();
            else {
                // Fallback local badge update
                const count = cart.reduce((sum, item) => sum + item.qty, 0);
                const badge = document.getElementById('cartBadge');
                if(badge) { badge.innerText = count; badge.classList.remove('hidden'); }
            }

            showToast('Added to Cart!');
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.querySelector('span').innerText = msg;
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => toast.classList.add('translate-y-20', 'opacity-0'), 3000);
        }

        function copyLink() { navigator.clipboard.writeText(window.location.href); showToast('Link Copied!'); }

        // Initial badge update
        if(window.updateCartCount) window.updateCartCount();
        else {
             // Fallback on load
             let c = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
             const count = c.reduce((sum, item) => sum + item.qty, 0);
             const badge = document.getElementById('cartBadge');
             if(badge) { badge.innerText = count; if(count > 0) badge.classList.remove('hidden'); }
        }
    </script>
</body>
</html>