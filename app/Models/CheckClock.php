<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckClock extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'check_clock_type', 'check_clock_time', 'date', 'proof_path',];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
