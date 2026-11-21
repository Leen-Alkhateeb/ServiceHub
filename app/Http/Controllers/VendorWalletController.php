<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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
        $matchingBookings = Booking::withTrashed()
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

        $query = (clone $matchingBookings)->whereIn('id', $settlement->completed()->select('id'));
        $adjustments = WalletTransaction::query()->where('vendor_id', $vendorId)
            ->whereIn('booking_id', (clone $matchingBookings)->where('status', 'cancelled')->select('id'))
            ->whereIn('transaction_type', ['penalty', 'refund_share']);
        $cancellationEntries = ($filters['status'] ?? null) && $filters['status'] !== 'released'
            ? null
            : $adjustments->with('booking')->latest('id')->paginate(6, ['*'], 'cancellations_page')->withQueryString();

        $labels = ['released' => 'مُتاح', 'held' => 'قيد الحجز', 'ready' => 'جاهز للتحرير', 'issue' => 'يتطلب متابعة'];
        $netBalance = (float) WalletTransaction::where('vendor_id', $vendorId)->sum('amount');
        $outstandingPenalty = max(0, -$netBalance);
        $summary = ['released' => ['label' => 'الرصيد المتاح', 'amount' => max(0, $netBalance)]];
        foreach (array_diff_key($labels, $summary) as $status => $label) {
            $group = $settlement->statusQuery(clone $query, $status);
            $summary[$status] = [
                'label' => $label,
                'amount' => $group->sum('original_price'),
            ];
        }

        if ($status = $filters['status'] ?? null) {
            $settlement->statusQuery($query, $status);
        }

        $bookings = $query->with(['customer', 'payment', 'refundRequest', 'walletTransactions' => fn ($rows) => $rows->latest('id')])
            ->orderByRaw('COALESCE((SELECT MAX(created_at) FROM wallet_transactions WHERE booking_id = bookings.id), completed_at) DESC')
            ->latest('id')->paginate(6)->withQueryString();

        return view('pages.vendor.vendor-wallet', compact('bookings', 'summary', 'labels', 'filters', 'cancellationEntries', 'outstandingPenalty'));
    }
}
