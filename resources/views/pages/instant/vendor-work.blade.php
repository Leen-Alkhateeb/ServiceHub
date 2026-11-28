    <section class="panel">
        <h2>عروضك المعلقة</h2>
        @forelse($offers as $offer)
            <form class="instant-pending-offer" method="POST" action="{{ route('instant.withdraw', $offer) }}">
                @csrf
                <div class="instant-pending-offer-details">
                    <strong>طلب <bdi>#{{ $offer->instant_request_id }}</bdi></strong>
                    <span class="muted"><bdi>{{ $offer->offered_price }}</bdi> د.أ</span>
                </div>
                <button type="submit" class="btn btn-outline btn-sm">سحب العرض</button>
            </form>
        @empty<p>ما في عروض معلقة.</p>@endforelse
        {{ $offers->links('components.pager') }}
    </section>
    @include('pages.instant.history')
