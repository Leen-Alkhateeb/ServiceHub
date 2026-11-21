<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VendorServiceRequestController extends Controller
{
    private const OFFER_RULES = [
        'price' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
        'description' => ['required', 'string', 'max:3000'],
        'duration_minutes' => ['nullable', 'integer', 'between:5,1440'],
        'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=5000,max_height=5000'],
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('offer', ['service_id' => ['required', 'integer']] + self::OFFER_RULES);
        $vendor = $request->user();

        $this->withOfferImage($request, 'offer', function (?string $image) use ($vendor, $data) {
            User::query()->lockForUpdate()->findOrFail($vendor->id);
            $service = Service::query()->whereKey($data['service_id'])
                ->where('approval_status', 'approved')->where('is_deleted', false)
                ->whereHas('category', fn($category) => $category->where('is_deleted', false))->first();
            if (!$service) {
                throw ValidationException::withMessages(['service_id' => 'اختر خدمة معتمدة من القائمة.'])->errorBag('offer');
            }

            $offers = $vendor->vendorServices()->where('service_id', $service->id);
            if ((clone $offers)->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['service_id' => 'هذه الخدمة مضافة بالفعل إلى ملفك.'])->errorBag('offer');
            }

            // Reuse offers submitted under the previous catalog approval workflow.
            $offer = $offers->whereIn('status', ['pending', 'rejected', 'inactive'])->latest('id')->first();
            $attributes = array_diff_key($data, ['image' => true]) + ['status' => 'active', 'rejection_reason' => null];
            if ($offer) {
                $previousImage = $offer->image;
                $offer->update($attributes + ['image' => $image ?? ($previousImage ?: $service->image)]);

                return $previousImage;
            } else {
                $vendor->vendorServices()->create($attributes + ['image' => $image ?? $service->image]);
            }
        });

        return redirect(route('vendor.profile') . '#services')->with('offer_status', 'تمت إضافة الخدمة إلى ملفك.');
    }

    public function suggest(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('suggestion', [
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
        ] + self::OFFER_RULES);
        $vendor = $request->user();

        $this->withOfferImage($request, 'suggestion', function (?string $image) use ($vendor, $data) {
            User::query()->lockForUpdate()->findOrFail($vendor->id);
            $category = Category::where('is_deleted', false)->lockForUpdate()->find($data['category_id']);
            if (!$category) {
                throw ValidationException::withMessages(['category_id' => 'اختر قسمًا متاحًا.'])->errorBag('suggestion');
            }
            if (
                $category->services()->where('is_deleted', false)->whereIn('approval_status', ['pending', 'approved'])
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])->exists()
            ) {
                throw ValidationException::withMessages(['name' => 'هذه الخدمة موجودة بالفعل أو قيد المراجعة.'])->errorBag('suggestion');
            }

            $service = $category->services()->create([
                'name' => $data['name'],
                'description' => $data['description'],
                'image' => '',
                'approval_status' => 'pending',
                'suggested_by' => $vendor->id,
            ]);
            $vendor->vendorServices()->create([
                'service_id' => $service->id,
                'price' => $data['price'],
                'description' => $data['description'],
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'image' => $image ?? '',
                'status' => 'pending',
            ]);
        });

        return redirect(route('vendor.profile') . '#services')->with('offer_status', 'تم إرسال اقتراح الخدمة إلى الإدارة.');
    }

    public function index(): View
    {
        $requests = VendorService::with(['vendor', 'service.category'])->whereIn('status', ['pending', 'rejected'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('updated_at')->latest('id')->paginate(15);

        return view('pages.admin.vendor-service-requests', compact('requests'));
    }

    public function update(Request $request, VendorService $offer): RedirectResponse
    {
        $data = $request->validateWithBag('offerEdit', ['editing_offer' => ['nullable', 'integer']] + self::OFFER_RULES);
        $this->withOfferImage($request, 'offerEdit', function (?string $image) use ($request, $offer, $data) {
            $current = $request->user()->vendorServices()->whereIn('status', ['active', 'pending'])->lockForUpdate()->findOrFail($offer->id);
            $previousImage = $current->image;
            $current->update([
                'price' => $data['price'],
                'description' => $data['description'],
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'image' => $image ?? $previousImage,
            ]);
            if ($current->status === 'pending') {
                $current->service()->where('approval_status', 'pending')->update(['description' => $data['description']]);
            }

            return $previousImage;
        });

        return redirect(route('vendor.profile') . '#services')->with('offer_status', 'تم تعديل العرض.');
    }

    private function withOfferImage(Request $request, string $bag, callable $save): void
    {
        $image = $request->file('image')?->store('vendor-offer-images', 'public');
        if ($image === false) {
            throw ValidationException::withMessages(['image' => 'تعذر حفظ الصورة. حاول مرة أخرى.'])->errorBag($bag);
        }

        try {
            $previousImage = DB::transaction(fn () => $save($image), 3);
        } catch (\Throwable $exception) {
            if ($image) {
                Storage::disk('public')->delete($image);
            }

            throw $exception;
        }

        if ($image && str_starts_with($previousImage ?? '', 'vendor-offer-images/')) {
            Storage::disk('public')->delete($previousImage);
        }
    }

    public function destroy(Request $request, VendorService $offer): RedirectResponse
    {
        DB::transaction(function () use ($request, $offer) {
            $current = $request->user()->vendorServices()->whereIn('status', ['active', 'pending', 'rejected'])->lockForUpdate()->findOrFail($offer->id);
            if ($current->status === 'pending') {
                $current->service()->where('approval_status', 'pending')->update(['approval_status' => 'withdrawn']);
            }
            $current->update(['status' => 'inactive']);
        });

        return redirect(route('vendor.profile') . '#services')->with('offer_status', 'تم حذف العرض من ملفك.');
    }

    public function decide(Request $request, VendorService $offer): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:active,rejected'],
            'rejection_reason' => ['nullable', 'required_if:decision,rejected', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($offer, $data) {
            User::query()->lockForUpdate()->findOrFail($offer->vendor_id);
            $current = VendorService::query()->lockForUpdate()->findOrFail($offer->id);
            abort_unless($current->status === 'pending', 409);
            $service = $current->service()->lockForUpdate()->firstOrFail();
            $approved = $data['decision'] === 'active';
            $reason = $approved ? null : $data['rejection_reason'];
            if ($approved) {
                abort_unless(in_array($service->approval_status, ['approved', 'pending'], true)
                    && !$service->is_deleted && !$service->category->is_deleted, 409);
            }
            if ($service->approval_status === 'pending') {
                $service->update(['approval_status' => $approved ? 'approved' : 'rejected', 'rejection_reason' => $reason]);
            }
            $current->update(['status' => $data['decision'], 'rejection_reason' => $reason]);
            $current->vendor->notifications()->create([
                'title' => $approved ? 'تمت الموافقة على الخدمة' : 'لم تتم الموافقة على الخدمة',
                'message' => $service->name . ($reason ? ' · ' . $reason : ''),
                'type' => 'service',
            ]);
        }, 3);

        return redirect()->route('admin.service-requests.index')->with('status', 'تم حفظ قرار المراجعة.');
    }
}
