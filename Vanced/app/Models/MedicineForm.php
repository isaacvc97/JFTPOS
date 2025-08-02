<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineForm extends Model
{
    protected $fillable = ['name', 'image'];

    public function dosages() {
    return $this->hasMany(MedicineDosage::class);
}
}
