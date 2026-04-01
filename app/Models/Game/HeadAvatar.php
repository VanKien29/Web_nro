<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class HeadAvatar extends Model
{
    protected $connection = 'game';
    protected $table = 'head_avatar';
    public $timestamps = false;

    protected $fillable = ['head_id', 'gender', 'avatar_url'];
}
