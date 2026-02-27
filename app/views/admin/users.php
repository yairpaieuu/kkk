<div class="p-6 pb-24">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-white flex items-center gap-2">
            🛡️ System Users
            <span class="bg-gray-800 text-gray-400 text-sm py-1 px-3 rounded-full"><?= count($users) ?></span>
        </h2>
        <button onclick="openUserModal()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-bold shadow-lg transition flex items-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Add New User
        </button>
    </div>

    <?php if(!empty($message)): ?>
        <?php $bgClass = strpos($message, 'Error') !== false ? 'bg-red-500/20 text-red-400 border-red-500/30' : 'bg-green-500/20 text-green-400 border-green-500/30'; ?>
        <div class="<?= $bgClass ?> border p-4 rounded-xl mb-6 flex items-center gap-3 animate-fade-in">
            <i class="fa-solid <?= strpos($message, 'Error') !== false ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="glass-panel rounded-xl overflow-hidden border border-white/10 shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-gray-400 text-sm">
                <thead class="bg-gray-800 text-gray-200 uppercase text-xs">
                    <tr>
                        <th class="p-4">User Info</th>
                        <th class="p-4">Role</th>
                        <th class="p-4">Created</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach($users as $u): ?>
                    <tr class="hover:bg-white/5 transition">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white
                                    <?= $u['role'] === 'admin' ? 'bg-red-500/20 text-red-500' : ($u['role'] === 'sales' ? 'bg-green-500/20 text-green-500' : 'bg-blue-500/20 text-blue-500') ?>">
                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-bold text-white"><?= htmlspecialchars($u['username']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <?php if($u['role'] === 'admin'): ?>
                                <span class="bg-red-500/10 text-red-400 border border-red-500/20 px-2 py-1 rounded text-[10px] uppercase font-bold tracking-wider">Admin</span>
                            <?php elseif($u['role'] === 'sales'): ?>
                                <span class="bg-green-500/10 text-green-400 border border-green-500/20 px-2 py-1 rounded text-[10px] uppercase font-bold tracking-wider">Sales Staff</span>
                            <?php else: ?>
                                <span class="bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-1 rounded text-[10px] uppercase font-bold tracking-wider">Account</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-xs font-mono"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button onclick='editUser(<?= json_encode($u) ?>)' class="bg-blue-600/20 hover:bg-blue-600 text-blue-500 hover:text-white p-2 rounded-lg transition">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Delete user <?= $u['username'] ?>?');" class="inline">
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="bg-red-600/20 hover:bg-red-600 text-red-500 hover:text-white p-2 rounded-lg transition">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- USER MODAL -->
<div id="userModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeUserModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-6 bg-[#1e293b] rounded-2xl border border-white/10 shadow-2xl">
        <div class="flex justify-between items-center mb-6 border-b border-white/10 pb-4">
            <h3 class="text-xl font-bold text-white" id="modalTitle">Add User</h3>
            <button onclick="closeUserModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="add_user" id="actionInput" value="1">
            <input type="hidden" name="id" id="userId">
            
            <div>
                <label class="block text-gray-400 text-xs font-bold mb-1 uppercase">Username</label>
                <input type="text" name="username" id="username" required class="w-full bg-gray-800 border border-gray-600 text-white p-3 rounded-xl focus:border-blue-500 outline-none transition">
            </div>

            <!-- ADDED EMAIL BOX HERE -->
            <div>
                <label class="block text-gray-400 text-xs font-bold mb-1 uppercase">Email Address</label>
                <input type="email" name="email" id="email" required class="w-full bg-gray-800 border border-gray-600 text-white p-3 rounded-xl focus:border-blue-500 outline-none transition">
            </div>
            
            <div>
                <label class="block text-gray-400 text-xs font-bold mb-1 uppercase">Role</label>
                <select name="role" id="role" class="w-full bg-gray-800 border border-gray-600 text-white p-3 rounded-xl focus:border-blue-500 outline-none transition cursor-pointer">
                    <option value="admin">Admin (Full Access)</option>
                    <option value="sales">Sales (POS, Orders)</option>
                    <option value="account">Account (Products, Reports)</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-400 text-xs font-bold mb-1 uppercase">Password</label>
                <input type="password" name="password" id="password" class="w-full bg-gray-800 border border-gray-600 text-white p-3 rounded-xl focus:border-blue-500 outline-none transition">
                <p class="text-[10px] text-gray-500 mt-1 hidden" id="passHint">Leave blank to keep current password</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl shadow-lg transition mt-2">Save User</button>
        </form>
    </div>
</div>

<script>
    function openUserModal() {
        document.getElementById('modalTitle').innerText = 'Add New User';
        document.getElementById('actionInput').name = 'add_user';
        document.getElementById('userId').value = '';
        
        // Reset fields
        document.getElementById('username').value = '';
        document.getElementById('email').value = ''; // Reset Email
        document.getElementById('role').value = 'sales';
        
        document.getElementById('password').required = true;
        document.getElementById('passHint').classList.add('hidden');
        
        document.getElementById('userModal').classList.remove('hidden');
    }

    function editUser(user) {
        document.getElementById('modalTitle').innerText = 'Edit User';
        document.getElementById('actionInput').name = 'edit_user';
        document.getElementById('userId').value = user.id;
        
        // Populate fields
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email; // Populate Email
        document.getElementById('role').value = user.role;
        
        // Password optional when editing
        document.getElementById('password').required = false;
        document.getElementById('passHint').classList.remove('hidden');
        
        document.getElementById('userModal').classList.remove('hidden');
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.add('hidden');
    }
</script>