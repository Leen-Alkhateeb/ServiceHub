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

    protected $table = 'images';

    protected $attributes = ['kind' => 'portfolio'];

    protected $fillable = [
        'project_id',
        'image',
        'path',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('portfolio', fn ($query) => $query->where('images.kind', 'portfolio'));
    }

    public function getImageAttribute(): string
    {
        return $this->path;
    }

    public function setImageAttribute(string $path): void
    {
        $this->attributes['path'] = $path;
    }

    public function getRouteKey()
    {
        return $this->legacy_portfolio_id ?? 'shared-'.$this->getKey();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if (preg_match('/^shared-([0-9]+)$/D', (string) $value, $match)) {
            return $query->whereKey($match[1]);
        }

        return $query->where('legacy_portfolio_id', ctype_digit((string) $value) ? $value : 0);
    }

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
