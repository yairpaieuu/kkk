<?php
// Get current URL for sharing
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$shareText = urlencode("Check out " . $product['name'] . " on KKK LED SHOP!");
$shareUrl = urlencode($currentUrl);

// Helper to check if file is video
function isVideo($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - KKK LED SHOP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <meta property="og:title" content="<?= htmlspecialchars($product['name']) ?>" />
    <meta property="og:image" content="<?= $currentUrl ?>/../<?= $product['image'] ?>" />
    <meta property="og:description" content="<?= number_format($product['price']) ?> MMK" />
</head>
<body class="bg-[#0f172a] text-white font-sans min-h-screen">

    <!-- Navbar -->
    <nav class="bg-gray-900/80 backdrop-blur-md sticky top-0 z-50 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/shop" class="text-gray-400 hover:text-white flex items-center gap-2 transition">
                <i class="fa-solid fa-arrow-left"></i> Back to Shop
            </a>
            <a href="/cart" class="text-white hover:text-blue-400 relative">
                <i class="fa-solid fa-cart-shopping text-xl"></i>
                <span id="cartBadge" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center hidden">0</span>
            </a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-10">
        
        <!-- MAIN PRODUCT GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-16 mb-10">
            
            <!-- LEFT: MEDIA GALLERY -->
            <div class="space-y-4">
                <div id="mainMediaContainer" class="aspect-square bg-gray-800 rounded-2xl overflow-hidden border border-white/10 relative group flex items-center justify-center">
                    <!-- Media will be injected here by JS -->
                </div>

                <!-- Thumbnails -->
                <?php 
                    $allMedia = [];
                    if($product['image']) $allMedia[] = $product['image'];
                    if(!empty($product['gallery'])) $allMedia = array_merge($allMedia, $product['gallery']);
                ?>

                <?php if(count($allMedia) > 0): ?>
                <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
                    <?php foreach($allMedia as $index => $media): ?>
                        <?php $isVid = isVideo($media); ?>
                        <button onclick="renderMainMedia('/<?= $media ?>')" 
                                class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border border-white/10 hover:border-blue-500 transition relative">
                            <?php if($isVid): ?>
                                <video src="/<?= $media ?>" class="w-full h-full object-cover"></video>
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40"><i class="fa-solid fa-play text-white text-xs"></i></div>
                            <?php else: ?>
                                <img src="/<?= $media ?>" class="w-full h-full object-cover">
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: DETAILS -->
            <div>
                <div class="text-blue-400 text-sm font-bold uppercase tracking-wider mb-2">
                    <?= htmlspecialchars($product['category_name'] ?? 'General') ?>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-white mb-4"><?= htmlspecialchars($product['name']) ?></h1>
                
                <div class="flex items-center gap-4 mb-6">
                    <?php if(!empty($product['has_discount'])): ?>
                        <div class="flex flex-col">
                            <span class="text-gray-400 line-through text-lg"><?= number_format($product['original_price']) ?> MMK</span>
                            <div class="text-3xl font-mono text-yellow-400 font-bold">
                                <?= number_format($product['price']) ?> <span class="text-base text-gray-400">MMK</span>
                            </div>
                        </div>
                        <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-bold animate-pulse">-<?= $product['discount_percent'] ?>% OFF</span>
                    <?php else: ?>
                        <div class="text-3xl font-mono text-green-400 font-bold">
                            <?= number_format($product['price']) ?> <span class="text-base text-gray-400">MMK</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="h-px bg-white/10 w-full mb-6"></div>

                <!-- COLOR SELECTION -->
                <?php 
                    $colors = !empty($product['colors']) ? array_map('trim', explode(',', $product['colors'])) : [];
                ?>
                <?php if(!empty($colors) && $colors[0] !== ''): ?>
                <div class="mb-6">
                    <label class="block text-sm text-gray-400 mb-3">Select Color:</label>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach($colors as $index => $color): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="<?= $color ?>" class="peer sr-only" <?= $index===0 ? 'checked' : '' ?>>
                                <div class="px-4 py-2 bg-gray-800 border border-gray-600 rounded-lg peer-checked:bg-blue-600 peer-checked:border-blue-500 peer-checked:text-white text-gray-300 hover:bg-gray-700 transition capitalize">
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
                        <div class="bg-red-500/20 text-red-500 p-4 rounded-xl text-center font-bold border border-red-500/30">
                            🚫 Out of Stock
                        </div>
                    <?php else: ?>
                        <div class="flex gap-4">
                            <!-- Qty -->
                            <div class="flex items-center bg-gray-800 rounded-xl border border-white/10">
                                <button onclick="updateQty(-1)" class="px-4 py-3 text-gray-400 hover:text-white">-</button>
                                <input type="number" id="qty" value="1" min="1" max="10" class="w-12 bg-transparent text-center text-white outline-none font-bold" readonly>
                                <button onclick="updateQty(1)" class="px-4 py-3 text-gray-400 hover:text-white">+</button>
                            </div>

                            <!-- Add Button -->
                            <button onclick="addToCartDetailed()" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-600/20 transition transform active:scale-95 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-cart-plus"></i> Add to Cart
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- INFO LIST -->
                <div class="bg-white/5 rounded-xl p-5 border border-white/5 space-y-3 text-sm text-gray-300 mb-6">
                    <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="font-bold text-white uppercase"><?= $product['type'] ?></span></div>
                    <?php if(!empty($product['barcode'])): ?><div class="flex justify-between"><span class="text-gray-500">Barcode</span><span class="font-mono"><?= htmlspecialchars($product['barcode']) ?></span></div><?php endif; ?>
                    <?php if(!empty($product['warranty_period'])): ?><div class="flex justify-between"><span class="text-gray-500">Warranty</span><span class="text-yellow-400"><?= htmlspecialchars($product['warranty_period']) ?></span></div><?php endif; ?>
                </div>

                <!-- SOCIAL SHARE -->
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold mb-3 tracking-widest">Share this Product</p>
                    <div class="flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#1877F2] text-white flex items-center justify-center hover:scale-110 transition shadow-lg"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="https://t.me/share/url?url=<?= $shareUrl ?>&text=<?= $shareText ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#229ED9] text-white flex items-center justify-center hover:scale-110 transition shadow-lg"><i class="fa-brands fa-telegram"></i></a>
                        <a href="viber://forward?text=<?= $shareText ?>%20<?= $shareUrl ?>" target="_blank" class="w-10 h-10 rounded-full bg-[#7360f2] text-white flex items-center justify-center hover:scale-110 transition shadow-lg"><i class="fa-brands fa-viber"></i></a>
                        <button onclick="copyLink()" class="w-10 h-10 rounded-full bg-gray-700 text-gray-300 flex items-center justify-center hover:bg-gray-600 transition shadow-lg"><i class="fa-solid fa-link"></i></button>
                    </div>
                </div>

            </div>
        </div>

        <!-- NEW: FULL WIDTH DESCRIPTION BOX -->
        <?php if(!empty($product['description'])): ?>
        <div class="mb-16">
            <h3 class="text-white text-xl font-bold mb-4 flex items-center gap-2 border-b border-white/10 pb-2">
                <i class="fa-solid fa-align-left text-blue-400"></i> Description
            </h3>
            <div class="bg-gray-800/30 rounded-2xl p-6 border border-white/5 text-gray-300 text-base leading-relaxed whitespace-pre-wrap">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- SEPARATOR & RECOMMENDED -->
        <?php if(!empty($recommended)): ?>
        <div class="relative flex items-center py-10">
            <div class="flex-grow border-t border-white/10"></div>
            <span class="flex-shrink-0 mx-4 text-gray-500 text-sm uppercase tracking-widest">   
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-white mb-2">You Might Also Like</h2>
                </div>
            </span>
            <div class="flex-grow border-t border-white/10"></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach($recommended as $rec): ?>
            <a href="/product?id=<?= $rec['id'] ?>" class="group block bg-gray-800 rounded-xl overflow-hidden hover:shadow-xl hover:shadow-blue-500/10 transition transform hover:-translate-y-1 border border-white/5 relative">
                <span class="absolute inset-0 z-10"></span>
                <div class="aspect-square overflow-hidden relative bg-gray-900">
                    <?php if($rec['image']): ?>
                        <img src="/<?= htmlspecialchars($rec['image']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-600 text-xs">No Image</div>
                    <?php endif; ?>
                    
                    <?php if(!empty($rec['has_discount'])): ?>
                        <div class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg z-10">
                            -<?= $rec['discount_percent'] ?>%
                        </div>
                    <?php endif; ?>

                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded">
                        <?= $rec['type'] === 'digital' ? 'DIGITAL' : 'PHYSICAL' ?>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-white text-sm truncate mb-1"><?= htmlspecialchars($rec['name']) ?></h3>
                    <div class="flex justify-between items-center mt-2">
                        <?php if(!empty($rec['has_discount'])): ?>
                            <div class="flex flex-col leading-tight">
                                <span class="text-[10px] text-gray-400 line-through"><?= number_format($rec['original_price']) ?> Ks</span>
                                <span class="text-green-400 font-mono font-bold text-sm"><?= number_format($rec['price']) ?> Ks</span>
                            </div>
                        <?php else: ?>
                            <span class="text-green-400 font-mono font-bold text-sm"><?= number_format($rec['price']) ?> Ks</span>
                        <?php endif; ?>
                        
                        <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-white group-hover:bg-blue-600 transition">
                            <i class="fa-solid fa-arrow-right text-xs transform -rotate-45 group-hover:rotate-0 transition"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- 1. LIGHTBOX MODAL -->
    <div id="lightboxModal" class="fixed inset-0 z-[60] hidden bg-black/90 backdrop-blur-sm flex items-center justify-center p-4 cursor-zoom-out" onclick="closeLightbox()">
        <img id="lightboxImage" src="" class="max-w-full max-h-[95vh] rounded-lg shadow-2xl transition-transform duration-300">
        <button onclick="closeLightbox()" class="absolute top-5 right-5 text-white/50 hover:text-white transition p-2">
            <i class="fa-solid fa-xmark text-3xl"></i>
        </button>
    </div>

    <!-- TOAST -->
    <div id="toast" class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl transform translate-y-20 opacity-0 transition duration-300 z-50 font-bold flex items-center gap-2">
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

        const initialMedia = product.image ? '/' + product.image : null;
        if(initialMedia) renderMainMedia(initialMedia); else document.getElementById('mainMediaContainer').innerHTML = '<div class="text-gray-500">No Image</div>';

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