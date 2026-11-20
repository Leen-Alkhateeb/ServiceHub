<?php

namespace App\Models;

use App\Models\VendorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'customer_id',
        'vendor_service_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendorService(): BelongsTo
    {
        return $this->belongsTo(VendorService::class, 'vendor_service_id');
    }
}
