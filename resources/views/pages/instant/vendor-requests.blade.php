<section class="panel stack">
    <div class="instant-row"><h2>طلبات قريبة</h2><a class="btn btn-outline btn-sm" href="{{ route('instant.vendor') }}">تحديث الطلبات</a></div>
    <div class="instant-request-list" aria-label="الطلبات القريبة">
    @forelse($matches as $candidate)
            @php($offer = $enabled[$candidate->service_id])
            @php($location = $locations[$candidate->id])
            @php($estimate = $estimates[$location['point']] ?? null)
            <article class="instant-request-card">
                <iframe class="instant-request-map" title="الموقع التقريبي للطلب {{ $candidate->id }}" src="{{ $location['map'] }}" loading="lazy" referrerpolicy="no-referrer"></iframe>
                <div class="instant-request-body stack">
                    <div class="instant-request-location">
                        <strong>موقع الطلب التقريبي</strong>
                        @if($estimate)
                            <span>بالسيارة · {{ $estimate['km'] < 0.1 ? 'أقل من 0.1' : $estimate['km'] }} كم · حوالي {{ $estimate['minutes'] }} د</span>
                        @else
                            <span>تقدير المسافة والوقت غير متاح حاليًا</span>
                        @endif
                    </div>
                    <h3>{{ $offer->service->name }} · #{{ $candidate->id }}</h3>
                    <p>{{ $candidate->description }}</p>
                    @foreach($images->get($candidate->id, []) as $image)
                        <a href="{{ route('instant.image', [$candidate, $image]) }}">تحميل صورة المشكلة {{ $loop->iteration }}</a>
                    @endforeach
                    <details class="instant-request-details">
                    <summary class="btn btn-outline btn-sm">قدّم عرض</summary>
                    <form method="POST" action="{{ route('instant.offer', $candidate) }}" class="instant-request-offer">
                        @csrf<input type="hidden" name="vendor_service_id" value="{{ $offer->id }}">
                        <div class="field"><label for="price-{{ $candidate->id }}">سعرك بالدينار الأردني</label><input id="price-{{ $candidate->id }}" name="offered_price" type="number" min="0.001" max="9999999.999" step="0.001" required><small>حصتك العادية 85٪ من السعر المدفوع.</small></div>
                        <div class="field"><label for="arrival-{{ $candidate->id }}">الوصول بالدقائق</label><input id="arrival-{{ $candidate->id }}" name="arrival_minutes" type="number" min="1" max="1439" required></div>
                        <div class="field"><label for="duration-{{ $candidate->id }}">مدة العمل بالدقائق</label><input id="duration-{{ $candidate->id }}" name="duration_minutes" type="number" min="5" max="1440" required></div>
                        <div class="instant-request-submit"><button class="btn btn-yellow">إرسال عرض</button></div>
                    </form>
                    </details>
                </div>
            </article>
        @empty
            @if(!$profile->work_permit_path || !$profile->permit_expiry_date || $profile->permit_expiry_date < today(\App\Models\Booking::SCHEDULE_TIMEZONE)->toDateString())
                <p class="muted">استقبال الطلبات الفورية يحتاج تصريح عمل ساري ومسجّل. راجع بيانات التصريح مع الإدارة.</p>
            @elseif(!$profile->instant_available)
                <p class="muted">استقبال الطلبات متوقف. اضغط «تفعيل استقبال الطلبات» عشان تشوف الطلبات القريبة.</p>
            @elseif($enabled->isEmpty())
                <p class="muted">فعّل الاستقبال الفوري لخدمة مؤهلة عشان تستقبل طلباتها.</p>
            @else
                <p class="muted">ما في طلبات مطابقة لموقعك وتوفّرك حاليًا.</p>
            @endif
        @endforelse
    </div>
    @if($matches->isNotEmpty())
        <small class="muted">تقدير القيادة مبني على شبكة الطرق. <a href="https://www.openstreetmap.org/copyright">© مساهمو OpenStreetMap</a> · <a href="https://www.openstreetmap.org/fixthemap">تصحيح الخريطة</a></small>
    @endif
    </section>
