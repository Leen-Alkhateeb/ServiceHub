<div>
    <input type="hidden" name="latitude" value="{{ old('latitude') }}">
    <input type="hidden" name="longitude" value="{{ old('longitude') }}">
    <button type="button" class="btn btn-outline" data-instant-locate>استخدم موقعي الحالي</button>
    @php($localAmman = app()->environment('local') && in_array(request()->getHost(), ['127.0.0.1', 'localhost'], true) && config('instant.local_amman_default'))
    @if($localAmman || (app()->environment('testing') && request()->getHost() === '127.0.0.1' && str_starts_with((string) config('database.connections.mysql.database'), 'servicehub_now_preview_')))
        <button type="button" class="btn btn-outline" data-instant-preview-location @if($localAmman) data-instant-location-default @endif>استخدم موقع عمّان التجريبي</button>
        @if($localAmman)<p class="muted">موقع عمّان هو الاختيار الافتراضي للتجربة المحلية. اضغط «استخدم موقعي الحالي» للتبديل.</p>@endif
    @endif
    <p class="muted" data-instant-location-status role="status"></p>
</div>
