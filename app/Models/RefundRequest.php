<?php

namespace App\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RefundRequest extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $casts = ['reviewed_at' => 'datetime', 'compensation_due_at' => 'datetime', 'compensation_amount' => 'decimal:3'];

    protected $fillable = [
        'booking_id',
        'submitted_by',
        'reason',
        'status',
        'admin_response',
        'refund_amount',
        'reviewed_at',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id')->withTrashed();
    }

    public function customer(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Booking::class, 'id', 'id', 'booking_id', 'customer_id')->withTrashedParents();
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
