<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Company extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'contact_number',
        'address',
        'email',
        'based_in',
        'registration_number',
        'sst_number',
        'bill_id_prefix'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
