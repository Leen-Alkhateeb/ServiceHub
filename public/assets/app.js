import * as Turbo from 'https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.23/dist/turbo.es2017-esm.min.js';

const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => [...document.querySelectorAll(selector)];

const get = (key, fallback) => {
    try {
        return JSON.parse(localStorage.getItem(`sh_${key}`)) ?? fallback;
    } catch {
        return fallback;
    }
};

const put = (key, value) => {
    localStorage.setItem(`sh_${key}`, JSON.stringify(value));
};

const msg = (text) => {
    const notice = document.createElement('div');
    notice.className = 'alert';
    notice.textContent = text;
    document.querySelector('main')?.prepend(notice);
    setTimeout(() => notice.remove(), 4200);
};

const services = [
    { id: 1, name: 'كشف وإصلاح تسريبات المياه', cat: 'السباكة', vendor: 'خالد العمري', city: 'عمّان', price: 15, rating: '4.8', style: 'blue', desc: 'فحص مصادر التسريب وإصلاح الوصلات والمحابس التالفة.' },
    { id: 2, name: 'فحص وصيانة لوحة التوزيع', cat: 'الكهرباء', vendor: 'سامر الحداد', city: 'الزرقاء', price: 20, rating: '4.9', style: 'gold', desc: 'فحص اللوحة والأحمال والأعطال الكهربائية.' },
    { id: 3, name: 'فحص وتعبئة غاز المكيف', cat: 'صيانة المكيفات', vendor: 'يزن المومني', city: 'عمّان', price: 30, rating: '4.9', style: '', desc: 'فحص التبريد والضغط وتعبئة الغاز عند الحاجة.' },
    { id: 4, name: 'تنظيف وصيانة مكيف سبليت', cat: 'صيانة المكيفات', vendor: 'محمد بني ياسين', city: 'إربد', price: 18, rating: '4.7', style: '', desc: 'تنظيف الوحدة وفحص التشغيل والصيانة.' },
    { id: 5, name: 'تشخيص أعطال كهربائية بالمكاتب', cat: 'الكهرباء', vendor: 'فراس العبادي', city: 'البلقاء', price: 22, rating: '4.8', style: 'gold', desc: 'تشخيص الدوائر والأعطال الكهربائية.' },
    { id: 6, name: 'تأسيس وصيانة تمديدات صحية', cat: 'السباكة', vendor: 'أحمد الزعبي', city: 'العقبة', price: 25, rating: '4.6', style: 'blue', desc: 'تأسيس وصيانة تمديدات المياه.' },
    { id: 7, name: 'تركيب وصيانة إنارة المحلات', cat: 'الكهرباء', vendor: 'ليث القضاة', city: 'عمّان', price: 12, rating: '4.5', style: 'gold', desc: 'تركيب وحدات الإنارة وصيانتها.' },
    { id: 8, name: 'صيانة سخانات وخزانات المياه', cat: 'السباكة', vendor: 'عمر الخطيب', city: 'مادبا', price: 20, rating: '4.4', style: 'blue', desc: 'فحص السخانات والخزانات وإصلاح الأعطال.' },
];

function card(service) {
    const isFavorite = get('favorites', []).includes(service.id);

    return `
        <article class="card">
            <div class="art ${service.style}">${service.cat}</div>
            <div class="card-body">
                <span class="pill">${service.cat}</span>
                <h3 style="margin-top:10px">${service.name}</h3>
                <p>${service.vendor} · ${service.city} · ★ ${service.rating}</p>
                <div class="card-footer">
                    <strong class="price">${service.price} د.أ</strong>
                    <div>
                        <button class="btn btn-outline btn-sm" data-fav="${service.id}" aria-label="المفضلة">
                            ${isFavorite ? '♥' : '♡'}
                        </button>
                        <a class="btn btn-outline btn-sm" href="/service-details?id=${service.id}">التفاصيل</a>
                        <a class="btn btn-yellow btn-sm" href="/booking?id=${service.id}">احجز</a>
                    </div>
                </div>
            </div>
        </article>
    `;
}

function renderCards(items, selector) {
    const element = $(selector);
    if (!element) return;

    element.innerHTML = items.length
        ? items.map(card).join('')
        : '<div class="panel">لا توجد نتائج مطابقة.</div>';
}

let initializedBody = null;

function init() {
    // turbo:load may run after DOMContentLoaded; don't initialize the same body twice.
    if (initializedBody === document.body) return;
    initializedBody = document.body;

    const params = new URLSearchParams(location.search);
    const selected = services.find((service) => service.id === +(params.get('id') || 1)) || services[0];

    if ($('#service-cards')) renderCards(services, '#service-cards');
    if ($('#home-cards')) renderCards(services.slice(0, 4), '#home-cards');
    if ($('#favorite-cards')) {
        renderCards(services.filter((service) => get('favorites', []).includes(service.id)), '#favorite-cards');
    }

    if ($('#service-detail')) {
        $('#service-detail').innerHTML = `
            <div class="art ${selected.style}" style="height:280px">${selected.cat}</div>
            <div class="panel">
                <span class="pill yellow">${selected.cat}</span>
                <h2>${selected.name}</h2>
                <p>${selected.desc}</p>
                <p class="muted">${selected.vendor} · ${selected.city} · ★ ${selected.rating}</p>
                <h3>${selected.price} د.أ</h3>
                <a class="btn btn-yellow" href="/booking?id=${selected.id}">اختار الموعد واحجز</a>
                <a class="btn btn-outline" href="/vendor-profile?id=${selected.id}">ملف الفني</a>
            </div>
        `;
    }

    if ($('#booking-service')) {
        $('#booking-service').innerHTML = `
            <h3>${selected.name}</h3>
            <p>${selected.vendor} · ${selected.city}</p>
            <p>${selected.desc}</p>
            <strong>${selected.price} د.أ</strong>
        `;
    }

    if ($('#booking-price')) $('#booking-price').textContent = `${selected.price} د.أ`;

    if ($('#booking-form')) {
        $('#booking-form').addEventListener('submit', (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const bookings = get('bookings', []);

            bookings.unshift({
                id: Date.now(),
                service: selected.name,
                vendor: selected.vendor,
                price: selected.price,
                date: form.date.value,
                time: form.time.value,
                status: 'بانتظار موافقة الفني',
                created: new Date().toISOString(),
            });

            put('bookings', bookings);
            Turbo.visit('/customer-bookings');
        });
    }

    if ($('#booking-list')) {
        const bookings = get('bookings', []);
        $('#booking-list').innerHTML = bookings.length
            ? bookings.map((booking) => `
                <div class="item-row">
                    <div>
                        <b>${booking.service}</b>
                        <p class="muted small">${booking.vendor} · ${booking.date} · ${booking.time} · ${booking.price} د.أ</p>
                        <span class="pill yellow">${booking.status}</span>
                    </div>
                    <a href="/booking-details?booking=${booking.id}" class="btn btn-outline btn-sm">التفاصيل</a>
                </div>
            `).join('')
            : '<div class="notice">لا توجد حجوزات بعد. <a href="/services"><b>اكتشف الخدمات</b></a></div>';
    }

    if ($('#booking-detail')) {
        const booking = get('bookings', []).find((item) => item.id === +params.get('booking'));
        $('#booking-detail').innerHTML = booking
            ? `
                <h2>${booking.service}</h2>
                <p>${booking.vendor} · ${booking.date} · ${booking.time}</p>
                <p><b>الحالة:</b> ${booking.status}</p>
                <p><b>السعر الأصلي:</b> ${booking.price} د.أ</p>
                <div class="notice">الإلغاء مجاني خلال ساعتين من موافقة الفني؛ بعد ذلك غرامة 15% من قيمة الحجز. الدفع خلال 30 دقيقة من الموافقة.</div>
                <button class="btn btn-danger" data-cancel="${booking.id}">طلب إلغاء</button>
            `
            : '<p>لم يتم العثور على الحجز.</p>';
    }

    if ($('#search-input')) {
        $('#search-input').addEventListener('input', (event) => {
            const term = event.currentTarget.value.trim();
            const category = $('#category-filter')?.value || '';

            renderCards(
                services.filter((service) =>
                    (!category || service.cat === category)
                    && [service.name, service.cat, service.vendor, service.city].some((value) => value.includes(term))
                ),
                '#service-cards'
            );
        });
    }

    if ($('#category-filter')) {
        $('#category-filter').addEventListener('change', () => {
            $('#search-input')?.dispatchEvent(new Event('input'));
        });
    }

    if ($('#ai-form')) {
        $('#ai-form').addEventListener('submit', (event) => {
            event.preventDefault();
            const query = $('#ai-query').value;
            const category = /كهرب|فيوز|إنارة|قاطع/.test(query)
                ? 'الكهرباء'
                : /مكيف|تبريد|غاز/.test(query)
                    ? 'صيانة المكيفات'
                    : /مي|ماء|تسريب|سخان|خزان/.test(query)
                        ? 'السباكة'
                        : null;

            $('#ai-result').innerHTML = category
                ? `<div class="notice">اقتراح أولي: <b>${category}</b> — ليس تشخيصًا. <a href="/services?cat=${encodeURIComponent(category)}"><b>عرض الخدمات</b></a></div>`
                : '<div class="notice">لم نحدد الخدمة بدقة. تصفح الأقسام أو جرّب وصفًا أوضح. هذا نموذج تجريبي؛ تكامل Gemini يحتاج Backend.</div>';
        });
    }

    if (params.get('cat') && $('#category-filter')) {
        $('#category-filter').value = params.get('cat');
        $('#category-filter').dispatchEvent(new Event('change'));
    }

    $$('form[data-demo]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            msg('تم حفظ الطلب في الواجهة التجريبية. الربط الفعلي يحتاج Laravel API.');
            form.reset();
        });
    });
}

// One delegated click listener survives Turbo page visits; it won't multiply on every visit.
document.addEventListener('click', (event) => {
    const favoriteButton = event.target.closest('[data-fav]');
    if (favoriteButton) {
        const id = Number(favoriteButton.dataset.fav);
        let favorites = get('favorites', []);
        favorites = favorites.includes(id)
            ? favorites.filter((favoriteId) => favoriteId !== id)
            : [...favorites, id];

        put('favorites', favorites);
        favoriteButton.textContent = favorites.includes(id) ? '♥' : '♡';

        if ($('#favorite-cards')) {
            renderCards(services.filter((service) => favorites.includes(service.id)), '#favorite-cards');
        }
    }

    const cancelButton = event.target.closest('[data-cancel]');
    if (cancelButton) {
        const bookings = get('bookings', []);
        const booking = bookings.find((item) => item.id === +cancelButton.dataset.cancel);

        if (booking && confirm('تأكيد طلب إلغاء الحجز؟')) {
            booking.status = 'ملغي (تجريبي)';
            put('bookings', bookings);
            Turbo.visit(location.href, { action: 'replace' });
        }
    }

    if (event.target.closest('#menu-toggle')) {
        $('#navlinks')?.classList.toggle('open');
    }
});

document.addEventListener('turbo:load', init);
document.addEventListener('DOMContentLoaded', init, { once: true });

// Covers the case where this module loads after the initial Turbo event.
if (document.readyState !== 'loading') init();
