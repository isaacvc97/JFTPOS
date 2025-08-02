<?php

// app/Models/CashRegister.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'opened_at', 'closed_at', 'initial_amount',
        'closing_amount', 'system_amount', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class);
    }

    public function getTotalIncomeAttribute()
    {
        return $this->movements()->where('type', 'income')->sum('amount');
    }

    public function getTotalExpenseAttribute()
    {
        return $this->movements()->where('type', 'expense')->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return $this->initial_amount + $this->total_income - $this->total_expense;
    }
}
