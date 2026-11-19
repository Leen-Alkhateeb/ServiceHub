<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'sender_id',
        'subject',
        'message',
        'status',
        'reviewed_at',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
