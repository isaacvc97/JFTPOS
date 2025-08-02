<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'access_key', 'client_id', 'user_id', 'sale_type', 'payment_type', 'total', 'pago', 'cambio', 'status'
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->hasOne(Account::class);
    }

    // PURCHASE
    // public function account()
    // {
    //     return $this->hasOne(Account::class);
    // }
}
