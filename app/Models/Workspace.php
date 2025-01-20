<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'domain',
        'slack_workspace_id',
    ];

    public function googleDrives()
    {
        return $this->hasMany(GoogleDrive::class);
    }
}
