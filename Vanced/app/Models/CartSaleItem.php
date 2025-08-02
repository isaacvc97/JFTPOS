<?php

// app/Models/CartSaleItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartSaleItem extends Model
{
    use HasFactory;
    // use SoftDeletes;
    protected $fillable = [
        'cart_sale_id',
        'medicine_presentation_id',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
    ];

    // 🔗 Relación con el carrito de venta (cabecera)
    public function cartSale()
    {
        return $this->belongsTo(CartSale::class);
    }

    public function presentation()
    {
        return $this->belongsTo(MedicinePresentation::class, 'medicine_presentation_id')
            ->with('dosage.medicine', 'dosage.form');
    }

    // 🔗 Relación con la presentación del medicamento
    public function presentation0()
    {
        return $this->belongsTo(MedicinePresentation::class, 'medicine_presentation_id');
    }

    // 🔁 Acceso rápido a medicamento
    public function medication()
    {
        return $this->presentation?->dosage?->medication();
    }

    // 🔁 Acceso a concentración y forma
    public function dosage()
    {
        return $this->presentation?->dosage;
    }
}
