<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminServicesController extends Controller
{
    // عرض الخدمات
    public function adminServices(Request $request)
    {
        $services = Service::with('category')
            ->where('is_deleted', false)

            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })

            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->input('category_id'));
            })

            ->when($request->input('status') === 'active', function ($query) {
                $query->where('is_active', true);
            })

            ->when($request->input('status') === 'inactive', function ($query) {
                $query->where('is_active', false);
            })

            ->latest()
            ->paginate(6)
            ->withQueryString();

        $categories = Category::where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view(
            'pages.admin.admin-services',
            compact('services', 'categories')
        );
    }

    // إضافة خدمة
    public function addServices(Request $request)
    {
        $newServices = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('is_deleted', false),
            ],

            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $imagePath = $request->file('image')
            ->store('services', 'public');

        try {
            Service::create([
                'name' => $newServices['name'],
                'description' => $newServices['description'],
                'category_id' => $newServices['category_id'],
                'image' => $imagePath,
                'is_active' => true,
                'is_deleted' => false,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($imagePath);
            throw $e;
        }

        return redirect()->back()
            ->with('success', 'تمت إضافة الخدمة بنجاح');
    }

    // تعديل خدمة
    public function editServices(Request $request, Service $service)
    {
        abort_if($service->is_deleted, 404);

        $newServices = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('is_deleted', false),
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $oldImage = null;
        $newImage = null;

        if ($request->hasFile('image')) {
            $oldImage = $service->image;

            $newImage = $request->file('image')
                ->store('services', 'public');

            $newServices['image'] = $newImage;
        }

        try {
            $service->update($newServices);
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if ($newImage && $oldImage && $oldImage !== $newImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->back()
            ->with('success', 'تم تعديل الخدمة بنجاح');
    }

    // تفعيل وتعطيل الخدمة
    public function toggleServices(Service $service)
    {
        abort_if($service->is_deleted, 404);

        $service->is_active = !$service->is_active;

        $service->save();

        return redirect()->back()->with(
            'success',
            $service->is_active
                ? 'تم تفعيل الخدمة بنجاح'
                : 'تم تعطيل الخدمة بنجاح'
        );
    }

    // الحذف المنطقي للخدمة
    public function deleteServices(Service $service)
    {
        abort_if($service->is_deleted, 404);

        $service->is_deleted = true;

        $service->save();

        return redirect()->back()
            ->with('success', 'تم حذف الخدمة بنجاح');
    }
}
