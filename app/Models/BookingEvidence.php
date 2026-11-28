<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingEvidence extends Model
{
    protected $table = 'images';

    public $timestamps = false;

    protected $fillable = ['kind', 'path', 'instant_request_id', 'uploaded_by', 'created_at'];

    protected $hidden = ['path'];
}
