<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model {
    protected $fillable = ['nombre', 'ruc', 'telefono', 'direccion', 'owner_id'];

    public function owner() {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users() {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function invitations() {
        return $this->hasMany(BranchInvitation::class);
    }

    public function invitacionesPendientes() {
        return $this->hasMany(BranchInvitation::class)->where('estado', 'pendiente');
    }
}
