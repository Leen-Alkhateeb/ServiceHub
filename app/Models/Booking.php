<?php

namespace App\Models;

use App\Models\RecurringAvailability;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    public const SCHEDULE_TIMEZONE = 'Asia/Amman';

    public const SLOT_STEP_MINUTES = 30;

    public function scopeBlockingSlot(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where(function ($bookings) {
            $bookings->whereIn('status', ['pending', 'confirmed', 'paid', 'in_progress', 'completed'])
                ->orWhere(function ($unpaid) {
                    $unpaid->whereIn('status', ['approved', 'awaiting_payment'])
                        ->where(fn ($deadline) => $deadline->whereNull('payment_deadline')
                            ->orWhere('payment_deadline', '>', now()));
                });
        });
    }

    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'vendor_service_id',
        'availability_id',
        'booking_date',
        'start_time',
        'end_time',
        'service_name',
        'service_description',
        'original_price',
        'status',
        'approved_at',
        'payment_deadline',
        'completed_at',
        'objection_deadline',
        'cancelled_at',
        'cancelled_by',
        'cancellation_penalty',
        'cancellation_refund_amount',
        'cancellation_platform_share',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'approved_at' => 'datetime',
        'payment_deadline' => 'datetime',
        'customer_completed_at' => 'datetime',
        'vendor_completed_at' => 'datetime',
        'completed_at' => 'datetime',
        'objection_deadline' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function acceptsCompletionConfirmation(): bool
    {
        return in_array($this->status, ['confirmed', 'paid', 'in_progress'], true)
            && $this->payment?->status === 'paid'
            && $this->booking_date?->copy()->shiftTimezone(self::SCHEDULE_TIMEZONE)->setTimeFromTimeString($this->end_time)->lte(now());
    }

    public function scheduledStart(): ?\Illuminate\Support\Carbon
    {
        return $this->booking_date?->copy()->shiftTimezone(self::SCHEDULE_TIMEZONE)->setTimeFromTimeString($this->start_time);
    }

    public function canBeCancelled(): bool
    {
        if (! in_array($this->status, ['pending', 'awaiting_payment', 'approved', 'confirmed', 'paid'], true)
            || $this->customer_completed_at || $this->vendor_completed_at || ! $this->scheduledStart()?->isFuture()) {
            return false;
        }

        if ($this->payment?->status !== 'paid') {
            return true;
        }

        $paidAt = $this->payment->paid_at ?? $this->payment->created_at;

        return $paidAt !== null && now()->lte($paidAt->copy()->addHours(2));
    }

    public function vendorService(): BelongsTo
    {
        return $this->belongsTo(VendorService::class, 'vendor_service_id');
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(RecurringAvailability::class, 'availability_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'booking_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'booking_id');
    }

    public function refundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class, 'booking_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'booking_id');
    }
}
