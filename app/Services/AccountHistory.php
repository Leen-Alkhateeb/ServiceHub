<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountHistory
{
    public function forTab(User $account, string $tab): ?LengthAwarePaginator
    {
        return match ($tab) {
            'bookings' => $account->bookingsAsCustomer()->with('vendorService.vendor')->latest('id')->paginate(8)->withQueryString(),
            'payments' => $account->bookingsAsCustomer()->whereHas('payment')->with('payment')
                ->withMax('payment', 'created_at')->orderByDesc('payment_max_created_at')->latest('id')->paginate(8)->withQueryString(),
            'income' => $account->role === 'vendor'
                ? $account->walletTransactions()->with('booking')->latest('id')->paginate(8)->withQueryString()
                : null,
            default => null,
        };
    }
}
