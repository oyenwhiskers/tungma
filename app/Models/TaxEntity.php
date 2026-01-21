<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxEntity extends Model
{
    use HasFactory;

    protected $fillable = [
        'identity_type',
        'identity_no',
        'tin',
        'full_tin',
        'tax_category',
        'tax_classification',
        'tax_branch_id',
        'address',
        'post_code',
        'phone',
        'email_address',
        'sst_register_no',
        'trade_name',
        'business_activity_desc',
        'msic_code',
        'city',
        'state_code',
        'country_code',
    ];
}
