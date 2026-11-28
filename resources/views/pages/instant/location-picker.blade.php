<div data-location-picker data-leaflet-css="{{ asset('assets/leaflet-1.9.4/leaflet.css') }}" data-leaflet-js="{{ asset('assets/leaflet-1.9.4/leaflet.js') }}">
    <button type="button" class="btn btn-outline" data-location-picker-open>اختار موقع الخدمة على الخريطة</button>
    <dialog class="hub-dialog instant-location-dialog" aria-labelledby="location-picker-title">
        <div class="dialog-head">
            <h2 id="location-picker-title">موقع الخدمة</h2>
            <x-modal-close data-location-picker-close />
        </div>
        <p>اضغط على موقع الخدمة أو حرّك الدبوس، ثم اعتمد الموقع.</p>
        <div class="instant-location-map" data-location-map aria-label="خريطة اختيار موقع الخدمة"></div>
        <div data-location-coordinates>
            <label>خط العرض <input type="text" inputmode="decimal" dir="ltr" data-location-latitude></label>
            <label>خط الطول <input type="text" inputmode="decimal" dir="ltr" data-location-longitude></label>
        </div>
        <p class="muted" role="status" data-location-picker-status></p>
        <button type="button" class="btn btn-yellow" data-location-picker-apply>اعتماد الموقع</button>
    </dialog>
</div>
