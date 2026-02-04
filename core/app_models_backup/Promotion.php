<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    //use SoftDeletes;

   public $table = 'promotions';

   protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'description',
        'chinese',
        'myanmar',
        'promotion_type',
        'duration',
        'extra_month',
        'extra_days',
        'status'
    ];
}
