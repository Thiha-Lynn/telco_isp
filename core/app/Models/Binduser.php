<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Binduser extends Model
{
    use HasFactory;

    protected $table = 'bind_history';
    protected $guarded = [];
}
