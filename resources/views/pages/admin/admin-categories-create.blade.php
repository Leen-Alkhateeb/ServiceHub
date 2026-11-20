@extends('layouts.app')

@section('title', 'إضافة قسم | ServiceHub')
@section('page_id', 'admin-categories')

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <div class="panel">
            <h2>إضافة قسم جديد</h2>

            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">

                @csrf

                <div class="field">
                    <label for="name">اسم القسم</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <span style="color: #b42318">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="description">وصف القسم</label>
                    <textarea id="description" name="description" required>{{ old('description') }}</textarea>
                    @error('description')
                        <span style="color: #b42318">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="image">صورة القسم</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                    @error('image')
                        <span style="color: #b42318">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-yellow">
                    إضافة القسم
                </button>
            </form>
        </div>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection
