@extends('layouts.app')
@section('title', 'الملف الشخصي | ServiceHub')
@section('page_id', 'customer-settings')
@section('content')

@include('components.navbar')<div class="container layout">@include('components.sidebars.customer')<div><div class="dash-title"><div><span class="small muted">لوحة العميل</span><h1>الملف الشخصي</h1></div><span class="demo">بيانات تجريبية</span></div><div class="panel"><h3>الملف الشخصي</h3><p>تحديث الاسم ورقم الهاتف والموقع والصورة الشخصية.</p><form data-demo=""><div class="field"><label>الاسم الأول</label><input name="f0" placeholder="الاسم الأول" required="" type="text"/></div><div class="field"><label>اسم العائلة</label><input name="f1" placeholder="اسم العائلة" required="" type="text"/></div><button class="btn btn-yellow">حفظ تجريبي</button></form></div></div></div>@include('components.footer')@include('components.mobile-nav-customer')
@endsection
@extends('layouts.app')
@section('title', 'معلوماتي')

@section('content')
@include('pages.partials.market-assets')

@php $user = auth()->user(); @endphp

<div class="profile-layout">
    @include('pages.customer.menu')

    <section class="stack">
        <h2>معلوماتي</h2>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('customer.settings.update') }}" enctype="multipart/form-data" class="card">
            @csrf
            @method('PUT')

            <div class="grid two">
                <div class="field">
                    <label for="first_name">الاسم الأول</label>
                    <input class="input" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                </div>
                <div class="field">
                    <label for="last_name">اسم العائلة</label>
                    <input class="input" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                </div>
                <div class="field">
                    <label>البريد الإلكتروني</label>
                    <input class="input" dir="ltr" value="{{ $user->email }}" disabled>
                </div>
                <div class="field">
                    <label for="phone">رقم الهاتف</label>
                    <input class="input" id="phone" name="phone" dir="ltr" value="{{ old('phone', $user->phone) }}" placeholder="0791234567">
                </div>
                <div class="field">
                    <label for="location">المحافظة</label>
                    <select class="input" id="location" name="location">
                        <option value="">اختر المحافظة</option>
                        @foreach ($governorates as $governorate)
                            <option value="{{ $governorate }}" @selected(old('location', $user->location) === $governorate)>{{ $governorate }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="gender">الجنس</label>
                    <select class="input" id="gender" name="gender">
                        <option value="">غير محدد</option>
                        <option value="male"   @selected(old('gender', $user->gender) === 'male')>ذكر</option>
                        <option value="female" @selected(old('gender', $user->gender) === 'female')>أنثى</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="profile_image">الصورة الشخصية</label>
                <input class="input" id="profile_image" type="file" name="profile_image" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
        </form>
    </section>
</div>


@endsection