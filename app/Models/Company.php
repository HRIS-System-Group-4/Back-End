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
        'branch_id'
    ];
}
