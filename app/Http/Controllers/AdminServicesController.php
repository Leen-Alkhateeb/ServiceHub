<?php

namespace App\Http\Controllers;

use App\Services\AdminPeriod;

use App\Models\Category;
use App\Models\Service;
use App\Models\VendorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminServicesController extends Controller
{
     
    public function adminServices(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer'],
        ]);

        $services = AdminPeriod::apply(Service::with('category'))
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
            ->withQueryString()
            ->through(function (Service $service) {
                $image = $service->image;
                $path = ltrim($image ?? '', '/');
                $service->display_image_url = match (true) {
                    str_starts_with($image ?? '', 'https://'), str_starts_with($image ?? '', 'http://') => $image,
                    str_starts_with($path, 'assets/') && ! str_contains($path, '..') && is_file(public_path($path)) => asset($path),
                    $this->storedImagePath($image) !== null => route('admin.services.image', $service),
                    default => null,
                };

                return $service;
            });

        $categories = Category::where('is_deleted', false)
            ->orderBy('name')
            ->get();

        return view(
            'pages.admin.admin-services',
            compact('services', 'categories')
        );
    }

    public function image(Service $service): StreamedResponse
    {
        abort_if($service->is_deleted, 404);
        $path = $this->storedImagePath($service->image);
        abort_unless($path, 404);

        $disk = Storage::disk('public');
        $type = $disk->mimeType($path);
        abort_unless(in_array($type, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'], true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    private function storedImagePath(?string $image): ?string
    {
        $path = ltrim(str_replace('\\', '/', $image ?? ''), '/');
        $path = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        return ! str_contains($path, '..') && preg_match('/\.(jpe?g|png|webp|gif|avif)$/i', $path)
            && Storage::disk('public')->exists($path) ? $path : null;
    }

     
    public function addServices(Request $request)
    {
        $newServices = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('is_deleted', 0),
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
        if ($imagePath === false) {
            throw ValidationException::withMessages(['image' => 'تعذر حفظ صورة الخدمة.']);
        }

        try {
            Service::create([
                'name' => $newServices['name'],
                'description' => $newServices['description'],
                'category_id' => $newServices['category_id'],
                'image' => $imagePath,
                'approval_status' => 'approved',
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
                    ->where('is_deleted', 0),
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
            if ($newImage === false) {
                throw ValidationException::withMessages(['image' => 'تعذر حفظ صورة الخدمة.']);
            }

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

        if ($newImage && $oldImage && str_starts_with($oldImage, 'services/')
            && ! Service::where('image', $oldImage)->exists()
            && ! VendorService::where('image', $oldImage)->exists()) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->back()
            ->with('success', 'تم تعديل الخدمة بنجاح');
    }

     
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

     
    public function deleteServices(Service $service)
    {
        abort_if($service->is_deleted, 404);

        $service->is_deleted = true;

        $service->save();

        return redirect()->back()
            ->with('success', 'تم حذف الخدمة بنجاح');
    }
}
