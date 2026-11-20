<?php

namespace App\Models;

use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PortfolioImage extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'image',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(PortfolioProject::class, 'project_id');
    }

    public function displayUrl(): ?string
    {
        if (str_starts_with($this->image, 'demo/portfolio/')) {
            $asset = 'assets/demo/portfolio/'.basename($this->image);

            return is_file(public_path($asset)) ? asset($asset) : null;
        }

        return Storage::disk('public')->exists($this->image) ? route('vendor.portfolio.image', $this) : null;
    }
}
