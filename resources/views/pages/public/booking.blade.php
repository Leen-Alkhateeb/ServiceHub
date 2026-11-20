@extends('layouts.app')
@section('title', 'حجز خدمة | ServiceHub')
@section('page_id', 'booking')
@section('content')
    @include('components.navbar')
    <main>
        <div class="container page-head">
            <h1>احجز الموعد المناسب</h1>
            <div class="steps"><span class="step active">1 · الخدمة</span><span class="step">2 · الموعد</span><span
                    class="step">3 · التفاصيل</span><span class="step">4 · المراجعة</span><span class="step">5 ·
                    الحالة</span><span class="step">6 · الدفع</span><span class="step">7 · الإنجاز</span></div>
            <div class="split">
                <div class="panel">
                    <h3>الخدمة المختارة</h3>
                    <div id="booking-service"></div>
                    <hr style="border:0;border-top:1px solid #eee;margin:20px 0" />
                    <form id="booking-form">
                        <div class="field"><label>تاريخ الحجز</label><input name="date" required="" type="date" /></div>
                        <div class="field"><label>فترة التوفر الأسبوعية (نموذج تجريبي)</label><select name="time"
                                required="">
                                <option value="">اختار فترة</option>
                                <option>09:00–12:00</option>
                                <option>12:00–15:00</option>
                                <option>15:00–18:00</option>
                            </select></div>
                        <div class="notice">الحجز يحجز نافذة التوفر كاملة في التاريخ المحدد. التحقق الفعلي من التعارضات
                            يتطلب Laravel.</div><button class="btn btn-yellow">إرسال طلب الحجز</button>
                    </form>
                </div>
                <div class="panel">
                    <h3>ملخص الحجز</h3>
                    <p>الفني يراجع الطلب قبل تأكيده.</p>
                    <div class="item-row"><b>السعر الأصلي</b><b id="booking-price"></b></div>
                    <p class="muted small">بعد الموافقة: مهلة دفع 30 دقيقة.</p>
                </div>
            </div>
        </div>
        <div style="height:70px"></div>
    </main>@include('components.footer')@include('components.mobile-nav-public')
@endsection