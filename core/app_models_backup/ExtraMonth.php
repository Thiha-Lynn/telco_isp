<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraMonth extends Model
{
   public $table = 'extra_months';

   protected $primaryKey = 'id';

    protected $fillable = [
        'duration',
        'extra_month',
        'status'
    ];
}
