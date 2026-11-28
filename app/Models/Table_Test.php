<?php

namespace App\Models;

use Database\Factories\TableTestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Table_Test extends Model
{
        use HasFactory, Notifiable;
        protected static function newFactory()
    {
        return TableTestFactory::new();
    }

}
