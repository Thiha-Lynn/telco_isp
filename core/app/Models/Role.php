<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'role';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'role_password',
        'created_at',
        'updated_at',
        'created_by',
    ];
}
