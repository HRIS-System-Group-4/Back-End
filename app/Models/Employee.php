<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'address',
        'ck_settings_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
