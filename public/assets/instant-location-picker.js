const picker = document.querySelector('[data-location-picker]');

if (picker) {
    const form = picker.closest('form');
    const dialog = picker.querySelector('dialog');
    const surface = picker.querySelector('[data-location-map]');
    const latitude = picker.querySelector('[data-location-latitude]');
    const longitude = picker.querySelector('[data-location-longitude]');
    const status = picker.querySelector('[data-location-picker-status]');
    const amman = [31.95, 35.91];
    let map;
    let marker;
    let loading;

    const coordinates = (lat, lng) => {
        if (!lat.trim() || !lng.trim()) return null;
        const point = [Number(lat), Number(lng)];
        return point.every(Number.isFinite) && Math.abs(point[0]) <= 90 && Math.abs(point[1]) <= 180 ? point : null;
    };

    const select = ([lat, lng]) => {
        latitude.value = lat.toFixed(6);
        longitude.value = lng.toFixed(6);
        marker?.setLatLng([lat, lng]);
        status.textContent = '';
    };

    function loadMap() {
        if (loading) return loading;
        loading = Promise.all([
            ['link', { rel: 'stylesheet', href: picker.dataset.leafletCss }],
            ['script', { src: picker.dataset.leafletJs }],
        ].map(([tag, attributes]) => new Promise((resolve, reject) => {
            const asset = Object.assign(document.createElement(tag), attributes);
            asset.onload = resolve;
            asset.onerror = reject;
            document.head.append(asset);
        })));
        return loading;
    }

    picker.querySelector('[data-location-picker-open]').addEventListener('click', async () => {
        const initial = coordinates(form.elements.latitude.value, form.elements.longitude.value) ?? amman;
        select(initial);
        dialog.showModal();
        if (map) {
            map.invalidateSize().setView(initial, map.getZoom());
            return;
        }
        status.textContent = 'جارٍ تحميل الخريطة…';
        try {
            await loadMap();
            if (!dialog.open || map) return;
            const leaflet = window.L;
            const center = coordinates(latitude.value, longitude.value) ?? initial;
            map = leaflet.map(surface).setView(center, 13);
            leaflet.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).on('tileerror', () => {
                if (!status.textContent) status.textContent = 'تعذّر تحميل بعض أجزاء الخريطة. بإمكانك إدخال الإحداثيات مباشرةً.';
            }).addTo(map);
            marker = leaflet.marker(center, {
                draggable: true,
                title: 'موقع الخدمة',
                icon: leaflet.divIcon({ className: 'instant-location-pin', iconSize: [22, 22], iconAnchor: [11, 11] }),
            }).addTo(map);
            const selectPoint = ({ lat, lng }) => select([lat, ((lng + 180) % 360 + 360) % 360 - 180]);
            map.on('click', ({ latlng }) => selectPoint(latlng));
            marker.on('dragend', () => selectPoint(marker.getLatLng()));
            status.textContent = '';
        } catch {
            status.textContent = 'الخريطة غير متاحة حاليًا. بإمكانك إدخال الإحداثيات مباشرةً.';
        }
    });

    picker.querySelector('[data-location-picker-close]').addEventListener('click', () => dialog.close());
    picker.querySelector('[data-location-picker-apply]').addEventListener('click', () => {
        const point = coordinates(latitude.value, longitude.value);
        if (!point) {
            status.textContent = 'أدخل خط عرض وخط طول صالحين.';
            return;
        }
        form.elements.latitude.value = point[0].toFixed(6);
        form.elements.longitude.value = point[1].toFixed(6);
        form.dataset.instantLocationManual = 'true';
        form.querySelector('[data-instant-location-status]').textContent = 'تم اعتماد موقع الخدمة المختار.';
        dialog.close();
    });
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.matches('input')) event.preventDefault();
    });
}
