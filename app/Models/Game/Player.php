<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $connection = 'game';
    protected $table = 'player';
    public $timestamps = false;

    protected $fillable = [
        'account_id', 'name', 'gender', 'head', 'data_point', 'data_task',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function getPowerAttribute(): int
    {
        $dp = json_decode($this->data_point, true);
        return (is_array($dp) && isset($dp[1])) ? (int) $dp[1] : 0;
    }

    public function getTaskDataAttribute(): array
    {
        $dt = json_decode($this->data_task, true);
        return is_array($dt) ? $dt : [];
    }
}
