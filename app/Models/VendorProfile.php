<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProfile extends Model
{
    use HasFactory;

    protected $hidden = ['latitude', 'longitude', 'location_updated_at', 'last_seen_at'];

    protected $fillable = [
        'user_id',
        'experience_years',
        'bio',
        'work_type',
        'work_permit_path',
        'permit_expiry_date',
        'application_status',
        'applied_at',
        'rejection_reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
