<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bill_code', 'date', 'amount', 'description', 'payment_details',
        'customer_info', 'courier_policy_id', 'company_id', 'eta', 'sst_details', 'policy_snapshot', 'media_attachment'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'policy_snapshot' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function courierPolicy()
    {
        return $this->belongsTo(CourierPolicy::class);
    }
}
