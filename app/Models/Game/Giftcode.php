<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class Giftcode extends Model
{
    protected $connection = 'game';
    protected $table = 'giftcode';
    public $timestamps = false;

    protected $fillable = [
        'code', 'item_id', 'quantity', 'option_id', 'option_param',
        'expired', 'max_use', 'used_count',
    ];
}
