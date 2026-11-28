<header class="instant-hero">
    <div>
        <span class="instant-tag">صلّحها الآن</span>
        <h1>{{ $heading }}</h1>
        @isset($description)<p>{{ $description }}</p>@endisset
    </div>
    @isset($badge)<span class="instant-tag instant-tag-light">{{ $badge }}</span>@endisset
</header>
