<div class="p-4 pb-24">
    <h2 class="text-2xl font-bold text-white mb-6">🚚 Supplier Management</h2>

    <?php if(!empty($message)): ?>
        <?php $bgClass = strpos($message, 'Error') !== false ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400'; ?>
        <div class="<?= $bgClass ?> p-3 rounded-lg mb-4"><?= $message ?></div>
    <?php endif; ?>

    <!-- ADD FORM -->
    <div class="glass-panel p-6 rounded-xl mb-8 border border-white/10">
        <h3 class="text-lg font-bold text-white mb-4">Add New Supplier</h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="add_supplier" value="1">
            <input type="text" name="name" placeholder="Supplier Name" required class="bg-gray-800 border border-gray-700 text-white p-2 rounded-lg">
            <input type="text" name="phone" placeholder="Phone Number" required class="bg-gray-800 border border-gray-700 text-white p-2 rounded-lg">
            <input type="email" name="email" placeholder="Email" class="bg-gray-800 border border-gray-700 text-white p-2 rounded-lg">
            <input type="text" name="address" placeholder="Address" class="bg-gray-800 border border-gray-700 text-white p-2 rounded-lg">
            
            <button type="submit" class="col-span-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-lg transition">Save Supplier</button>
        </form>
    </div>

    <!-- LIST -->
    <div class="glass-panel rounded-xl overflow-hidden border border-white/10 shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-gray-400 text-sm">
                <thead class="bg-gray-800 text-gray-200 uppercase text-xs">
                    <tr>
                        <th class="p-4">Name</th>
                        <th class="p-4">Phone</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Address</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach($suppliers as $s): ?>
                    <tr class="hover:bg-white/5 transition">
                        <td class="p-4 text-white font-medium"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="p-4"><?= htmlspecialchars($s['phone']) ?></td>
                        <td class="p-4"><?= htmlspecialchars($s['email']) ?></td>
                        <td class="p-4 truncate max-w-xs"><?= htmlspecialchars($s['address']) ?></td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2">
                                <!-- Edit Button -->
                                <button onclick='openEditModal(<?= json_encode($s) ?>)' class="bg-blue-600/20 hover:bg-blue-600 text-blue-500 hover:text-white p-2 rounded-lg transition">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                
                                <!-- Delete Form -->
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                    <input type="hidden" name="delete_supplier" value="1">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="bg-red-600/20 hover:bg-red-600 text-red-500 hover:text-white p-2 rounded-lg transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($suppliers)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-500">No suppliers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editSupplierModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl">
        <div class="flex justify-between items-center mb-6 border-b border-white/10 pb-4">
            <h3 class="text-xl font-bold text-white">Edit Supplier</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="edit_supplier" value="1">
            <input type="hidden" name="id" id="editId">
            
            <div>
                <label class="block text-gray-400 text-xs mb-1">Name</label>
                <input type="text" name="name" id="editName" required class="w-full bg-gray-800 border border-gray-600 text-white p-2.5 rounded-lg focus:border-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-gray-400 text-xs mb-1">Phone</label>
                <input type="text" name="phone" id="editPhone" required class="w-full bg-gray-800 border border-gray-600 text-white p-2.5 rounded-lg focus:border-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-gray-400 text-xs mb-1">Email</label>
                <input type="email" name="email" id="editEmail" class="w-full bg-gray-800 border border-gray-600 text-white p-2.5 rounded-lg focus:border-blue-500 outline-none">
            </div>
            
            <div>
                <label class="block text-gray-400 text-xs mb-1">Address</label>
                <textarea name="address" id="editAddress" rows="2" class="w-full bg-gray-800 border border-gray-600 text-white p-2.5 rounded-lg focus:border-blue-500 outline-none"></textarea>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg font-bold">Cancel</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-2 rounded-lg font-bold shadow-lg">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(supplier) {
        document.getElementById('editId').value = supplier.id;
        document.getElementById('editName').value = supplier.name;
        document.getElementById('editPhone').value = supplier.phone;
        document.getElementById('editEmail').value = supplier.email || '';
        document.getElementById('editAddress').value = supplier.address || '';
        
        document.getElementById('editSupplierModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editSupplierModal').classList.add('hidden');
    }
</script>