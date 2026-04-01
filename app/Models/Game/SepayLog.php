<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class SepayLog extends Model
{
    protected $connection = 'game';
    protected $table = 'sepay_logs';
    public $timestamps = false;

    protected $fillable = [
        'trans_id', 'amount', 'content', 'account_number',
        'bank', 'status', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
