<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bill_code', 'date', 'bus_datetime', 'amount', 'description', 'payment_details',
        'customer_info', 'courier_policy_id', 'company_id', 'eta', 'sst_details', 'policy_snapshot', 'media_attachment',
        'is_paid', 'created_by', 'checked_by'
    ];

    protected $casts = [
        'date' => 'date',
        'bus_datetime' => 'datetime',
        'amount' => 'decimal:2',
        'policy_snapshot' => 'array',
        'is_paid' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function courierPolicy()
    {
        return $this->belongsTo(CourierPolicy::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
