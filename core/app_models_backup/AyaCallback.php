<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AyaCallback extends Model
{
    protected $primaryKey = 'id';
    protected $table      = 'aya_callbacks';
    protected $fillable   = ['orders'];
 
}
