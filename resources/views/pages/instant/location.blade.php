<div>
    <input type="hidden" name="latitude" value="{{ old('latitude') }}">
    <input type="hidden" name="longitude" value="{{ old('longitude') }}">
    <button type="button" class="btn btn-outline" data-instant-locate>استخدم موقعي الحالي</button>
    @if(app()->environment('testing') && request()->getHost() === '127.0.0.1' && str_starts_with((string) config('database.connections.mysql.database'), 'servicehub_now_preview_'))
        <button type="button" class="btn btn-outline" data-instant-preview-location>استخدم موقع عمّان التجريبي</button>
    @endif
    <p class="muted" data-instant-location-status role="status"></p>
</div>
