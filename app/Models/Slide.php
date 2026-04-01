<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    protected $fillable = ['title', 'image', 'link', 'status', 'sort_order'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
