<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    /**
     * Tabel yang dipakai model.
     */
    protected $table = 'branch';

    /**
     * Primary key bertipe string (UUID).
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Kolom yang dapat di-isi mass-assignment.
     */
    protected $fillable = [
        'id',
        'company_id',
        'branch_name',
        'address',
        'city',
        'country',
    ];

    /**
     * ---------------------
     *      RELATIONS
     * ---------------------
     */

    /**
     * Branch → Company (Many-to-One).
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Branch → Employees (One-to-Many).
     * Pastikan model Employee punya relasi kebalikannya (belongsTo Branch).
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}