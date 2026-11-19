@extends('layouts.app')
@section('title', 'تفاصيل الحجز | ServiceHub')
@section('page_id', 'booking-details')
@section('content')
@include('components.navbar')<main><div class="container page-head" style="max-width:780px"><div class="panel" id="booking-detail"></div><div class="panel" style="margin-top:16px"><h3>الاعتراض بعد الإنجاز</h3><p>خلال 24 ساعة من اكتمال الخدمة، يمكن تقديم اعتراض واحد لكل حجز.</p><a class="btn btn-outline" href="/customer-refund">تقديم اعتراض</a></div></div></main>@include('components.footer')@include('components.mobile-nav-public')
@endsection
