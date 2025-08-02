<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'generic_name', 'laboratory_id', 'description'];

    public function laboratory() {
        return $this->belongsTo(Laboratory::class);
    }

    public function dosages() {
        return $this->hasMany(MedicineDosage::class);
    }
}
