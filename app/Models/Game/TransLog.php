<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class TransLog extends Model
{
    protected $connection = 'game';
    protected $table = 'trans_log';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'type', 'telco', 'serial', 'code', 'amount',
        'declared_amount', 'status', 'trans_id', 'message', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }
}
