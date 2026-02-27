<div class="p-6 pb-24">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white">📦 Product Management</h2>
    </div>

    <?php if(!empty($message)): ?>
        <div class="bg-green-500/20 text-green-400 p-3 rounded-lg mb-6 border border-green-500/30"><?= $message ?></div>
    <?php endif; ?>

    <!-- ADD/EDIT PRODUCT FORM -->
    <div class="glass-panel p-6 rounded-xl border border-white/10 mb-8" id="productFormContainer">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-white" id="formTitle">Add New Product</h3>
            <button type="button" onclick="resetForm()" id="cancelEditBtn" class="hidden text-sm text-red-400 hover:underline">Cancel Edit</button>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6" id="productForm">
            
            <!-- Hidden Inputs -->
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="product_id" id="productId" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Info -->
                <div class="md:col-span-2">
                    <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Product Name</label>
                    <input type="text" name="name" id="name" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                </div>

                <!-- Description Field -->
                <div class="md:col-span-2">
                    <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none" placeholder="Product details, specs, etc..."></textarea>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Type</label>
                        <select name="type" id="prodType" onchange="toggleFields()" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                            <option value="physical">Physical Item</option>
                            <option value="digital">Digital Product</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Status</label>
                        <select name="is_active" id="isActive" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                            <option value="1">Active</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Category</label>
                        <select name="category_id" id="category_id" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                            <option value="">-- None --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="text-right mt-1">
                            <a href="/admin/categories" class="text-blue-400 text-xs hover:underline">+ Manage Categories</a>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Stock -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Price (MMK)</label>
                        <input type="number" name="price" id="price" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                    </div>
                    <div id="stockField">
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Stock Qty</label>
                        <input type="number" name="stock" id="stock" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                    </div>
                </div>

                <!-- Colors & Warranty -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Available Colors</label>
                        <input type="text" name="colors" id="colors" placeholder="e.g. Red, Blue, Black" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Warranty</label>
                        <input type="text" name="warranty" id="warranty" placeholder="e.g. 1 Year" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                    </div>
                </div>

                <!-- Details (Barcode) -->
                <div class="grid grid-cols-2 gap-4">
                    <div id="barcodeField">
                        <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Barcode</label>
                        <input type="text" name="barcode" id="barcode" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                    </div>
                </div>

                <!-- Digital Link -->
                <div id="digitalField" class="hidden md:col-span-2">
                    <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Download Link / Key</label>
                    <input type="text" name="download_link" id="download_link" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                </div>

                <!-- MEDIA SECTION -->
                <div class="md:col-span-2 pt-4 border-t border-white/10">
                    <h4 class="text-white font-bold mb-4 flex items-center gap-2"><i class="fa-regular fa-images"></i> Media</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- 1. Main Thumbnail -->
                        <div>
                            <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Main Thumbnail</label>
                            <input type="file" name="image" id="mainImageInput" accept="image/*" class="w-full bg-gray-800 text-gray-400 text-sm rounded-lg border border-gray-700 cursor-pointer p-2 file:bg-blue-600 file:border-0 file:rounded file:text-white file:px-2 file:text-xs">
                            <div id="mainPreview" class="mt-3 w-full h-32 bg-gray-800/50 rounded-lg border border-gray-700 flex items-center justify-center overflow-hidden hidden">
                                <img src="" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <!-- 2. Gallery Images -->
                        <div>
                            <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Additional Images</label>
                            <input type="file" name="gallery[]" id="galleryInput" multiple accept="image/*" class="w-full bg-gray-800 text-gray-400 text-sm rounded-lg border border-gray-700 cursor-pointer p-2 file:bg-blue-600 file:border-0 file:rounded file:text-white file:px-2 file:text-xs">
                            <p class="text-[10px] text-gray-500 mt-1">Hold Ctrl to select multiple.</p>
                            <div id="galleryPreview" class="mt-3 grid grid-cols-4 gap-2 hidden"></div>
                        </div>
                        <!-- 3. Product Video -->
                        <div>
                            <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Product Video (Optional)</label>
                            <input type="file" name="video" id="videoInput" accept="video/mp4,video/webm" class="w-full bg-gray-800 text-gray-400 text-sm rounded-lg border border-gray-700 cursor-pointer p-2 file:bg-purple-600 file:border-0 file:rounded file:text-white file:px-2 file:text-xs">
                            <p class="text-[10px] text-gray-500 mt-1">Max size: 30MB.</p>
                            <div id="videoPreview" class="mt-3 w-full h-32 bg-gray-800/50 rounded-lg border border-gray-700 flex items-center justify-center overflow-hidden hidden relative">
                                <video src="" class="w-full h-full object-cover" controls></video>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" id="submitBtn" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition w-full md:w-auto">
                Save Product
            </button>
        </form>
    </div>

    <!-- NEW FEATURE: BULK CATEGORY DISABLE -->
    <div class="glass-panel p-6 rounded-xl border border-white/10 mb-8 bg-white/5">
        <h3 class="text-white font-bold mb-4 flex items-center gap-2 text-sm uppercase"><i class="fa-solid fa-bolt text-yellow-500"></i> Bulk Actions</h3>
        <!-- FIX: Action points to /admin/product/bulk-status -->
        <form action="/admin/product/bulk-status" method="POST" class="flex flex-col md:flex-row items-end gap-4" onsubmit="return confirm('Are you sure? This will update ALL products in the selected category.')">
            
            <div class="w-full md:w-1/3">
                <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Select Category</label>
                <select name="category_id" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                    <option value="">-- Choose Category --</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-full md:w-1/3">
                <label class="block text-gray-400 text-xs mb-2 uppercase font-bold">Action</label>
                <select name="status" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                    <option value="0">❌ Disable All Products</option>
                    <option value="1">✅ Enable All Products</option>
                </select>
            </div>

            <button type="submit" class="w-full md:w-auto bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg border border-white/10 transition">
                Apply Action
            </button>
        </form>
    </div>

    <!-- PRODUCT LIST TABLE -->
    <div class="glass-panel rounded-xl overflow-hidden border border-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 text-gray-400 text-xs uppercase border-b border-white/10">
                        <th class="p-4">Product</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Colors</th>
                        <th class="p-4">Price</th>
                        <th class="p-4">Stock</th>
                        <th class="p-4">Type</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-300 text-sm">
                    <?php foreach($products as $p): ?>
                    <?php 
                        // Ensure is_active is handled as integer 0 or 1
                        $isActive = isset($p['is_active']) ? (int)$p['is_active'] : 1; 
                    ?>
                    <tr class="border-b border-white/5 hover:bg-white/5 transition group <?= $isActive === 0 ? 'opacity-60 bg-red-500/5' : '' ?>">
                        <td class="p-4 flex items-center gap-3">
                            <?php if($p['image']): ?>
                                <img src="/<?= $p['image'] ?>" class="w-10 h-10 rounded object-cover bg-gray-800 ring-1 ring-white/10">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded bg-gray-800 flex items-center justify-center text-xs">N/A</div>
                            <?php endif; ?>
                            <div>
                                <div class="font-bold text-white"><?= htmlspecialchars($p['name']) ?></div>
                                <div class="text-[10px] text-gray-500"><?= htmlspecialchars($p['barcode'] ?? '') ?></div>
                            </div>
                        </td>
                        <td class="p-4">
                            <?php if($isActive === 1): ?>
                                <span class="bg-green-500/10 text-green-400 px-2 py-1 rounded text-[10px] uppercase font-bold border border-green-500/20">Active</span>
                            <?php else: ?>
                                <span class="bg-red-500/10 text-red-400 px-2 py-1 rounded text-[10px] uppercase font-bold border border-red-500/20">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4"><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                        <td class="p-4 text-xs max-w-[150px] truncate">
                            <?= !empty($p['colors']) ? htmlspecialchars($p['colors']) : '<span class="text-gray-600">-</span>' ?>
                        </td>
                        <td class="p-4 text-emerald-400 font-mono"><?= number_format($p['price']) ?></td>
                        <td class="p-4">
                            <?php if($p['type'] == 'physical'): ?>
                                <span class="<?= $p['stock'] < 5 ? 'text-red-400 font-bold' : 'text-gray-300' ?>"><?= $p['stock'] ?></span>
                            <?php else: ?>
                                <span class="text-blue-400">∞</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded text-[10px] uppercase font-bold <?= $p['type']=='physical'?'bg-purple-500/20 text-purple-400':'bg-cyan-500/20 text-cyan-400' ?>">
                                <?= $p['type'] ?>
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- TOGGLE STATUS BUTTON -->
                                <a href="/admin/product/toggle?id=<?= $p['id'] ?>" class="p-2 rounded-lg transition <?= $isActive === 1 ? 'text-green-400 hover:bg-green-500/20' : 'text-red-400 hover:bg-red-500/20' ?>" title="<?= $isActive === 1 ? 'Disable' : 'Enable' ?>">
                                    <i class="fa-solid fa-power-off"></i>
                                </a>

                                <button onclick='editProduct(<?= json_encode($p) ?>)' class="bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-500 hover:text-yellow-400 p-2 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <a href="/products?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product permanently?');" class="bg-red-500/10 hover:bg-red-500/20 text-red-500 hover:text-red-400 p-2 rounded-lg transition" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // --- 1. EDIT LOGIC (Populate Form) ---
    function editProduct(product) {
        document.getElementById('productFormContainer').scrollIntoView({ behavior: 'smooth' });

        document.getElementById('formTitle').innerText = "Edit Product: " + product.name;
        document.getElementById('submitBtn').innerText = "Update Product";
        document.getElementById('submitBtn').classList.replace('bg-blue-600', 'bg-yellow-600');
        document.getElementById('submitBtn').classList.replace('hover:bg-blue-500', 'hover:bg-yellow-500');
        document.getElementById('cancelEditBtn').classList.remove('hidden');

        document.getElementById('formAction').value = "update";
        document.getElementById('productId').value = product.id;

        document.getElementById('name').value = product.name;
        // Populate Description
        document.getElementById('description').value = product.description || '';
        document.getElementById('price').value = product.price;
        document.getElementById('stock').value = product.stock;
        document.getElementById('barcode').value = product.barcode || '';
        document.getElementById('colors').value = product.colors || '';
        document.getElementById('warranty').value = product.warranty_period || '';
        document.getElementById('download_link').value = product.download_link || '';
        
        document.getElementById('prodType').value = product.type;
        document.getElementById('category_id').value = product.category_id || '';
        document.getElementById('isActive').value = (product.is_active !== undefined) ? product.is_active : 1;

        // Main Image Preview
        const previewBox = document.getElementById('mainPreview');
        const previewImg = previewBox.querySelector('img');
        if (product.image) {
            previewImg.src = "/" + product.image;
            previewBox.classList.remove('hidden');
        } else {
            previewBox.classList.add('hidden');
        }

        // Reset other previews
        document.getElementById('galleryPreview').classList.add('hidden');
        document.getElementById('videoPreview').classList.add('hidden');

        toggleFields();
    }

    // --- 2. RESET FORM ---
    function resetForm() {
        document.getElementById('productForm').reset();
        document.getElementById('formTitle').innerText = "Add New Product";
        document.getElementById('submitBtn').innerText = "Save Product";
        document.getElementById('submitBtn').classList.replace('bg-yellow-600', 'bg-blue-600');
        document.getElementById('submitBtn').classList.replace('hover:bg-yellow-500', 'hover:bg-blue-500');
        document.getElementById('cancelEditBtn').classList.add('hidden');
        document.getElementById('formAction').value = "create";
        document.getElementById('productId').value = "";
        document.getElementById('isActive').value = "1"; // Default active
        document.getElementById('mainPreview').classList.add('hidden');
        document.getElementById('galleryPreview').classList.add('hidden');
        document.getElementById('videoPreview').classList.add('hidden');
        toggleFields();
    }

    // --- 3. TOGGLE FIELDS ---
    function toggleFields() {
        const type = document.getElementById('prodType').value;
        const stockField = document.getElementById('stockField');
        const barcodeField = document.getElementById('barcodeField');
        const digitalField = document.getElementById('digitalField');

        if(type === 'digital') {
            stockField.classList.add('opacity-50', 'pointer-events-none');
            barcodeField.classList.add('opacity-50', 'pointer-events-none');
            digitalField.classList.remove('hidden');
        } else {
            stockField.classList.remove('opacity-50', 'pointer-events-none');
            barcodeField.classList.remove('opacity-50', 'pointer-events-none');
            digitalField.classList.add('hidden');
        }
    }

    // --- 4. IMAGE PREVIEW (Main) ---
    document.getElementById('mainImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const previewBox = document.getElementById('mainPreview');
        const previewImg = previewBox.querySelector('img');
        if(file) {
            const reader = new FileReader();
            reader.onload = function(e) { previewImg.src = e.target.result; previewBox.classList.remove('hidden'); }
            reader.readAsDataURL(file);
        }
    });

    // --- 5. GALLERY IMAGES PREVIEW ---
    document.getElementById('galleryInput').addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        const container = document.getElementById('galleryPreview');
        container.innerHTML = '';
        if(files.length > 0) {
            container.classList.remove('hidden');
            files.slice(0, 5).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = "w-full aspect-square bg-gray-800 rounded border border-gray-700 overflow-hidden relative";
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    container.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }
    });

    // --- 6. VIDEO PREVIEW (New) ---
    document.getElementById('videoInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const container = document.getElementById('videoPreview');
        const video = container.querySelector('video');
        
        if (file) {
            if (file.size > 30 * 1024 * 1024) {
                alert("Video size exceeds 30MB limit!");
                this.value = ''; // Clear input
                container.classList.add('hidden');
                return;
            }
            video.src = URL.createObjectURL(file);
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    });
</script>