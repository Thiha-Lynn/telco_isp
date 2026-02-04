<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusDescription extends Model
{
    use HasFactory;

    protected $table = 'status_description';
    protected $guarded = [];
}
