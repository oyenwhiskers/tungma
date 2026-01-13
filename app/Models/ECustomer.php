<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ECustomer extends Model
{
    use HasFactory;

    // Optional (Laravel will auto-detect)
    protected $table = 'e_customers';

    protected $fillable = [
        'ic_number',
        'name',
        'contact_number',
        'email_address',
        'address',
        'tin_number',
    ];
}
