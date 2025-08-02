<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
     use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'identification', 'phone', 'email', 'address'
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
