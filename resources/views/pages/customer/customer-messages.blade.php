@extends('layouts.app')
@section('title', 'رسائل الدعم | ServiceHub')
@section('page_id', 'customer-messages')

    @include('components.navbar')<div class="container layout">@include('components.sidebars.customer')<div>

            <div class="dash-title">
                <div><span class="small muted">لوحة العميل</span>
                    <h1>رسائل الدعم</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <h3>رسائل الدعم</h3>
                <p>يمكن إرسال رسالة للإدارة؛ لا يوجد نظام رد داخلي.</p>
                <form data-demo="">
                    <div class="field"><label>الموضوع</label><input name="f0" placeholder="الموضوع" required=""
                            type="text" /></div>
                    <div class="field"><label>التفاصيل</label><input name="f1" placeholder="التفاصيل" required=""
                            type="text" /></div><button class="btn btn-yellow">حفظ تجريبي</button>
                </form>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-customer')

@endsection

@endsection

