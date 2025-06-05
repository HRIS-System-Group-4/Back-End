<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClockRequest extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'check_clock_type',
        'check_clock_time',
        'proof_path',
        'latitude',
        'longitude',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id', 'id');
    }
}
