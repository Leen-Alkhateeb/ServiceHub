<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InstantFinance
{
    public function hasDebt(int $customer): bool
    {
        return DB::table('wallet_transactions')->where('user_id', $customer)->where('account_type', 'customer')
            ->where('transaction_type', 'debt')->where('status', 'outstanding')->exists();
    }

    public function cancellationPenalty(Booking $booking, bool $vendorCancelled, ?\App\Models\RefundRequest $existingCase = null): void
    {
        $vendor = $booking->vendorService->vendor_id;
        $debtor = $vendorCancelled ? $vendor : $booking->customer_id;
        $beneficiary = $vendorCancelled ? $booking->customer_id : $vendor;
        $penalty = $this->roundedShare($this->fils($booking->agreed_price), 15);
        if ($penalty === 0) return;

        $amount = $this->decimal($penalty);
        abort_if($this->ledger($booking, 'debt') || $booking->payment?->paid_amount > 0, 409);
        $case = $existingCase ?? $booking->cases()->make([
            'submitted_by' => $debtor, 'status' => 'accepted',
            'reason' => $vendorCancelled ? 'إلغاء الفني بعد التعيين' : 'إلغاء العميل بعد مغادرة الفني',
            'reviewed_at' => now(),
        ]);
        $case->forceFill([
            'case_type' => $existingCase?->case_type ?? 'exceptional_cancellation', 'submitted_by' => $existingCase?->submitted_by ?? $debtor,
            'responsible_user_id' => $debtor, 'beneficiary_id' => $beneficiary,
            'compensation_amount' => $amount, 'compensation_status' => 'approved', 'compensation_due_at' => now()->addDay(),
        ])->save();
        $this->entry($booking, 'debt', $amount, $vendorCancelled ? 'vendor' : 'customer', $debtor, 'debit', [
            'status' => 'outstanding', 'due_at' => now(), 'refund_request_id' => $case->id,
        ]);
    }

    public function payCancellationDebt(Booking $booking, int $customer): void
    {
        abort_unless($booking->booking_type === 'instant' && (int) $booking->customer_id === $customer, 404);
        $this->withPlatformLock(function () use ($booking, $customer) {
            abort_unless(User::query()->lockForUpdate()->findOrFail($customer)->status === 'active', 403);
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $debt = $this->ledger($current, 'debt');
            abort_unless($debt && $debt->account_type === 'customer', 409);
            $case = $current->cases()->lockForUpdate()->findOrFail($debt->refund_request_id);
            abort_unless($current->status === 'cancelled' && (int) $case->responsible_user_id === $customer && ! $this->blocked($current), 409);
            if ($debt->status === 'repaid') return;
            abort_unless($debt->status === 'outstanding', 409);
            $this->entry($current, 'debt_repayment', $debt->amount, 'customer', $customer, 'debit', [
                'related_transaction_id' => $debt->id, 'refund_request_id' => $case->id, 'performed_by' => $customer,
            ]);
            $advance = $this->ledger($current, 'platform_advance');
            if ($advance) {
                $this->entry($current, 'advance_recovery', $debt->amount, 'platform', null, 'credit', ['related_transaction_id' => $advance->id]);
                $this->platformEntry($current, 'advance_recovery', $debt->amount, 'credit');
                $current->forceFill(['settlement_status' => 'settled'])->save();
            } else {
                $this->creditCompensation($current, $case, null, $debt->id);
            }
            DB::table('wallet_transactions')->where('id', $debt->id)->update(['status' => 'repaid']);
        });
    }

    public function fundCompensation(Booking $booking, int $admin): bool
    {
        return $this->withPlatformLock(function () use ($booking, $admin) {
            $current = Booking::query()->where('booking_type', 'instant')->lockForUpdate()->findOrFail($booking->id);
            abort_unless(User::query()->whereKey($admin)->where('role', 'admin')->where('status', 'active')->exists(), 403);
            $debt = $this->ledger($current, 'debt');
            abort_unless($debt, 409);
            $case = $current->cases()->lockForUpdate()->findOrFail($debt->refund_request_id);
            if ($this->ledger($current, 'platform_advance')) return true;
            abort_unless($current->status === 'cancelled' && $case->compensation_status === 'approved'
                && $case->compensation_due_at && now()->gte($case->compensation_due_at)
                && $debt->status === 'outstanding' && ! $this->blocked($current) && ! $this->ledger($current, 'compensation'), 409);
            $amount = $this->decimal($this->remainingDebtFils($debt));
            if ($this->availableFils() < $this->fils($amount)) {
                if ($current->settlement_status !== 'insufficient_funds') {
                    $current->forceFill(['settlement_status' => 'insufficient_funds'])->save();
                    app(InstantMarketplace::class)->notify($case->beneficiary_id, 'التعويض بانتظار توفر رصيد تجريبي كافٍ.');
                    app(InstantMarketplace::class)->notify($admin, 'الرصيد التجريبي لا يكفي لتعويض الحجز رقم '.$current->id.'.');
                }
                return false;
            }
            $this->entry($current, 'platform_advance', $amount, 'platform', null, 'debit', [
                'performed_by' => $admin, 'refund_request_id' => $case->id,
            ]);
            $this->platformEntry($current, 'platform_advance', $amount, 'debit');
            $this->creditCompensation($current, $case, $admin, $this->ledger($current, 'platform_advance')->id, $amount);

            return true;
        });
    }

    private function creditCompensation(Booking $booking, \App\Models\RefundRequest $case, ?int $admin, int $source, ?string $amount = null): void
    {
        $vendor = (int) $case->beneficiary_id !== (int) $booking->customer_id;
        $this->entry($booking, 'compensation', $amount ?? $case->compensation_amount, $vendor ? 'vendor' : 'customer', $case->beneficiary_id, 'credit', [
            'refund_request_id' => $case->id,
            'performed_by' => $admin, 'related_transaction_id' => $source,
        ]);
        $case->forceFill(['compensation_status' => 'paid'])->save();
        $booking->forceFill(['settlement_status' => $admin ? 'advanced' : 'settled'])->save();
        app(InstantMarketplace::class)->notify($case->beneficiary_id, 'تم تسجيل التعويض التجريبي عن إلغاء الحجز.');
    }

    public function collect(Booking $booking, int $customer): void
    {
        abort_unless((int) $booking->customer_id === $customer && $booking->booking_type === 'instant', 404);
        DB::transaction(function () use ($booking, $customer) {
            abort_unless(User::query()->lockForUpdate()->findOrFail($customer)->status === 'active', 403);
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            abort_unless($current->status === 'completed' && ! $this->blocked($current), 409);
            $payment = $current->payment()->firstOrNew();
            $debt = $this->debt($current);
            $resolution = $this->exceptionalResolution($current);
            if ($payment->status === 'paid' && ! $debt) return;
            abort_if($current->settled_at || ($payment->exists && ! ($debt && $resolution)
                && in_array($payment->status, ['paid', 'refunded', 'partially_refunded'], true)), 409);
            $amount = $debt?->amount ?? $current->refundRequest?->approved_service_amount
                ?? $resolution?->approved_service_amount ?? $current->agreed_price;
            if ($this->fils($amount) === 0) return;
            $collected = $this->fils((string) ($payment->paid_amount ?? '0')) + $this->fils($amount);
            $refunded = $this->fils((string) ($payment->refunded_amount ?? '0'));
            abort_if($collected > 9999999999 || ($resolution && $collected - $refunded !== $this->fils($resolution->approved_service_amount)), 409);
            $payment->forceFill(['amount' => $this->decimal($collected), 'paid_amount' => $this->decimal($collected), 'status' => 'paid', 'payment_method' => 'card', 'paid_at' => now()])->save();
            $this->entry($current, $debt ? 'debt_repayment' : 'service_payment', $amount, 'customer', $customer, 'debit', [
                'related_transaction_id' => $debt?->id, 'performed_by' => $customer,
            ]);
            if ($debt) DB::table('wallet_transactions')->where('id', $debt->id)->update(['status' => 'repaid']);
            $current->forceFill(['settlement_status' => 'on_hold'])->save();
            app(InstantMarketplace::class)->notify($current->vendorService->vendor_id, 'تم تسجيل الدفع التجريبي لحجزك الفوري.');
            app(InstantMarketplace::class)->notify($customer, 'تم تسجيل دفعتك التجريبية للحجز الفوري.');
        }, 3);
    }

    public function decide(Booking $booking, int $admin, string $decision, string $reason): void
    {
        DB::transaction(function () use ($booking, $admin, $decision, $reason) {
            User::query()->lockForUpdate()->findOrFail($booking->customer_id);
            $current = Booking::query()->where('booking_type', 'instant')->lockForUpdate()->findOrFail($booking->id);
            abort_if($this->exceptionalResolution($current), 409, 'الحجز له قرار مالي استثنائي معتمد.');
            $case = $current->refundRequest()->where('case_type', 'objection')->where('status', 'pending')->lockForUpdate()->firstOrFail();
            $fils = $this->fils($current->agreed_price);
            $approved = $decision === 'accepted'
                ? $this->decimal($fils - $this->roundedShare($fils, 70))
                : $current->agreed_price;
            $case->forceFill(['status' => $decision, 'admin_response' => $reason, 'reviewed_at' => now(), 'reviewed_by' => $admin, 'approved_service_amount' => $approved])->save();
            if ($this->fils($approved) > 0 && $current->payment?->status !== 'paid') {
                $this->recordDebt($current, $approved, now(), $case->id);
                $current->forceFill(['settlement_status' => 'awaiting_admin'])->save();
            }
            $market = app(InstantMarketplace::class);
            $market->event($current, $admin, ['event_type' => 'admin_decision', 'description' => $reason, 'new_status' => $decision]);
            $market->notify($current->customer_id, 'تم اتخاذ قرار بشأن اعتراض الحجز الفوري رقم '.$current->id.'.');
            $market->notify($current->vendorService->vendor_id, 'تم اتخاذ قرار بشأن اعتراض الحجز الفوري رقم '.$current->id.'.');
        }, 3);
    }

    public function resolveExceptionalService(Booking $booking, int $caseId, int $admin, string $amount, string $reason, ?string $responsibility): void
    {
        $vendor = (int) $booking->vendorService->vendor_id;
        $this->withPlatformLock(function () use ($booking, $caseId, $admin, $amount, $reason, $responsibility, $vendor) {
            User::whereKey([$booking->customer_id, $vendor])->orderBy('id')->lockForUpdate()->get();
            $current = Booking::where('booking_type', 'instant')->lockForUpdate()->findOrFail($booking->id);
            abort_unless(User::whereKey($admin)->where('role', 'admin')->where('status', 'active')->exists(), 403);
            $case = $current->cases()->whereIn('case_type', ['partial_service', 'non_execution', 'exceptional_cancellation'])
                ->lockForUpdate()->findOrFail($caseId);
            $approved = $this->fils($amount);
            $responsible = match ($responsibility) {
                'customer' => $current->customer_id,
                'vendor' => $vendor,
                default => null,
            };
            if ($case->status === 'accepted' && $case->approved_service_amount !== null) {
                abort_unless($this->fils($case->approved_service_amount) === $approved
                    && $case->admin_response === $reason && (int) $case->reviewed_by === $admin
                    && (int) $case->responsible_user_id === (int) $responsible, 409);
                return;
            }
            abort_unless($case->status === 'pending' && ! $current->settled_at
                && in_array($current->status, ['arrived', 'in_progress', 'completed'], true)
                && ! $current->pendingCases()->whereKeyNot($caseId)->exists()
                && $current->refundRequest?->status !== 'accepted' && ! $this->exceptionalResolution($current)
                && ! $this->ledger($current, 'payout') && ! $this->ledger($current, 'platform_advance')
                && ! $this->ledger($current, 'commission'), 409, 'في قيد منفّذ أو حالة مرتبطة تحتاج مراجعة قبل اعتماد المبلغ.');
            abort_unless($approved >= 0 && $approved <= $this->fils($current->agreed_price)
                && ($case->case_type !== 'non_execution' || $approved === 0)
                && ($current->status !== 'arrived' || $approved === 0), 422, 'قيمة العمل المعتمدة غير صالحة.');

            $payment = $current->payment()->lockForUpdate()->first();
            $collected = $this->fils((string) ($payment?->paid_amount ?? '0'));
            $refunded = $this->fils((string) ($payment?->refunded_amount ?? '0'));
            $net = $collected - $refunded;
            abort_if($net < 0 || $collected + max(0, $approved - $net) > 9999999999, 409, 'المبلغ المحصّل لا يطابق حدود السجل المالي.');
            $refund = max(0, $net - $approved);
            if ($refund > 0) {
                $this->entry($current, 'refund', $this->decimal($refund), 'customer', $current->customer_id, 'credit',
                    ['refund_request_id' => $caseId, 'performed_by' => $admin], ':resolution-'.$caseId);
                $payment->forceFill(['refunded_amount' => $this->decimal($refunded + $refund), 'refunded_at' => now(),
                    'status' => $approved === 0 ? 'refunded' : 'partially_refunded'])->save();
            }

            $oldDebt = $this->debt($current);
            if ($oldDebt) {
                $this->entry($current, 'adjustment', $oldDebt->amount, 'customer', $current->customer_id, 'credit', [
                    'related_transaction_id' => $oldDebt->id, 'refund_request_id' => $caseId,
                    'performed_by' => $admin, 'description' => $reason,
                ], ':resolution-'.$caseId);
                DB::table('wallet_transactions')->where('id', $oldDebt->id)->update(['status' => 'superseded']);
            }
            if ($net < $approved) {
                $this->entry($current, 'debt', $this->decimal($approved - $net), 'customer', $current->customer_id, 'debit', [
                    'status' => 'outstanding', 'due_at' => now(), 'refund_request_id' => $caseId, 'related_transaction_id' => $oldDebt?->id,
                ], ':resolution-'.$caseId);
            }

            $case->forceFill(['status' => 'accepted', 'approved_service_amount' => $this->decimal($approved),
                'refund_amount' => $this->decimal($refund), 'responsible_user_id' => $responsible, 'reviewed_at' => now(),
                'reviewed_by' => $admin, 'admin_response' => $reason])->save();
            $current->forceFill($approved === 0 ? [
                'status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $admin,
                'settlement_status' => 'settled', 'settled_at' => now(),
            ] : [
                'status' => 'completed', 'completed_at' => $current->completed_at ?? now(),
                'objection_deadline' => $current->objection_deadline ?? now()->addDay(),
                'payment_deadline' => $current->payment_deadline ?? now(),
                'settlement_status' => $net >= $approved ? 'on_hold' : 'awaiting_admin',
            ])->save();
            $market = app(InstantMarketplace::class);
            $market->event($current, $admin, ['event_type' => 'admin_decision', 'description' => $reason, 'new_status' => $current->status]);
            $market->notify($current->customer_id, 'تم اعتماد قيمة العمل المنجَز في الحجز الفوري رقم '.$current->id.'.');
            $market->notify($vendor, 'تم اعتماد قيمة العمل المنجَز في الحجز الفوري رقم '.$current->id.'.');
        });
    }

    private function exceptionalResolution(Booking $booking): ?\App\Models\RefundRequest
    {
        return $booking->cases()->whereIn('case_type', ['partial_service', 'non_execution', 'exceptional_cancellation'])
            ->where('status', 'accepted')->whereNotNull('approved_service_amount')->first();
    }

    public function run(): void
    {
        Booking::query()->where('booking_type', 'instant')->where('status', 'completed')
            ->where('objection_deadline', '<=', now())->whereIn('settlement_status', ['on_hold', 'pending'])
            ->chunkById(100, function ($bookings) {
                foreach ($bookings as $booking) $this->settle($booking);
            });
    }

    private function settle(Booking $booking): void
    {
        $vendorId = $booking->vendorService?->vendor_id;
        $this->withPlatformLock(function () use ($booking, $vendorId) {
            if (User::query()->whereKey([$booking->customer_id, $vendorId])->orderBy('id')->lockForUpdate()->get()->count() !== 2) return;
            $current = Booking::query()->lockForUpdate()->find($booking->id);
            if (! $current || $current->status !== 'completed' || $current->settled_at || ! $current->objection_deadline
                || $current->objection_deadline->isFuture() || $this->blocked($current)) return;

            $payment = $current->payment;
            $case = $current->refundRequest;
            $noPaymentDue = $case?->status === 'accepted' && $this->fils($case->approved_service_amount) === 0;
            $collectedStatus = in_array($payment?->status, ['paid', 'partially_refunded'], true);
            if (! $collectedStatus && ! $noPaymentDue) {
                if (! $current->payment_deadline || $current->payment_deadline->isFuture()) return;
                $this->recordDebt($current, $case?->approved_service_amount ?? $current->agreed_price, $current->payment_deadline, $case?->id);
                $current->forceFill(['settlement_status' => 'awaiting_admin'])->save();
                app(InstantMarketplace::class)->notify($current->customer_id, 'انتهت مهلة الدفع وسُجّل مبلغ مستحق على الحجز الفوري.');

                return;
            }

            $amount = $this->fils((string) ($payment?->paid_amount ?? '0')) - $this->fils((string) ($payment?->refunded_amount ?? '0'));
            $vendor = $current->vendorService->vendor_id;
            if ($case?->status === 'accepted') {
                $reference = $this->fils($current->agreed_price);
                $share = $this->roundedShare($reference, 15);
                $retained = $reference - $this->roundedShare($reference, 70);
                if ($amount < $retained) return;
                $commission = $retained - $share;
                $refund = $amount - $retained;
                if ($refund > 0) {
                    $refundAmount = $this->decimal($refund);
                    $this->entry($current, 'refund', $refundAmount, 'customer', $current->customer_id, 'credit', ['refund_request_id' => $case->id]);
                    $payment->forceFill(['refunded_amount' => $refundAmount, 'refunded_at' => now(), 'status' => 'partially_refunded'])->save();
                    $case->forceFill(['refund_amount' => $refundAmount])->save();
                }
            } else {
                $share = $this->roundedShare($amount, 85);
                $commission = $amount - $share;
            }
            $advance = $this->ledger($current, 'platform_advance');
            if ($advance) {
                abort_unless($this->fils($advance->amount) === $share, 409);
                $this->entry($current, 'advance_recovery', $advance->amount, 'platform', null, 'credit', ['related_transaction_id' => $advance->id]);
                $this->platformEntry($current, 'advance_recovery', $advance->amount, 'credit');
            } else {
                $net = $this->recoverVendorDebts($current, $vendor, $share);
                $this->entry($current, 'payout', $this->decimal($net), 'vendor', $vendor, 'credit');
            }
            $this->entry($current, 'commission', $this->decimal($commission), 'platform', null, 'credit');
            if ($commission > 0) {
                $this->platformEntry($current, 'commission', $this->decimal($commission), 'credit');
            }
            $current->forceFill(['settlement_status' => 'settled', 'settled_at' => now()])->save();
            app(InstantMarketplace::class)->notify($vendor, 'تم تحرير مستحقات الحجز الفوري.');
            app(InstantMarketplace::class)->notify($current->customer_id, 'تمت تسوية الحجز الفوري رقم '.$current->id.'.');
        });
    }

    public function availableFils(): int
    {
        $entries = DB::table('wallet_transactions')->whereNull('booking_id')
            ->where('account_type', 'platform')->where('status', 'posted')
            ->where('idempotency_key', 'like', 'now-cash:%')
            ->whereIn('transaction_type', ['commission', 'platform_advance', 'advance_recovery', 'adjustment']);

        return (int) $entries->selectRaw("COALESCE(SUM(CASE WHEN direction = 'debit' THEN -amount ELSE amount END) * 1000, 0) AS fils")->value('fils');
    }

    public function reverseCommission(int $originalId, int $admin, string $amount, string $reason, string $requestId): void
    {
        $this->withPlatformLock(function () use ($originalId, $admin, $amount, $reason, $requestId) {
            abort_unless(User::query()->whereKey($admin)->where('role', 'admin')->where('status', 'active')->exists(), 403);
            $original = DB::table('wallet_transactions')->where('id', $originalId)->whereNull('booking_id')
                ->where('account_type', 'platform')->where('transaction_type', 'commission')
                ->where('direction', 'credit')->where('status', 'posted')->where('idempotency_key', 'like', 'now-cash:%')
                ->lockForUpdate()->first();
            abort_unless($original, 404);
            $fils = $this->fils($amount);
            abort_unless($fils > 0, 422);
            $key = 'now-cash:correction:'.$requestId;
            $entries = DB::table('wallet_transactions');
            $existing = (clone $entries)->where('idempotency_key', $key)->first();
            if ($existing) {
                abort_unless((int) $existing->related_transaction_id === $originalId && (int) $existing->performed_by === $admin
                    && $this->fils($existing->amount) === $fils && $existing->description === $reason, 409);
                return;
            }
            $previous = (clone $entries)->where('transaction_type', 'adjustment')->where('related_transaction_id', $originalId)
                ->where('direction', 'debit')->where('status', 'posted')->sum('amount');
            abort_if($fils > $this->fils($original->amount) - $this->fils((string) $previous) || $fils > $this->availableFils(), 409);
            $entries->insert([
                'account_type' => 'platform', 'transaction_type' => 'adjustment', 'status' => 'posted', 'direction' => 'debit',
                'idempotency_key' => $key, 'amount' => $this->decimal($fils), 'related_transaction_id' => $originalId,
                'performed_by' => $admin, 'description' => $reason, 'created_at' => now(),
            ]);
        });
    }

    public function fundVendor(Booking $booking, int $admin): bool
    {
        $vendorId = $booking->vendorService?->vendor_id;
        return $this->withPlatformLock(function () use ($booking, $admin, $vendorId) {
            abort_unless(User::query()->whereKey([$booking->customer_id, $vendorId])->orderBy('id')->lockForUpdate()->get()->count() === 2, 404);
            $current = Booking::query()->where('booking_type', 'instant')->lockForUpdate()->findOrFail($booking->id);
            abort_unless(User::query()->whereKey($admin)->where('role', 'admin')->where('status', 'active')->exists(), 403);
            if ($this->ledger($current, 'platform_advance')) return true;
            abort_unless($current->status === 'completed' && ! $current->settled_at
                && $current->objection_deadline?->lte(now()) && ! $this->blocked($current)
                && $this->debt($current) && $current->payment?->status !== 'paid'
                && ! $this->ledger($current, 'payout'), 409);

            $reference = $this->exceptionalResolution($current)?->approved_service_amount ?? $current->agreed_price;
            $share = $this->roundedShare($this->fils($reference), $current->refundRequest?->status === 'accepted' ? 15 : 85);
            abort_unless($share > 0, 409);
            $market = app(InstantMarketplace::class);
            $vendor = $current->vendorService->vendor_id;
            if ($this->availableFils() < $share) {
                if ($current->settlement_status !== 'insufficient_funds') {
                    $current->forceFill(['settlement_status' => 'insufficient_funds'])->save();
                    $market->notify($vendor, 'مستحق الحجز الفوري معلّق بانتظار توفر رصيد تجريبي كافٍ.');
                    $market->notify($admin, 'الرصيد التجريبي لا يكفي لتمويل الحجز الفوري رقم '.$current->id.'.');
                }
                return false;
            }

            $amount = $this->decimal($share);
            $this->entry($current, 'platform_advance', $amount, 'platform', null, 'debit', ['performed_by' => $admin]);
            $this->platformEntry($current, 'platform_advance', $amount, 'debit');
            $net = $this->recoverVendorDebts($current, $vendor, $share);
            $this->entry($current, 'payout', $this->decimal($net), 'vendor', $vendor, 'credit', [
                'performed_by' => $admin,
                'related_transaction_id' => $this->ledger($current, 'platform_advance')->id,
            ]);
            $current->forceFill(['settlement_status' => 'advanced'])->save();
            $market->notify($vendor, 'تم تمويل مستحق الحجز الفوري من الرصيد التجريبي.');

            return true;
        });
    }

    public function recordOverpayment(Booking $booking, int $entryId, int $admin, string $corrected, string $reason): void
    {
        $owner = $booking->walletTransactions()->whereKey($entryId)->value('user_id');
        abort_unless($owner, 404);
        $this->withPlatformLock(function () use ($booking, $entryId, $admin, $corrected, $reason, $owner) {
            User::whereKey($owner)->lockForUpdate()->firstOrFail();
            $current = Booking::withTrashed()->lockForUpdate()->findOrFail($booking->id);
            abort_unless(User::whereKey($admin)->where('role', 'admin')->where('status', 'active')->exists(), 403);
            $original = $current->walletTransactions()->whereKey($entryId)->where('status', 'posted')->where('direction', 'credit')
                ->whereIn('account_type', ['customer', 'vendor'])->whereIn('transaction_type', ['compensation', 'payout'])->lockForUpdate()->firstOrFail();
            $suffix = ':overpayment-'.$entryId;
            $excess = $this->fils($original->amount) - $this->fils($corrected);
            abort_if($this->fils($corrected) < 0 || $excess <= 0 || ! $original->user_id, 422);
            $correction = $this->ledger($current, 'adjustment'.$suffix);
            if ($correction) {
                abort_unless($this->fils($correction->amount) === $excess && $correction->description === $reason
                    && (int) $correction->performed_by === $admin, 409);
                return;
            }
            $source = DB::table('wallet_transactions')->where('id', $original->related_transaction_id)->first();
            if ($source?->transaction_type === 'platform_advance' && ! $this->ledger($current, 'advance_recovery')) {
                $recipient = ['platform', null];
            } elseif ($source?->transaction_type === 'debt') {
                $recipient = [$source->account_type, $source->user_id];
            } elseif ($original->transaction_type === 'payout' && $current->settled_at && $current->payment?->paid_amount > 0) {
                $recipient = ['customer', $current->customer_id];
            } else {
                abort(409, 'مصدر الدفعة لا يثبت الحساب المستحق للاسترداد.');
            }
            abort_if(($recipient[0] !== 'platform' && ! $recipient[1])
                || ($recipient[1] !== null && (int) $recipient[1] === (int) $original->user_id), 409);
            $amount = $this->decimal($excess);
            $this->entry($current, 'adjustment', $amount, $recipient[0], $recipient[1], 'credit', [
                'status' => 'outstanding', 'description' => $reason, 'related_transaction_id' => $original->id, 'performed_by' => $admin,
            ], $suffix);
            $this->entry($current, 'debt', $amount, $original->account_type, $original->user_id, 'debit', [
                'status' => 'outstanding', 'description' => $reason, 'due_at' => now(), 'performed_by' => $admin,
                'related_transaction_id' => $this->ledger($current, 'adjustment'.$suffix)->id,
            ], $suffix);
            app(InstantMarketplace::class)->notify($original->user_id, 'سُجّل تصحيح موثّق لدفعة زائدة.');
        });
    }

    public function repayOverpayment(int $debtId, int $customer): void
    {
        $this->withPlatformLock(function () use ($debtId, $customer) {
            abort_unless(User::lockForUpdate()->findOrFail($customer)->status === 'active', 403);
            $debt = DB::table('wallet_transactions')->where('id', $debtId)->where('user_id', $customer)->where('account_type', 'customer')
                ->where('transaction_type', 'debt')->where('idempotency_key', 'like', 'now:%:debt:overpayment-%')->lockForUpdate()->first();
            abort_unless($debt, 404);
            if ($debt->status === 'repaid') return;
            $booking = Booking::withTrashed()->lockForUpdate()->findOrFail($debt->booking_id);
            abort_unless($debt->status === 'outstanding' && ! $this->blocked($booking), 409);
            $correction = DB::table('wallet_transactions')->where('id', $debt->related_transaction_id)->first();
            abort_unless($correction?->status === 'outstanding', 409);
            $this->recoverOverpayment($booking, $debt, $correction, $this->remainingDebtFils($debt), 'customer');
        });
    }

    private function recoverOverpayment(Booking $booking, object $debt, object $correction, int $fils, string $source): void
    {
        $amount = $this->decimal($fils);
        $suffix = ':correction-'.$correction->id.'-'.$source;
        $this->entry($booking, 'debt_repayment', $amount, $debt->account_type, $debt->user_id, 'debit',
            ['related_transaction_id' => $debt->id], $suffix);
        $this->entry($booking, 'adjustment', $amount, $correction->account_type, $correction->user_id, 'credit',
            ['related_transaction_id' => $correction->id], $suffix);
        if ($correction->account_type === 'platform') {
            $this->platformEntry($booking, 'adjustment', $amount, 'credit', $suffix);
        }
        if ($this->remainingDebtFils($debt) === 0) {
            DB::table('wallet_transactions')->whereIn('id', [$debt->id, $correction->id])->update(['status' => 'repaid']);
        }
    }

    public function withPlatformLock(callable $operation): mixed
    {
        $connection = DB::connection();
        $lock = 'now-platform-'.sha1($connection->getDatabaseName());
        abort_unless((int) $connection->selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lock], false)->acquired === 1, 503);
        try {
            return $connection->transaction($operation, 3);
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock], false);
        }
    }

    public function scheduledNetEntitlement(Booking $booking, int $vendor, string $gross): string
    {
        $original = $this->fils($gross);
        $remaining = $this->recoverVendorDebts($booking, $vendor, $original);
        if ($remaining < $original) {
            app(InstantMarketplace::class)->notify($vendor, 'اقتُطع '.$this->decimal($original - $remaining).' د.أ من حصة الحجز لسداد دين فوري.');
        }

        return $this->decimal($remaining);
    }

    private function platformEntry(Booking $booking, string $type, string $amount, string $direction, string $suffix = ''): void
    {
        DB::table('wallet_transactions')->insert([
            'idempotency_key' => "now-cash:{$booking->id}:{$type}{$suffix}", 'transaction_type' => $type,
            'account_type' => 'platform', 'amount' => $amount, 'direction' => $direction,
            'status' => 'posted', 'created_at' => now(),
        ]);
    }

    private function recordDebt(Booking $booking, string $amount, $dueAt, ?int $caseId): void
    {
        $this->entry($booking, 'debt', $amount, 'customer', $booking->customer_id, 'debit', [
            'status' => 'outstanding', 'due_at' => $dueAt, 'refund_request_id' => $caseId,
        ]);
    }

    private function ledger(Booking $booking, string $type): ?object
    {
        return DB::table('wallet_transactions')->where('idempotency_key', "now:{$booking->id}:{$type}")->first();
    }

    private function remainingDebtFils(object $debt): int
    {
        $repaid = DB::table('wallet_transactions')->where('related_transaction_id', $debt->id)
            ->where('transaction_type', 'debt_repayment')->where('status', 'posted')->sum('amount');

        return $this->fils($debt->amount) - $this->fils((string) $repaid);
    }

    private function recoverVendorDebts(Booking $earned, int $vendor, int $available): int
    {
        if ($available === 0) return 0;
        $debts = DB::table('wallet_transactions')->where('account_type', 'vendor')->where('user_id', $vendor)
            ->where('transaction_type', 'debt')->where('status', 'outstanding')->orderBy('id')->lockForUpdate()->get();
        foreach ($debts as $debt) {
            if (str_contains((string) $debt->idempotency_key, ':debt:overpayment-')) {
                $origin = Booking::withTrashed()->find($debt->booking_id);
                $correction = DB::table('wallet_transactions')->where('id', $debt->related_transaction_id)->first();
                if (! $origin || $this->blocked($origin) || $correction?->status !== 'outstanding') continue;
                $offset = min($available, $this->remainingDebtFils($debt));
                if ($offset <= 0) continue;
                $this->recoverOverpayment($origin, $debt, $correction, $offset, (string) $earned->id);
                $available -= $offset;
                if ($available === 0) break;
                continue;
            }
            $source = Booking::query()->find($debt->booking_id);
            $case = $source?->cases()->find($debt->refund_request_id);
            if (! $case || $this->blocked($source) || ! in_array($case->compensation_status, ['approved', 'paid'], true)) continue;
            $remaining = $this->remainingDebtFils($debt);
            if ($remaining <= 0) continue;
            $deduction = min($available, $remaining);
            $amount = $this->decimal($deduction);
            $suffix = ':offset-'.$earned->id;
            $this->entry($source, 'debt_repayment', $amount, 'vendor', $vendor, 'debit', [
                'related_transaction_id' => $debt->id, 'refund_request_id' => $case->id,
            ], $suffix);
            $advance = $this->ledger($source, 'platform_advance');
            if ($advance) {
                $this->entry($source, 'advance_recovery', $amount, 'platform', null, 'credit', ['related_transaction_id' => $advance->id], $suffix);
                $this->platformEntry($source, 'advance_recovery', $amount, 'credit', $suffix);
            } else {
                $this->entry($source, 'compensation', $amount, 'customer', $case->beneficiary_id, 'credit', [
                    'related_transaction_id' => $debt->id, 'refund_request_id' => $case->id,
                ], $suffix);
            }
            if ($deduction === $remaining) {
                DB::table('wallet_transactions')->where('id', $debt->id)->update(['status' => 'repaid']);
                $case->forceFill(['compensation_status' => 'paid'])->save();
                $source->forceFill(['settlement_status' => 'settled'])->save();
            }
            $available -= $deduction;
            if ($available === 0) break;
        }

        return $available;
    }

    private function blocked(Booking $booking): bool
    {
        return $booking->pendingCases()->exists();
    }

    private function debt(Booking $booking): ?object
    {
        return DB::table('wallet_transactions')->where('booking_id', $booking->id)->where('transaction_type', 'debt')
            ->where('account_type', 'customer')->where('status', 'outstanding')
            ->where('idempotency_key', 'not like', '%:debt:overpayment-%')->first();
    }

    private function entry(Booking $booking, string $type, string $amount, string $account, ?int $user, string $direction, array $extra = [], string $suffix = ''): void
    {
        $key = "now:{$booking->id}:{$type}{$suffix}";
        if (DB::table('wallet_transactions')->where('idempotency_key', $key)->exists()) return;
        DB::table('wallet_transactions')->insert($extra + [
            'booking_id' => $booking->id, 'idempotency_key' => $key, 'transaction_type' => $type, 'amount' => $amount,
            'account_type' => $account, 'user_id' => $user, 'direction' => $direction, 'status' => 'posted', 'created_at' => now(),
        ]);
    }

    private function fils(string $amount): int
    {
        [$dinars, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $dinars * 1000 + (int) str_pad($fraction, 3, '0');
    }

    private function decimal(int $fils): string
    {
        return sprintf('%d.%03d', intdiv($fils, 1000), $fils % 1000);
    }

    private function roundedShare(int $fils, int $percent): int
    {
        return intdiv($fils * $percent + 50, 100);
    }
}
