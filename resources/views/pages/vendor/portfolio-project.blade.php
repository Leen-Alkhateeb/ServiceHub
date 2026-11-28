@extends('layouts.app')
@section('title', $project->name.'')
@section('page_id', 'portfolio-project')
@section('content')
@include('components.navbar')
<main @class(['container project-page', 'vendor-project-page' => $isOwner])>
    <a class="project-back" href="{{ route('vendor.profile', ['tab' => 'portfolio'] + ($isOwner ? [] : ['vendor' => $project->vendor_id])) }}#portfolio">→ العودة إلى معرض الأعمال</a>
    @if (session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
    <div class="product-project">
        <section class="project-media" aria-label="صور المشروع" data-project-gallery>
            @php
                $photoUrls = $images->getCollection()->map(fn ($photo) => $photo->displayUrl())->filter()->values();
            @endphp
            @if ($photoUrls->isNotEmpty())
                <a class="project-stage" data-project-stage href="{{ $photoUrls->first() }}" target="_blank" rel="noopener">
                    <img src="{{ $photoUrls->first() }}" alt="{{ $project->name }} · صورة المشروع 1" width="1600" height="1200" data-project-main>
                    <span class="project-stage-action">عرض بالحجم الكامل <span aria-hidden="true">↗</span></span>
                </a>
                @if ($photoUrls->count() > 1)
                    <div class="project-thumbnails">
                        @foreach ($photoUrls as $photoUrl)
                            <button type="button" data-project-photo="{{ $photoUrl }}" data-photo-alt="{{ $project->name }} · صورة المشروع {{ $loop->iteration }}" aria-label="عرض الصورة {{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                <img src="{{ $photoUrl }}" alt="" width="160" height="120" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="project-stage portfolio-placeholder">لا توجد صور متاحة لهذا المشروع</div>
            @endif
            {{ $images->onEachSide(1)->links('components.pager') }}
        </section>
        <section class="project-info" aria-labelledby="project-title">
            <span class="eyebrow">{{ $project->vendor->vendorProfile?->work_type ?? 'معرض الأعمال' }}</span>
            <h1 id="project-title">{{ $project->name }}</h1>
            @if ($isOwner)
                @include('pages.vendor.partials.project-management')
            @endif
            <dl class="project-key-details">
                <div>
                    <dt>قيمة المشروع</dt>
                    <dd>@if ($project->project_price !== null)<strong class="project-price"><bdi>{{ number_format($project->project_price, 2) }}</bdi> د.أ</strong>@else غير مذكورة @endif</dd>
                </div>
                @if ($project->completion_date)
                    <div><dt>تاريخ الإنجاز</dt><dd><time datetime="{{ $project->completion_date }}">{{ $project->completion_date }}</time></dd></div>
                @endif
                <div><dt>الموقع</dt><dd>{{ $project->vendor->location }}</dd></div>
            </dl>
            <section class="project-copy">
                <h2>تفاصيل العمل</h2>
                @foreach (preg_split('/\R\s*\R/u', trim($project->description), -1, PREG_SPLIT_NO_EMPTY) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </section>
        </section>
    </div>
</main>
@include('components.footer')
@include($isOwner ? 'components.mobile-nav-vendor' : 'components.mobile-nav-public')
@endsection
