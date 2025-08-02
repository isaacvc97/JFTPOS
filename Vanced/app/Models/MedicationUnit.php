<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicationUnit extends Model
{
    public $table = 'medication_units';
    public $timestamps = false;

    protected $guarded = [];
}
