<section class="panel">
    <h2>الحجوزات الفورية</h2>
    @forelse($bookings as $item)
        <p><a href="{{ route('instant.booking', $item) }}">#{{ $item->id }} · {{ $item->service_name }}</a></p>
    @empty<p class="muted">ما في حجوزات فورية.</p>@endforelse
    {{ $bookings->links('components.pager') }}
</section>
