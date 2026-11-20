<?php

namespace App\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringAvailability extends Model
{
    protected $table = 'recurring_availability';

    protected $fillable = [
        'vendor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'availability_id');
    }
}
