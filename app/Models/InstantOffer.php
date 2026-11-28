<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstantOffer extends Model
{
    protected $fillable = ['vendor_service_id', 'offered_price', 'arrival_minutes', 'duration_minutes', 'status'];

    protected $casts = ['offered_price' => 'decimal:3'];

    public function vendorService(): BelongsTo
    {
        return $this->belongsTo(VendorService::class);
    }

    public function instantRequest(): BelongsTo
    {
        return $this->belongsTo(InstantRequest::class);
    }
}
