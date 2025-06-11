<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionInvoice extends Model
{
    protected $table = 'subscription_invoices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'pricing_id',
        'xendit_invoice_id',
        'status',
        'amount',
        'invoice_url',
        'expires_at',
    ];

    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function pricing()
    {
        return $this->belongsTo(SubscriptionPricing::class, 'pricing_id');
    }
}
