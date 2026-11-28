function locate(form, status) {
    const preview = form.querySelector('[data-instant-preview-location]');
    if (preview && (sessionStorage.getItem('instant-preview-location')
        ?? (preview.hasAttribute('data-instant-location-default') ? 'amman' : 'real')) === 'amman') {
        form.elements.latitude.value = '31.950000';
        form.elements.longitude.value = '35.910000';
        status.textContent = 'تم تحديد موقع عمّان التجريبي.';
        return Promise.resolve();
    }
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) return reject();
        navigator.geolocation.getCurrentPosition(({ coords }) => {
            if (form.dataset.instantLocationManual) return resolve();
            form.elements.latitude.value = coords.latitude.toFixed(6);
            form.elements.longitude.value = coords.longitude.toFixed(6);
            status.textContent = 'تم تحديد الموقع.';
            resolve();
        }, reject, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    });
}

function locationUnavailable(status, error) {
    const messages = {
        1: 'المتصفح منع الوصول للموقع. اسمح بالموقع من إعدادات الصفحة وحاول مرة ثانية.',
        2: 'الجهاز ما قدر يحدّد موقعك. تأكّد إن خدمة الموقع مفعّلة، أو افتح الصفحة مباشرةً في Chrome أو Edge.',
        3: 'انتهت مهلة تحديد الموقع. تأكّد إن خدمة الموقع مفعّلة وحاول مرة ثانية.',
    };
    status.textContent = messages[error?.code] ?? 'الموقع غير متاح في هذا المتصفح. افتح الصفحة مباشرةً في Chrome أو Edge.';
}

const locationSubmissions = new WeakSet();
const completedServiceSubmissions = new WeakSet();
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || form.elements.namedItem('action')?.value !== 'complete') return;
    if (completedServiceSubmissions.has(form)) {
        event.preventDefault();
        return;
    }
    completedServiceSubmissions.add(form);
});
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!form.matches('[data-instant-location]')) return;
    if (locationSubmissions.has(form)) {
        event.preventDefault();
        return;
    }
    if (form.elements.latitude.value && form.elements.longitude.value) return;

    event.preventDefault();
    locationSubmissions.add(form);
    form.dataset.instantDraft = 'true';
    const status = form.querySelector('[data-instant-location-status]');
    status.textContent = 'جارٍ تحديد الموقع…';
    try {
        await locate(form, status);
    } catch (error) {
        locationUnavailable(status, error);
        return;
    } finally {
        locationSubmissions.delete(form);
    }
    setTimeout(() => form.requestSubmit(event.submitter ?? undefined), 0);
}, true);

document.addEventListener('click', (event) => {
    const previewButton = event.target.closest('[data-instant-preview-location]');
    const previewForm = previewButton?.closest('[data-instant-location]');
    if (previewForm) {
        delete previewForm.dataset.instantLocationManual;
        sessionStorage.setItem('instant-preview-location', 'amman');
        previewForm.dataset.instantDraft = 'true';
        locate(previewForm, previewForm.querySelector('[data-instant-location-status]'));
        return;
    }
    const button = event.target.closest('[data-instant-locate]');
    const form = button?.closest('[data-instant-location]');
    if (!form) return;
    delete form.dataset.instantLocationManual;
    if (form.querySelector('[data-instant-preview-location]')) {
        sessionStorage.setItem('instant-preview-location', 'real');
        form.elements.latitude.value = '';
        form.elements.longitude.value = '';
    }
    form.dataset.instantDraft = 'true';
    const status = form.querySelector('[data-instant-location-status]');
    status.textContent = 'جارٍ تحديد الموقع…';
    locate(form, status).catch((error) => locationUnavailable(status, error));
});

for (const form of document.querySelectorAll('[data-instant-refresh-location]')) {
    const status = form.querySelector('[data-instant-location-status]');
    if (form.dataset.instantRefreshLocation) {
        let updating = false;
        const refresh = async () => {
            if (document.hidden || updating) return;
            updating = true;
            try {
                await locate(form, status);
                const data = new FormData(form);
                data.set('refresh_location', '1');
                const response = await fetch(form.action, { method: 'POST', body: data, headers: { Accept: 'application/json' } });
                status.textContent = response.ok ? 'مشاركة الموقع مفعّلة.' : 'تعذّر تحديث التوفّر على الموقع. حدّث الصفحة وحاول مرة ثانية.';
            } catch (error) {
                if (typeof error?.code === 'number' || !navigator.geolocation) {
                    locationUnavailable(status, error);
                } else {
                    status.textContent = 'تعذّر إرسال تحديث الموقع. تأكّد من اتصالك وحاول مرة ثانية.';
                }
            } finally {
                updating = false;
            }
        };
        refresh();
        setInterval(refresh, Number(form.dataset.instantRefreshLocation) * 1000);
        document.addEventListener('visibilitychange', refresh);
    }
}

const aiForm = document.querySelector('[data-instant-ai]');
if (aiForm) {
    const button = aiForm.querySelector('[data-instant-suggest]');
    const status = aiForm.querySelector('[data-instant-suggestion]');
    const description = aiForm.elements.description;
    const selection = aiForm.elements.service_id;
    const defaultLabel = button.textContent;
    let suggestedValue = '';
    selection.addEventListener('change', () => { suggestedValue = ''; });
    description.addEventListener('input', () => {
        if (suggestedValue && selection.value === suggestedValue) {
            selection.value = '';
            suggestedValue = '';
            status.textContent = '';
        }
    });
    button.addEventListener('click', async () => {
        const problem = description.value.trim();
        if (!problem) { status.textContent = 'اكتب وصف المشكلة أولًا.'; return; }
        const previousSelection = selection.value;
        button.disabled = true;
        status.textContent = 'جارٍ البحث عن خدمة مناسبة…';
        try {
            const data = new FormData();
            data.set('_token', aiForm.elements._token.value);
            data.set('problem', problem);
            const response = await fetch(aiForm.dataset.instantAi, { method: 'POST', body: data, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error();
            const result = await response.json();
            if (description.value.trim() !== problem) {
                status.textContent = 'تغيّر الوصف. اضغط زر الاقتراح مرة ثانية.';
                return;
            }
            const option = [...selection.options].find(item => item.value === String(result.service_id));
            button.textContent = result.status === 'clarify' ? 'أعد الاقتراح بعد إضافة التفاصيل' : defaultLabel;
            if (result.status === 'matched' && option) {
                if (selection.value === previousSelection) {
                    selection.value = option.value;
                    suggestedValue = option.value;
                }
                status.textContent = 'الخدمة المقترحة: ' + option.textContent.trim();
                return;
            }
            status.textContent = result.message ?? 'اختار الخدمة يدويًا.';
            if (result.status === 'clarify') description.focus();
        } catch {
            status.textContent = 'تعذّر تحميل الاقتراح. اختار الخدمة يدويًا.';
        } finally {
            button.disabled = false;
        }
    });
}

const liveSections = [...document.querySelectorAll('[data-instant-live]')];
if (liveSections.length) {
    const status = document.querySelector('[data-instant-live-status]');
    let busy = false;
    let submitting = false;
    const isEditing = (live) => live.contains(document.activeElement)
        && document.activeElement.matches('input:not([type="hidden"]), textarea, select')
        || live.querySelector('form[data-instant-draft], .instant-request-details[open]');
    liveSections.forEach((live) => {
        live.addEventListener('submit', (event) => { if (!event.defaultPrevented) submitting = true; });
        live.addEventListener('input', ({ target }) => {
            const form = target.closest('form');
            if (form) form.dataset.instantDraft = 'true';
        });
    });
    const refresh = async () => {
        if (busy || submitting || document.hidden) return;
        busy = true;
        try {
            const response = await fetch(location.href, { cache: 'no-store' });
            if (!response.ok) throw new Error();
            const documentUpdate = new DOMParser().parseFromString(await response.text(), 'text/html');
            const incoming = documentUpdate.querySelectorAll('[data-instant-live]');
            if (incoming.length !== liveSections.length) throw new Error();
            const currentNotifications = document.querySelector('.notification-dropdown');
            const newNotifications = documentUpdate.querySelector('.notification-dropdown');
            if (currentNotifications && newNotifications && currentNotifications.innerHTML !== newNotifications.innerHTML) {
                newNotifications.open = currentNotifications.open;
                currentNotifications.replaceWith(newNotifications);
            }
            liveSections.forEach((live, index) => {
                const update = incoming[index];
                if (!submitting && !isEditing(live) && live.innerHTML !== update.innerHTML) {
                    live.replaceChildren(...update.childNodes);
                }
            });
            status.textContent = '';
        } catch {
            status.textContent = 'تعذّر تحديث البيانات. رح نحاول مرة ثانية.';
        } finally {
            busy = false;
        }
    };
    setInterval(refresh, 5000);
    window.addEventListener('online', refresh);
    document.addEventListener('visibilitychange', refresh);
    liveSections.forEach((live) => live.addEventListener('focusout', () => setTimeout(refresh, 0)));
}

function previewFils(value) {
    if (!/^\d+(\.\d{1,3})?$/.test(value)) return null;
    const [whole, fraction = ''] = value.split('.');
    return Number(whole) * 1000 + Number(fraction.padEnd(3, '0'));
}

document.addEventListener('input', ({ target }) => {
    if (!target.matches('input[name="service_amount"]')) return;
    const preview = target.form.querySelector('[data-resolution-preview]');
    if (!preview) return;
    const approved = previewFils(target.value);
    if (approved === null || !target.validity.valid) {
        preview.textContent = '';
        return;
    }
    const collected = previewFils(preview.dataset.collected) - previewFils(preview.dataset.refunded);
    const vendor = Math.floor((approved * 85 + 50) / 100);
    const money = (fils) => (fils / 1000).toFixed(3);
    preview.textContent = `الفني ${money(vendor)} د.أ · المنصة ${money(approved - vendor)} د.أ · الاسترداد ${money(Math.max(0, collected - approved))} د.أ · المتبقي للدفع ${money(Math.max(0, approved - collected))} د.أ`;
});
