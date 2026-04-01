<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class ItemOptionTemplate extends Model
{
    protected $connection = 'game';
    protected $table = 'item_option_template';
    public $timestamps = false;

    protected $fillable = ['id', 'name'];
}
