@php($period = \App\Services\AdminPeriod::dates())
<section @class(['admin-period', 'admin-period-with-action' => $showCreateService ?? false]) aria-label="فترة بيانات لوحة الإدارة">
    <form method="POST" action="{{ route('admin.period') }}" class="admin-period-fields">
        @csrf
        <div class="field">
            <label for="period-from">من</label>
            <input type="date" name="period_from" id="period-from" value="{{ is_string(old('period_from')) ? old('period_from') : ($period['from'] ?? '') }}" min="1000-01-01" max="9999-12-31" @error('period_from') aria-invalid="true" aria-describedby="admin-period-error" @enderror>
        </div>
        <div class="field">
            <label for="period-to">إلى</label>
            <input type="date" name="period_to" id="period-to" value="{{ is_string(old('period_to')) ? old('period_to') : ($period['to'] ?? '') }}" min="1000-01-01" max="9999-12-31" @error('period_to') aria-invalid="true" aria-describedby="admin-period-error" @enderror>
        </div>
        <button class="btn btn-yellow" type="submit">تطبيق الفترة</button>
    </form>
    @if($period)
        <form method="POST" action="{{ route('admin.period') }}" class="admin-period-reset">
            @csrf
            <button class="btn btn-outline" type="submit">مسح الفترة</button>
        </form>
    @endif
    @if($showCreateService ?? false)
        <button type="button" class="services-btn services-btn-primary" data-service-open="create">+ إضافة خدمة جديدة</button>
    @endif
</section>
@if($errors->hasAny(['period_from', 'period_to']))
    <div class="admin-period-feedback">
        <p class="notice" id="admin-period-error" role="alert">{{ $errors->first('period_from') ?: $errors->first('period_to') }}</p>
    </div>
@endif
