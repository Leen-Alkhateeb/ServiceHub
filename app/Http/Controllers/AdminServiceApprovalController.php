<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use App\Models\VendorService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminServiceApprovalController extends Controller
{
    // عرض طلبات اعتماد الخدمات المقترحة
    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string']]);

        $requests = VendorService::with([
            'vendor',
            'service.category',
        ])

            // عرض اقتراحات الخدمات فقط
            ->whereHas('service', function ($query) {
                $query->whereNotNull('suggested_by')
                    ->whereColumn(
                        'services.suggested_by',
                        'vendor_services.vendor_id'
                    );
            })

            // البحث باسم الخدمة أو مقدم الخدمة
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->whereHas('service', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                            $vendorQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })

            // الفلترة حسب حالة اعتماد الخدمة
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->input('status');

                if (
                    in_array($status, [
                        'pending',
                        'approved',
                        'rejected',
                        'withdrawn',
                    ], true)
                ) {
                    $query->whereHas('service', function ($serviceQuery) use ($status) {
                        $serviceQuery->where('approval_status', $status);
                    });
                }
            })

            // الطلبات المعلقة أولاً
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 0
                    ELSE 1
                END
            ")

            ->latest('updated_at')
            ->latest('id')

            // Pagination
            ->paginate(6)
            ->withQueryString();

        return view(
            'pages.admin.vendor-service-requests',
            compact('requests')
        );
    }

    // قبول أو رفض اقتراح الخدمة
    public function decide(
        Request $request,
        VendorService $offer
    ): RedirectResponse {

        $data = $request->validate([
            'decision' => [
                'required',
                'in:active,rejected',
            ],

            'rejection_reason' => [
                'nullable',
                'required_if:decision,rejected',
                'string',
                'max:500',
            ],
        ]);

        DB::transaction(function () use ($offer, $data) {

            // قفل حساب مقدم الخدمة
            User::query()
                ->lockForUpdate()
                ->findOrFail($offer->vendor_id);

            // جلب الطلب مع قفله
            $current = VendorService::query()
                ->lockForUpdate()
                ->findOrFail($offer->id);

            // الطلب يجب أن يكون قيد المراجعة
            abort_unless(
                $current->status === 'pending',
                409
            );

            // جلب الخدمة المرتبطة بالطلب
            $service = $current->service()
                ->lockForUpdate()
                ->firstOrFail();

            // التأكد أن الخدمة مقترحة من هذا الـVendor
            abort_unless(
                $service->suggested_by !== null
                && (int) $service->suggested_by === (int) $current->vendor_id,
                409
            );

            // الخدمة يجب أن تكون قيد المراجعة
            abort_unless(
                $service->approval_status === 'pending',
                409
            );

            $approved = $data['decision'] === 'active';

            $reason = $approved
                ? null
                : $data['rejection_reason'];

            // التحقق من صلاحية الخدمة والقسم عند الموافقة
            if ($approved) {
                abort_unless(
                    !$service->is_deleted
                    && $service->category
                    && !$service->category->is_deleted,
                    409
                );
            }

            // تحديث حالة اعتماد الخدمة الأساسية
            $service->update([
                'approval_status' => $approved
                    ? 'approved'
                    : 'rejected',

                'rejection_reason' => $reason,
            ]);

            // تحديث حالة عرض الـVendor
            $current->update([
                'status' => $approved
                    ? 'active'
                    : 'rejected',

                'rejection_reason' => $reason,
            ]);

            // إرسال إشعار لمقدم الخدمة
            $current->vendor->notifications()->create([
                'title' => $approved
                    ? 'تمت الموافقة على الخدمة'
                    : 'لم تتم الموافقة على الخدمة',

                'message' => $service->name
                    . ($reason ? ' · ' . $reason : ''),

                'type' => 'service',
            ]);

        }, 3);

        return redirect()
            ->route('admin.service-requests.index')
            ->with(
                'status',
                $data['decision'] === 'active'
                ? 'تمت الموافقة على الخدمة بنجاح.'
                : 'تم رفض الخدمة بنجاح.'
            );
    }
}
