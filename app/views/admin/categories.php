<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white">📂 Categories</h2>
    </div>

    <?php if(!empty($message)): ?>
        <div class="bg-blue-500/20 text-blue-400 p-3 rounded-lg mb-6 border border-blue-500/30"><?= $message ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Add Category Form -->
        <div class="glass-panel p-6 rounded-xl border border-white/10 h-fit">
            <h3 class="text-lg font-bold text-white mb-4">Add New Category</h3>
            <form method="POST">
                <input type="hidden" name="add_category" value="1">
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-2">Category Name</label>
                    <input type="text" name="name" required placeholder="e.g. Laptops, Cables" 
                        class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg transition shadow-lg">
                    Add Category
                </button>
            </form>
        </div>

        <!-- Category List -->
        <div class="md:col-span-2 glass-panel p-6 rounded-xl border border-white/10">
            <h3 class="text-lg font-bold text-white mb-4">Existing Categories</h3>
            
            <?php if(empty($categories)): ?>
                <div class="text-center text-gray-500 py-10">No categories found.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach($categories as $cat): ?>
                    <div class="bg-gray-800/50 p-4 rounded-lg border border-white/5 flex justify-between items-center group hover:border-blue-500/50 transition">
                        <span class="text-white font-medium"><?= htmlspecialchars($cat['name']) ?></span>
                        <form method="POST" onsubmit="return confirm('Are you sure? This might affect products linked to it.');">
                            <input type="hidden" name="delete_category" value="1">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-300 bg-red-500/10 hover:bg-red-500/20 p-2 rounded-lg transition">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>