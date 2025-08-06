<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model {
    protected $fillable = ['nombre', 'ruc', 'telefono', 'direccion', 'owner_id'];
    
    public function owner() {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users() {
        return $this->hasMany(User::class);
    }

    public function invitaciones() {
        return $this->hasMany(BranchInvitation::class);
    }
}
