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
});

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

const projectImages = document.getElementById('project-images');
let previews = [];
projectImages?.addEventListener('change', () => {
    previews.forEach((url) => URL.revokeObjectURL(url));
    previews = [];
    const gallery = document.querySelector('[data-upload-previews]');
    gallery.replaceChildren();
    projectImages.setCustomValidity(projectImages.files.length > 8 ? 'يمكن رفع 8 صور كحد أقصى.' : '');
    if (!projectImages.checkValidity()) return;

    for (const file of projectImages.files) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            projectImages.setCustomValidity('اختر صور JPEG أو PNG أو WebP بحجم لا يتجاوز 5 ميغابايت لكل صورة.');
            return;
        }
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = file.name;
        previews.push(image.src);
        gallery.append(image);
    }
});

const assistant = document.querySelector('[data-maintenance-assistant]');
assistant?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = document.querySelector('[data-assistant-result]');
    const button = assistant.querySelector('button');
    result.textContent = 'جارٍ البحث عن القسم المناسب…';
    button.disabled = true;
    try {
        const response = await fetch(assistant.action, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new FormData(assistant),
        });
        const body = await response.json();
        result.textContent = body.message || 'تعذر عرض اقتراح الآن.';
    } catch {
        result.textContent = 'تعذر الاتصال بالمساعد. تفقد اتصالك وحاول مرة أخرى.';
    } finally {
        button.disabled = false;
    }
});

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

const notificationMenu = document.querySelector('.notification-dropdown');
if (notificationMenu) {
    document.addEventListener('click', (event) => {
        if (notificationMenu.open && !notificationMenu.contains(event.target)) notificationMenu.open = false;
    });
    notificationMenu.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !notificationMenu.open) return;
        notificationMenu.open = false;
        notificationMenu.querySelector('summary').focus();
    });
}
