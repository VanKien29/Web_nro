<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'admin_username',
        'action',
        'target_type',
        'target_id',
        'target_label',
        'summary',
        'before_state',
        'after_state',
        'meta',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'meta' => 'array',
    ];

    public $timestamps = true;

    const UPDATED_AT = null;
}
