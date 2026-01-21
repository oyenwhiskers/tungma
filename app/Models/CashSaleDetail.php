<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSaleDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function cashSale()
    {
        return $this->belongsTo(CashSale::class);
    }
}
