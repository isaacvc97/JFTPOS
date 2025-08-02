<?php

namespace App\Models;

use App\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'date',
        'amount',
        'method',
        'note',
    ];

    /* protected $casts = [
        'date' => 'date: Y-m-d H:i:s',
    ]; */

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}