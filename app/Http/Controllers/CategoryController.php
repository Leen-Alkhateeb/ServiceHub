<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
        {
            $categories = Category::where('is_deleted', false)->get();

            return view('pages.admin.admin-categories', compact('categories'));
        }   

    public function create()
        {
            return view('pages.admin.admin-categories-create');
        }

    public function store(Request $request)
        {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'image' => ['required', 'image', 'max:2048'],
            ]);

            $validated['image'] = $request->file('image')->store('categories', 'public');

            Category::create($validated);

            return redirect()
                ->route('admin.categories')
                ->with('success', 'تمت إضافة القسم بنجاح');
        }
    public function edit(Category $category)
            {
                return view('pages.admin.admin-categories-edit', compact('category'));
            }
    

    public function update(Request $request, Category $category)
            {
                $validated = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'description' => ['required', 'string'],
                    'image' => ['nullable', 'image', 'max:2048'],
                ]);

                if ($request->hasFile('image')) {
                    $validated['image'] = $request->file('image')->store('categories', 'public');
                }

                $category->update($validated);

                return redirect()
                    ->route('admin.categories')
                    ->with('success', 'تم تعديل القسم بنجاح');
            }

    public function destroy(Category $category)
            {
                $category->update([
                    'is_deleted' => true,
                ]);

                return redirect()
                    ->route('admin.categories')
                    ->with('success', 'تم حذف القسم بنجاح');
            }
}

