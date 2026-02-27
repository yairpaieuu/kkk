<div class="flex flex-col h-[calc(100vh-80px)]">
    
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Manage Purchases</h1>
            <p class="text-gray-400 text-sm">View, edit, or delete purchase records</p>
        </div>
        <a href="/purchase" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20 transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> New Purchase
        </a>
    </div>

    <!-- TABLE CONTAINER -->
    <div class="bg-[#1e293b] border border-gray-700 rounded-2xl shadow-xl overflow-hidden flex-1 flex flex-col">
        <div class="overflow-y-auto custom-scrollbar flex-1">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-800/50 sticky top-0 z-10 backdrop-blur-md">
                    <tr class="text-xs text-gray-400 uppercase border-b border-gray-700">
                        <th class="px-6 py-4 font-bold">ID</th>
                        <th class="px-6 py-4 font-bold">Date</th>
                        <th class="px-6 py-4 font-bold">Supplier</th>
                        <th class="px-6 py-4 font-bold">Product</th>
                        <th class="px-6 py-4 font-bold text-center">Qty</th>
                        <th class="px-6 py-4 font-bold text-right">Total Cost</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-300 divide-y divide-gray-700/50">
                    <?php foreach($purchases as $p): ?>
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-4 text-gray-500">#<?= $p['id'] ?></td>
                        <td class="px-6 py-4"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-900/30 text-blue-400 px-2 py-1 rounded text-xs border border-blue-800/50">
                                <?= htmlspecialchars($p['supplier_name']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-medium text-white"><?= htmlspecialchars($p['product_name']) ?></td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-gray-700 text-white px-2 py-1 rounded text-xs font-mono"><?= $p['quantity'] ?></span>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-emerald-400">
                            <?= number_format($p['total_amount']) ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick='openEditModal(<?= json_encode($p) ?>)' class="w-8 h-8 rounded-lg bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white flex items-center justify-center transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <?php if($_SESSION['user_role'] === 'admin'): ?>
                                <form method="POST" action="/admin/purchases/delete" onsubmit="return confirm('Are you sure? This will reduce stock quantity back!')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white flex items-center justify-center transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($purchases)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fa-solid fa-box-open text-4xl mb-3 opacity-50"></i>
                            <p>No purchase records found.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="fixed inset-0 bg-black/80 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <form action="/admin/purchases/edit" method="POST" class="bg-[#1e293b] w-full max-w-lg rounded-2xl border border-gray-700 shadow-2xl scale-95 transition-transform duration-200" id="editModalContent">
        
        <div class="flex justify-between items-center p-6 border-b border-gray-700">
            <h3 class="text-xl font-bold text-white">Edit Purchase</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <input type="hidden" name="purchase_id" id="edit_purchase_id">
            <input type="hidden" name="item_id" id="edit_item_id">

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Product (Read Only)</label>
                <input type="text" id="edit_product_name" readonly class="w-full bg-gray-800 border border-gray-600 text-gray-400 rounded-xl px-4 py-3 cursor-not-allowed">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Supplier</label>
                <select name="supplier_id" id="edit_supplier_id" class="w-full bg-[#0f172a] border border-gray-600 text-white rounded-xl px-4 py-3 focus:border-blue-500 focus:outline-none">
                    <?php foreach($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Quantity</label>
                    <input type="number" name="quantity" id="edit_quantity" min="1" class="w-full bg-[#0f172a] border border-gray-600 text-white rounded-xl px-4 py-3 focus:border-blue-500 focus:outline-none">
                    <p class="text-[10px] text-yellow-500 mt-1">* Updates current stock</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Total Cost</label>
                    <input type="number" name="cost" id="edit_cost" min="0" step="0.01" class="w-full bg-[#0f172a] border border-gray-600 text-white rounded-xl px-4 py-3 focus:border-blue-500 focus:outline-none">
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-700 bg-gray-800/50 rounded-b-2xl flex justify-end gap-3">
            <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 rounded-xl text-gray-300 hover:text-white hover:bg-white/5 transition">Cancel</button>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/20 transition">Save Changes</button>
        </div>
    </form>
</div>

<!-- SCRIPT -->
<script>
    function openEditModal(data) {
        document.getElementById('edit_purchase_id').value = data.id;
        document.getElementById('edit_item_id').value = data.item_id;
        document.getElementById('edit_product_name').value = data.product_name;
        document.getElementById('edit_supplier_id').value = data.supplier_id; // Requires supplier_id in data query (I will add update to controller)
        document.getElementById('edit_quantity').value = data.quantity;
        document.getElementById('edit_cost').value = data.total_amount; // Assuming 1 line item per purchase ID based on previous structure

        // Note: Controller SQL needs 'supplier_id' in select to make dropdown auto-select work perfectly.
        // The previous controller code handles joining, but make sure `p.supplier_id` is selected.
        // It is currently: SELECT p.id ... 
        // I'll ensure the controller code above includes p.supplier_id just in case (it's implicit in p.* but explicitly fetching helps).
        // Actually the provided controller code select specific fields. 
        // FIX: Update Controller SQL to include `p.supplier_id`. 
        
        const modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            document.getElementById('editModalContent').classList.remove('scale-95');
            document.getElementById('editModalContent').classList.add('scale-100');
        }, 10);
    }

    function closeEditModal() {
        const modalContent = document.getElementById('editModalContent');
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            document.getElementById('editModal').classList.add('hidden');
        }, 200);
    }
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #1e293b; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
</style>