<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MbtBindUser extends Model
{
    use HasFactory;

    protected $table = 'mbt_bind_user';
    protected $guarded = [];
}
