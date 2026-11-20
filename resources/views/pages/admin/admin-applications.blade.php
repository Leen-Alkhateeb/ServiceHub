@extends('layouts.app')
@section('title', 'طلبات انضمام الفنيين | ServiceHub')
@section('page_id', 'admin-applications')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.admin')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة الإدارة</span>
                    <h1>طلبات انضمام الفنيين</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="panel">
                <h3>طلبات انضمام الفنيين</h3>
                <p>مراجعة تصريح العمل وتاريخ انتهائه والموافقة أو الرفض.</p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>الرقم</th>
                                <th>العنصر</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>—</td>
                                <td>لا توجد بيانات متصلة</td>
                                <td><span class="pill yellow">نموذج واجهة</span></td>
                                <td>—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>@include('components.footer')@include('components.mobile-nav-admin')
@endsection