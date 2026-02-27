<div class="p-4 pb-24">
    <h2 class="text-2xl font-bold text-white mb-6">📦 Online Orders</h2>

    <div class="glass-panel overflow-hidden rounded-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-gray-400 text-sm">
                <thead class="bg-gray-800 text-white uppercase">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Customer</th>
                        <th class="p-4">Receipt</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach($orders as $o): ?>
                    <tr class="hover:bg-white/5">
                        <td class="p-4 text-blue-400 font-mono">#<?= $o['id'] ?></td>
                        
                        <td class="p-4">
                            <div class="text-white font-bold"><?= htmlspecialchars($o['customer_name'] ?? 'Guest') ?></div>
                            <div class="text-xs"><?= htmlspecialchars($o['customer_phone'] ?? '') ?></div>
                            <div class="text-xs italic opacity-70"><?= htmlspecialchars($o['customer_address'] ?? '') ?></div>
                        </td>

                        <td class="p-4">
                            <?php if($o['payment_receipt']): ?>
                                <a href="/<?= $o['payment_receipt'] ?>" target="_blank" class="text-blue-400 hover:underline flex items-center gap-1">
                                    <span>📄 View</span>
                                </a>
                            <?php else: ?>
                                <span class="text-gray-600">No Receipt</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-4">
                            <div class="text-white font-bold"><?= number_format($o['total_amount']) ?> Ks</div>
                            <div class="text-xs text-gray-500">Disc: -<?= number_format($o['discount_amount']) ?></div>
                        </td>

                        <td class="p-4">
                            <?php 
                                $colors = [
                                    'pending' => 'bg-yellow-500/20 text-yellow-500',
                                    'approved' => 'bg-green-500/20 text-green-500', // Intermediate state, rarely shown
                                    'preparing' => 'bg-blue-500/20 text-blue-500',
                                    'delivering' => 'bg-purple-500/20 text-purple-500',
                                    'delivered' => 'bg-green-500/20 text-green-500',
                                    'completed' => 'bg-green-500/20 text-green-500',
                                    'rejected' => 'bg-red-500/20 text-red-500',
                                ];
                                $cls = $colors[$o['status']] ?? 'bg-gray-700 text-gray-300';
                            ?>
                            <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $cls ?>">
                                <?= $o['status'] ?>
                            </span>
                        </td>

                        <td class="p-4">
                            <form action="/admin/order/update-status" method="POST" class="flex gap-2">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                
                                <?php if($o['status'] === 'pending'): ?>
                                    <button name="status" value="approved" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded text-xs">Approve</button>
                                    <button name="status" value="rejected" class="bg-red-600 hover:bg-red-500 text-white px-3 py-1 rounded text-xs">Reject</button>
                                
                                <?php elseif($o['status'] === 'preparing'): ?>
                                    <button name="status" value="delivering" class="bg-purple-600 hover:bg-purple-500 text-white px-3 py-1 rounded text-xs">Ship ➜</button>
                                
                                <?php elseif($o['status'] === 'delivering'): ?>
                                    <button name="status" value="delivered" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded text-xs">Complete</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>