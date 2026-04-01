<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class TopupTransaction extends Model
{
    protected $connection = 'game';
    protected $table = 'topup_transactions';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'amount', 'type', 'trans_id', 'status', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }
}
