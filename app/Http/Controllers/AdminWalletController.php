<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class AdminWalletController extends Controller
{
    


    public function index()
    {
         $transactions = WalletTransaction::with(['vendor', 'booking'])->whereNotNull('booking_id')
            ->latest()
            ->paginate(10);

        return view('pages.admin.admin-wallet', compact('transactions'));
    }

    


    public function create()
    {
         
    }

    


    public function store(Request $request)
    {
         
    }

    


    public function show(string $id)
    {
         
    }

    


    public function edit(string $id)
    {
         
    }

    


    public function update(Request $request, string $id)
    {
         
    }

    


    public function destroy(string $id)
    {
         $transaction = WalletTransaction::findOrFail($id);
        abort_if($transaction->idempotency_key !== null, 403);
        $transaction->delete();

        return redirect()->route('admin.wallet.index')
            ->with('success', 'تم حذف سجل حركة المحفظة بنجاح.');
    }
}
