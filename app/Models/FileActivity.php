<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileActivity extends Model
{
    protected $fillable = [
        'file_id',
        'file_name',
        'actor_email',
        'creator_email',
        'action_type',
        'changes',
        'is_read'
    ];

    protected $casts = [
        'changes' => 'array',
        'is_read' => 'boolean'
    ];
} 