<?php
    function contactSection(array $sections, string $key): bool {
        $s = $sections[$key] ?? null;
        return $s && isset($s['is_visible']) && (int)$s['is_visible'] === 1;
    }

    function contactSettings(array $sections, string $key): array {
        $s = $sections[$key] ?? null;
        if (!$s) return [];
        $decoded = json_decode($s['settings'] ?? '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    $heroSection    = $pageSections['hero']         ?? null;
    $infoSection    = $pageSections['contact_info'] ?? null;
    $mapSection     = $pageSections['map']          ?? null;
    $formSection    = $pageSections['contact_form'] ?? null;

    $heroVisible = $heroSection && isset($heroSection['is_visible']) && (int)$heroSection['is_visible'] === 1;
    $heroTitle   = $heroVisible ? htmlspecialchars($heroSection['title']   ?? 'Contact Us')                   : 'Contact Us';
    $heroContent = $heroVisible ? htmlspecialchars($heroSection['content'] ?? "We'd love to hear from you.") : "We'd love to hear from you.";

    $siteName = htmlspecialchars($siteSettings['site_name'] ?? 'Our Store');
    $phone    = htmlspecialchars($siteSettings['phone']     ?? '');
    $email    = htmlspecialchars($siteSettings['email']     ?? 'support@store.com');
    $address  = htmlspecialchars($siteSettings['address']   ?? 'Our Location');

    $mapSettings = contactSettings($pageSections, 'map');
    $mapUrl      = trim($mapSettings['map_url'] ?? '');
    $showMap     = contactSection($pageSections, 'map') && $mapUrl !== '';
?>

<?php /* ── 1. HERO ─────────────────────────────────────────────── */ ?>
<section class="bg-gradient-to-br from-blue-700 via-blue-600 to-blue-500 py-16 text-center text-white">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-3"><?= $heroTitle ?></h1>
        <p class="text-blue-100 text-base md:text-lg max-w-xl mx-auto"><?= $heroContent ?></p>
    </div>
</section>

<?php /* ── 2. CONTACT INFO CARDS ──────────────────────────────── */ ?>
<section class="py-12 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Phone -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex flex-col items-center text-center gap-3">
                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-phone text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-slate-800 font-semibold text-base mb-1">Phone</h3>
                    <?php if ($phone): ?>
                    <a href="tel:<?= $phone ?>" class="text-slate-500 text-sm hover:text-blue-600 transition"><?= $phone ?></a>
                    <?php else: ?>
                    <span class="text-slate-400 text-sm">Not provided</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex flex-col items-center text-center gap-3">
                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-envelope text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-slate-800 font-semibold text-base mb-1">Email</h3>
                    <a href="mailto:<?= $email ?>" class="text-slate-500 text-sm hover:text-blue-600 transition"><?= $email ?></a>
                </div>
            </div>

            <!-- Address -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex flex-col items-center text-center gap-3">
                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center">
                    <i class="fa-solid fa-location-dot text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h3 class="text-slate-800 font-semibold text-base mb-1">Address</h3>
                    <p class="text-slate-500 text-sm"><?= $address ?></p>
                </div>
            </div>

        </div>
    </div>
</section>

<?php /* ── 3. MAP ──────────────────────────────────────────────── */ ?>
<?php if ($showMap): ?>
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <iframe
                src="<?= htmlspecialchars($mapUrl) ?>"
                width="100%"
                height="400"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Our Location">
            </iframe>
        </div>
    </div>
</section>
<?php endif; ?>

<?php /* ── 4. CONTACT FORM ─────────────────────────────────────── */ ?>
<?php if (contactSection($pageSections, 'contact_form')): ?>
<section class="py-12 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm border border-slate-100 p-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-6">Send Us a Message</h2>

            <div id="contactAlert" class="hidden mb-5 rounded-lg px-4 py-3 text-sm font-medium"></div>

            <form id="contactForm" novalidate>

                <!-- Name -->
                <div class="mb-5">
                    <label for="contactName" class="block text-sm font-medium text-slate-700 mb-1">
                        Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="contactName" name="name" required
                           placeholder="Your name"
                           class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <!-- Email -->
                <div class="mb-5">
                    <label for="contactEmail" class="block text-sm font-medium text-slate-700 mb-1">
                        Email <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" id="contactEmail" name="email" required
                           placeholder="you@example.com"
                           class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <!-- Phone -->
                <div class="mb-5">
                    <label for="contactPhone" class="block text-sm font-medium text-slate-700 mb-1">
                        Phone
                    </label>
                    <input type="tel" id="contactPhone" name="phone"
                           placeholder="Your phone number"
                           class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                </div>

                <!-- Message -->
                <div class="mb-6">
                    <label for="contactMessage" class="block text-sm font-medium text-slate-700 mb-1">
                        Message <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="contactMessage" name="message" required rows="5"
                              placeholder="Write your message here…"
                              class="w-full bg-white border border-slate-300 text-slate-800 placeholder-slate-400 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition resize-y"></textarea>
                </div>

                <!-- Submit -->
                <button type="submit" id="contactSubmitBtn"
                        class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-semibold py-3 rounded-lg transition-all duration-150 text-sm">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span id="contactBtnText">Send Message</span>
                </button>

            </form>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
(function () {
    const form      = document.getElementById('contactForm');
    const alert     = document.getElementById('contactAlert');
    const submitBtn = document.getElementById('contactSubmitBtn');
    const btnText   = document.getElementById('contactBtnText');

    if (!form) return;

    function showAlert(message, isSuccess) {
        alert.textContent = message;
        alert.className = isSuccess
            ? 'mb-5 rounded-lg px-4 py-3 text-sm font-medium bg-emerald-50 border border-emerald-200 text-emerald-700'
            : 'mb-5 rounded-lg px-4 py-3 text-sm font-medium bg-rose-50 border border-rose-200 text-rose-700';
        alert.classList.remove('hidden');
        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const name    = document.getElementById('contactName').value.trim();
        const email   = document.getElementById('contactEmail').value.trim();
        const phone   = document.getElementById('contactPhone').value.trim();
        const message = document.getElementById('contactMessage').value.trim();

        if (!name || !email || !message) {
            showAlert('Please fill in all required fields.', false);
            return;
        }

        submitBtn.disabled = true;
        btnText.textContent = 'Sending…';

        try {
            const res  = await fetch('/api/contact', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ name, email, phone, message }),
            });
            const data = await res.json();

            if (res.ok && data.success) {
                showAlert(data.message ?? 'Your message has been sent. We\'ll get back to you soon!', true);
                form.reset();
            } else {
                showAlert(data.message ?? 'Something went wrong. Please try again.', false);
            }
        } catch (err) {
            console.error('Contact form error:', err);
            showAlert('Network error. Please check your connection and try again.', false);
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Send Message';
        }
    });
}());
</script>
