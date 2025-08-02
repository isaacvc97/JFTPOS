<?php

// app/Models/CartSale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartSale extends Model
{
    use HasFactory;
    // use SoftDeletes;
    
    protected $fillable = [
        'user_id',
        'session_id',
        'client_id',
        'note'
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i',
    ];


    public function items()
    {
        return $this->hasMany(CartSaleItem::class, 'cart_sale_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
