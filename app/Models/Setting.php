<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key_name', 'value', 'description'];

    public static function getValue(string $key, string $default = ''): string
    {
        $setting = static::where('key_name', $key)->first();
        return $setting?->value ?? $default;
    }
}
