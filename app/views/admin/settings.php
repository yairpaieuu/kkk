<div class="p-4 pb-24">
    <h2 class="text-2xl font-bold text-white mb-6">⚙️ System Settings</h2>

    <?php if(!empty($message)): ?>
        <div class="bg-green-500/20 text-green-400 p-3 rounded-lg mb-4 border border-green-500/30"><?= $message ?></div>
    <?php endif; ?>

    <!-- 1. GENERAL SETTINGS -->
    <div class="glass-panel p-6 rounded-xl border border-white/10 mb-8">
        <form method="POST" action="/admin/settings" class="space-y-6">
            <div>
                <h3 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-2">General Info</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-400 text-sm">Site Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'KKK LED Shop') ?>" 
                            class="w-full bg-gray-800 border border-gray-700 text-white p-2 rounded-lg mt-1">
                    </div>
                    <div>
                        <label class="text-gray-400 text-sm">Phone Number (For Invoice)</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>" 
                            class="w-full bg-gray-800 border border-gray-700 text-white p-2 rounded-lg mt-1">
                    </div>
                    <div class="col-span-2">
                        <label class="text-gray-400 text-sm">Shop Address (For Invoice)</label>
                        <textarea name="address" rows="2" class="w-full bg-gray-800 border border-gray-700 text-white p-2 rounded-lg mt-1"><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit General -->
            <div class="pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition w-full md:w-auto">
                    Save General Settings
                </button>
            </div>
        </form>
    </div>

    <!-- 2. BANNER SETTINGS (NEW) -->
    <div class="glass-panel p-6 rounded-xl border border-white/10 mb-8">
        <h2 class="text-xl font-bold text-white mb-4">Homepage Banners</h2>
        
        <!-- Upload Form -->
        <form action="/admin/banner/add" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4 mb-6 items-end">
            <div class="flex-1 w-full">
                <label class="block text-gray-400 text-xs mb-2">Banner Image (Rec: 1200x600)</label>
                <input type="file" name="banner_image" required class="w-full bg-gray-800 text-white rounded p-2 border border-gray-600 text-sm">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-gray-400 text-xs mb-2">Link URL (Optional)</label>
                <input type="text" name="link_url" placeholder="/shop?cat=digital" class="w-full bg-gray-800 text-white rounded p-2 border border-gray-600 h-[38px] text-sm">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded h-[38px] font-bold text-sm">Add Banner</button>
        </form>

        <!-- Existing Banners List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (!empty($banners)): ?>
                <?php foreach ($banners as $b): ?>
                    <div class="relative group rounded-lg overflow-hidden border border-gray-700">
                        <img src="/<?= $b['image_path'] ?>" class="w-full h-32 object-cover">
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <form action="/admin/banner/delete" method="POST" onsubmit="return confirm('Delete this banner?');">
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm font-bold">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-2 text-center py-8 text-gray-500 bg-gray-800/30 rounded border border-gray-700 border-dashed">
                    No banners uploaded yet.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. SECURITY SETTINGS -->
    <div class="glass-panel p-6 rounded-xl border border-white/10">
        <form method="POST" action="/admin/settings" class="space-y-6">
            <div>
                <h3 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-2">Security</h3>
                <div>
                    <label class="text-gray-400 text-sm">Change Admin Password</label>
                    <input type="password" name="new_password" placeholder="Leave empty to keep current password" 
                        class="w-full bg-gray-800 border border-gray-700 text-white p-2 rounded-lg mt-1">
                    
                    <!-- Hidden inputs to preserve general settings if form submits both -->
                    <input type="hidden" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
                    <input type="hidden" name="address" value="<?= htmlspecialchars($settings['address'] ?? '') ?>">
                </div>
            </div>

            <!-- Submit Security -->
            <div class="pt-2">
                <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition w-full md:w-auto">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>