<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\VendorSettlement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorWalletController extends Controller
{
    public function __invoke(Request $request, VendorSettlement $settlement): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'status' => ['nullable', 'in:released,held,ready,issue'],
        ]);
        $vendorId = $request->user()->id;
        $query = $settlement->completed()
            ->whereHas('vendorService', fn (Builder $offer) => $offer->where('vendor_id', $vendorId))
            ->when($filters['from'] ?? null, fn (Builder $rows, string $from) => $rows->where('booking_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $rows, string $to) => $rows->where('booking_date', '<=', $to))
            ->when($filters['q'] ?? null, function (Builder $rows, string $search) {
                $rows->where(function (Builder $match) use ($search) {
                    $match->where('service_name', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn (Builder $user) => $user->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%'));
                    if (ctype_digit($search)) {
                        $match->orWhereKey($search);
                    }
                });
            });

        $labels = ['released' => 'مُتاح', 'held' => 'قيد الحجز', 'ready' => 'جاهز للتحرير', 'issue' => 'يتطلب متابعة'];
        $summary = [];
        foreach ($labels as $status => $label) {
            $group = $settlement->statusQuery(clone $query, $status);
            $summary[$status] = [
                'label' => $label,
                'amount' => $status === 'released'
                    ? WalletTransaction::where('vendor_id', $vendorId)->whereIn('booking_id', (clone $group)->select('id'))->sum('amount')
                    : $group->sum('original_price'),
            ];
        }

        if ($status = $filters['status'] ?? null) {
            $settlement->statusQuery($query, $status);
        }

        $bookings = $query->with(['customer', 'payment', 'refundRequest', 'walletTransactions' => fn ($rows) => $rows->latest('id')])
            ->orderByRaw('COALESCE((SELECT MAX(created_at) FROM wallet_transactions WHERE booking_id = bookings.id), completed_at) DESC')
            ->latest('id')->paginate(6)->withQueryString();

        return view('pages.vendor.vendor-wallet', compact('bookings', 'summary', 'labels', 'filters'));
    }
}
