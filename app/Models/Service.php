<?php

namespace App\Models;

use App\Models\Category;
use App\Models\VendorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image',
        'approval_status',
        'rejection_reason',
        'is_deleted',
        'is_active',
        'suggested_by',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function vendorServices(): HasMany
    {
        return $this->hasMany(VendorService::class, 'service_id');
    }

    public function suggestedBy(): BelongsTo
{
    return $this->belongsTo(User::class, 'suggested_by');
}
}
