<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id', 'medicine_presentation_id', 'quantity', 'price', 'subtotal'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

     public function presentation()
    {
        return $this->belongsTo(MedicinePresentation::class, 'medicine_presentation_id')
            ->with('dosage.medicine', 'dosage.form');
    }
}
