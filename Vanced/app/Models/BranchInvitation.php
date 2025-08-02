<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchInvitation extends Model {
    protected $fillable = ['branch_id', 'email', 'token', 'estado', 'enviado_por'];

    public function branch() {
        return $this->belongsTo(Branch::class);
    }
}
