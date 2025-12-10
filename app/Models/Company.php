<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'contact_number', 'address', 'email', 'bill_id_prefix'
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
