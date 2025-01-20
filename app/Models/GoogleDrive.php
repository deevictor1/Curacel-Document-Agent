<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleDrive extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'drive_id',
        'name',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
