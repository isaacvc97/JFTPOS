<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineDosage extends Model
{
    protected $fillable = ['medicine_id', 'medicine_form_id', 'concentration'];
    public function medicine() {
        return $this->belongsTo(Medicine::class);
    }
    public function form() {
        return $this->belongsTo(MedicineForm::class, 'medicine_form_id');
    }
    public function presentations() {
        return $this->hasMany(MedicinePresentation::class, 'medicine_dosage_id');
    }
}
