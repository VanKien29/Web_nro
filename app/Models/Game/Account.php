<?php

namespace App\Models\Game;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    protected $connection = 'game';
    protected $table = 'account';
    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'username', 'password', 'email', 'ban', 'is_admin', 'active',
        'cash', 'danap', 'coin', 'thoi_vang', 'tv_mo', 'diem_da_nhan',
        'luotquay', 'vang', 'event_point', 'token', 'xsrf_token', 'ip_address',
    ];

    protected $hidden = ['password'];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function player()
    {
        return $this->hasOne(Player::class, 'account_id');
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class, 'user_id');
    }
}
