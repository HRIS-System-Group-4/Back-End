<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'company';

    protected $fillable = [
        'id',
        'company_username',
        'company_name',
        'description',
        'branch_id',
        'latitude',
        'longitude',
        'location_radius',
        'subscription_active',
        'subscription_expires_at',
    ];

    protected $casts = [
        'subscription_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->hasOne(Admin::class, 'company_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'company_id');
    }
}
