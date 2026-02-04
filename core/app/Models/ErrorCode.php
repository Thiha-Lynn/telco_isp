<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorCode extends Model
{
    use HasFactory;

    protected $table = 'error_code';
    protected $guarded = [];
}
