const bubble = document.querySelector('[data-instant-inbox]');
if (bubble) {
    const badge = bubble.querySelector('[data-instant-inbox-count]');
    let updating = false;

    async function refreshCount() {
        if (document.hidden || updating) return;
        updating = true;
        try {
            const response = await fetch(bubble.dataset.instantInbox, { cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const { count } = await response.json();
            if (!Number.isInteger(count) || count < 0) return;
            badge.hidden = count === 0;
            badge.textContent = count > 99 ? '99+' : String(count);
            bubble.setAttribute('aria-label', count ? 'الطلبات الفورية، '+badge.textContent+' طلب متاح' : 'الطلبات الفورية');
        } catch {
            // Keep the last count until the connection comes back.
        } finally {
            updating = false;
        }
    }

    refreshCount();
    setInterval(refreshCount, 5000);
    window.addEventListener('online', refreshCount);
    document.addEventListener('visibilitychange', refreshCount);
}
