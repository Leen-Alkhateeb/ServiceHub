@extends('layouts.app')

@section('title', 'تعديل قسم | ServiceHub')
@section('page_id', 'admin-categories')

@section('content')
    @include('components.navbar')

    <div class="container layout">
        @include('components.sidebars.admin')

        <div class="panel">
            <h2>تعديل القسم</h2>

            <form action="{{ route('admin.categories.update', $category->id) }}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <div class="field">
    <label for="name">اسم القسم</label>
    <input
        type="text"
        id="name"
        name="name"
        value="{{ old('name', $category->name) }}"
        required
    >
    @error('name')
        <span style="color: #b42318">{{ $message }}</span>
    @enderror
</div>

<div class="field">
    <label for="description">وصف القسم</label>
    <textarea
        id="description"
        name="description"
        required
    >{{ old('description', $category->description) }}</textarea>
    @error('description')
        <span style="color: #b42318">{{ $message }}</span>
    @enderror
</div>

<div class="field">
    <label for="image">تغيير صورة القسم (اختياري)</label>
    <input
        type="file"
        id="image"
        name="image"
        accept="image/*"
    >
    @error('image')
        <span style="color: #b42318">{{ $message }}</span>
    @enderror
</div>

<button type="submit" class="btn btn-yellow">
    حفظ التعديلات
</button>
            </form>
        </div>
    </div>

    @include('components.footer')
    @include('components.mobile-nav-admin')
@endsection