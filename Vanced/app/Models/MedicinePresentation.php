<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicinePresentation extends Model
{
    protected $fillable = ['medicine_dosage_id', 'unit_type', 'quantity', 'cost', 'price', 'stock', 'barcode'];
    
    public function medicine()
    {
        return $this->belongsTo(Medicine::class, "medicine_id");
    }

    public function medicines()
    {
        return $this->belongsTo(MedicinePresentation::class);
    }

    public function dosage() 
    {
        return $this->belongsTo(MedicineDosage::class, 'medicine_dosage_id');
    }

    public function cartSaleItems()
    {
        return $this->hasMany(CartSaleItem::class);
    }
}
