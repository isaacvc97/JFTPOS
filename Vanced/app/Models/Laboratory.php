<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Laboratory extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
        'ruc',
        'address',
        'phone',
        'representative_name',
        'representative_phone'
    ];

    public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }
    
}
