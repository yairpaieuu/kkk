<?php
    function contactSection(array $sections, string $key): bool {
        $s = $sections[$key] ?? null;
        return $s && isset($s['is_visible']) && (int)$s['is_visible'] === 1;
    }
    $pageSections = $pageSections ?? [];
    $siteSettings = $siteSettings ?? [];

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
<section class="bg-gradient-to-br from-blue-800 via-blue-700 to-blue-600 py-20 text-center text-white">
    <div class="max-w-6xl mx-auto px-4">

        <!-- Badge -->
        <span class="inline-block mb-5 px-4 py-1.5 rounded-full text-xs font-semibold tracking-widest uppercase bg-white/20 border border-white/30 text-white">
            Get in Touch
        </span>

        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4 leading-tight">
            <?= $heroTitle ?>
        </h1>
        <p class="text-blue-200 text-lg max-w-lg mx-auto leading-relaxed">
            <?= $heroContent ?>
        </p>

        <!-- Stat pills -->
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <span class="bg-white/10 border border-white/20 text-white text-sm px-4 py-2 rounded-full">⚡ Quick Response</span>
            <span class="bg-white/10 border border-white/20 text-white text-sm px-4 py-2 rounded-full">💬 Friendly Support</span>
            <span class="bg-white/10 border border-white/20 text-white text-sm px-4 py-2 rounded-full">🕐 Available Weekdays</span>
        </div>

    </div>
</section>

<?php /* ── 2. MAIN CONTENT — TWO COLUMN LAYOUT ───────────────── */ ?>
<section class="bg-slate-50 py-16">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-5 gap-8 items-start">

            <!-- ── LEFT COLUMN ── -->
            <div class="md:col-span-2 flex flex-col gap-4">

                <!-- Card A: Contact Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-bold text-slate-800 text-lg mb-4">Contact Details</h2>
                    <div class="flex flex-col gap-4">

                        <!-- Phone -->
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-phone text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wide">Phone</p>
                                <?php if ($phone): ?>
                                <a href="tel:<?= $phone ?>" class="text-slate-700 text-sm font-medium hover:text-blue-600 transition"><?= $phone ?></a>
                                <?php else: ?>
                                <span class="text-slate-400 text-sm">Not provided</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-envelope text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wide">Email</p>
                                <a href="mailto:<?= $email ?>" class="text-slate-700 text-sm font-medium hover:text-blue-600 transition break-all"><?= $email ?></a>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fa-solid fa-location-dot text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 mb-0.5 font-medium uppercase tracking-wide">Address</p>
                                <p class="text-slate-700 text-sm font-medium leading-snug"><?= $address ?></p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Card B: Business Hours -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-bold text-slate-800 text-lg mb-4">Business Hours</h2>
                    <ul class="flex flex-col gap-2.5 text-sm">
                        <li class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Mon – Fri</span>
                            <span class="text-slate-700 font-semibold">9:00 AM – 6:00 PM</span>
                        </li>
                        <li class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Saturday</span>
                            <span class="text-slate-700 font-semibold">10:00 AM – 4:00 PM</span>
                        </li>
                        <li class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Sunday</span>
                            <span class="text-rose-500 font-semibold">Closed</span>
                        </li>
                    </ul>
                </div>

                <!-- Card C: Social Links -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h2 class="font-bold text-slate-800 text-lg mb-4">Follow Us</h2>
                    <div class="flex flex-wrap gap-3">
                        <a href="#" aria-label="Facebook"
                           class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white hover:opacity-80 transition text-sm">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="Instagram"
                           class="w-10 h-10 rounded-full bg-pink-500 flex items-center justify-center text-white hover:opacity-80 transition text-sm">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="#" aria-label="Telegram"
                           class="w-10 h-10 rounded-full bg-sky-500 flex items-center justify-center text-white hover:opacity-80 transition text-sm">
                            <i class="fa-brands fa-telegram"></i>
                        </a>
                        <a href="#" aria-label="YouTube"
                           class="w-10 h-10 rounded-full bg-red-600 flex items-center justify-center text-white hover:opacity-80 transition text-sm">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                    </div>
                </div>

            </div><!-- /LEFT -->

            <!-- ── RIGHT COLUMN — Contact Form ── -->
            <div class="md:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">

                    <div class="mb-7">
                        <h2 class="text-2xl font-bold text-slate-800 mb-1">Send us a Message</h2>
                        <p class="text-slate-500 text-sm">We'll get back to you within 24 hours.</p>
                    </div>

                    <div id="contactAlert" class="hidden mb-5 rounded-xl px-4 py-3 text-sm font-medium"></div>

                    <form id="contactForm" novalidate>

                        <!-- Row 1: Name + Email -->
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="contactName" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
                                    Name <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" id="contactName" name="name" required
                                       placeholder="Your full name"
                                       class="bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition w-full text-slate-800 placeholder-slate-400">
                            </div>
                            <div>
                                <label for="contactEmail" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
                                    Email <span class="text-rose-500">*</span>
                                </label>
                                <input type="email" id="contactEmail" name="email" required
                                       placeholder="you@example.com"
                                       class="bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition w-full text-slate-800 placeholder-slate-400">
                            </div>
                        </div>

                        <!-- Row 2: Phone -->
                        <div class="mb-4">
                            <label for="contactPhone" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
                                Phone
                            </label>
                            <input type="tel" id="contactPhone" name="phone"
                                   placeholder="Your phone number"
                                   class="bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition w-full text-slate-800 placeholder-slate-400">
                        </div>

                        <!-- Row 3: Subject -->
                        <div class="mb-4">
                            <label for="contactSubject" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
                                Subject
                            </label>
                            <select id="contactSubject" name="subject"
                                    class="bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition w-full text-slate-700">
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Order Support">Order Support</option>
                                <option value="Product Question">Product Question</option>
                                <option value="Feedback">Feedback</option>
                            </select>
                        </div>

                        <!-- Row 4: Message -->
                        <div class="mb-6">
                            <label for="contactMessage" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
                                Message <span class="text-rose-500">*</span>
                            </label>
                            <textarea id="contactMessage" name="message" required rows="5"
                                      placeholder="Write your message here…"
                                      class="bg-white border border-slate-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition w-full text-slate-800 placeholder-slate-400 resize-y"></textarea>
                        </div>

                        <!-- Submit -->
                        <button type="submit" id="contactSubmitBtn"
                                class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3.5 rounded-xl transition-all hover:shadow-md active:scale-[0.98] text-sm">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span id="contactBtnText">Send Message</span>
                        </button>

                    </form>
                </div>
            </div><!-- /RIGHT -->

        </div>
    </div>
</section>

<?php /* ── 3. MAP ──────────────────────────────────────────────── */ ?>
<?php if ($showMap): ?>
<section class="bg-white py-12">
    <div class="max-w-6xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <iframe
                src="<?= htmlspecialchars($mapUrl) ?>"
                width="100%"
                height="420"
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

<?php /* ── 4. FAQ STRIP ─────────────────────────────────────────── */ ?>
<section class="bg-slate-50 border-t border-slate-100 py-10">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="font-bold text-2xl text-slate-800 text-center mb-7">Frequently Asked Questions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <p class="font-semibold text-slate-800 text-sm mb-2">How long does delivery take?</p>
                <p class="text-slate-500 text-sm leading-relaxed">Typically 2–5 business days depending on your location.</p>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <p class="font-semibold text-slate-800 text-sm mb-2">Can I return a product?</p>
                <p class="text-slate-500 text-sm leading-relaxed">Yes, within 7 days of delivery. Contact us to initiate a return.</p>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
                <p class="font-semibold text-slate-800 text-sm mb-2">Do you offer wholesale?</p>
                <p class="text-slate-500 text-sm leading-relaxed">Yes! Contact us for bulk order pricing and wholesale options.</p>
            </div>

        </div>
    </div>
</section>

<script>
(function () {
    const form      = document.getElementById('contactForm');
    const alertEl   = document.getElementById('contactAlert');
    const submitBtn = document.getElementById('contactSubmitBtn');
    const btnText   = document.getElementById('contactBtnText');

    if (!form) return;

    function showAlert(message, isSuccess) {
        alertEl.textContent = message;
        alertEl.className = isSuccess
            ? 'mb-5 rounded-xl px-4 py-3 text-sm font-medium bg-emerald-50 border border-emerald-200 text-emerald-700'
            : 'mb-5 rounded-xl px-4 py-3 text-sm font-medium bg-rose-50 border border-rose-200 text-rose-700';
        alertEl.classList.remove('hidden');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const name    = document.getElementById('contactName').value.trim();
        const email   = document.getElementById('contactEmail').value.trim();
        const phone   = document.getElementById('contactPhone').value.trim();
        const subject = document.getElementById('contactSubject').value;
        const message = document.getElementById('contactMessage').value.trim();

        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!name || !email || !message) {
            showAlert('Please fill in all required fields.', false);
            return;
        }
        if (!emailRe.test(email)) {
            showAlert('Please enter a valid email address.', false);
            return;
        }

        submitBtn.disabled = true;
        btnText.textContent = 'Sending…';

        try {
            const res  = await fetch('/api/contact', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ name, email, phone, subject, message }),
            });
            const data = await res.json();

            if (res.ok && data.success) {
                showAlert(data.message ?? "Your message has been sent. We'll get back to you soon!", true);
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
