<div class="p-4 pb-24">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white">💸 Expenses Management</h2>
    </div>

    <?php if(!empty($message)): ?>
        <div class="bg-blue-600/20 text-blue-400 p-3 rounded-lg mb-6 border border-blue-500/30 flex items-center gap-2 animate-pulse">
            <i class="fa-solid fa-circle-info"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- OVERVIEW CARDS -->
    <?php 
        $currentMonthTotal = 0;
        $currentMonthCount = 0;
        $currentMonth = date('m');
        $currentYear = date('Y');

        foreach($expenses as $ex) {
            $eDate = strtotime($ex['date']);
            if(date('m', $eDate) == $currentMonth && date('Y', $eDate) == $currentYear) {
                $currentMonthTotal += $ex['amount'];
                $currentMonthCount++;
            }
        }
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="glass-panel p-5 rounded-xl border border-red-500/20 bg-red-500/5 relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-red-400 text-xs font-bold uppercase tracking-wider mb-1">This Month's Spending</p>
                <h3 class="text-2xl font-bold text-white font-mono"><?= number_format($currentMonthTotal) ?> <span class="text-sm text-gray-400">MMK</span></h3>
            </div>
            <i class="fa-solid fa-money-bill-transfer absolute -right-2 -bottom-2 text-6xl text-red-500/10"></i>
        </div>
        <div class="glass-panel p-5 rounded-xl border border-gray-600/20 bg-gray-700/10 relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Transactions This Month</p>
                <h3 class="text-2xl font-bold text-white"><?= number_format($currentMonthCount) ?></h3>
            </div>
            <i class="fa-solid fa-list-ol absolute -right-2 -bottom-2 text-6xl text-gray-500/10"></i>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 1. ADD EXPENSE FORM -->
        <div class="lg:col-span-1">
            <div class="glass-panel p-6 rounded-xl border border-white/10 sticky top-6">
                <h3 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-2">
                    <i class="fa-solid fa-plus-circle text-blue-400 mr-2"></i> Add New Expense
                </h3>
                
                <form method="POST" action="/admin/expenses" class="space-y-4">
                    <input type="hidden" name="add_expense" value="1">
                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Shop Rent" 
                            class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none transition">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-400 text-xs mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" required placeholder="0" min="0" 
                                class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none transition font-mono">
                        </div>
                        <div>
                            <label class="block text-gray-400 text-xs mb-1">Category</label>
                            <select name="category" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none appearance-none">
                                <option value="Operational">Operational</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Salary">Salary</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Date <span class="text-red-500">*</span></label>
                        <input type="date" name="date" required value="<?= date('Y-m-d') ?>" 
                            class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-gray-400 text-xs mb-1">Description</label>
                        <textarea name="description" rows="3" placeholder="Details..." 
                            class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none transition"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg shadow-lg transition transform hover:scale-[1.02]">
                        Save Expense
                    </button>
                </form>
            </div>
        </div>

        <!-- 2. EXPENSE HISTORY LIST -->
        <div class="lg:col-span-2">
            <div class="glass-panel rounded-xl border border-white/10 overflow-hidden shadow-2xl">
                <div class="bg-gray-800/80 p-4 border-b border-white/10 flex justify-between items-center">
                    <h3 class="text-white font-bold text-sm uppercase tracking-wider">History</h3>
                    <div class="text-xs text-gray-400">Sort by Date (Desc)</div>
                </div>
                
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left text-sm text-gray-400">
                        <thead class="bg-gray-800 text-gray-200 uppercase text-xs sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="p-4">Date</th>
                                <th class="p-4">Details</th>
                                <th class="p-4 text-right">Amount</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if(empty($expenses)): ?>
                                <tr>
                                    <td colspan="4" class="p-10 text-center text-gray-500">No expenses recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($expenses as $ex): ?>
                                <tr class="hover:bg-white/5 transition group">
                                    <td class="p-4 align-top whitespace-nowrap">
                                        <div class="text-white font-bold"><?= date('M d', strtotime($ex['date'])) ?></div>
                                        <div class="text-[10px] text-gray-500"><?= date('Y', strtotime($ex['date'])) ?></div>
                                    </td>
                                    
                                    <td class="p-4 align-top">
                                        <div class="text-white font-bold mb-1"><?= htmlspecialchars($ex['title']) ?></div>
                                        <span class="px-2 py-0.5 rounded text-[10px] uppercase border border-gray-600 bg-gray-700 text-gray-300">
                                            <?= htmlspecialchars($ex['category']) ?>
                                        </span>
                                        <?php if(!empty($ex['description'])): ?>
                                            <div class="text-xs text-gray-500 mt-1 italic max-w-[200px] break-words"><?= htmlspecialchars($ex['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-4 align-top text-right">
                                        <span class="text-red-400 font-bold font-mono group-hover:text-red-300">
                                            -<?= number_format($ex['amount']) ?>
                                        </span>
                                    </td>

                                    <!-- ACTIONS -->
                                    <td class="p-4 align-top text-right">
                                        <div class="flex justify-end gap-2">
                                            <!-- EDIT BTN -->
                                            <button onclick='openEditModal(<?= json_encode($ex) ?>)' 
                                                class="w-8 h-8 rounded bg-gray-700 hover:bg-blue-600 text-gray-300 hover:text-white transition flex items-center justify-center">
                                                <i class="fa-solid fa-pen text-xs"></i>
                                            </button>
                                            
                                            <!-- DELETE FORM -->
                                            <form method="POST" action="/admin/expenses" onsubmit="return confirm('Delete this expense?');">
                                                <input type="hidden" name="delete_expense" value="1">
                                                <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                                                <button type="submit" class="w-8 h-8 rounded bg-gray-700 hover:bg-red-600 text-gray-300 hover:text-white transition flex items-center justify-center">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div id="editModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="document.getElementById('editModal').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl">
        <div class="flex justify-between items-center mb-6 border-b border-white/10 pb-4">
            <h3 class="text-xl font-bold text-white">✏️ Edit Expense</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <form method="POST" action="/admin/expenses" class="space-y-4">
            <input type="hidden" name="edit_expense" value="1">
            <input type="hidden" name="id" id="edit_id">

            <div>
                <label class="block text-gray-400 text-xs mb-1">Title</label>
                <input type="text" name="title" id="edit_title" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-gray-400 text-xs mb-1">Amount</label>
                    <input type="number" name="amount" id="edit_amount" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none font-mono">
                </div>
                <div>
                    <label class="block text-gray-400 text-xs mb-1">Category</label>
                    <select name="category" id="edit_category" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
                        <option value="Operational">Operational</option>
                        <option value="Marketing">Marketing</option>
                        <option value="Salary">Salary</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-400 text-xs mb-1">Date</label>
                <input type="date" name="date" id="edit_date" required class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-gray-400 text-xs mb-1">Description</label>
                <textarea name="description" id="edit_description" rows="3" class="w-full bg-gray-800 border border-gray-700 text-white p-3 rounded-lg focus:border-blue-500 outline-none"></textarea>
            </div>

            <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-lg shadow-lg transition">Update Changes</button>
        </form>
    </div>
</div>

<script>
    function openEditModal(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_title').value = data.title;
        document.getElementById('edit_amount').value = data.amount;
        document.getElementById('edit_category').value = data.category;
        document.getElementById('edit_date').value = data.date;
        document.getElementById('edit_description').value = data.description;
        document.getElementById('editModal').classList.remove('hidden');
    }
</script>