<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ECompany extends Model
{
    use HasFactory;

    protected $table = 'e_companies';

    protected $fillable = [
        'name',
        'contact_number',
        'email_address',
        'address',
        'business_activity_description',
        'registration_number',
        'tin_number',
        'sst_registration_number',
        'msic_code',
    ];

}
