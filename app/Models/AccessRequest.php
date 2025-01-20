<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $fillable = [
        'file_id',
        'file_name',
        'requester_email',
        'owner_email',
        'status'
    ];
} 