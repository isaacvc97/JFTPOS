<?php
// app/Models/Account.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'description',
        'amount',
        'paid_amount',
        'due_date',
        'client_id',
        'purchase_id',
        'sale_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // Relaciones

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    // Métodos auxiliares

    public function getPendingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pendiente' && $this->due_date && $this->due_date->isPast();
    }


    public function payments()
    {
        return $this->hasMany(AccountPayment::class, 'account_id');
    }

    protected static function booted()
    {
        static::saving(function ($account) {
            $account->recalculateStatus();
        });
    }

    //  Importante: Si modificas los pagos desde el controlador, recuerda llamar a $account->recalculateStatus() después de agregar/eliminar un pago.

    public function recalculateStatus()
    {
        $this->paid_amount = $this->payments()->sum('amount');

        if ($this->paid_amount >= $this->amount) {
            $this->status = 'pagado';
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->status = 'vencido';
        } else {
            $this->status = 'pendiente';
        }
    }
}
