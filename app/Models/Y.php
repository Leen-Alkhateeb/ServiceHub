<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Y extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'desc'];

    public function getTable()
    {
        return Schema::hasTable('y_s') ? 'y_s' : 'yTable';
    }
}
