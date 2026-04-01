<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $connection = 'game';
    protected $table = 'account';
    public $timestamps = false;

    protected $fillable = [
        'username', 'password', 'email', 'ban', 'is_admin', 'active',
        'cash', 'danap', 'coin', 'thoi_vang', 'tv_mo', 'diem_da_nhan',
        'luotquay', 'vang', 'event_point', 'token', 'xsrf_token',
    ];

    public function player()
    {
        return $this->hasOne(Player::class, 'account_id');
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class, 'user_id');
    }
}
