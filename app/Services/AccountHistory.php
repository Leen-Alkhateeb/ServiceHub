<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountHistory
{
    public function forTab(User $account, string $tab, Request $request): array
    {
        $filters = [];
        $historyCategories = collect();
        $entries = null;

        if ($tab === 'income' && $account->role === 'vendor') {
            $entries = $account->walletTransactions()->with('booking')->latest('id')->paginate(8)->withQueryString();
        }

        if (in_array($tab, ['bookings', 'payments'], true)) {
            $filters = $request->validate([
                'q' => ['nullable', 'string', 'max:100'],
                'category' => ['nullable', 'integer', 'exists:categories,id'],
                'from' => ['nullable', 'date_format:Y-m-d'],
                'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            ]);

            $historyCategories = Category::orderBy('name')->get(['id', 'name']);
            $query = $account->bookingsAsCustomer()->with(['vendorService.vendor', 'vendorService.service.category', 'payment', 'refundRequest']);
            if ($tab === 'payments') {
                $query->withTrashed()->whereHas('payment', fn (Builder $payment) => $this->filterDates(
                    $payment, DB::raw('COALESCE(paid_at, created_at)'), $filters,
                ))->withMax('payment', 'created_at')->orderByDesc('payment_max_created_at');
            } else {
                $query->where(fn (Builder $rows) => $this->filterDates($rows, 'booking_date', $filters));
            }

            if ($search = $filters['q'] ?? null) {
                $query->where(function (Builder $rows) use ($search) {
                    $rows->where('service_name', 'like', '%'.$search.'%')
                        ->orWhereHas('vendorService.vendor', function (Builder $vendor) use ($search) {
                            foreach (preg_split('/\s+/u', trim($search)) ?: [] as $name) {
                                $vendor->where(fn (Builder $person) => $person
                                    ->where('first_name', 'like', '%'.$name.'%')->orWhere('last_name', 'like', '%'.$name.'%'));
                            }
                        });
                });
            }
            $query->when($filters['category'] ?? null, fn ($rows, $category) => $rows
                ->whereHas('vendorService.service', fn (Builder $service) => $service->where('category_id', $category)));
            $entries = $query->latest('id')->paginate(8)->withQueryString();
        }

        return compact('entries', 'filters', 'historyCategories');
    }

    private function filterDates(Builder $rows, string|Expression $date, array $filters): void
    {
        $rows->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate($date, '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate($date, '<=', $to));
    }
}
