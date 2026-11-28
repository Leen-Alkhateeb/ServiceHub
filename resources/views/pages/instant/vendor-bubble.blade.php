<x-public-stylesheet file="instant-vendor-bubble.css" />
<a class="instant-vendor-bubble" href="{{ route('instant.vendor') }}"
    data-instant-inbox="{{ route('instant.vendor.inbox-count') }}" aria-label="الطلبات الفورية" title="الطلبات الفورية">
    <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="5" y="4" width="14" height="17" rx="3" />
        <path d="M9 4V3h6v1m-6 6h6m-6 4h6m-6 4h3" />
    </svg>
    <span data-instant-inbox-count hidden aria-hidden="true"></span>
</a>
<script type="module" src="{{ asset('assets/instant-vendor-bubble.js') }}?v={{ filemtime(public_path('assets/instant-vendor-bubble.js')) }}"></script>
