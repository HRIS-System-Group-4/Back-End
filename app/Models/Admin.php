<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'admin';

    protected $fillable = [
        'id',
        'user_id',
        'first_name',
        'last_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }
}
