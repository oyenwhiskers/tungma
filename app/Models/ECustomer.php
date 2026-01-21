<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ECustomer extends Model
{
    protected $fillable = [
        'date_time',
        'bill_id',
        'amount',
        'tin_number',
        'customer_name',
        'customer_type',
        'contact_number',
        'email_address',
        'identity_type',
        'customer_ic',
        'business_reg_number',
        'old_business_reg_number',
        'sst_reg_number',
        'msic_code',
        'address',
        'postcode',
        'city',
        'state',
        'country',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function msicCodes()
    {
        return $this->belongsToMany(MsicCode::class, 'e_customer_msic_code', 'e_customer_id', 'msic_code_id');
    }
}
