<?php
/**
 * Child view: profile.php
 * Rendered inside layout.php via renderView().
 * Variables injected via extract(): $user, $orders
 */
?>

<!-- ===== PROFILE HERO STRIP ===== -->
<section class="bg-slate-50 border-b border-slate-100">
    <div class="max-w-5xl mx-auto px-4 py-10 flex flex-col sm:flex-row items-center gap-6">
        <!-- Avatar -->
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-600 to-violet-600 flex items-center justify-center text-3xl font-bold text-white shadow-md flex-shrink-0">
            <?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))) ?>
        </div>
        <!-- Identity -->
        <div class="text-center sm:text-left">
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($user['username']) ?></h1>
            <p class="text-slate-500 text-sm mt-0.5"><?= htmlspecialchars($user['email']) ?></p>
            <p class="text-slate-400 text-xs mt-1 flex items-center justify-center sm:justify-start gap-1">
                <i class="fa-regular fa-calendar-check text-blue-400"></i>
                Member since <?= htmlspecialchars(date('M Y', strtotime($user['created_at']))) ?>
            </p>
        </div>
    </div>
</section>

<!-- ===== MAIN CONTENT ===== -->
<div class="max-w-5xl mx-auto px-4 py-8 space-y-8">

    <!-- ===== USER INFO CARDS ===== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Phone -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex items-start gap-4">
            <span class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-phone text-blue-500 text-sm"></i>
            </span>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Phone</p>
                <p class="text-slate-900 font-medium text-sm">
                    <?php if (!empty($user['phone'])): ?>
                        <?= htmlspecialchars($user['phone']) ?>
                    <?php else: ?>
                        <span class="text-slate-400 italic">Not set</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <!-- Address -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex items-start gap-4">
            <span class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-location-dot text-blue-500 text-sm"></i>
            </span>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Address</p>
                <p class="text-slate-900 font-medium text-sm">
                    <?php if (!empty($user['address'])): ?>
                        <?= htmlspecialchars($user['address']) ?>
                    <?php else: ?>
                        <span class="text-slate-400 italic">Not set</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- ===== ORDER HISTORY ===== -->
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-5 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-blue-500"></i> Order History
        </h2>

        <?php if (empty($orders)): ?>
            <!-- Empty state -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-14 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-bag-shopping text-3xl text-slate-300"></i>
                </div>
                <h3 class="text-base font-semibold text-slate-900 mb-1">No orders yet</h3>
                <p class="text-slate-500 text-sm mb-6">Looks like you haven't bought anything yet.</p>
                <a href="/shop" class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2.5 rounded-full transition">
                    Start Shopping
                </a>
            </div>

        <?php else: ?>
            <div class="space-y-4">
                <?php
                $statusBadge = [
                    'pending'    => 'bg-amber-50 text-amber-700 border border-amber-200',
                    'approved'   => 'bg-blue-50 text-blue-700 border border-blue-200',
                    'preparing'  => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                    'delivering' => 'bg-purple-50 text-purple-700 border border-purple-200',
                    'completed'  => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                    'rejected'   => 'bg-rose-50 text-rose-700 border border-rose-200',
                ];
                ?>
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">

                        <!-- Order header: ID, date, status, total -->
                        <div class="flex flex-wrap justify-between items-start gap-4 pb-4 mb-4 border-b border-slate-100">
                            <div>
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="font-mono font-bold text-blue-600 text-base">
                                        #<?= htmlspecialchars(str_pad($order['id'], 5, '0', STR_PAD_LEFT)) ?>
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        <?= htmlspecialchars(date('M d, Y h:i A', strtotime($order['created_at']))) ?>
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 mt-1">
                                    Total:
                                    <span class="font-bold text-blue-600">
                                        <?= htmlspecialchars(number_format($order['total_amount'])) ?> MMK
                                    </span>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase <?= $statusBadge[$order['status']] ?? 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>

                        <!-- Digital Downloads -->
                        <?php if (in_array($order['status'], ['approved', 'completed']) && !empty($order['items'])): ?>
                            <?php
                            $hasDownloads = false;
                            foreach ($order['items'] as $item) {
                                if (($item['type'] ?? '') === 'digital' && !empty($item['download_link'])) {
                                    $hasDownloads = true;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($hasDownloads): ?>
                                <div class="mb-4">
                                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wide mb-2 flex items-center gap-2">
                                        <i class="fa-solid fa-download"></i> Digital Downloads
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <?php if (($item['type'] ?? '') === 'digital' && !empty($item['download_link'])): ?>
                                                <a href="<?= htmlspecialchars($item['download_link']) ?>" target="_blank"
                                                   class="flex justify-between items-center bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 p-3 rounded-lg transition group/dl">
                                                    <span class="text-sm text-slate-800 font-medium"><?= htmlspecialchars($item['name']) ?></span>
                                                    <i class="fa-solid fa-cloud-arrow-down text-emerald-600 group-hover/dl:scale-110 transition"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Documents & Proofs -->
                        <div class="flex flex-wrap gap-3">

                            <?php if (!empty($order['payment_receipt'])): ?>
                                <a href="/<?= htmlspecialchars($order['payment_receipt']) ?>" target="_blank"
                                   class="flex items-center gap-2 px-4 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 transition">
                                    <i class="fa-solid fa-receipt text-blue-500"></i> My Payment Slip
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($order['delivery_proof'])): ?>
                                <a href="/<?= htmlspecialchars($order['delivery_proof']) ?>" target="_blank"
                                   class="flex items-center gap-2 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-xs font-bold text-emerald-700 transition">
                                    <i class="fa-solid fa-box-open"></i> View Delivery Proof
                                </a>
                            <?php elseif ($order['status'] === 'completed'): ?>
                                <span class="flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-400 cursor-not-allowed">
                                    <i class="fa-solid fa-box"></i> No Delivery Photo
                                </span>
                            <?php endif; ?>

                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>