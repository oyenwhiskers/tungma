<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivity;

class Bill extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'bill_code',
        'date',
        'amount',
        'description',
        'payment_details',
        'from_company_id',
        'to_company_id',
        'sender_name',
        'sender_phone',
        'receiver_name',
        'receiver_phone',
        'courier_policy_id',
        'company_id',
        'sst_details',
        'policy_snapshot',
        'media_attachment',
        'payment_proof_attachment',
        'is_paid',
        'is_collected',
        'status',
        'created_by',
        'checked_by',
        'bus_departures_id'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'policy_snapshot' => 'array',
        'is_paid' => 'boolean',
        'is_collected' => 'boolean',
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

    public function busDeparture()
    {
        return $this->belongsTo(BusDepartures::class, 'bus_departures_id')->withTrashed();
    }
}
