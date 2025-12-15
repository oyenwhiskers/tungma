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
        'customer_info', 'customer_ic_number', 'customer_received_date', 'from_company_id', 'to_company_id', 'sender_name', 'sender_phone',
        'receiver_name', 'receiver_phone', 'courier_policy_id', 'company_id', 'eta',
        'sst_details', 'policy_snapshot', 'media_attachment', 'payment_proof_attachment',
        'is_paid', 'status', 'created_by', 'checked_by'
    ];

    protected $casts = [
        'date' => 'date',
        'bus_datetime' => 'datetime',
        'customer_received_date' => 'date',
        'amount' => 'decimal:2',
        'policy_snapshot' => 'array',
        'is_paid' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function fromCompany()
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    public function toCompany()
    {
        return $this->belongsTo(Company::class, 'to_company_id');
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
