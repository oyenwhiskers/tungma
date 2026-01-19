<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsicCode extends Model
{
    protected $table = 'msic_codes';
    protected $fillable = ['code', 'description'];
    public $timestamps = false;
}
