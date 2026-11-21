@extends('layouts.app')
@section('title', 'ملف الفني | ServiceHub')
@section('page_id', 'vendor-profile')
@section('content')
@include('components.navbar')
<main @class(['container', 'layout vendor-profile-layout' => $ownProfile, 'page-head' => ! $ownProfile])>
    @if ($ownProfile)
        @include('components.sidebars.vendor')
    @endif
    <div class="technician-profile dashboard-content">
    <header class="technician-hero">
        <x-account-picture :account="$profile" class="technician-avatar" aria-hidden="true" />
        <div class="technician-heading">
            @if ($profile->application_status === 'approved')<span class="pill yellow">فني معتمد</span>@endif
            <h1>{{ $profile->first_name }} {{ $profile->last_name }}</h1>
            <p class="technician-trade">{{ $profile->work_type }}</p>
            @if ($ownProfile && $profile->application_status !== 'approved')
                <a class="btn btn-outline btn-sm" href="{{ route('vendor.apply') }}">حالة طلب الانضمام</a>
            @endif
            <p>{{ $profile->bio }}</p>
            <ul class="profile-facts">
                <li>{{ $profile->location }}</li>
                <li>{{ $profile->experience_years }} سنوات خبرة</li>
                @if ($rating !== null)
                    @php
                        $profileStars = max(0, min(5, (int) round($rating)));
                    @endphp
                    <li class="rating-display" aria-label="{{ number_format((float) $rating, 1) }} من 5">{{ str_repeat('★', $profileStars).str_repeat('☆', 5 - $profileStars) }}</li>
                @endif
            </ul>
            @if ($ownProfile)
                @include('components.account-picture-editor')
                @include('components.account-details-editor', ['vendorDetails' => $profile])
            @endif
        </div>
    </header>
    <x-account-tabs :tab="$tab" :tabs="$tabs" route-name="vendor.profile" :route-params="$ownProfile ? [] : ['vendor' => $profile->id]" />

    @if ($tab === 'services')
    <section class="section" id="services">
        <div class="section-head">
            <h2>الخدمات المتاحة</h2>
            @if ($ownProfile)
                <div class="moderation-actions">
                    <button type="button" class="btn btn-yellow" data-open-dialog="offer-form">إضافة خدمة</button>
                    <button type="button" class="btn btn-outline" data-open-dialog="suggestion-form">اقتراح خدمة</button>
                </div>
            @endif
        </div>
        @if (session('offer_status'))<div class="notice" role="status">{{ session('offer_status') }}</div>@endif
        <div class="grid">
            @forelse ($services as $service)
                <article class="panel">
                    @if ($service->image_url)
                        <img class="offer-image" src="{{ $service->image_url }}" alt="" width="480" height="270" loading="lazy" data-optional-image>
                    @endif
                    <h3>{{ $service->name }}</h3>
                    <p>{{ $service->description ?: $service->default_description }}</p>
                    <strong class="price">{{ number_format($service->price, 2) }} د.أ</strong>
                    @if ($service->duration_minutes)<p class="muted">{{ $service->duration_minutes }} دقيقة تقريبًا</p>@endif
                    <button type="button" class="btn btn-outline" data-service-dialog
                        data-name="{{ $service->name }}"
                        data-price="{{ number_format($service->price, 2) }} د.أ"
                        data-duration="{{ $service->duration_minutes ? $service->duration_minutes.' دقيقة تقريبًا' : '' }}"
                        data-image="{{ $service->image_url }}"
                        data-description="{{ $service->description ?: $service->default_description }}"
                        data-reviews-url="{{ route('vendor.service.reviews', $service->id) }}">التفاصيل والتقييمات</button>
                    @if ($ownProfile)
                        <div class="moderation-actions">@include('pages.vendor.partials.offer-management', ['offer' => $service])</div>
                    @endif
                </article>
            @empty
                <p class="muted">لا توجد خدمات متاحة.</p>
            @endforelse
        </div>
        {{ $services->fragment('services')->onEachSide(1)->links('components.pager') }}
        @if ($ownProfile && $requests->isNotEmpty())
            <section class="panel offer-history">
                <h3>طلبات واقتراحات الخدمات</h3>
                @foreach ($requests as $requestRow)
                    <article class="item-row">
                        <div><strong>{{ $requestRow->service->name }}</strong>
                            @if ($requestRow->rejection_reason)<p>{{ $requestRow->rejection_reason }}</p>@endif
                        </div>
                        <span class="hub-status" data-status="{{ $requestRow->status === 'pending' ? 'held' : 'issue' }}">{{ $requestRow->status === 'pending' ? 'قيد المراجعة' : 'مرفوض' }}</span>
                        <div class="moderation-actions">@include('pages.vendor.partials.offer-management', ['offer' => $requestRow])</div>
                    </article>
                @endforeach
                {{ $requests->fragment('services')->onEachSide(1)->links('components.pager') }}
            </section>
        @endif
    </section>
    @endif

    @if ($tab === 'portfolio')
    <section class="section" id="portfolio">
        <div class="section-head">
            <h2>معرض الأعمال</h2>
            @if ($ownProfile)
                <button type="button" class="btn btn-yellow" data-open-dialog="project-form">إضافة مشروع</button>
            @endif
        </div>
        @if (session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
        <div class="grid">
            @forelse ($projects as $project)
                <a class="card portfolio-card" href="{{ route('vendor.portfolio.show', $project->id) }}">
                    @if ($coverUrl = $project->coverImage?->displayUrl())
                        <img class="portfolio-cover" src="{{ $coverUrl }}" alt="" width="800" height="600" loading="lazy">
                    @endif
                    <div class="card-body">
                        <h3>{{ $project->name }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit($project->description, 110) }}</p>
                        @if ($project->completion_date)<time datetime="{{ $project->completion_date }}">{{ $project->completion_date }}</time>@endif
                        @if ($project->project_price !== null)<strong class="price">{{ number_format($project->project_price, 2) }} د.أ</strong>@endif
                        <span class="btn btn-outline btn-sm portfolio-details">عرض التفاصيل</span>
                    </div>
                </a>
            @empty
                <p class="muted">لا توجد أعمال سابقة.</p>
            @endforelse
        </div>
        {{ $projects->fragment('portfolio')->onEachSide(1)->links('components.pager') }}
    </section>
    @endif
    @if ($entries)
        <x-account-history :tab="$tab" :entries="$entries" :filters="$filters" :categories="$historyCategories" route-name="vendor.profile" />
    @endif
    </div>
</main>

<dialog class="hub-dialog" id="service-details-dialog" aria-labelledby="service-dialog-title">
    <div class="dialog-head">
        <h2 id="service-dialog-title"></h2>
        <button type="button" class="btn btn-outline btn-sm" data-close-dialog>إغلاق</button>
    </div>
    <img class="offer-image" alt="" width="480" height="270" data-service-image data-optional-image hidden>
    <p data-service-description></p>
    <strong class="price" data-service-price></strong>
    <p class="muted" data-service-duration hidden></p>
    <h3>تقييمات العملاء</h3>
    <div class="service-review-scroll" tabindex="0" aria-label="قائمة تقييمات العملاء">
        <div data-review-list></div>
        <p data-review-status role="status"></p>
        <button class="btn btn-outline btn-sm" type="button" data-more-reviews hidden>تحميل المزيد</button>
    </div>
</dialog>

@if ($ownProfile && $tab === 'services')
    @include('pages.vendor.partials.service-request-dialog')
    @include('pages.vendor.partials.service-suggestion-dialog')
@endif
@if ($ownProfile && $tab === 'portfolio')
    <dialog class="hub-dialog" id="project-form" aria-labelledby="project-form-title" @if ($errors->portfolio->any()) data-auto-dialog @endif>
        <div class="dialog-head">
            <h2 id="project-form-title">إضافة مشروع إلى معرض الأعمال</h2>
            <button type="button" class="btn btn-outline btn-sm" data-close-dialog>إغلاق</button>
        </div>
        @if ($errors->portfolio->any())
            <ul class="notice" role="alert">
                @foreach ($errors->portfolio->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('vendor.portfolio.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="field"><label for="project-name">اسم المشروع</label><input id="project-name" name="name" maxlength="255" value="{{ is_string(old('name')) ? old('name') : '' }}" required></div>
            <div class="field"><label for="project-description">وصف المشروع</label><textarea id="project-description" name="description" rows="4" maxlength="5000" required>{{ is_string(old('description')) ? old('description') : '' }}</textarea></div>
            <div class="field"><label for="project-price">قيمة المشروع (د.أ)</label><input id="project-price" name="project_price" type="number" min="0" max="99999999.99" step="0.01" value="{{ is_scalar(old('project_price')) ? old('project_price') : '' }}"></div>
            <div class="field"><label for="project-date">تاريخ الإنجاز</label><input type="date" id="project-date" name="completion_date" max="{{ now()->toDateString() }}" value="{{ is_string(old('completion_date')) ? old('completion_date') : '' }}"></div>
            @include('pages.vendor.partials.project-image-fields', ['projectImages' => collect(), 'requiresImage' => true])
            <button class="btn btn-yellow" type="submit">حفظ المشروع</button>
        </form>
    </dialog>
@endif
@include('components.footer')
@include($ownProfile ? 'components.mobile-nav-vendor' : 'components.mobile-nav-public')
@endsection
