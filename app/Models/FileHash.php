<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileHash extends Model
{
    protected $fillable = [
        'disk','path','hash','size','ref_count'
    ];
}
