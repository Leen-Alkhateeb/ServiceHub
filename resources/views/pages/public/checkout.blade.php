@extends('layouts.app')
@section('title', 'دفع عربون الحجز')
@section('page_id', 'checkout')
@section('content')
@include('components.navbar')
@php
    $total = round((float) $booking->original_price, 2);
    $deposit = round($total * 0.5, 2);
    $remaining = round($total - $deposit, 2);
    $deadlineIso = $booking->payment_deadline?->toIso8601String();
@endphp
<style>
.checkout-page{direction:rtl;min-height:75vh;padding:36px 16px 60px;background:#f5f8f6;color:#18342e}.checkout-wrap{width:min(980px,100%);margin:auto}.checkout-back{display:inline-block;margin-bottom:18px;color:#176b5b;text-decoration:none;font-weight:700}.checkout-head{margin-bottom:22px}.checkout-head h1{margin:0 0 7px;font-size:clamp(27px,4vw,38px)}.checkout-head p{margin:0;color:#71817c}.checkout-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}.checkout-card{padding:24px;border:1px solid #e4ece8;border-radius:16px;background:#fff;box-shadow:0 8px 24px #18342e0a}.checkout-card h2{margin:0 0 18px;font-size:20px}.checkout-row{display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid #e4ece8}.checkout-row span{color:#71817c}.checkout-row strong{text-align:left}.breakdown{display:grid;gap:10px;margin-top:18px;padding:16px;border-radius:12px;background:#f1f7f3}.breakdown div{display:flex;justify-content:space-between;gap:12px}.breakdown .deposit{color:#176b5b;font-weight:800;font-size:18px}.breakdown .remaining{color:#765918;font-weight:700}.field{margin:0 0 16px}.field label{display:block;margin-bottom:7px;font-weight:700}.field input{width:100%;min-height:48px;padding:11px 13px;border:1px solid #dce7e2;border-radius:10px;font:inherit}.field input[dir=ltr]{text-align:left}.pay-button{width:100%;min-height:50px;border:0;border-radius:10px;color:white;background:#176b5b;font:inherit;font-weight:800;cursor:pointer}.deadline{margin:14px 0 0;color:#71817c;text-align:center}.timer{color:#176b5b;font-weight:800}.expired{display:none;padding:12px;border-radius:10px;background:#fff1f1;color:#922}.errors{margin-bottom:16px;padding:12px;border-radius:10px;background:#fff1f1;color:#922}@media(max-width:720px){.checkout-grid{grid-template-columns:1fr}.checkout-card{padding:19px}}
</style>
<main class="checkout-page"><div class="checkout-wrap">
<a class="checkout-back" href="{{ route('market.index') }}">← العودة إلى الخدمات</a>
<header class="checkout-head"><h1>تأكيد الحجز والدفع</h1><p>ادفع 50٪ الآن، والمبلغ المتبقي عند وصول مقدّم الخدمة.</p></header>
@if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
<div class="checkout-grid">
<section class="checkout-card"><h2>ملخص الحجز</h2>
<div class="checkout-row"><span>الخدمة</span><strong>{{ $booking->service_name }}</strong></div>
<div class="checkout-row"><span>مقدّم الخدمة</span><strong>{{ $booking->vendorService->vendor->first_name }} {{ $booking->vendorService->vendor->last_name }}</strong></div>
<div class="checkout-row"><span>الموعد</span><strong>{{ $booking->booking_date->format('Y-m-d') }} · {{ substr($booking->start_time,0,5) }}</strong></div>
<div class="checkout-row"><span>قيمة الخدمة كاملة</span><strong>{{ number_format($total,2) }} د.أ</strong></div>
<div class="breakdown"><div class="deposit"><span>العربون الآن (50٪)</span><strong>{{ number_format($deposit,2) }} د.أ</strong></div><div class="remaining"><span>المتبقي عند الاستلام</span><strong>{{ number_format($remaining,2) }} د.أ</strong></div></div>
</section>
<section class="checkout-card"><h2>تأكيد دفع العربون</h2>
<div class="expired" id="expired-message">انتهت مهلة الدفع. ارجع للحجوزات واختر موعدًا آخر.</div>
<form id="checkout-form" method="POST" action="{{ route('payment.store',$booking) }}">@csrf<input type="hidden" name="payment_method" value="card">
<div class="field"><label for="card_name">الاسم على البطاقة</label><input id="card_name" name="card_name" value="{{ old('card_name') }}" autocomplete="cc-name"></div>
<div class="field"><label for="card_number">رقم البطاقة</label><input id="card_number" name="card_number" dir="ltr" inputmode="numeric" autocomplete="cc-number" maxlength="19" placeholder="4242 4242 4242 4242"></div>
<button class="pay-button" id="pay-button" type="submit">تأكيد العربون {{ number_format($deposit,2) }} د.أ</button>
<p class="deadline">الوقت المتبقي للدفع: <span class="timer" id="countdown">30:00</span></p>
</form></section></div></div></main>
<script>
(()=>{const deadline=@json($deadlineIso);if(!deadline)return;const end=new Date(deadline).getTime(),label=document.getElementById('countdown'),form=document.getElementById('checkout-form'),expired=document.getElementById('expired-message'),button=document.getElementById('pay-button');function tick(){let sec=Math.max(0,Math.floor((end-Date.now())/1000));label.textContent=String(Math.floor(sec/60)).padStart(2,'0')+':'+String(sec%60).padStart(2,'0');if(sec<=0){form.style.display='none';expired.style.display='block';clearInterval(timer)}}let timer;tick();timer=setInterval(tick,1000)})();
</script>
@include('components.footer')
@include('components.mobile-nav-public')
@endsection


