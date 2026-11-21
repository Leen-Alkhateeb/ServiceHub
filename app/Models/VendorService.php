<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\Favorite;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorService extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'service_id',
        'description',
        'price',
        'image',
        'status',
        'duration_minutes',
        'rejection_reason',
    ];

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereHas('service', fn ($service) => $service->where('approval_status', 'approved')->where('is_deleted', false)
                ->whereHas('category', fn ($category) => $category->where('is_deleted', false)))
            ->whereHas('vendor', fn ($vendor) => $vendor->where('role', 'vendor')->where('status', 'active')
                ->whereHas('vendorProfile', fn ($profile) => $profile->where('application_status', 'approved')));
    }

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
