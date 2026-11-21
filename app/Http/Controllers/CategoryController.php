<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string']]);
        $search = trim((string) $request->query('q', ''));

        $query = Category::where('is_deleted', false)
            ->withCount(['services' => function ($services) {
                $services->where('is_deleted', false);
            }]);

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $categories = $query->latest('id')->paginate(10)->withQueryString();

        $totalCategories = Category::where('is_deleted', false)->count();

        return view('pages.admin.admin-categories', compact('categories', 'search', 'totalCategories'));
    }

    public function create()
    {
        return view('pages.admin.admin-categories-create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('is_deleted', 0),
            ],
            'description' => ['required', 'string', 'max:1000'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->validationMessages());

        $validated['image'] = $request->file('image')->store('categories', 'public');

        Category::create($validated);

        return redirect()
            ->route('admin.categories')
            ->with('success', 'تمت إضافة القسم بنجاح.');
    }

    public function edit(Category $category)
    {
        abort_if($category->is_deleted, 404);

        return view('pages.admin.admin-categories-edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        abort_if($category->is_deleted, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('is_deleted', 0)->ignore($category->id),
            ],
            'description' => ['required', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->validationMessages());

        if ($request->hasFile('image')) {
            $oldImage = $category->image;

            $validated['image'] = $request->file('image')->store('categories', 'public');

            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }
        } else {
            unset($validated['image']);
        }

        $category->update($validated);

        return redirect()
            ->route('admin.categories')
            ->with('success', 'تم تعديل القسم بنجاح.');
    }

    public function destroy(Category $category)
    {
        abort_if($category->is_deleted, 404);

        // Archive only, so services and bookings linked to this category stay safe.
        $category->update([
            'is_deleted' => true,
        ]);

        return redirect()
            ->route('admin.categories')
            ->with('success', 'تم حذف القسم ' . $category->name . ' بنجاح.');
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'اسم القسم مطلوب.',
            'name.max' => 'اسم القسم يجب ألا يزيد عن 255 حرفاً.',
            'name.unique' => 'يوجد قسم آخر بنفس الاسم.',
            'description.required' => 'وصف القسم مطلوب.',
            'description.max' => 'وصف القسم يجب ألا يزيد عن 1000 حرف.',
            'image.required' => 'صورة القسم مطلوبة.',
            'image.image' => 'الملف المختار يجب أن يكون صورة.',
            'image.mimes' => 'الصور المسموحة: JPG أو PNG أو WEBP.',
            'image.max' => 'حجم الصورة يجب ألا يزيد عن 2 ميغابايت.',
        ];
    }
}
