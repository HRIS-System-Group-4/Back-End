<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterFormat extends Model
{
    use HasFactory, SoftDeletes;
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'name',
        'content',
        'status',       // 1 = aktif, 0 = non-aktif (atau sesuai enum Anda)
    ];

    protected $dates = ['deleted_at'];

    public function letters()
    {
        return $this->hasMany(Letter::class, 'letter_format_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
