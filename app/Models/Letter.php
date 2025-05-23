<?php

// app/Models/Letter.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;     // UUID
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'letter_format_id',
        'user_id',
        'name',
        'file_path',
        'note',
    ];

    /* ---------- relasi ---------- */
    public function format()
    {
        return $this->belongsTo(LetterFormat::class, 'letter_format_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }


    /* ---------- accessor url ---------- */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
