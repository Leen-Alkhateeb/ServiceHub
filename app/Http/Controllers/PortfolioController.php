<?php

namespace App\Http\Controllers;

use App\Models\PortfolioImage;
use App\Models\PortfolioProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PortfolioController extends Controller
{
    public function show(Request $request, PortfolioProject $project): View
    {
        $this->authorizeView($request, $project);
        $images = $project->images()->where('is_active', true)->oldest('id')->paginate(8);
        $isOwner = (int) $request->user()?->id === (int) $project->vendor_id;

        return view('pages.vendor.portfolio-project', compact('project', 'images', 'isOwner'));
    }

    public function image(Request $request, PortfolioImage $image): StreamedResponse
    {
        $this->authorizeView($request, $image->project);
        abort_unless($image->is_active, 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($image->image), 404);

        return $disk->response($image->image, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(Request $request, PortfolioProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);

        return $this->save($request, $project);
    }

    private function save(Request $request, ?PortfolioProject $project = null): RedirectResponse
    {
        $bag = $project ? 'portfolioEdit' : 'portfolio';
        $data = $request->validateWithBag($bag, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'project_price' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,99999999.99'],
            'completion_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'images' => [$project ? 'nullable' : 'required', 'array', 'min:1', 'max:8'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $vendor = $request->user();
        $paths = [];
        try {
            foreach ($data['images'] ?? [] as $file) {
                $path = $file->store('portfolio/'.$vendor->id, 'public');
                if (! $path) {
                    throw ValidationException::withMessages(['images' => 'تعذر حفظ الصور. حاول مرة أخرى.'])->errorBag($bag);
                }
                $paths[] = $path;
            }

            DB::transaction(function () use ($vendor, $data, $paths, $project, $bag) {
                $attributes = [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'project_price' => $data['project_price'] ?? null,
                    'completion_date' => $data['completion_date'] ?? null,
                ];
                if ($project) {
                    $project = $vendor->portfolioProjects()->where('is_active', true)->lockForUpdate()->findOrFail($project->id);
                    if ($project->images()->where('is_active', true)->count() + count($paths) > 8) {
                        throw ValidationException::withMessages(['images' => 'يمكن عرض 8 صور للمشروع. احذف صورة قبل إضافة صور أخرى.'])->errorBag($bag);
                    }
                    $project->update($attributes);
                } else {
                    $project = $vendor->portfolioProjects()->create($attributes);
                }
                $project->images()->createMany(array_map(fn (string $path) => ['image' => $path], $paths));
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);

            throw $exception;
        }

        return $project
            ? redirect()->route('vendor.portfolio.show', $project)->with('status', 'تم تعديل المشروع.')
            : redirect(route('vendor.profile').'#portfolio')->with('status', 'تمت إضافة المشروع.');
    }

    public function destroy(Request $request, PortfolioProject $project): RedirectResponse
    {
        $this->authorizeOwner($request, $project);
        $project->update(['is_active' => false]);

        return redirect(route('vendor.profile').'#portfolio')->with('status', 'تم حذف المشروع من معرض الأعمال.');
    }

    public function destroyImage(Request $request, PortfolioImage $image): RedirectResponse
    {
        $project = $image->project;
        $this->authorizeOwner($request, $project);
        abort_unless($image->is_active, 404);
        $image->forceFill(['is_active' => false])->save();

        return redirect()->route('vendor.portfolio.show', $project)->with('status', 'تم حذف الصورة من المعرض.');
    }

    private function authorizeOwner(Request $request, PortfolioProject $project): void
    {
        abort_unless($project->is_active && (int) $request->user()->id === (int) $project->vendor_id, 404);
    }

    private function authorizeView(Request $request, PortfolioProject $project): void
    {
        abort_unless($project->is_active, 404);
        $vendor = $project->vendor;
        abort_unless($vendor->role === 'vendor' && $vendor->status === 'active', 404);
        abort_unless(
            (int) $request->user()?->id === (int) $vendor->id || $vendor->vendorProfile?->application_status === 'approved',
            404,
        );
    }
}
