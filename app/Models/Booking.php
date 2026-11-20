<?php

namespace App\Models;

use App\Models\RecurringAvailability;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\VendorService;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'booking_date' => 'date',
        'approved_at' => 'datetime',
        'payment_deadline' => 'datetime',
        'completed_at' => 'datetime',
        'objection_deadline' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
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