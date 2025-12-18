<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusDepartures extends Model
{
    protected $fillable = [
        'departure_time',
        'company_id',
    ];

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
