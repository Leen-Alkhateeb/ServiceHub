<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstantRequest extends Model
{
    protected $fillable = ['customer_id', 'service_id', 'description', 'address', 'latitude', 'longitude', 'status', 'expires_at'];

    protected $hidden = ['address', 'latitude', 'longitude'];

    protected $casts = ['expires_at' => 'datetime'];

    public function offers(): HasMany
    {
        return $this->hasMany(InstantOffer::class);
    }
}
