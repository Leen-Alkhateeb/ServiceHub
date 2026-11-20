@extends('layouts.app')

@section('title', 'الأقسام | ServiceHub')
@section('page_id', 'admin-categories')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <div>
            <div class="dash-title">
                <div>
                    <span class="small muted">لوحة الإدارة</span>
                    <h1>الأقسام</h1>
                </div>
                <a href="{{ route('admin.categories.create') }}" class="btn btn-yellow">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    إضافة قسم جديد
                </a>
            </div>

            <div class="panel">
                <h3>الأقسام</h3>
                <p>إدارة اسم القسم ووصفه وصورته وحالة الحذف.</p>

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
                            @forelse ($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>
                                        <span class="pill">نشط</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.categories.edit', $category->id) }}"
                                            class="btn btn-outline btn-sm" title="تعديل القسم" aria-label="تعديل القسم">
                                            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                        </a>

                                        <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                                            style="display: inline-block;"
                                            onsubmit="return confirm('هل أنت متأكد من حذف هذا القسم؟');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm" title="حذف القسم"
                                                aria-label="حذف القسم">
                                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">لا توجد أقسام حالياً</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
