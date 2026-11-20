@props(['category'])
<a class="card directory-card" href="{{ route('categories.show', $category->id) }}">
    <x-directory-photo :picture="$category->image" :trade="$category->name" kind="category" />
    <div class="directory-card-row">
        <div class="card-body">
            <h3>{{ $category->name }}</h3>
            <p>{{ $category->description }}</p>
        </div>
        <span class="directory-arrow" aria-hidden="true">←</span>
    </div>
</a>
