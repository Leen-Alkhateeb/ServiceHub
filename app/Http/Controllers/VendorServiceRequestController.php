<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VendorServiceRequestController extends Controller
{
    private const OFFER_RULES = [
        'price' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,99999999.99'],
        'description' => ['required', 'string', 'max:3000'],
        'duration_minutes' => ['nullable', 'integer', 'between:5,1440'],
    ];

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('offer', ['service_id' => ['required', 'integer']] + self::OFFER_RULES);
        $vendor = $request->user();

        DB::transaction(function () use ($vendor, $data) {
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
            $attributes = $data + ['status' => 'active', 'rejection_reason' => null];
            if ($offer) {
                $offer->update($attributes + ['image' => $offer->image ?: $service->image]);
            } else {
                $vendor->vendorServices()->create($attributes + ['image' => $service->image]);
            }
        }, 3);

        return redirect(route('vendor.profile') . '#services')->with('offer_status', 'تمت إضافة الخدمة إلى ملفك.');
    }

    public function suggest(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('suggestion', [
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
        ] + self::OFFER_RULES);
        $vendor = $request->user();

        DB::transaction(function () use ($vendor, $data) {
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
                'image' => '',
                'status' => 'pending',
            ]);
        }, 3);

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
        DB::transaction(function () use ($request, $offer, $data) {
            $current = $request->user()->vendorServices()->whereIn('status', ['active', 'pending'])->lockForUpdate()->findOrFail($offer->id);
            $current->update([
                'price' => $data['price'],
                'description' => $data['description'],
                'duration_minutes' => $data['duration_minutes'] ?? null,
            ]);
            if ($current->status === 'pending') {
                $current->service()->where('approval_status', 'pending')->update(['description' => $data['description']]);
            }
        });

        return redirect(route('vendor.profile') . '#services')->with('offer_status', 'تم تعديل العرض.');
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
