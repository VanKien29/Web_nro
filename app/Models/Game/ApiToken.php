<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $connection = 'game';
    protected $table = 'api_tokens';
    public $timestamps = false;

    protected $fillable = ['user_id', 'token'];

    public function account()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    public static function generateFor(int $userId): self
    {
        return self::create([
            'user_id' => $userId,
            'token' => Str::random(64),
        ]);
    }
}
