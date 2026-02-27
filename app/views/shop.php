<!-- Load FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="pb-24">
    
    <!-- 1. SEARCH & FILTER HEADER -->
    <div class="sticky top-0 z-30 bg-[#0f172a]/95 backdrop-blur-md py-4 border-b border-white/5 -mx-4 px-4 mb-6 -mt-4">
        <form id="filterForm" onsubmit="event.preventDefault(); fetchProducts();">
            
            <!-- Search Bar -->
            <div class="relative mb-4">
                <input type="text" id="searchInput" placeholder="Search products..." oninput="debounceSearch()"
                    class="w-full bg-gray-800 border border-gray-700 text-white placeholder-gray-500 rounded-xl py-3 pl-12 pr-4 outline-none focus:border-blue-500 transition shadow-lg">
                <button type="submit" class="absolute left-0 top-0 h-full w-12 flex items-center justify-center text-gray-400 hover:text-white transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>

            <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
                
                <!-- Category Select (UPDATED) -->
                <div class="relative min-w-[160px]">
                    <select id="catSelect" onchange="fetchProducts()" class="w-full appearance-none bg-gray-800 text-white text-sm rounded-lg pl-9 pr-8 py-2.5 border border-gray-700 outline-none focus:border-blue-500 cursor-pointer hover:bg-gray-700 transition">
                        <option value="">All Categories</option>
                        <optgroup label="Main Types">
                            <option value="physical">All Physical Items</option>
                            <option value="digital">All Digital Goods</option>
                        </optgroup>
                        
                        <!-- Dynamic Categories -->
                        <?php if(!empty($categories)): ?>
                            <optgroup label="Specific Categories">
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                    <i class="fa-solid fa-layer-group absolute left-3 top-3 text-gray-400 text-xs pointer-events-none"></i>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-3.5 text-gray-500 text-[10px] pointer-events-none"></i>
                </div>

                <!-- Sort Select -->
                <div class="relative min-w-[150px]">
                    <select id="sortSelect" onchange="fetchProducts()" class="w-full appearance-none bg-gray-800 text-white text-sm rounded-lg pl-9 pr-8 py-2.5 border border-gray-700 outline-none focus:border-blue-500 cursor-pointer hover:bg-gray-700 transition">
                        <option value="newest">Newest First</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="name_asc">Name: A-Z</option>
                    </select>
                    <i class="fa-solid fa-arrow-up-wide-short absolute left-3 top-3 text-gray-400 text-xs pointer-events-none"></i>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-3.5 text-gray-500 text-[10px] pointer-events-none"></i>
                </div>

                <!-- Reset Button -->
                <button type="button" onclick="resetFilters()" class="bg-red-500/10 text-red-400 text-sm rounded-lg px-4 py-2.5 border border-red-500/20 whitespace-nowrap hover:bg-red-500 hover:text-white transition flex items-center gap-2">
                    <i class="fa-solid fa-xmark"></i> Clear
                </button>
            </div>
        </form>
    </div>

    <!-- 2. PRODUCT GRID CONTAINER -->
    <div id="productGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <!-- Products will be injected here by JS -->
    </div>

    <!-- Loading State -->
    <div id="loading" class="hidden text-center py-20">
        <i class="fa-solid fa-circle-notch fa-spin text-3xl text-blue-500"></i>
    </div>

    <!-- No Results State -->
    <div id="noResults" class="hidden text-center py-20">
        <div class="bg-gray-800 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-magnifying-glass text-3xl text-gray-500"></i>
        </div>
        <h3 class="text-xl font-bold text-white">No products found</h3>
        <p class="text-gray-400 text-sm mt-2">Try adjusting your search or filters.</p>
    </div>

    <!-- DEVELOPER CREDIT -->
    <div class="mt-12 text-center pb-8 border-t border-white/5 pt-8">
        <p class="text-[10px] text-gray-600 uppercase tracking-widest">
            Developed By 
            <a href="https://areativedigital.com/" target="_blank" class="text-gray-500 hover:text-blue-400 transition font-bold">Areative</a>
        </p>
    </div>

</div>

<script>
    let debounceTimer;

    function debounceSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchProducts, 300); 
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('catSelect').value = '';
        document.getElementById('sortSelect').value = 'newest';
        fetchProducts();
    }

    async function fetchProducts() {
        const q = document.getElementById('searchInput').value;
        const cat = document.getElementById('catSelect').value;
        const sort = document.getElementById('sortSelect').value;
        
        const grid = document.getElementById('productGrid');
        const loading = document.getElementById('loading');
        const noResults = document.getElementById('noResults');

        grid.innerHTML = '';
        loading.classList.remove('hidden');
        noResults.classList.add('hidden');

        try {
            const response = await fetch(`/api/shop/search?q=${encodeURIComponent(q)}&cat=${encodeURIComponent(cat)}&sort=${encodeURIComponent(sort)}`);
            const data = await response.json();

            loading.classList.add('hidden');

            if (data.products.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            data.products.forEach(p => {
                const stockStatus = p.type === 'physical' 
                    ? `<div class="text-[10px] mb-2 ${p.stock > 0 ? 'text-green-400' : 'text-red-400'} flex items-center gap-1"><i class="fa-solid fa-circle text-[6px]"></i> ${p.stock > 0 ? 'In Stock: ' + p.stock : 'Out of Stock'}</div>`
                    : `<div class="text-[10px] mb-2 text-blue-400 flex items-center gap-1"><i class="fa-solid fa-bolt text-[8px]"></i> Instant Download</div>`;

                // --- CHANGE IS HERE: Updated label to PHYSICAL PRODUCT ---
                const typeBadge = p.type === 'digital' 
                    ? `<div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded flex items-center gap-1"><i class="fa-solid fa-download text-[9px] text-blue-400"></i> DIGITAL</div>`
                    : `<div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md text-white text-[10px] font-bold px-2 py-1 rounded flex items-center gap-1"><i class="fa-solid fa-box text-[9px] text-yellow-400"></i> PHYSICAL PRODUCT</div>`;

                // Uses global addToCart
                const actionBtn = (p.type === 'physical' && p.stock <= 0)
                    ? `<button disabled class="bg-gray-700 text-gray-500 p-2 rounded-lg cursor-not-allowed z-20 relative"><i class="fa-solid fa-ban"></i></button>`
                    : `<button onclick="event.preventDefault(); addToCart(this)" 
                        data-id="${p.id}" 
                        data-name="${p.name.replace(/"/g, '&quot;')}" 
                        data-price="${p.price}" 
                        data-image="${p.image}" 
                        data-type="${p.type}" 
                        data-stock="${p.stock}"
                        class="bg-blue-600 hover:bg-blue-500 text-white w-8 h-8 flex items-center justify-center rounded-lg shadow-lg shadow-blue-600/20 active:scale-90 transition z-20 relative"><i class="fa-solid fa-cart-plus"></i></button>`;

                const imageHtml = p.image 
                    ? `<img src="/${p.image}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">`
                    : `<div class="w-full h-full flex items-center justify-center text-gray-600 flex-col gap-2"><i class="fa-regular fa-image text-2xl"></i><span class="text-xs">No Image</span></div>`;

                // --- DISCOUNT LOGIC ---
                let priceHtml = '';
                let discountBadge = '';

                if (p.has_discount) {
                    priceHtml = `
                        <div class="flex flex-col leading-tight">
                            <span class="text-[10px] text-gray-400 line-through">${parseInt(p.original_price).toLocaleString()} Ks</span>
                            <span class="text-yellow-400 font-bold text-sm">${parseInt(p.price).toLocaleString()} Ks</span>
                        </div>
                    `;
                    discountBadge = `
                        <div class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded shadow-lg z-10 animate-pulse">
                            -${p.discount_percent}%
                        </div>
                    `;
                } else {
                    priceHtml = `<span class="text-yellow-400 font-bold text-sm">${parseInt(p.price).toLocaleString()} Ks</span>`;
                }

                const cardHtml = `
                    <div class="bg-white/5 border border-white/10 rounded-xl overflow-hidden hover:border-blue-500/50 transition group flex flex-col relative hover:-translate-y-1 hover:shadow-xl duration-300">
                        <a href="/product?id=${p.id}" class="absolute inset-0 z-10"></a>
                        <div class="aspect-square bg-gray-800 relative overflow-hidden">
                            ${imageHtml}
                            ${typeBadge}
                            ${discountBadge}
                        </div>
                        <div class="p-3 flex flex-col flex-grow relative pointer-events-none">
                            <h3 class="text-white font-bold text-sm truncate mb-1">${p.name}</h3>
                            ${stockStatus}
                            <div class="mt-auto flex items-center justify-between pointer-events-auto">
                                ${priceHtml}
                                ${actionBtn}
                            </div>
                        </div>
                    </div>
                `;
                grid.insertAdjacentHTML('beforeend', cardHtml);
            });

        } catch (error) {
            console.error('Error fetching products:', error);
            loading.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', fetchProducts);
</script>