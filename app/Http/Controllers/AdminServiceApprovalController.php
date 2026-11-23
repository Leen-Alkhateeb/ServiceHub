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
     
    public function index(Request $request): View
    {
        $request->validate(['search' => ['nullable', 'string']]);

        $requests = VendorService::with([
            'vendor',
            'service.category',
        ])

             
            ->whereHas('service', function ($query) {
                $query->whereNotNull('suggested_by')
                    ->whereColumn(
                        'services.suggested_by',
                        'vendor_services.vendor_id'
                    );
            })

             
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

             
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 0
                    ELSE 1
                END
            ")

            ->latest('updated_at')
            ->latest('id')

             
            ->paginate(6)
            ->withQueryString();

        return view(
            'pages.admin.vendor-service-requests',
            compact('requests')
        );
    }

     
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

             
            User::query()
                ->lockForUpdate()
                ->findOrFail($offer->vendor_id);

             
            $current = VendorService::query()
                ->lockForUpdate()
                ->findOrFail($offer->id);

             
            abort_unless(
                $current->status === 'pending',
                409
            );

             
            $service = $current->service()
                ->lockForUpdate()
                ->firstOrFail();

             
            abort_unless(
                $service->suggested_by !== null
                && (int) $service->suggested_by === (int) $current->vendor_id,
                409
            );

             
            abort_unless(
                $service->approval_status === 'pending',
                409
            );

            $approved = $data['decision'] === 'active';

            $reason = $approved
                ? null
                : $data['rejection_reason'];

             
            if ($approved) {
                abort_unless(
                    !$service->is_deleted
                    && $service->category
                    && !$service->category->is_deleted,
                    409
                );
            }

             
            $service->update([
                'approval_status' => $approved
                    ? 'approved'
                    : 'rejected',

                'rejection_reason' => $reason,
            ]);

             
            $current->update([
                'status' => $approved
                    ? 'active'
                    : 'rejected',

                'rejection_reason' => $reason,
            ]);

             
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
                'success',
                $data['decision'] === 'active'
                ? 'تمت الموافقة على الخدمة بنجاح.'
                : 'تم رفض الخدمة بنجاح.'
            );
    }
}
