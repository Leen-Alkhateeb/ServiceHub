<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\Favorite;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorService extends Model
{
    protected $fillable = [
        'vendor_id',
        'service_id',
        'description',
        'price',
        'image',
        'is_active',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'vendor_service_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'vendor_service_id');
    }
}
