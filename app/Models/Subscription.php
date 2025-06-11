<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'admin_id',
        'company_id',
        'subscription_pricing_id',
        'start_date',
        'end_date',
        'is_active',
        'created_at',
        'updated_at',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function isCurrentlyActive()
    {
        $now = now()->toDateString();
        return $this->is_active && $this->start_date <= $now && $this->end_date >= $now;
    }

    public function pricing()
    {
        return $this->belongsTo(SubscriptionPricing::class, 'subscription_pricing_id');
    }
}
