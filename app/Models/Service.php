<?php

namespace App\Models;

use App\Models\Category;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image',
        'is_deleted',
        'approval_status',
        'rejection_reason',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function vendorServices(): HasMany
    {
        return $this->hasMany(VendorService::class, 'service_id');
    }
}
