<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckClockSetting extends Model
{
    use HasFactory;

    protected $table = 'check_clock_settings';

    protected $fillable = [
        'id',
        'name',
        'type',
        'deleted_at',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
