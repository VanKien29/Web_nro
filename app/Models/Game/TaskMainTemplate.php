<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;

class TaskMainTemplate extends Model
{
    protected $connection = 'game';
    protected $table = 'task_main_template';
    public $timestamps = false;

    protected $fillable = ['id', 'name'];
}
