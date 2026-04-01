<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class ItemTemplate extends Model
{
    protected $connection = 'game';
    protected $table = 'item_template';
    public $timestamps = false;

    protected $fillable = ['id', 'name', 'icon_id', 'type'];
}
