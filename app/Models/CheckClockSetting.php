<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckClockSetting extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'type'];

    // Constants for clock types
    const TYPE_CLOCK_IN  = 1;
    const TYPE_CLOCK_OUT = 2;
    const TYPE_SICK_LEAVE  = 3;
    const TYPE_ANNUAL_LEAVE = 4;
    const TYPE_ABSENT = 5;

    public function times()
    {
        return $this->hasMany(CheckClockSettingTime::class, 'ck_settings_id');
    }

    /**
     * Return readable name of type
     */
    public static function getTypeName($type)
    {
        return match ($type) {
            self::TYPE_CLOCK_IN  => 'Clock In',
            self::TYPE_CLOCK_OUT => 'Clock Out',
            self::TYPE_SICK_LEAVE     => 'Sick Leave',
            self::TYPE_ANNUAL_LEAVE     => 'Annual Leave',
            self::TYPE_ABSENT    => 'Absent',
            default              => 'Unknown',
        };
    }

    public function getTypeLabelAttribute()
    {
        return self::getTypeName($this->type);
    }
}
