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
        'state_code',
        'country',
        'country_code',
        'is_exported',
        'is_processed',
    ];

    /**
     * Malaysian State Codes mapping (LHDN compliant)
     */
    public static $stateCodes = [
        '01' => 'Johor',
        '02' => 'Kedah',
        '03' => 'Kelantan',
        '04' => 'Melaka',
        '05' => 'Negeri Sembilan',
        '06' => 'Pahang',
        '07' => 'Pulau Pinang',
        '08' => 'Perak',
        '09' => 'Perlis',
        '10' => 'Selangor',
        '11' => 'Terengganu',
        '12' => 'Sabah',
        '13' => 'Sarawak',
        '14' => 'Wilayah Persekutuan Kuala Lumpur',
        '15' => 'Wilayah Persekutuan Labuan',
        '16' => 'Wilayah Persekutuan Putrajaya',
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
