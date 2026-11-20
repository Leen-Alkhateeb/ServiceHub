@extends('layouts.app')
@section('title', 'الدفع التجريبي | ServiceHub')
@section('page_id', 'checkout')
@section('content')
    @include('components.navbar')
    <main>
        <div class="container page-head" style="max-width:640px">
            <div class="panel">
                <h1>الدفع</h1><span class="demo">لا يتم أي دفع حقيقي</span>
                <p>يبدأ عدّاد مهلة الدفع 30 دقيقة بعد موافقة الفني، وليس عند إرسال الطلب.</p>
                <form data-demo="">
                    <div class="field"><label>طريقة الدفع</label><select required="">
                            <option>دفع تجريبي</option>
                        </select></div><button class="btn btn-yellow">تأكيد الدفع التجريبي</button>
                </form>
            </div>
        </div>
    </main>@include('components.footer')@include('components.mobile-nav-public')
@endsection