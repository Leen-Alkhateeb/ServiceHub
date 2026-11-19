<?php

namespace App\Models;

use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioImage extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'image',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PortfolioProject::class, 'project_id');
    }
}
