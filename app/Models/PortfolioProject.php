<?php

namespace App\Models;

use App\Models\PortfolioImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PortfolioProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'completion_date',
        'project_url',
        'project_price',
        'is_active',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PortfolioImage::class, 'project_id');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(PortfolioImage::class, 'project_id')
            ->ofMany(['id' => 'min'], fn ($images) => $images->where('is_active', true));
    }
}
