let currentBody;
let pageEvents;

function updateFilePicker(input) {
    const name = input.closest('[data-file-picker]').querySelector('.file-picker-name');
    const files = Array.from(input.files, (file) => file.name);
    name.textContent = files.join('، ') || name.dataset.empty;
    name.title = files.join('، ');
}

document.addEventListener('change', (event) => {
    if (event.target.matches('[data-file-picker] input[type="file"]')) updateFilePicker(event.target);
    if (event.target.matches('[data-offer-image-field] input[type="file"]')) {
        const input = event.target;
        const file = input.files[0];
        const preview = input.closest('[data-offer-image-field]').querySelector('[data-offer-image-preview]');
        const image = preview.querySelector('img');
        const caption = preview.querySelector('figcaption');
        image.dataset.original ??= image.getAttribute('src') ?? '';
        if (image.dataset.original) image.src = image.dataset.original;
        else image.removeAttribute('src');
        preview.hidden = !image.dataset.original;
        caption.textContent = 'الصورة الحالية';

        if (file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 5 * 1024 * 1024) {
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                if (input.files[0] !== file) return;
                image.src = reader.result;
                caption.textContent = 'معاينة الصورة المختارة';
                preview.hidden = false;
            }, { once: true });
            reader.readAsDataURL(file);
        }
    }
    if (event.target.matches('[data-inline-choice] input[type="radio"]')) {
        const choice = event.target.closest('[data-inline-choice]');
        choice.querySelector('[data-choice-label]').textContent = event.target.nextElementSibling.textContent;
    }
});

document.addEventListener('invalid', (event) => {
    const choice = event.target.closest('[data-inline-choice]');
    if (choice) choice.open = true;
}, true);

function initializePage() {
    if (currentBody === document.body) return;
    pageEvents?.abort();
    currentBody = document.body;
    pageEvents = new AbortController();
    const { signal } = pageEvents;
    document.querySelectorAll('[data-file-picker] input[type="file"]').forEach(updateFilePicker);

    const registrationPassword = document.querySelector('[data-registration-password]');
    if (registrationPassword) {
        const checklist = document.getElementById('password-requirements');
        const confirmation = registrationPassword.form.elements.password_confirmation;
        const encoder = new TextEncoder();

        function updatePasswordRequirements() {
            const value = registrationPassword.value;
            const requirements = {
                length: Array.from(value).length >= 8 && encoder.encode(value).length <= 72,
                uppercase: /[A-Z]/.test(value),
                lowercase: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
                symbol: /[\p{P}\p{S}]/u.test(value),
            };
            checklist.querySelectorAll('[data-password-rule]').forEach((item) => {
                const met = requirements[item.dataset.passwordRule];
                item.classList.toggle('is-met', met);
                item.querySelector('span').textContent = met ? '✓' : '○';
            });
            const valid = Object.values(requirements).every(Boolean);
            checklist.hidden = value.length === 0 || valid;
            registrationPassword.setCustomValidity(value && !valid ? 'أكمل شروط كلمة المرور.' : '');
            confirmation.setCustomValidity(confirmation.value && confirmation.value !== value ? 'كلمتا المرور غير متطابقتين.' : '');
        }

        registrationPassword.addEventListener('input', updatePasswordRequirements, { signal });
        confirmation.addEventListener('input', updatePasswordRequirements, { signal });
        updatePasswordRequirements();
    }

    const serviceDialog = document.getElementById('service-details-dialog');
    let serviceRequest;
    let nextReviews;

    async function loadReviews() {
        if (!nextReviews || !serviceDialog.open) return;

        serviceRequest?.abort();
        const request = new AbortController();
        serviceRequest = request;
        const button = serviceDialog.querySelector('[data-more-reviews]');
        const status = serviceDialog.querySelector('[data-review-status]');
        const list = serviceDialog.querySelector('[data-review-list]');
        const scroll = serviceDialog.querySelector('.service-review-scroll');
        const position = scroll.scrollTop;
        const restoreButtonFocus = document.activeElement === button;
        button.disabled = true;
        status.textContent = 'جارٍ تحميل التقييمات…';

        try {
            const response = await fetch(nextReviews, { signal: request.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Reviews request failed');
            const page = await response.json();
            if (request.signal.aborted) return;
            const fragment = document.createDocumentFragment();
            for (const review of page.data) {
                const row = document.createElement('article');
                row.className = 'service-review';
                const heading = document.createElement('strong');
                heading.textContent = review.first_name + ' · ';
                const stars = document.createElement('span');
                const rating = Math.min(5, Math.max(0, Number(review.rating) || 0));
                stars.className = 'rating-display';
                stars.textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating);
                stars.setAttribute('aria-label', rating + ' من 5 نجوم');
                heading.append(stars);
                row.append(heading);
                if (review.created_at) {
                    const date = document.createElement('time');
                    date.textContent = review.created_at.slice(0, 10);
                    date.dir = 'ltr';
                    row.append(date);
                }
                if (review.comment) {
                    const comment = document.createElement('p');
                    comment.textContent = review.comment;
                    row.append(comment);
                }
                fragment.append(row);
            }
            list.append(fragment);
            nextReviews = page.next_page_url;
            status.textContent = list.children.length ? '' : 'لا توجد تقييمات لهذه الخدمة بعد.';
            button.hidden = !nextReviews;
        } catch (error) {
            if (request.signal.aborted) return;
            status.textContent = 'تعذر تحميل التقييمات. جرّب مرة أخرى.';
            button.hidden = false;
        } finally {
            if (!request.signal.aborted) {
                button.disabled = false;
                if (restoreButtonFocus) (button.hidden ? scroll : button).focus({ preventScroll: true });
                scroll.scrollTop = position;
            }
        }
    }

    document.addEventListener('click', (event) => {
        const choiceDone = event.target.closest('[data-choice-done]');
        if (choiceDone) {
            const choice = choiceDone.closest('[data-inline-choice]');
            choice.open = false;
            choice.querySelector('summary').focus();
        }
        const archive = event.target.closest('[data-archive-action]');
        if (archive) {
            const dialog = document.getElementById('archive-dialog');
            dialog.querySelector('form').action = archive.dataset.archiveAction;
            dialog.querySelector('#archive-message').textContent = archive.dataset.archiveMessage;
            dialog.showModal();
        }
        const close = event.target.closest('[data-close-dialog]');
        if (close) close.closest('dialog').close();

        const open = event.target.closest('[data-open-dialog]');
        if (open) document.getElementById(open.dataset.openDialog)?.showModal();

        const service = event.target.closest('[data-service-dialog]');
        if (service) {
            serviceRequest?.abort();
            nextReviews = service.dataset.reviewsUrl;
            serviceDialog.querySelector('h2').textContent = service.dataset.name;
            serviceDialog.querySelector('[data-service-description]').textContent = service.dataset.description;
            serviceDialog.querySelector('[data-service-price]').textContent = service.dataset.price;
            const image = serviceDialog.querySelector('[data-service-image]');
            image.hidden = !service.dataset.image;
            if (service.dataset.image) image.src = service.dataset.image;
            else image.removeAttribute('src');
            const duration = serviceDialog.querySelector('[data-service-duration]');
            duration.textContent = service.dataset.duration;
            duration.hidden = !service.dataset.duration;
            serviceDialog.querySelector('[data-review-list]').replaceChildren();
            serviceDialog.querySelector('[data-more-reviews]').hidden = true;
            serviceDialog.querySelector('.service-review-scroll').scrollTop = 0;
            serviceDialog.showModal();
            loadReviews();
        }

        if (event.target.closest('[data-more-reviews]')) loadReviews();
    }, { signal });

    document.querySelectorAll('[data-optional-image]').forEach((image) => {
        image.addEventListener('error', () => image.hidden = true);
        if (image.hasAttribute('src') && image.complete && !image.naturalWidth) image.hidden = true;
    });

    document.querySelectorAll('[data-directory-fallback]').forEach((image) => {
        const restore = () => {
            if (image.getAttribute('src') !== image.dataset.directoryFallback) image.src = image.dataset.directoryFallback;
        };
        image.addEventListener('error', restore);
        if (image.complete && !image.naturalWidth) restore();
    });

    serviceDialog?.addEventListener('close', () => serviceRequest?.abort());
    document.querySelector('dialog[data-auto-dialog]')?.showModal();

    for (const fields of document.querySelectorAll('[data-project-image-fields]')) {
        const rows = fields.querySelector('[data-image-rows]');
        const add = fields.querySelector('[data-add-image-field]');
        const removals = [...fields.querySelectorAll('[name="remove_images[]"]')];
        const limit = fields.querySelector('[data-image-limit]');
        const template = rows.firstElementChild.cloneNode(true);
        const requiresImage = fields.hasAttribute('data-requires-image');
        const previews = new Map();

        if (rows.firstElementChild.hidden) rows.firstElementChild.remove();
        add.hidden = false;

        const update = () => {
            const kept = removals.filter((checkbox) => !checkbox.checked).length;
            const inputs = [...rows.querySelectorAll('input')];
            const selected = inputs.filter((input) => input.files.length).length;
            const total = kept + selected;
            rows.querySelectorAll('[data-image-label]').forEach((label, index) => {
                label.textContent = 'صورة جديدة ' + (index + 1);
            });
            inputs.forEach((input, index) => {
                input.required = requiresImage && selected === 0 && index === 0;
            });
            const overflow = total > 8;
            for (const checkbox of removals) {
                checkbox.setCustomValidity(overflow ? 'احذف صورة أو أزل أحد الملفات الجديدة حتى يكون المجموع 8 صور.' : '');
            }
            add.disabled = kept + inputs.length >= 8;
            limit.textContent = overflow ? 'احذف صورة أو أزل أحد الملفات الجديدة.' : total + ' من 8 صور';
        };

        const clearPreview = (input) => {
            if (previews.has(input)) URL.revokeObjectURL(previews.get(input));
            previews.delete(input);
            const preview = input.closest('[data-image-row]').querySelector('[data-image-preview]');
            preview.hidden = true;
            preview.removeAttribute('src');
            return preview;
        };

        fields.addEventListener('click', (event) => {
            if (event.target.closest('[data-add-image-field]')) {
                const row = template.cloneNode(true);
                const input = row.querySelector('input');
                row.hidden = false;
                input.disabled = false;
                row.querySelector('[data-remove-image-field]').hidden = false;
                rows.append(row);
                update();
                input.focus();
            }

            const remove = event.target.closest('[data-remove-image-field]');
            if (remove) {
                const row = remove.closest('[data-image-row]');
                const input = row.querySelector('input');
                clearPreview(input);
                if (requiresImage && rows.children.length === 1) {
                    input.value = '';
                    updateFilePicker(input);
                    input.setCustomValidity('');
                    input.focus();
                } else {
                    row.remove();
                    add.focus();
                }
                update();
            }
        });

        fields.addEventListener('change', (event) => {
            const input = event.target;
            if (input.matches('input[type="file"]')) {
                const preview = clearPreview(input);
                const file = input.files[0];
                const invalid = file && (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024);
                input.setCustomValidity(invalid ? 'اختر صورة JPG أو PNG أو WebP بحجم لا يتجاوز 5 ميغابايت.' : '');
                if (file && !invalid) {
                    const url = URL.createObjectURL(file);
                    previews.set(input, url);
                    preview.src = url;
                    preview.hidden = false;
                }
            }
            update();
        });

        signal.addEventListener('abort', () => {
            previews.forEach((url) => URL.revokeObjectURL(url));
        });
        rows.querySelector('[data-remove-image-field]')?.removeAttribute('hidden');
        update();
    }

    const assistant = document.querySelector('[data-maintenance-assistant]');
    if (assistant) {
        const section = assistant.closest('.assistant-search');
        const input = assistant.elements.problem;
        const label = assistant.querySelector('[data-assistant-label]');
        const submit = assistant.querySelector('button[type="submit"]');
        const examples = section.querySelector('[data-assistant-examples]');
        const answer = section.querySelector('[data-assistant-answer]');
        const heading = section.querySelector('[data-assistant-heading]');
        const result = section.querySelector('[data-assistant-result]');
        const services = section.querySelector('[data-assistant-services]');
        const category = section.querySelector('[data-assistant-category]');
        const options = section.querySelector('[data-assistant-options]');
        const reset = section.querySelector('[data-assistant-reset]');
        const initialPlaceholder = input.placeholder;
        let context = '';

        function setContext(value) {
            context = value;
            input.placeholder = context ? 'اكتب التفاصيل المطلوبة هون' : initialPlaceholder;
            label.textContent = context ? 'تفاصيل إضافية عن المشكلة' : 'وصف المشكلة';
            submit.textContent = context ? 'إرسال التفاصيل' : 'جرّب المساعد';
            examples.hidden = Boolean(context);
        }

        examples.addEventListener('click', (event) => {
            const example = event.target.closest('[data-assistant-example]');
            if (!example || submit.disabled) return;
            input.value = example.textContent.trim();
            assistant.requestSubmit();
        });
        reset.addEventListener('click', () => {
            assistant.reset();
            setContext('');
            answer.hidden = true;
            input.focus();
        });
        assistant.addEventListener('submit', async (event) => {
            event.preventDefault();
            const problem = input.value.trim();
            if (!problem || submit.disabled) return;

            const data = new FormData(assistant);
            if (context) data.set('context', context);
            answer.hidden = false;
            delete answer.dataset.status;
            heading.textContent = 'جارٍ تحليل وصف المشكلة…';
            result.textContent = '';
            services.replaceChildren();
            services.hidden = true;
            category.hidden = true;
            options.replaceChildren();
            options.hidden = true;
            submit.disabled = true;
            reset.disabled = true;

            try {
                const response = await fetch(assistant.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: data,
                    signal,
                });
                if (response.status === 429) {
                    heading.textContent = 'المساعد مشغول الآن';
                    result.textContent = 'انتظر شوي وحاول مرة ثانية.';
                    return;
                }
                if (!response.ok) throw new Error('Assistant request failed');

                const body = await response.json();
                const titles = {
                    match: 'القسم المقترح',
                    clarify: 'خلّينا نحدّد المشكلة أكثر',
                    options: 'خيارات الصيانة',
                    urgent: 'تنبيه عاجل',
                    outside_scope: 'مساعد الصيانة',
                };
                answer.dataset.status = body.status;
                heading.textContent = titles[body.status] || 'مساعد الصيانة';
                result.textContent = body.message;
                if (body.status === 'clarify') {
                    setContext(problem);
                    input.value = '';
                    input.focus({ preventScroll: true });
                } else {
                    setContext('');
                }
                for (const option of body.category_options ?? []) {
                    const link = document.createElement('a');
                    link.className = 'btn btn-yellow btn-sm';
                    link.href = option.url;
                    link.textContent = option.name;
                    options.append(link);
                }
                options.hidden = options.childElementCount === 0;
                if (body.category_url || body.browse_url) {
                    category.href = body.category_url ?? body.browse_url;
                    category.textContent = body.category_url ? 'عرض خدمات '+body.category_name : 'تصفّح أقسام الصيانة';
                    category.hidden = false;
                }
                for (const name of body.available_services ?? []) {
                    const item = document.createElement('li');
                    item.textContent = name;
                    services.append(item);
                }
                services.hidden = services.childElementCount === 0;
            } catch {
                if (signal.aborted) return;
                heading.textContent = 'تعذّر تحميل الاقتراح';
                result.textContent = 'حاول مرة ثانية بعد التأكد من الاتصال.';
            } finally {
                submit.disabled = false;
                reset.disabled = false;
            }
        });
    }

    document.querySelectorAll('.account-avatar img, .technician-avatar img').forEach((image) => {
        const showInitials = () => image.hidden = true;
        image.addEventListener('error', showInitials);
        if (image.complete && !image.naturalWidth) showInitials();
    });

    const gallery = document.querySelector('[data-project-gallery]');
    gallery?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-project-photo]');
        if (!button) return;
        const image = gallery.querySelector('[data-project-main]');
        image.src = button.dataset.projectPhoto;
        image.alt = button.dataset.photoAlt;
        gallery.querySelector('[data-project-stage]').href = button.dataset.projectPhoto;
        gallery.querySelector('[aria-pressed="true"]').setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-pressed', 'true');
    });

    for (const notificationMenu of document.querySelectorAll('.notification-dropdown, .account-dropdown')) {
        document.addEventListener('click', (event) => {
            if (notificationMenu.open && !notificationMenu.contains(event.target)) notificationMenu.open = false;
        }, { signal });
        notificationMenu.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !notificationMenu.open) return;
            notificationMenu.open = false;
            notificationMenu.querySelector('summary').focus();
        });
    }

    const registration = document.querySelector('[data-registration]');
    if (registration) {
        const type = registration.querySelector('#account-type');
        const personal = registration.querySelector('[data-registration-personal]');
        const profession = registration.querySelector('[data-registration-vendor]');
        const next = registration.querySelector('[data-registration-next]');
        const back = registration.querySelector('[data-registration-back]');
        const submit = registration.querySelector('[data-registration-submit]');
        const progress = registration.querySelector('[data-registration-progress]');
        let showProfession = false;

        const render = () => {
            const vendor = type.value === 'vendor';
            personal.hidden = showProfession;
            profession.hidden = !showProfession;
            next.hidden = !vendor || showProfession;
            back.hidden = !showProfession;
            submit.hidden = vendor && !showProfession;
            progress.textContent = vendor ? (showProfession ? 'الخطوة ٢ من ٢ · بيانات المهنة' : 'الخطوة ١ من ٢ · بيانات الحساب') : '';
            profession.querySelectorAll('[data-vendor-required]').forEach((field) => {
                field.disabled = !showProfession;
                field.required = showProfession;
            });
        };
        const advance = () => {
            for (const field of personal.querySelectorAll('input, select')) {
                if (!field.reportValidity()) return;
            }
            showProfession = true;
            render();
            profession.querySelector('input').focus();
        };
        type.addEventListener('change', render);
        next.addEventListener('click', advance);
        back.addEventListener('click', () => {
            showProfession = false;
            render();
            type.focus();
        });
        registration.addEventListener('submit', (event) => {
            if (type.value === 'vendor' && !showProfession) {
                event.preventDefault();
                advance();
            }
        });
        render();
    }

    signal.addEventListener('abort', () => {
        serviceRequest?.abort();
    });
}

document.addEventListener('turbo:load', initializePage);
document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('dialog[open]').forEach((dialog) => dialog.close());
    const overlays = document.querySelectorAll('.user-modal:not([hidden]), .service-modal-backdrop:not([hidden]), #request-decision-modal:not([hidden])');
    if (overlays.length) {
        overlays.forEach((overlay) => overlay.hidden = true);
        document.body.style.overflow = '';
    }
});
initializePage();
