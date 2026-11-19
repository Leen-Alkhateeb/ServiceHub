@extends('layouts.app')
@section('title', 'نظرة عامة | ServiceHub')
@section('page_id', 'admin-dashboard')
@section('content')
    @include('components.navbar')
    <div class="container layout">@include('components.sidebars.admin')
        <div>
            <div class="dash-title">
                <div><span class="small muted">لوحة الإدارة</span>
                    <h1>نظرة عامة</h1>
                </div><span class="demo">بيانات تجريبية</span>
            </div>
            <div class="stats">
                <div class="stat"><strong>—</strong><span>المستخدمون</span></div>
                <div class="stat"><strong>—</strong><span>طلبات الفنيين</span></div>
                <div class="stat"><strong>—</strong><span>الحجوزات</span></div>
                <div class="stat"><strong>—</strong><span>الاعتراضات</span></div>
            </div>
            <div class="panel">
                <h3>نظرة عامة</h3>
                <p>مؤشرات المستخدمين والفنيين والحجوزات والمدفوعات.</p>
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