<div class="p-4 pb-24">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-white">🏗️ Visual Page Builder</h2>
            <p class="text-gray-400 text-sm mt-1">Drag sections to reorder, edit content, and toggle visibility for each page.</p>
        </div>
        <button id="saveAllBtn" onclick="saveAllChanges()"
            class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded-lg shadow-lg transition flex items-center gap-2 self-start md:self-auto">
            <i class="fa-solid fa-floppy-disk"></i>
            <span id="saveBtnLabel">Save All Changes</span>
        </button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="bg-green-500/20 text-green-400 p-3 rounded-lg mb-5 border border-green-500/30 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Page Tabs -->
    <div class="flex gap-1 border-b border-white/10 mb-6" id="pageTabs">
        <button class="page-tab active" data-page="home">🏠 Home Page</button>
        <button class="page-tab" data-page="shop">🛒 Shop Page</button>
        <button class="page-tab" data-page="contact">📬 Contact Page</button>
    </div>

    <!-- Section Panels -->
    <?php
    $pageKeys = ['home', 'shop', 'contact'];

    $sectionMeta = [
        'hero'               => ['label' => 'Hero Banner',       'icon' => 'fa-image'],
        'promo_strip'        => ['label' => 'Features Strip',    'icon' => 'fa-star'],
        'featured_products'  => ['label' => 'Featured Products', 'icon' => 'fa-box-open'],
        'categories'         => ['label' => 'Shop by Category',  'icon' => 'fa-layer-group'],
        'page_header'        => ['label' => 'Page Header',       'icon' => 'fa-heading'],
        'contact_info'       => ['label' => 'Contact Info',      'icon' => 'fa-address-card'],
        'map'                => ['label' => 'Location Map',      'icon' => 'fa-map-location-dot'],
        'contact_form'       => ['label' => 'Contact Form',      'icon' => 'fa-envelope'],
    ];

    foreach ($pageKeys as $pageKey):
        $rows = $sections[$pageKey] ?? [];
    ?>
    <div class="page-panel <?= $pageKey === 'home' ? '' : 'hidden' ?>" data-page="<?= $pageKey ?>">
        <?php if (empty($rows)): ?>
            <div class="glass-panel p-8 rounded-xl border border-white/10 text-center text-gray-500">
                <i class="fa-solid fa-layer-group text-3xl mb-3 opacity-30"></i>
                <p>No sections defined for this page yet.</p>
            </div>
        <?php else: ?>
        <div class="sortable-list" id="sortable-<?= $pageKey ?>">
            <?php foreach ($rows as $row):
                $key   = $row['section_key'] ?? '';
                $meta  = $sectionMeta[$key] ?? ['label' => ucwords(str_replace('_', ' ', $key)), 'icon' => 'fa-puzzle-piece'];
                $settingsArr = json_decode($row['settings'] ?? '{}', true) ?: [];
                $mapUrl = $settingsArr['map_url'] ?? '';
                $isMap  = ($key === 'map');
            ?>
            <div class="glass-panel p-4 rounded-xl border border-white/10 mb-3 flex items-start gap-4 cursor-grab active:cursor-grabbing section-card"
                 data-id="<?= (int)$row['id'] ?>"
                 data-page="<?= htmlspecialchars($pageKey) ?>"
                 data-key="<?= htmlspecialchars($key) ?>">

                <!-- Drag Handle + Label -->
                <div class="flex flex-col items-center gap-2 pt-1 min-w-[60px]">
                    <i class="fa-solid fa-grip-vertical text-slate-400 text-lg drag-handle"></i>
                    <i class="fa-solid <?= $meta['icon'] ?> text-blue-400 text-base mt-1"></i>
                    <span class="text-[10px] text-gray-500 text-center leading-tight mt-1"><?= htmlspecialchars($meta['label']) ?></span>
                </div>

                <!-- Editable Fields -->
                <div class="flex-1 grid grid-cols-1 gap-2">
                    <div>
                        <label class="text-gray-400 text-xs mb-1 block">Title</label>
                        <input type="text"
                               class="section-title bg-gray-800 border border-gray-700 text-white rounded-lg p-2 text-sm w-full focus:outline-none focus:border-blue-500 transition"
                               value="<?= htmlspecialchars($row['title'] ?? '') ?>"
                               placeholder="Section title…">
                    </div>
                    <div>
                        <label class="text-gray-400 text-xs mb-1 block">Content</label>
                        <textarea rows="2"
                                  class="section-content bg-gray-800 border border-gray-700 text-white rounded-lg p-2 text-sm w-full resize-none focus:outline-none focus:border-blue-500 transition"
                                  placeholder="Section content…"><?= htmlspecialchars($row['content'] ?? '') ?></textarea>
                    </div>
                    <?php if ($isMap): ?>
                    <div>
                        <label class="text-gray-400 text-xs mb-1 block">Map Embed URL</label>
                        <input type="text"
                               class="section-map-url bg-gray-800 border border-gray-700 text-white rounded-lg p-2 text-sm w-full focus:outline-none focus:border-blue-500 transition"
                               value="<?= htmlspecialchars($mapUrl) ?>"
                               placeholder="https://maps.google.com/maps?q=…&output=embed">
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Visibility Toggle -->
                <div class="flex flex-col items-center gap-1 pt-1 min-w-[56px]">
                    <label class="toggle-switch">
                        <input type="checkbox" class="section-visible" <?= $row['is_visible'] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="text-[10px] text-gray-500 mt-1">Visible</span>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Toast notification -->
    <div id="toastNotification"
         class="fixed bottom-6 right-6 bg-green-600 text-white px-5 py-3 rounded-xl shadow-2xl flex items-center gap-3 translate-y-20 opacity-0 transition-all duration-300 z-50 pointer-events-none">
        <i class="fa-solid fa-circle-check text-lg"></i>
        <span id="toastMessage">Changes saved successfully!</span>
    </div>

</div>

<style>
/* Page tabs */
.page-tab {
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #94a3b8;
    border-bottom: 2px solid transparent;
    background: transparent;
    cursor: pointer;
    transition: color 0.2s, border-color 0.2s;
    white-space: nowrap;
}
.page-tab:hover { color: #e2e8f0; }
.page-tab.active { color: #60a5fa; border-bottom-color: #2563eb; }

/* Toggle switch */
.toggle-switch { position: relative; display: inline-block; width: 42px; height: 24px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: #374151;
    border-radius: 9999px;
    cursor: pointer;
    transition: background 0.25s;
}
.toggle-slider::before {
    content: '';
    position: absolute;
    left: 3px;
    top: 3px;
    width: 18px;
    height: 18px;
    background: #9ca3af;
    border-radius: 50%;
    transition: transform 0.25s, background 0.25s;
}
.toggle-switch input:checked + .toggle-slider { background: #1d4ed8; }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(18px); background: #fff; }

/* Sortable ghost */
.sortable-ghost { opacity: 0.35; background: rgba(59, 130, 246, 0.1); border: 1px dashed #2563eb !important; }
.sortable-chosen { box-shadow: 0 0 0 2px #2563eb80; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
(function () {
    // --- Tab switching ---
    const tabs   = document.querySelectorAll('.page-tab');
    const panels = document.querySelectorAll('.page-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.add('hidden'));
            tab.classList.add('active');
            document.querySelector(`.page-panel[data-page="${tab.dataset.page}"]`).classList.remove('hidden');
        });
    });

    // --- SortableJS for each page list ---
    document.querySelectorAll('.sortable-list').forEach(list => {
        Sortable.create(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
        });
    });

    // --- Save all changes ---
    window.saveAllChanges = function () {
        const btn   = document.getElementById('saveAllBtn');
        const label = document.getElementById('saveBtnLabel');

        btn.disabled = true;
        label.textContent = 'Saving…';
        btn.classList.add('opacity-70', 'cursor-not-allowed');

        const payload = [];

        document.querySelectorAll('.sortable-list').forEach(list => {
            const cards = list.querySelectorAll('.section-card');
            cards.forEach((card, index) => {
                const settingsObj = {};
                const mapInput = card.querySelector('.section-map-url');
                if (mapInput) settingsObj.map_url = mapInput.value.trim();

                payload.push({
                    id:         parseInt(card.dataset.id, 10),
                    page:       card.dataset.page,
                    section_key: card.dataset.key,
                    title:      card.querySelector('.section-title').value,
                    content:    card.querySelector('.section-content').value,
                    is_visible: card.querySelector('.section-visible').checked ? 1 : 0,
                    sort_order: index,
                    settings:   JSON.stringify(settingsObj),
                });
            });
        });

        fetch('/api/page-builder/save', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ sections: payload }),
        })
        .then(res => {
            if (!res.ok) throw new Error('Server responded with ' + res.status);
            return res.json();
        })
        .then(() => {
            showToast('Changes saved successfully!');
        })
        .catch(err => {
            alert('Save failed: ' + err.message);
        })
        .finally(() => {
            btn.disabled = false;
            label.textContent = 'Save All Changes';
            btn.classList.remove('opacity-70', 'cursor-not-allowed');
        });
    };

    // --- Toast helper ---
    function showToast(msg) {
        const toast = document.getElementById('toastNotification');
        document.getElementById('toastMessage').textContent = msg;
        toast.classList.remove('translate-y-20', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3500);
    }
})();
</script>
